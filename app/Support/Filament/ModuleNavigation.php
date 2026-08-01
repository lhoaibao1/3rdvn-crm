<?php

namespace App\Support\Filament;

use App\Models\CrmModule;
use App\Models\User;
use App\Support\Permissions\SalesProjectAccess;
use Illuminate\Support\Facades\Auth;

class ModuleNavigation
{
    public static function label(string $slug, string $fallback): string
    {
        return CrmModule::query()->where('slug', $slug)->value('label') ?: $fallback;
    }

    public static function visible(string $slug, ?string $fallbackPermission = null): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $module = CrmModule::query()->where('slug', $slug)->first();

        if (! $module) {
            return $fallbackPermission ? $user->can($fallbackPermission) : true;
        }

        if (! $module->is_active) {
            return false;
        }

        $roles = collect($module->required_roles ?? [])->filter();

        if ($roles->isNotEmpty() && ! $user->hasAnyRole(['Admin', 'Sales Admin']) && ! $user->hasAnyRole($roles->all())) {
            return false;
        }

        $permissions = collect($module->required_permissions ?? [])->filter();

        $hasModulePermission = $permissions->isEmpty()
            ? ($fallbackPermission ? $user->can($fallbackPermission) : true)
            : $permissions->contains(fn (string $permission): bool => $user->can($permission));

        return $hasModulePermission && SalesProjectAccess::canAccessModule($user, $module);
    }
}
