<?php

namespace App\Policies;

use App\Models\SaleProfile;
use App\Models\User;
use App\Support\Permissions\RecordVisibility;

class SaleProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('profile.view');
    }

    public function view(User $user, SaleProfile $saleProfile): bool
    {
        return $user->can('profile.view')
            && RecordVisibility::canAccessUserOwnedRecord($user, $saleProfile, 'sale_owner_id', 'saleOwner');
    }

    public function create(User $user): bool
    {
        return $user->can('profile.create');
    }

    public function update(User $user, SaleProfile $saleProfile): bool
    {
        return $user->can('profile.update')
            && $this->view($user, $saleProfile);
    }

    public function delete(User $user, SaleProfile $saleProfile): bool
    {
        return $user->can('profile.delete')
            && $this->view($user, $saleProfile);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('profile.delete');
    }

    public function restore(User $user, SaleProfile $saleProfile): bool
    {
        return $this->delete($user, $saleProfile);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, SaleProfile $saleProfile): bool
    {
        return $user->hasRole('Admin') && $user->can('profile.delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('Admin') && $user->can('profile.delete');
    }

    public function submit(User $user): bool
    {
        return $user->can('profile.submit');
    }

    public function approve(User $user): bool
    {
        return $user->can('profile.approve');
    }

    public function reject(User $user): bool
    {
        return $user->can('profile.reject');
    }

    public function process(User $user): bool
    {
        return $user->can('profile.process');
    }

    public function complete(User $user): bool
    {
        return $user->can('profile.complete');
    }
}
