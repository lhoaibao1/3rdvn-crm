<?php

namespace App\Models;

use App\Support\Notifications\LeadNotificationSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    private const LOG_EXCEPT = ['updated_at'];

    protected $fillable = [
        'lead_code', 'sales_project_id', 'lead_name', 'phone', 'email', 'source', 'assigned_sale_id', 'created_by_id', 'team_id',
        'team_leader_id', 'am_id', 'zd_id', 'status',
        'note', 'payload', 'converted_sale_profile_id', 'converted_at', 'converted_by_id',
    ];

    protected $casts = [
        'sales_project_id' => 'integer',
        'assigned_sale_id' => 'integer',
        'created_by_id' => 'integer',
        'team_id' => 'integer',
        'team_leader_id' => 'integer',
        'am_id' => 'integer',
        'zd_id' => 'integer',
        'payload' => 'array',
        'converted_sale_profile_id' => 'integer',
        'converted_by_id' => 'integer',
        'converted_at' => 'datetime',
    ];


    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if (filled($lead->lead_code)) {
                return;
            }

            $date = now()->format('ymd');
            $count = static::query()
                ->where('lead_code', 'like', 'LD'.$date.'%')
                ->withTrashed()
                ->count() + 1;

            $lead->lead_code = 'LD'.$date.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        });

        static::created(function (Lead $lead): void {
            $lead->writeChangeLog('created', self::snapshot($lead->getAttributes()));
            LeadNotificationSender::leadCreated($lead);
        });

        static::updated(function (Lead $lead): void {
            $changes = [];

            foreach (array_diff_key($lead->getChanges(), array_flip(self::LOG_EXCEPT)) as $key => $newValue) {
                $changes[$key] = [
                    'old' => $lead->getOriginal($key),
                    'new' => $newValue,
                ];
            }

            if ($changes !== []) {
                $lead->writeChangeLog('updated', $changes);

                if (self::shouldNotifyUpdated($changes)) {
                    LeadNotificationSender::leadUpdated($lead->refresh(), $changes);
                }
            }
        });

        static::deleted(fn (Lead $lead): mixed => $lead->writeChangeLog('deleted', []));
        static::restored(fn (Lead $lead): mixed => $lead->writeChangeLog('restored', []));
    }

    public function changeLogs(): MorphMany
    {
        return $this->morphMany(RecordChangeLog::class, 'record')->oldest();
    }

    private static function snapshot(array $attributes): array
    {
        return collect($attributes)
            ->except(['created_at', 'updated_at', 'deleted_at'])
            ->mapWithKeys(fn (mixed $value, string $key): array => [$key => ['old' => null, 'new' => $value]])
            ->all();
    }

    private static function shouldNotifyUpdated(array $changes): bool
    {
        if (array_intersect(array_keys($changes), ['converted_at', 'converted_by_id', 'converted_sale_profile_id']) !== []) {
            return false;
        }

        if (($changes['status']['new'] ?? null) === 'Khách hàng thoả mãn điều kiện') {
            return false;
        }

        return array_intersect(array_keys($changes), ['status', 'note', 'payload', 'assigned_sale_id', 'team_leader_id', 'am_id', 'zd_id']) !== [];
    }

    private function writeChangeLog(string $action, array $changes): ?RecordChangeLog
    {
        return $this->changeLogs()->create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function salesProject(): BelongsTo
    {
        return $this->belongsTo(SalesProject::class, 'sales_project_id');
    }

    public function assignedSale(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sale_id');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }

    public function convertedSaleProfile(): BelongsTo
    {
        return $this->belongsTo(SaleProfile::class, 'converted_sale_profile_id');
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(CrmTeam::class, 'team_id');
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function am(): BelongsTo
    {
        return $this->belongsTo(User::class, 'am_id');
    }

    public function zd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zd_id');
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class, 'lead_id');
    }
}
