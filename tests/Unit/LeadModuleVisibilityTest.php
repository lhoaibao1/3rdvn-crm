<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LeadModuleVisibilityTest extends TestCase
{
    public function test_legacy_lead_module_is_hidden_from_navigation_and_dashboard_shortcut(): void
    {
        $resource = file_get_contents(dirname(__DIR__, 2).'/app/Filament/Resources/Leads/LeadResource.php');
        $dashboard = file_get_contents(dirname(__DIR__, 2).'/app/Filament/Pages/Dashboard.php');

        $this->assertMatchesRegularExpression(
            '/shouldRegisterNavigation\\(array \\$parameters = \\[\\]\\): bool\\s*\\{[^}]*return false;/s',
            $resource,
        );
        $this->assertStringContainsString("'leads' => null", $dashboard);
    }
}
