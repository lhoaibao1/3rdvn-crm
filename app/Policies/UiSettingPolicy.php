<?php

namespace App\Policies;

use App\Models\User;

class UiSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function view(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return $user->can('settings.update');
    }

    public function delete(User $user): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
