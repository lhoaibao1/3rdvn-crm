<?php

namespace Tests\Feature;

use App\Models\AffiliateConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliatePostbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_postback_requires_secret(): void
    {
        config(['services.affiliate.postback_secret' => 'test-secret']);
        $this->postJson('/api/integration/v1/affiliate/postback', ['conversion_id' => 'CV-1'])->assertForbidden();
    }

    public function test_postback_upserts_conversion_without_duplicates(): void
    {
        config(['services.affiliate.postback_secret' => 'test-secret']);
        $payload = [
            'conversion_id' => 'CV-1',
            'transaction_id' => 'TX-1',
            'conversion_status' => 'pending',
            'conversion_sale_amount' => 20000000,
            'conversion_publisher_payout' => 500000,
            'aff_sub1' => 'RD260001',
        ];

        $this->withHeader('X-Affiliate-Secret', 'test-secret')
            ->postJson('/api/integration/v1/affiliate/postback', $payload)
            ->assertOk()->assertJsonPath('ok', true);

        $payload['conversion_status'] = 'approved';
        $this->withHeader('X-Affiliate-Secret', 'test-secret')
            ->postJson('/api/integration/v1/affiliate/postback', $payload)->assertOk();

        $this->assertDatabaseCount('affiliate_conversions', 1);
        $this->assertSame('approved', AffiliateConversion::first()->conversion_status);
    }

    public function test_hyperlead_get_payload_is_normalized_and_mapped_to_employee(): void
    {
        config(['services.affiliate.postback_secret' => 'test-secret']);
        $user = User::factory()->create([
            'employee_code' => 'RD260103',
            'employment_status' => User::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/integration/v1/affiliate/postback?'.http_build_query([
            'secret' => 'test-secret',
            'conversion_id' => 'shbfinanceDG3A6E62608179879906',
            'transaction_id' => 'DG3A6E62608179879906',
            'click_id' => '6a82f2193da81a0001b04aff',
            'conversion_sale_amount' => '',
            'conversion_time' => '1786966650220',
            'conversion_modified_time' => '1786966650220',
            'click_time' => '1786966553240',
            'product_url' => '',
            'aff_sub1' => 'RD260103',
            'offer_id' => 'shbfinance',
            'landing_page' => 'shbfinance',
            'product_category_id' => 'WEB',
            'conversion_status' => 'pending',
            'conversion_status_code' => '0',
            'conversion_publisher_payout' => '0',
        ]))->assertOk()->assertJsonPath('ok', true);

        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame($user->getKey(), $conversion->created_by_id);
        $this->assertSame('shbfinance', $conversion->campaign_name);
        $this->assertSame('pending', $conversion->conversion_status);
        $this->assertNotNull($conversion->conversion_time);
    }
}
