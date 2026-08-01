<?php

namespace App\Support\Permissions;

use App\Models\CrmModule;
use App\Models\SalesProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SalesProjectAccess
{
    public static function userProjectSlugs(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return collect($user->sales_projects ?? [])
            ->filter(fn (mixed $slug): bool => is_string($slug) && filled($slug))
            ->unique()
            ->values()
            ->all();
    }

    public static function canAccessProject(?User $user, SalesProject|string|null $project): bool
    {
        if (! $user instanceof User || blank($project)) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        $slug = $project instanceof SalesProject ? $project->slug : $project;

        return in_array($slug, self::userProjectSlugs($user), true);
    }

    public static function canAccessModule(?User $user, CrmModule $module): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        $activeProjectSlugs = self::activeProjectQuery()
            ->where('crm_module_id', $module->getKey())
            ->pluck('slug')
            ->all();

        if ($activeProjectSlugs === []) {
            return true;
        }

        return collect(self::userProjectSlugs($user))->intersect($activeProjectSlugs)->isNotEmpty();
    }

    public static function activeProjectQuery(): Builder
    {
        return SalesProject::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
