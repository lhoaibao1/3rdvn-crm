<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin') || $user->can('application.view');
    }

    public function view(User $user, Application $application): bool
    {
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        return $user->can('application.view')
            && SalesProjectAccess::canAccessProject($user, $application->salesProject)
            && RecordVisibility::canAccessUserOwnedRecord($user, $application, 'assigned_sale_id', 'assignedSale');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin') || $user->can('application.create');
    }

    public function update(User $user, Application $application): bool
    {
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        if ($application->salesProject?->slug === 'acl-mix') {
            return AclMixWorkflow::canEditData($user, $application);
        }

        if ($application->salesProject?->slug === 'lotte-finance') {
            return LotteFinanceWorkflow::canEditData($user, $application);
        }

        return $user->can('application.update')
            && $this->view($user, $application);
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->hasRole('Admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('Admin');
    }
}
