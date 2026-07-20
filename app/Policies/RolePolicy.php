<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin') && $user->can('role.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin') && $user->can('role.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasRole('Admin') && $user->can('role.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasRole('Admin') && $user->can('role.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('Admin') && $user->can('role.delete');
    }
}
