<?php

namespace Tests\Unit;

use App\Filament\Resources\ApiMappings\ApiMappingResource;
use App\Filament\Resources\CrmLookups\CrmLookupResource;
use App\Filament\Resources\CrmModules\CrmModuleResource;
use App\Filament\Resources\CrmTeams\CrmTeamResource;
use App\Filament\Resources\ProcessingAssignmentConfigs\ProcessingAssignmentConfigResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\SalesChannels\SalesChannelResource;
use App\Filament\Resources\SalesProjects\SalesProjectResource;
use App\Filament\Resources\UiSettings\UiSettingResource;
use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use App\Support\Filament\AdminNavigation;
use PHPUnit\Framework\TestCase;

class AdminNavigationTest extends TestCase
{
    public function test_admin_and_configuration_resources_share_the_last_navigation_group(): void
    {
        $resources = [
            CrmModuleResource::class,
            SalesProjectResource::class,
            WorkflowConfigurationResource::class,
            ApiMappingResource::class,
            CrmLookupResource::class,
            SalesChannelResource::class,
            CrmTeamResource::class,
            ProcessingAssignmentConfigResource::class,
            RoleResource::class,
            UiSettingResource::class,
        ];

        foreach ($resources as $resource) {
            self::assertSame(AdminNavigation::GROUP, $resource::getNavigationGroup());
        }

        self::assertSame(AdminNavigation::GROUP, array_last(AdminNavigation::groups()));
    }
}
