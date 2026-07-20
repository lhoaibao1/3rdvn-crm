<?php

namespace App\Policies;

use App\Models\User;

class SalesProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales_project.view');
    }

    public function view(User $user): bool
    {
        return $user->can('sales_project.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sales_project.create');
    }

    public function update(User $user): bool
    {
        return $user->can('sales_project.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('sales_project.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sales_project.delete');
    }
}
