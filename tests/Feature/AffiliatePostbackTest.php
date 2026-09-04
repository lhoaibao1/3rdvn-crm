<?php

namespace Tests\Feature;

use App\Models\AffiliateConversion;
use App\Models\User;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

    public function test_status_only_postback_does_not_erase_reconciled_amounts(): void
    {
        config(['services.affiliate.postback_secret' => 'test-secret']);
        AffiliateConversion::query()->create([
            'partner' => 'hyperlead',
            'conversion_id' => 'shbfinanceTX-PRESERVE',
            'transaction_id' => 'TX-PRESERVE',
            'conversion_status' => 'pending',
            'sale_amount' => 30_000_000,
            'publisher_payout' => 900_000,
            'raw_payload' => [],
        ]);

        $this->withHeader('X-Affiliate-Secret', 'test-secret')
            ->postJson('/api/affiliate/postback/shb-finance', [
                'conversion_id' => 'shbfinanceTX-PRESERVE',
                'transaction_id' => 'TX-PRESERVE',
                'conversion_status' => 'approved',
            ])
            ->assertOk();

        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('approved', $conversion->conversion_status);
        $this->assertSame('30000000.00', $conversion->sale_amount);
        $this->assertSame('900000.00', $conversion->publisher_payout);
    }

    public function test_approved_hyperlead_postback_without_amount_queues_targeted_refresh(): void
    {
        Bus::fake();
        config(['services.affiliate.postback_secret' => 'test-secret']);

        $this->withHeader('X-Affiliate-Secret', 'test-secret')
            ->postJson('/api/affiliate/postback/shb-finance', [
                'conversion_id' => 'shbfinanceTX-REFRESH',
                'transaction_id' => 'TX-REFRESH',
                'conversion_status' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('sale_amount', null);

        Bus::assertDispatched(QueuedCommand::class, fn (QueuedCommand $command) =>
            $command->displayName() === 'affiliate:sync-hyperlead'
        );
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
        $this->assertSame('2026-08-17 18:37:30', $conversion->conversion_time?->format('Y-m-d H:i:s'));
    }

    public function test_accesstrade_endpoint_keeps_partner_separate(): void
    {
        Bus::fake();
        config(['services.affiliate.postback_secret' => 'test-secret']);
        User::factory()->create([
            'employee_code' => 'RD260103',
            'employment_status' => User::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/integration/v1/affiliate/accesstrade/postback?'.http_build_query([
            'secret' => 'test-secret',
            'conversion_id' => 'AT-CV-1',
            'transaction_id' => 'AT-TX-1',
            'offer_id' => 'vietcredit',
            'conversion_status' => 'pending',
            'aff_sub1' => 'RD260103',
        ]))->assertOk()->assertJsonPath('partner', 'accesstrade');

        $this->assertDatabaseHas('affiliate_conversions', [
            'partner' => 'accesstrade',
            'conversion_id' => 'AT-CV-1',
            'created_by_id' => User::query()->where('employee_code', 'RD260103')->value('id'),
        ]);
    }
}
