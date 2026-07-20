<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataCenterLead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'referral_code', 'customer_name', 'phone', 'email', 'identity_number', 'date_of_birth',
        'address', 'province_code', 'province_name', 'district_code', 'district_name', 'ward_code', 'ward_name',
        'source', 'status', 'call_note', 'contacted_at', 'assigned_user_id', 'created_by_id',
        'team_id', 'team_leader_id', 'am_id', 'zd_id', 'payload',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'contacted_at' => 'datetime',
        'payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (DataCenterLead $record): void {
            if (blank($record->referral_code)) {
                $record->forceFill([
                    'referral_code' => 'DC'.$record->created_at->format('ymd').str_pad((string) $record->getKey(), 6, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }

            $record->writeChangeLog('created', self::snapshot($record->getAttributes()));
        });

        static::updated(function (DataCenterLead $record): void {
            $changes = collect($record->getChanges())
                ->except(['updated_at'])
                ->mapWithKeys(fn (mixed $value, string $key): array => [$key => [
                    'old' => $record->getOriginal($key),
                    'new' => $value,
                ]])
                ->all();

            if ($changes !== []) {
                $record->writeChangeLog('updated', $changes);
            }
        });
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
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

    public function conversions(): HasMany
    {
        return $this->hasMany(DataCenterConversion::class);
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

    private function writeChangeLog(string $action, array $changes): void
    {
        $this->changeLogs()->create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
