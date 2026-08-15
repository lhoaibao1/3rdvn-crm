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
}
