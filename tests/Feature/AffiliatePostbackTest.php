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

    public function test_tinvay_postback_does_not_fail_when_partner_sends_time_macro_name(): void
    {
        $this->getJson('/api/affiliate/postback/tinvay?'.http_build_query([
            'conversion_id' => 'TIN-TIME-MACRO',
            'transaction_id' => 'TIN-ORDER-1',
            'status' => '0',
            'trans_time' => 'trans_time',
        ]))->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('affiliate_conversions', [
            'partner' => 'accesstrade',
            'conversion_id' => 'TIN-TIME-MACRO',
            'conversion_time' => null,
        ]);
    }

    public function test_vpbank_postback_does_not_fail_when_partner_sends_time_macro_name(): void
    {
        $this->getJson('/api/affiliate/postback/vpbank?'.http_build_query([
            'conversion_id' => 'VPB-TIME-MACRO',
            'transaction_id' => 'VPB-ORDER-1',
            'status' => '0',
            'trans_time' => 'trans_time',
        ]))->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('affiliate_conversions', [
            'partner' => 'isclix',
            'conversion_id' => 'VPB-TIME-MACRO',
            'conversion_time' => null,
        ]);
    }

    public function test_tinvay_postback_ignores_unresolved_identity_macros(): void
    {
        $this->getJson('/api/affiliate/postback/tinvay?'.http_build_query([
            'conversion_id' => 'conversion_id',
            'transaction_id' => '475851994',
            'sub1' => 'sub1',
        ]))
            ->assertOk()
            ->assertJsonPath('status', 'ignored')
            ->assertJsonPath('ignored', true);

        $this->assertDatabaseCount('affiliate_conversions', 0);
    }

    public function test_vpbank_postback_ignores_braced_identity_macros(): void
    {
        $this->getJson('/api/affiliate/postback/vpbank?'.http_build_query([
            'conversion_id' => '{conversion_id}',
            'transaction_id' => '{transaction_id}',
            'sub1' => '{sub1}',
        ]))
            ->assertOk()
            ->assertJsonPath('status', 'ignored')
            ->assertJsonPath('ignored', true);

        $this->assertDatabaseCount('affiliate_conversions', 0);
    }

    public function test_vpbank_postback_updates_api_report_row_instead_of_creating_a_duplicate(): void
    {
        AffiliateConversion::query()->create([
            'partner' => 'isclix',
            'conversion_id' => '362e9724126c0492e283729b092cea1f',
            'transaction_id' => '768941381_CFCC2608245321798',
            'campaign_name' => 'VPBank UPL',
            'conversion_status' => 'pending',
            'sale_amount' => 0,
            'raw_payload' => [
                'conversion_id' => 475975437,
                'customer_name' => 'PHAM DINH VAN',
                'merchant' => 'vpbank3t_vaytinchap',
            ],
        ]);

        $this->getJson('/api/integration/v1/affiliate/isclix/postback/vpbank-upl?'.http_build_query([
            'order_id' => '768941381_CFCC2608245321798',
            'transaction_id' => '475975437',
            'status' => '1',
            'product_price' => '30000000',
        ]))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseCount('affiliate_conversions', 1);
        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('362e9724126c0492e283729b092cea1f', $conversion->conversion_id);
        $this->assertSame('768941381_CFCC2608245321798', $conversion->transaction_id);
        $this->assertSame('approved', $conversion->conversion_status);
        $this->assertSame('PHAM DINH VAN', data_get($conversion->raw_payload, 'customer_name'));
        $this->assertSame(475975437, data_get($conversion->raw_payload, 'conversion_id'));
    }

    public function test_tinvay_postback_updates_api_report_row_instead_of_creating_a_duplicate(): void
    {
        AffiliateConversion::query()->create([
            'partner' => 'accesstrade',
            'conversion_id' => '38b946a2666327dcb0175c0c944ed90b',
            'transaction_id' => 'SEJnARw6F9CGCSC0qTT4TQ',
            'campaign_name' => 'Tin Vay',
            'conversion_status' => 'pending',
            'sale_amount' => 20_000_000,
            'raw_payload' => [
                'conversion_id' => 475830012,
                'customer_name' => 'HOANG BA THAO',
                'merchant' => 'vcredit2026',
            ],
        ]);

        $this->getJson('/api/integration/v1/affiliate/accesstrade/postback/tinvay?'.http_build_query([
            'order_id' => 'SEJnARw6F9CGCSC0qTT4TQ',
            'transaction_id' => '475830012',
            'status' => '0',
            'product_price' => '20000000',
        ]))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseCount('affiliate_conversions', 1);
        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('38b946a2666327dcb0175c0c944ed90b', $conversion->conversion_id);
        $this->assertSame('SEJnARw6F9CGCSC0qTT4TQ', $conversion->transaction_id);
        $this->assertSame('HOANG BA THAO', data_get($conversion->raw_payload, 'customer_name'));
        $this->assertSame(475830012, data_get($conversion->raw_payload, 'conversion_id'));
    }
}
