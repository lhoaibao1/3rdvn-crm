<?php

namespace Tests\Unit;

use App\Models\AffiliateCampaign;
use Carbon\Carbon;
use Tests\TestCase;

class AffiliateCampaignAvailabilityTest extends TestCase
{
    public function test_campaign_closes_at_configured_time(): void
    {
        Carbon::setTestNow('2026-08-26 15:00:00');

        $campaign = new AffiliateCampaign([
            'is_active' => true,
            'closes_at' => '2026-08-26 15:00:00',
        ]);

        $this->assertFalse($campaign->isOpen());
        Carbon::setTestNow();
    }

    public function test_disabled_campaign_is_closed_even_without_schedule(): void
    {
        $campaign = new AffiliateCampaign(['is_active' => false]);

        $this->assertFalse($campaign->isOpen());
    }

    public function test_active_campaign_before_closing_time_is_open(): void
    {
        Carbon::setTestNow('2026-08-26 14:59:59');

        $campaign = new AffiliateCampaign([
            'is_active' => true,
            'closes_at' => '2026-08-26 15:00:00',
        ]);

        $this->assertTrue($campaign->isOpen());
        Carbon::setTestNow();
    }

    public function test_closed_campaign_uses_project_paused_message_by_default(): void
    {
        $campaign = new AffiliateCampaign(['is_active' => false]);

        $this->assertSame('Dự án tạm dừng.', $campaign->closureReason());
    }

    public function test_closed_campaign_can_use_admin_configured_message(): void
    {
        $campaign = new AffiliateCampaign([
            'is_active' => false,
            'closure_message' => 'Dự án tạm dừng để bảo trì.',
        ]);

        $this->assertSame('Dự án tạm dừng để bảo trì.', $campaign->closureReason());
    }
}
