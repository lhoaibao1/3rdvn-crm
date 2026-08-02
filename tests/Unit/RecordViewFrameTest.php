<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RecordViewFrameTest extends TestCase
{
    public function test_every_record_infolist_uses_the_shared_view_frame(): void
    {
        $root = dirname(__DIR__, 2).'/app/Filament/Resources/';

        foreach ([
            'Applications/Schemas/ApplicationInfolist.php',
            'DataCenterLeads/Schemas/DataCenterLeadInfolist.php',
            'HotLeads/Schemas/HotLeadInfolist.php',
            'Leads/Schemas/LeadInfolist.php',
            'ProjectReports/Schemas/ProjectReportInfolist.php',
            'SaleProfiles/Schemas/SaleProfileInfolist.php',
            'Users/Schemas/UserInfolist.php',
        ] as $screen) {
            $source = file_get_contents($root.$screen);

            self::assertStringContainsString("->extraAttributes(['class' => 'crm-record-view-frame'])", $source, $screen);
        }
    }

    public function test_view_frame_highlights_headings_and_scrolls_only_the_active_panel_on_desktop(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Providers/Filament/AdminPanelProvider.php',
        );
        $start = strpos($source, '/* Record view: keep the chrome stable');
        $end = strpos($source, '    .fi-ta-ctn {', $start);
        $styles = substr($source, $start, $end - $start);

        self::assertStringContainsString('.crm-record-view-frame .fi-section-header-heading', $styles);
        self::assertStringContainsString('color: #086fb9 !important', $styles);
        self::assertStringContainsString('background: linear-gradient(100deg, #e9f5ff', $styles);
        self::assertStringContainsString('height: calc(100dvh - var(--crm-topbar-height) - 112px)', $styles);
        self::assertStringContainsString('overflow-y: auto !important', $styles);
        self::assertStringContainsString('overscroll-behavior: contain !important', $styles);
        self::assertStringContainsString('touch-action: pan-y !important', $styles);
        self::assertStringContainsString('@media (max-width: 1023px), (max-height: 679px)', $styles);
        self::assertStringContainsString('overflow: visible !important', $styles);
        self::assertSame(substr_count($styles, '{'), substr_count($styles, '}'));
    }
}
