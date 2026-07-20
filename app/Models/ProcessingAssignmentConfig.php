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
        $projectSlugs = self::leadProjectSlugs();
        $projectSlug = $this->salesProject()->value('slug');
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
            ->filter(fn (User $user): bool => self::canReceiveLead($user, $projectSlugs)
                && self::canReceiveProjectProcessing($user, $projectSlug))
            ->values();
    }

    public static function selectableUserOptions(?int $salesProjectId = null): array
    {
        $projectSlugs = self::leadProjectSlugs();
        $projectSlug = $salesProjectId
            ? SalesProject::query()->whereKey($salesProjectId)->value('slug')
            : null;

        return self::activeUsersQuery()
            ->with(['permissions', 'roles.permissions'])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => self::canReceiveLead($user, $projectSlugs))
            ->filter(fn (User $user): bool => self::canReceiveProjectProcessing($user, $projectSlug))
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

    private static function canReceiveLead(User $user, Collection $projectSlugs): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if (! $user->can('lead.view') || ! $user->can('lead.convert')) {
            return false;
        }

        return $projectSlugs->intersect($user->sales_projects ?? [])->isNotEmpty();
    }

    private static function canReceiveProjectProcessing(User $user, ?string $projectSlug): bool
    {
        return $projectSlug !== 'acl-mix' || $user->hasRole('Courier');
    }

    private static function leadProjectSlugs(): Collection
    {
        return SalesProject::query()
            ->where('is_active', true)
            ->whereHas('crmModule', fn (Builder $query): Builder => $query->where('slug', 'applications'))
            ->pluck('slug');
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
