<?php

namespace App\Support\Reports;

use App\Models\SalesProject;
use App\Models\User;
use App\Support\Permissions\SalesProjectAccess;
use Illuminate\Database\Eloquent\Builder;

class ProjectReportAccess
{
    public static function projectOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $query = SalesProjectAccess::activeProjectQuery();

        if (! $user->hasAnyRole(['Admin', 'Sales Admin'])) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);

            if ($slugs === []) {
                return [];
            }

            $query->whereIn('slug', $slugs);
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function creatableProjectOptions(?User $user): array
    {
        return collect(self::projectOptions($user))
            ->filter(fn (string $label, int|string $id): bool => filled(self::salesCode($user, $id)))
            ->all();
    }

    public static function canUseProject(?User $user, int|string|null $projectId): bool
    {
        if (! $user instanceof User || blank($projectId)) {
            return false;
        }

        $project = self::project($projectId);

        return $project instanceof SalesProject
            && ($user->hasAnyRole(['Admin', 'Sales Admin']) || SalesProjectAccess::canAccessProject($user, $project));
    }

    public static function project(int|string|null $projectId): ?SalesProject
    {
        if (blank($projectId)) {
            return null;
        }

        return SalesProjectAccess::activeProjectQuery()->find((int) $projectId);
    }

    public static function salesCode(?User $user, int|string|null $projectId): ?string
    {
        if (! self::canUseProject($user, $projectId)) {
            return null;
        }

        $project = self::project($projectId);
        $code = $project ? data_get($user?->sales_codes ?? [], $project->slug) : null;

        return filled($code) ? trim((string) $code) : null;
    }

    public static function applyVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return $query;
        }

        return $query->where('created_by_id', $user->getKey());
    }
}
