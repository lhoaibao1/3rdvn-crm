<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateCampaignRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_owned_campaign_link_forwards_with_employee_attribution(): void
    {
        User::factory()->create(['employee_code' => 'RD260001', 'employment_status' => User::STATUS_ACTIVE]);

        $this->get('/affiliate/shb-finance?ref=RD260001')
            ->assertRedirect('https://riofin.asia/v2/h6ZUoKMr6OVLqyCgJ9UNQkEnUZFMnjA2D_Pt6iQOrjw?lp=shbfinance&aff_sub1=RD260001');
    }

    public function test_owned_campaign_link_rejects_unknown_employee(): void
    {
        $this->get('/affiliate/shb-finance?ref=UNKNOWN')->assertNotFound();
    }
}
