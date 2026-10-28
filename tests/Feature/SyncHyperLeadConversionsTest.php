<?php

namespace Tests\Feature;

use App\Models\AffiliateConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncHyperLeadConversionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_report_fields_and_preserves_postback_only_data(): void
    {
        config(['services.affiliate.api_base_url' => 'https://publisher-api.riofintech.net', 'services.affiliate.publisher_id' => 'publisher', 'services.affiliate.api_token' => 'token']);
        $user = User::factory()->create(['employee_code' => 'RD260001']);
        AffiliateConversion::query()->create(['partner' => 'hyperlead', 'conversion_id' => 'shbfinanceTX-1', 'transaction_id' => 'TX-1', 'offer_id' => 'shbfinance', 'conversion_status' => 'pending', 'aff_sub1' => 'RD260001', 'landing_page' => 'shbfinance', 'status_message' => 'Đã nhận postback', 'created_by_id' => $user->id, 'raw_payload' => ['landing_page' => 'shbfinance']]);
        Http::fake(['publisher-api.riofintech.net/*' => Http::response(['status' => 1, 'message' => 'Success!', 'data' => [[
            'offer_id' => 'shbfinance', 'transaction_id' => 'TX-1', 'conversion_status_code' => -1, 'conversion_status' => 'rejected', 'conversion_sale_amount' => 20_000_000, 'conversion_publisher_payout' => 500_000, 'aff_sub1' => 'RD260001', 'click_time' => 1786966553240, 'conversion_time' => 1786966650220, 'conversion_modified_time' => 1786967450220, 'product_name' => 'SHB Finance',
        ]]])]);

        $this->artisan('affiliate:sync-hyperlead')->assertSuccessful();
        $conversion = AffiliateConversion::query()->sole();
        $this->assertSame('rejected', $conversion->conversion_status);
        $this->assertSame('20000000.00', $conversion->sale_amount);
        $this->assertSame('shbfinance', $conversion->landing_page);
        $this->assertSame('Đã nhận postback', $conversion->status_message);
        $this->assertSame($user->id, $conversion->created_by_id);
        $this->assertSame('SHB Finance', $conversion->raw_payload['product_name']);
        $this->assertSame('2026-08-17 18:37:30', $conversion->conversion_time?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-17 18:50:50', $conversion->conversion_modified_time?->format('Y-m-d H:i:s'));
    }

    public function test_a_failed_middle_page_does_not_block_newer_conversions(): void
    {
        config(['services.affiliate.api_base_url' => 'https://publisher-api.riofintech.net', 'services.affiliate.publisher_id' => 'publisher', 'services.affiliate.api_token' => 'token']);

        $oldRows = collect(range(1, 100))->map(fn (int $number) => [
            'offer_id' => 'shbfinance',
            'transaction_id' => 'OLD-'.$number,
            'conversion_status' => 'pending',
        ])->all();

        Http::fake(function ($request) use ($oldRows) {
            $page = (int) ($request->data()['page'] ?? 1);

            return match ($page) {
                1 => Http::response(['status' => 1, 'data' => $oldRows]),
                2 => Http::response(['message' => 'temporary timeout'], 504),
                3 => Http::response(['status' => 1, 'data' => [[
                    'offer_id' => 'shbfinance',
                    'transaction_id' => 'LATEST-TX',
                    'conversion_status' => 'disbursed',
                    'conversion_sale_amount' => 45_000_000,
                ]]]),
            };
        });

        $this->artisan('affiliate:sync-hyperlead')->assertSuccessful();

        $this->assertDatabaseHas('affiliate_conversions', [
            'partner' => 'hyperlead',
            'transaction_id' => 'LATEST-TX',
            'conversion_status' => 'disbursed',
            'sale_amount' => 45_000_000,
        ]);
    }
}
