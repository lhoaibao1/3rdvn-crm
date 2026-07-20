<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksCrudPermissions;

class ApiMappingPolicy
{
    use ChecksCrudPermissions;

    protected string $permissionPrefix = 'api_mapping';

    public function test(User $user): bool
    {
        return $user->can('api_mapping.test');
    }
}
