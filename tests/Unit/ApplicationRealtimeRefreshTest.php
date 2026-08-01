<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApplicationRealtimeRefreshTest extends TestCase
{
    public function test_application_tables_refresh_only_when_a_notification_arrives(): void
    {
        $root = dirname(__DIR__, 2);
        $table = file_get_contents(
            $root.'/app/Filament/Resources/Applications/Tables/ApplicationsTable.php',
        );
        $page = file_get_contents(
            $root.'/app/Filament/Resources/Applications/Pages/ListApplications.php',
        );
        $provider = file_get_contents(
            $root.'/app/Providers/Filament/AdminPanelProvider.php',
        );

        $this->assertStringNotContainsString('->poll(', $table);
        $this->assertStringContainsString("#[On('applicationRecordsChanged')]", $page);
        $this->assertStringContainsString('refreshApplicationRecords', $page);
        $this->assertStringContainsString(
            "window.Livewire?.dispatch('applicationRecordsChanged');",
            $provider,
        );
    }
}
