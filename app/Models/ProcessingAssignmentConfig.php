<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProcessingAssignmentConfig extends Model
{
    protected $fillable = [
        'sales_project_id',
        'is_enabled',
        'auto_assign',
        'user_ids',
        'statuses',
    ];

    protected $casts = [
        'sales_project_id' => 'integer',
        'is_enabled' => 'boolean',
        'auto_assign' => 'boolean',
        'user_ids' => 'array',
        'statuses' => 'array',
    ];

    public function salesProject(): BelongsTo
    {
        return $this->belongsTo(SalesProject::class);
    }

    public function configuredUsers(): Collection
    {
        $projectSlug = (string) $this->salesProject()->value('slug');
        $ids = collect($this->user_ids ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return self::activeUsersQuery()
            ->whereIn('id', $ids)
            ->with(['permissions', 'roles.permissions'])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => self::canReceiveProject($user, $projectSlug))
            ->values();
    }

    public static function selectableUserOptions(?int $salesProjectId = null): array
    {
        $projectSlug = (string) ($salesProjectId
            ? SalesProject::query()->whereKey($salesProjectId)->value('slug')
            : '');

        return self::activeUsersQuery()
            ->with(['permissions', 'roles.permissions'])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => self::canReceiveProject($user, $projectSlug))
            ->mapWithKeys(fn (User $user): array => [
                $user->getKey() => implode(' · ', array_filter([
                    $user->name,
                    $user->roles->pluck('name')->join(', '),
                    $user->uid,
                    $user->employee_code,
                ])),
            ])
            ->all();
    }

    public static function canReceiveProject(User $user, ?string $projectSlug): bool
    {
        if (blank($projectSlug) || $user->hasRole('Admin')) {
            return false;
        }

        return in_array($projectSlug, $user->sales_projects ?? [], true);
    }

    private static function activeUsersQuery(): Builder
    {
        return User::query()->whereNotIn('employment_status', [
            'inactive',
            User::STATUS_DEACTIVE,
            'resigned',
            User::STATUS_DELETED,
        ]);
    }
}
