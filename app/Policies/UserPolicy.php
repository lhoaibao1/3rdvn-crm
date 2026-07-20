<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RoleHierarchy;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('user.view') && (
            $user->hasRole('Admin')
            || $user->getKey() === $target->getKey()
            || RoleHierarchy::canManageUser($user, $target)
        );
    }

    public function create(User $user): bool
    {
        return RoleHierarchy::canCreateUsers($user);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('user.update') && RoleHierarchy::canUpdateUser($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('user.delete')
            && $user->getKey() !== $target->getKey()
            && RoleHierarchy::canManageUser($user, $target);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('user.delete') && RoleHierarchy::assignableRoles($user) !== [];
    }

    public function restore(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->deleteAny($user);
    }
}
