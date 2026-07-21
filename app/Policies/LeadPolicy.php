<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\Permissions\LeadAccess;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lead.view') && LeadAccess::canAccessLeadModule($user);
    }

    public function view(User $user, Lead $lead): bool
    {
        return LeadAccess::canView($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->can('lead.create') && LeadAccess::canAccessLeadModule($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        return LeadAccess::canUpdate($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return LeadAccess::canDelete($user, $lead);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('Admin') && LeadAccess::canAccessLeadModule($user);
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->hasRole('Admin') && $lead->trashed();
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole('Admin') && LeadAccess::canAccessLeadModule($user);
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $user->hasRole('Admin') && $lead->trashed();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('Admin') && LeadAccess::canAccessLeadModule($user);
    }
}
