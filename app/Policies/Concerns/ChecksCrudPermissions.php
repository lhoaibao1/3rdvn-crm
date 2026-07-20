<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksCrudPermissions
{
    public function viewAny(User $user): bool
    {
        return $user->can($this->permissionPrefix.'.view');
    }

    public function view(User $user): bool
    {
        return $user->can($this->permissionPrefix.'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->permissionPrefix.'.create');
    }

    public function update(User $user): bool
    {
        return $user->can($this->permissionPrefix.'.update');
    }

    public function delete(User $user): bool
    {
        return $user->can($this->permissionPrefix.'.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->delete($user);
    }

    public function restore(User $user): bool
    {
        return $this->delete($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->delete($user);
    }

    public function forceDelete(User $user): bool
    {
        return $this->delete($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->delete($user);
    }
}
