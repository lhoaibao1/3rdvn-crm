<?php

namespace Tests\Unit;

use App\Models\AffiliateConversion;
use App\Support\LosAffiliateConversionPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LosAffiliatePendingAmountTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_conversion_still_exposes_partner_approved_amount(): void
    {
        $conversion = AffiliateConversion::query()->create([
            'partner' => 'hyperlead',
            'conversion_id' => 'shbfinancePENDING-AMOUNT',
            'transaction_id' => 'PENDING-AMOUNT',
            'campaign_name' => 'SHB Finance',
            'conversion_status' => 'pending',
            'sale_amount' => 21_500_000,
            'raw_payload' => [],
        ]);

        $presented = LosAffiliateConversionPresenter::make($conversion);

        $this->assertSame('Chờ xử lý / Đang thẩm định', $presented['status_label']);
        $this->assertSame(21_500_000, $presented['approved_loan_amount']);
        $this->assertSame('21.500.000 VNĐ', $presented['approved_loan_amount_label']);
        $this->assertNull($presented['requested_loan_amount']);
    }
}
