<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'lead.view', 'lead.create', 'lead.update', 'lead.delete', 'lead.convert',
            'hot_lead.view', 'hot_lead.create', 'hot_lead.process', 'hot_lead.delete',
            'application.view', 'application.create', 'application.update',
            'profile.view', 'profile.create', 'profile.update', 'profile.delete', 'profile.submit',
            'profile.approve', 'profile.reject', 'profile.process', 'profile.complete',
            'approval.view', 'approval.update', 'approval.approve', 'approval.export',
            'api_mapping.view', 'api_mapping.create', 'api_mapping.update', 'api_mapping.delete', 'api_mapping.test',
            'module.view', 'module.create', 'module.update', 'module.delete',
            'lookup.view', 'lookup.create', 'lookup.update', 'lookup.delete',
            'sales_channel.view', 'sales_channel.create', 'sales_channel.update', 'sales_channel.delete',
            'sales_project.view', 'sales_project.create', 'sales_project.update', 'sales_project.delete',
            'report.view', 'report.export',
            'settings.view', 'settings.update',
            'user.view', 'user.create', 'user.update', 'user.delete', 'user.manage_team', 'user.assign_hierarchy',
            'role.view', 'role.create', 'role.update', 'role.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'Admin' => $permissions,
            'Sales Admin' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update', 'lead.convert',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit',
                'profile.approve', 'profile.reject', 'profile.process', 'profile.complete',
                'approval.view', 'approval.update', 'approval.approve', 'approval.export',
                'lookup.view', 'sales_channel.view', 'sales_project.view',
                'report.view', 'report.export',
                'settings.view',
                'user.view', 'user.create', 'user.update', 'user.manage_team', 'user.assign_hierarchy',
            ],
            'Team Leader' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit',
                'user.view', 'user.create', 'user.update', 'user.manage_team',
            ],
            'AM' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update', 'lead.convert',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit', 'profile.approve', 'profile.reject',
                'user.view', 'user.create', 'user.update', 'user.manage_team', 'user.assign_hierarchy',
                'report.view', 'report.export',
            ],
            'ZD' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update', 'lead.convert',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit', 'profile.approve', 'profile.reject',
                'approval.view', 'approval.update', 'approval.approve', 'approval.export',
                'user.view', 'user.create', 'user.update', 'user.manage_team', 'user.assign_hierarchy',
                'report.view', 'report.export',
            ],
            'Courier Manager' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update', 'lead.convert',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit',
                'user.view', 'user.create', 'user.update', 'user.manage_team',
            ],
            'Courier' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update', 'lead.convert',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit',
            ],
            'Direct Sale' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update', 'lead.convert',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit',
            ],
            'Telesale' => [
                'dashboard.view',
                'lead.view', 'lead.create', 'lead.update', 'lead.convert',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create', 'application.update',
                'profile.view', 'profile.create', 'profile.update', 'profile.submit',
            ],
            'CTV' => [
                'dashboard.view',
                'lead.view', 'lead.create',
                'hot_lead.view', 'hot_lead.create', 'hot_lead.process',
                'application.view', 'application.create',
                'profile.view', 'profile.create', 'profile.submit',
            ],
        ];

        foreach ($roles as $role => $rolePermissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($rolePermissions);
        }

        $legacyRoleMap = [
            'Sale' => 'Direct Sale',
            'Manager' => 'AM',
            'Ops' => 'ZD',
            'API Manager' => 'Admin',
        ];

        foreach ($legacyRoleMap as $legacyRole => $primaryRole) {
            $role = Role::query()->where('name', $legacyRole)->first();

            if (! $role) {
                continue;
            }

            User::role($legacyRole)->each(function (User $user) use ($legacyRole, $primaryRole): void {
                $user->assignRole($primaryRole);
                $user->removeRole($legacyRole);
            });

            $role->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
