<?php

namespace App\Policies;

use App\Models\User;

class CrmModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('module.view');
    }

    public function view(User $user): bool
    {
        return $user->can('module.view');
    }

    public function create(User $user): bool
    {
        return $user->can('module.create');
    }

    public function update(User $user): bool
    {
        return $user->can('module.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('module.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('module.delete');
    }
}
