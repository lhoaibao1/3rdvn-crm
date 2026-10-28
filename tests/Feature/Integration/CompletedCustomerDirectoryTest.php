<?php

namespace Tests\Feature\Integration;

use App\Enums\FeDeeplinkStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompletedCustomerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_token_is_required(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);
        $this->getJson('/api/integration/v1/completed-customers')->assertUnauthorized();
    }

    public function test_fe_pl_disbursed_is_exported_as_disbursed(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);

        $projectId = DB::table('sales_projects')->insertGetId([
            'name' => 'FE Deeplink',
            'slug' => 'fe-deeplink',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('applications')->insert([
            'sales_project_id' => $projectId,
            'application_code' => 'FE-PL-DIS-001',
            'applicant_name' => 'Khách hàng FE',
            'status' => FeDeeplinkStatus::PL_DISBURSED->value,
            'payload' => json_encode([
                'fields' => [
                    'approved_amount' => 20000000,
                    'disbursed_at' => now()->toIso8601String(),
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integration/v1/completed-customers')
            ->assertOk()
            ->assertJsonPath('data.0.application_code', 'FE-PL-DIS-001')
            ->assertJsonPath('data.0.status', 'Đã giải ngân');
    }

    public function test_approved_vietcredit_conversion_is_exported_as_tin_vay(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);

        DB::table('affiliate_conversions')->insert([
            'partner' => 'accesstrade',
            'conversion_id' => 'VCREDIT-APPROVED-001',
            'campaign_name' => 'vcredit2026',
            'conversion_status' => 'approved',
            'sale_amount' => 20000000,
            'aff_sub1' => 'RD260123',
            'raw_payload' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integration/v1/completed-customers')
            ->assertOk()
            ->assertJsonPath('data.0.application_code', 'VCREDIT-APPROVED-001')
            ->assertJsonPath('data.0.project_slug', 'tinvay')
            ->assertJsonPath('data.0.project_name', 'Tin Vay')
            ->assertJsonPath('data.0.status', 'Đã giải ngân')
            ->assertJsonPath('data.0.disbursed_amount', 20000000);
    }

    public function test_pending_tin_vay_conversion_is_not_exported_to_ess(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);

        DB::table('affiliate_conversions')->insert([
            'partner' => 'accesstrade',
            'conversion_id' => 'TIN-VAY-PENDING-001',
            'campaign_name' => 'Tin Vay',
            'conversion_status' => 'pending',
            'sale_amount' => 20000000,
            'aff_sub1' => 'RD260123',
            'raw_payload' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integration/v1/completed-customers')
            ->assertOk()
            ->assertJsonMissing(['application_code' => 'TIN-VAY-PENDING-001']);
    }

    public function test_affiliate_customer_uses_full_name_and_phone_from_verified_landing_payload(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);

        DB::table('affiliate_conversions')->insert([
            'partner' => 'hyperlead',
            'conversion_id' => 'SHB-APPROVED-CUSTOMER-001',
            'transaction_id' => 'DG-CUSTOMER-001',
            'campaign_name' => 'SHB Finance',
            'conversion_status' => 'approved',
            'sale_amount' => 35000000,
            'aff_sub1' => 'RD260123',
            'raw_payload' => json_encode([
                'attribution_verified' => true,
                'customer_name' => 'NGUYỄN THỊ MINH ANH',
                'customer_phone' => '0912345678',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integration/v1/completed-customers')
            ->assertOk()
            ->assertJsonPath('data.0.customer_name', 'NGUYỄN THỊ MINH ANH')
            ->assertJsonPath('data.0.phone', '0912345678');
    }
}
