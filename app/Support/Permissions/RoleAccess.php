<?php

namespace App\Support\Permissions;

use App\Models\CrmModule;
use App\Models\SaleProfile;
use App\Models\User;

class RoleAccess
{
    public static function canSeeModule(User $user, CrmModule $module): bool
    {
        if (! $module->is_active) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        $roles = $module->required_roles ?: [];
        if ($roles !== [] && ! $user->hasAnyRole(['Admin', 'Sales Admin']) && ! $user->hasAnyRole($roles)) {
            return false;
        }

        $permissions = $module->required_permissions ?: [];

        if ($permissions !== [] && ! $user->hasAnyPermission($permissions)) {
            return false;
        }

        return SalesProjectAccess::canAccessModule($user, $module);
    }

    public static function canManageSettings(User $user): bool
    {
        return $user->can('settings.update') || $user->hasRole('Admin');
    }

    public static function canApproveProfile(User $user, SaleProfile $profile): bool
    {
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        return $user->can('profile.approve') && ($user->team_id === null || $user->team_id === $profile->team_id);
    }

    public static function canEditProfile(User $user, SaleProfile $profile): bool
    {
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        if ($user->hasRole('Sale')) {
            return $profile->sale_owner_id === $user->id && $profile->approval_status !== 'Đã duyệt';
        }

        return $user->can('profile.update');
    }

    public static function canViewProfile(User $user, SaleProfile $profile): bool
    {
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        if ($user->hasRole('Sale')) {
            return $profile->sale_owner_id === $user->id;
        }

        if ($user->hasRole('Manager')) {
            return $user->team_id === null || $profile->team_id === $user->team_id;
        }

        return $user->can('profile.view');
    }
}
