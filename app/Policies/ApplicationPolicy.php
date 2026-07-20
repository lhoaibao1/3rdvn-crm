<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('application.view');
    }

    public function view(User $user, Application $application): bool
    {
        return $user->can('application.view')
            && SalesProjectAccess::canAccessProject($user, $application->salesProject)
            && RecordVisibility::canAccessUserOwnedRecord($user, $application, 'assigned_sale_id', 'assignedSale');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Application $application): bool
    {
        if (! $user->can('application.update') || ! $this->view($user, $application)) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        $application->loadMissing(['salesProject:id,slug', 'lead:id,status']);

        if (
            $application->salesProject?->slug === 'acl-mix'
            && (int) $application->created_by_id === (int) $user->getKey()
        ) {
            if ($application->lead && $application->lead->status !== 'Khách hàng thoả mãn điều kiện') {
                return false;
            }
        }

        return true;
    }

    public function delete(User $user, Application $application): bool
    {
        return false;
    }
}
