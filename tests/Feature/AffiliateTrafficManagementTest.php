<?php

namespace Tests\Feature;

use App\Models\AffiliateClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AffiliateTrafficManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_traffic_management(): void
    {
        Role::findOrCreate('Direct Sale', 'web');
        $sale = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RDTRAFFIC01',
            'employment_status' => 'active',
        ]));
        $sale->assignRole('Direct Sale');

        $token = $this->loginToken($sale);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/integration/v1/affiliate-portal/traffic')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_filter_paginate_and_export_all_campaign_traffic(): void
    {
        Role::findOrCreate('Admin', 'web');
        Role::findOrCreate('Direct Sale', 'web');

        $admin = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RDTRAFFICADMIN',
            'employment_status' => 'active',
        ]));
        $admin->assignRole('Admin');
        $publisher = User::withoutEvents(fn () => User::factory()->create([
            'name' => 'Publisher Traffic',
            'employee_code' => 'RDTRAFFIC02',
            'employment_status' => 'active',
        ]));
        $publisher->assignRole('Direct Sale');

        AffiliateClick::query()->create([
            'campaign_slug' => 'tinvay-vietcredit',
            'campaign_name' => 'Tin Vay - VietCredit',
            'partner' => 'accesstrade',
            'employee_code' => $publisher->employee_code,
            'user_id' => $publisher->getKey(),
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (iPhone) Mobile Safari/605.1',
            'referer' => 'https://zalo.me/example',
            'clicked_at' => now(),
        ]);
        AffiliateClick::query()->create([
            'campaign_slug' => 'lotte-finance',
            'campaign_name' => 'Lotte Finance',
            'partner' => 'internal',
            'employee_code' => $publisher->employee_code,
            'user_id' => $publisher->getKey(),
            'ip_address' => '203.0.113.20',
            'user_agent' => 'Mozilla/5.0 Chrome/130.0',
            'referer' => null,
            'clicked_at' => now(),
        ]);

        $token = $this->loginToken($admin);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/integration/v1/affiliate-portal/traffic?campaign=tinvay-vietcredit&device=mobile&source=zalo&per_page=20')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('stats.total_clicks', 1)
            ->assertJsonPath('data.0.campaign_slug', 'tinvay-vietcredit')
            ->assertJsonPath('data.0.employee_name', 'Publisher Traffic')
            ->assertJsonPath('data.0.source', 'Zalo')
            ->assertJsonMissingPath('data.0.partner')
            ->assertJsonCount(2, 'options.campaigns');

        $export = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/integration/v1/affiliate-portal/traffic/export?employee_code=RDTRAFFIC02')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filename', fn (string $filename): bool => str_ends_with($filename, '.xlsx'))
            ->assertJsonPath('count', 2);

        $xlsx = base64_decode((string) $export->json('xlsx_base64'));
        $this->assertStringStartsWith('PK', $xlsx);
    }

    private function loginToken(User $user): string
    {
        return (string) $this->postJson('/api/integration/v1/affiliate-portal/login', [
            'identifier' => $user->employee_code,
            'password' => 'password',
        ])->assertOk()->json('token');
    }
}
