<?php

namespace Tests\Feature;

use App\Models\AffiliateConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AffiliateConversionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_uses_the_same_filters_and_exports_the_full_matching_dataset_as_xlsx(): void
    {
        Role::findOrCreate('Admin', 'web');
        $admin = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RDEXPORTADMIN',
            'employment_status' => 'active',
        ]));
        $admin->assignRole('Admin');

        foreach ([1, 2] as $index) {
            AffiliateConversion::query()->create([
                'partner' => 'accesstrade',
                'conversion_id' => "TIN-EXPORT-{$index}",
                'transaction_id' => "GD-EXPORT-{$index}",
                'campaign_name' => 'Tin Vay - VietCredit',
                'conversion_status' => 'approved_waiting_disbursement',
                'sale_amount' => 20000000,
                'conversion_time' => now(),
                'aff_sub1' => $admin->employee_code,
                'created_by_id' => $admin->getKey(),
                'raw_payload' => [
                    'customer_name' => "Khách hàng {$index}",
                    'customer_phone' => '090000000'.$index,
                    'customer_group_label' => 'High',
                ],
            ]);
        }

        AffiliateConversion::query()->create([
            'partner' => 'hyperlead',
            'conversion_id' => 'SFINANCE-NOT-MATCHING',
            'campaign_name' => 'SFinance',
            'conversion_status' => 'success',
            'sale_amount' => 30000000,
            'conversion_time' => now(),
            'aff_sub1' => $admin->employee_code,
            'created_by_id' => $admin->getKey(),
            'raw_payload' => [],
        ]);

        $token = (string) $this->postJson('/api/integration/v1/affiliate-portal/login', [
            'identifier' => $admin->employee_code,
            'password' => 'password',
        ])->assertOk()->json('token');

        $export = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/integration/v1/affiliate-portal/conversions/export?campaign=tinvay&status=approved_waiting_disbursement&search=TIN-EXPORT')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filename', fn (string $filename): bool => str_ends_with($filename, '.xlsx'))
            ->assertJsonPath('count', 2);

        $xlsx = base64_decode((string) $export->json('xlsx_base64'));
        $this->assertStringStartsWith('PK', $xlsx);
        $this->assertStringNotContainsString('SFINANCE-NOT-MATCHING', $xlsx);
    }

    public function test_vpbank_report_uses_click_time_when_partner_omits_conversion_time(): void
    {
        Role::findOrCreate('Admin', 'web');
        $admin = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RDVPDATEADMIN',
            'employment_status' => 'active',
        ]));
        $admin->assignRole('Admin');

        $clickTime = now()->subHour()->startOfSecond();
        AffiliateConversion::query()->create([
            'partner' => 'isclix',
            'conversion_id' => 'VP-DATE-FALLBACK',
            'transaction_id' => 'VP-GD-DATE-FALLBACK',
            'campaign_name' => 'VPBank UPL',
            'conversion_status' => 'pending',
            'conversion_time' => null,
            'click_time' => $clickTime,
            'aff_sub1' => $admin->employee_code,
            'created_by_id' => $admin->getKey(),
            'raw_payload' => [],
        ]);

        $token = (string) $this->postJson('/api/integration/v1/affiliate-portal/login', [
            'identifier' => $admin->employee_code,
            'password' => 'password',
        ])->assertOk()->json('token');
        $date = $clickTime->format('Y-m-d');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/integration/v1/affiliate-portal/conversions?campaign=vpbank&date_from={$date}&date_to={$date}")
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.conversion_id', 'VP-DATE-FALLBACK')
            ->assertJsonPath('data.0.conversion_time', $clickTime->format('H:i d/m/Y'));

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/integration/v1/affiliate-portal/conversions/export?campaign=vpbank&date_from={$date}&date_to={$date}")
            ->assertOk()
            ->assertJsonPath('count', 1);
    }
}
