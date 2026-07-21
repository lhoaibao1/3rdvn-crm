<?php

namespace App\Models;

use App\Support\Reports\ProjectReportWorkflow;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sales_project_id',
    'lead_id',
    'application_code',
    'applicant_name',
    'phone',
    'identity_number',
    'status',
    'assigned_sale_id',
    'created_by_id',
    'team_id',
    'team_leader_id',
    'am_id',
    'zd_id',
    'payload',
    'note',
])]
class Application extends Model
{
    use HasFactory, SoftDeletes;

    private const LOG_EXCEPT = ['updated_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'assigned_sale_id' => 'integer',
            'created_by_id' => 'integer',
            'team_id' => 'integer',
            'team_leader_id' => 'integer',
            'am_id' => 'integer',
            'zd_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(fn (Application $application): mixed => $application->writeChangeLog('created', self::snapshot($application->getAttributes())));

        static::updated(function (Application $application): void {
            $changes = [];

            foreach (array_diff_key($application->getChanges(), array_flip(self::LOG_EXCEPT)) as $key => $newValue) {
                $changes[$key] = [
                    'old' => $application->getOriginal($key),
                    'new' => $newValue,
                ];
            }

            if ($changes !== []) {
                $application->writeChangeLog('updated', $changes);
            }
        });

        static::saved(fn (Application $application): mixed => ProjectReportWorkflow::syncFromApplication($application, auth()->user()));

        static::deleted(fn (Application $application): mixed => $application->writeChangeLog('deleted', []));
        static::restored(fn (Application $application): mixed => $application->writeChangeLog('restored', []));
    }

    public function changeLogs(): MorphMany
    {
        return $this->morphMany(RecordChangeLog::class, 'record')->latest();
    }

    private static function snapshot(array $attributes): array
    {
        return collect($attributes)
            ->except(['created_at', 'updated_at', 'deleted_at'])
            ->mapWithKeys(fn (mixed $value, string $key): array => [$key => ['old' => null, 'new' => $value]])
            ->all();
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedSale(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sale_id');
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

    public function projectReport(): HasOne
    {
        return $this->hasOne(ProjectReport::class);
    }
}
