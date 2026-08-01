<?php

namespace App\Policies;

use App\Models\ProjectReport;
use App\Models\User;
use App\Support\Reports\ProjectReportAccess;

class ProjectReportPolicy
{
    public function viewAny(User $user): bool
    {
        return ProjectReportAccess::projectOptions($user) !== [];
    }

    public function view(User $user, ProjectReport $projectReport): bool
    {
        return $user->hasAnyRole(['Admin', 'Sales Admin'])
            || (int) $projectReport->created_by_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ProjectReport $projectReport): bool
    {
        return $user->hasRole('Admin');
    }

    public function delete(User $user, ProjectReport $projectReport): bool
    {
        return $user->hasRole('Admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('Admin');
    }
}
