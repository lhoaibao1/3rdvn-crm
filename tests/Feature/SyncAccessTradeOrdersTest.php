<?php

namespace Tests\Feature;

use App\Models\AffiliateConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncAccessTradeOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_report_rekeys_an_earlier_vpbank_postback_instead_of_duplicating_it(): void
    {
        AffiliateConversion::query()->create([
            'partner' => 'isclix',
            'conversion_id' => '768941381_CFCC2608245321798',
            'transaction_id' => '475975437',
            'campaign_name' => 'VPBank UPL',
            'conversion_status' => 'pending',
            'raw_payload' => [],
        ]);

        Http::fake([
            'api.accesstrade.vn/*' => Http::response([
                'total' => 1,
                'data' => [[
                    'id' => '362e9724126c0492e283729b092cea1f',
                    'conversion_id' => 475975437,
                    'order_id' => '768941381_CFCC2608245321798',
                    'merchant' => 'vpbank3t_vaytinchap',
                    'status' => 1,
                    'transaction_value' => 30_000_000,
                    'customer_name' => 'PHAM DINH VAN',
                ]],
            ]),
        ]);

        $this->artisan('affiliate:sync-accesstrade', ['--days' => 1])
            ->assertSuccessful();

        $this->assertDatabaseCount('affiliate_conversions', 1);
        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('362e9724126c0492e283729b092cea1f', $conversion->conversion_id);
        $this->assertSame('768941381_CFCC2608245321798', $conversion->transaction_id);
        $this->assertSame('approved', $conversion->conversion_status);
        $this->assertSame('30000000.00', $conversion->sale_amount);
        $this->assertSame('PHAM DINH VAN', data_get($conversion->raw_payload, 'customer_name'));
    }

    public function test_api_report_rekeys_an_earlier_tinvay_postback_instead_of_duplicating_it(): void
    {
        AffiliateConversion::query()->create([
            'partner' => 'accesstrade',
            'conversion_id' => 'SEJnARw6F9CGCSC0qTT4TQ',
            'transaction_id' => '475830012',
            'campaign_name' => 'Tin Vay',
            'conversion_status' => 'pending',
            'raw_payload' => [],
        ]);

        Http::fake([
            'api.accesstrade.vn/*' => Http::response([
                'total' => 1,
                'data' => [[
                    'id' => '38b946a2666327dcb0175c0c944ed90b',
                    'conversion_id' => 475830012,
                    'transaction_id' => 'SEJnARw6F9CGCSC0qTT4TQ',
                    'merchant' => 'vcredit2026',
                    'status' => 0,
                    'transaction_value' => 20_000_000,
                    'customer_name' => 'HOANG BA THAO',
                ]],
            ]),
        ]);

        $this->artisan('affiliate:sync-accesstrade', ['--days' => 1])
            ->assertSuccessful();

        $this->assertDatabaseCount('affiliate_conversions', 1);
        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('38b946a2666327dcb0175c0c944ed90b', $conversion->conversion_id);
        $this->assertSame('SEJnARw6F9CGCSC0qTT4TQ', $conversion->transaction_id);
        $this->assertSame('Tin Vay', $conversion->campaign_name);
        $this->assertSame('HOANG BA THAO', data_get($conversion->raw_payload, 'customer_name'));
    }

    public function test_api_report_imports_shinhan_android_as_a_separate_campaign(): void
    {
        Http::fake([
            'api.accesstrade.vn/*' => Http::response([
                'total' => 1,
                'data' => [[
                    'id' => 'shinhan-api-conversion-001',
                    'conversion_id' => 476000001,
                    'order_id' => 'SVFC-ORDER-001',
                    'merchant' => 'shinhanfinance_taitoc',
                    'product_id' => '6949942463850829113',
                    'status' => 1,
                    'transaction_value' => 50_000_000,
                    'sub1' => 'RD260001',
                ]],
            ]),
        ]);

        $this->artisan('affiliate:sync-accesstrade', ['--days' => 1])
            ->assertSuccessful();

        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('accesstrade', $conversion->partner);
        $this->assertSame('Shinhan Finance Android', $conversion->campaign_name);
        $this->assertSame('shinhan-finance-android', $conversion->offer_id);
        $this->assertSame('SVFC-ORDER-001', $conversion->transaction_id);
        $this->assertSame('approved', $conversion->conversion_status);
        $this->assertSame('50000000.00', $conversion->sale_amount);
    }

    public function test_api_report_imports_shinhan_ios_as_a_separate_campaign(): void
    {
        Http::fake([
            'api.accesstrade.vn/*' => Http::response([
                'total' => 1,
                'data' => [[
                    'id' => 'shinhan-ios-conversion-001',
                    'conversion_id' => 476000002,
                    'order_id' => 'SVFC-IOS-ORDER-001',
                    'merchant' => 'shinhanfinance_taitoc',
                    'product_id' => '6949939948611548600',
                    'status' => 1,
                    'transaction_value' => 40_000_000,
                    'sub1' => 'RD260001',
                ]],
            ]),
        ]);

        $this->artisan('affiliate:sync-accesstrade', ['--days' => 1])->assertSuccessful();

        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('Shinhan Finance iOS', $conversion->campaign_name);
        $this->assertSame('shinhan-finance-ios', $conversion->offer_id);
        $this->assertSame('SVFC-IOS-ORDER-001', $conversion->transaction_id);
    }
}
