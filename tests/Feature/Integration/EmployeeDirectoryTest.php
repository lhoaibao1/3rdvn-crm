<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_is_required(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);

        $this->getJson('/api/integration/v1/users')->assertForbidden();
    }

    public function test_it_returns_full_paginated_employee_profiles(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);
        User::factory()->create([
            'name' => 'Nguyễn Văn A',
            'employee_code' => 'NV001',
            'phone' => '0909000001',
            'identity_number' => '079000000001',
            'bank_name' => 'Vietcombank',
            'bank_account_number' => '001100000001',
            'employment_status' => 'active',
        ]);

        $this->withToken('test-token')->getJson('/api/integration/v1/users?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.employee_code', 'NV001')
            ->assertJsonPath('data.0.identity_number', '079000000001')
            ->assertJsonPath('data.0.bank_name', 'Vietcombank')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure(['data' => [['roles', 'address_line', 'social_insurance_number', 'mail_address']], 'links', 'meta']);
    }

    public function test_it_excludes_couriers_and_inactive_users(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);
        User::factory()->create(['name' => 'Nhân sự nghỉ việc', 'employment_status' => 'resigned']);
        Role::create(['name' => 'Courier', 'guard_name' => 'web']);
        $courier = User::factory()->create(['name' => 'Nhân sự giao nhận', 'employment_status' => 'active']);
        $courier->assignRole('Courier');
        User::factory()->create(['name' => 'Nhân sự hợp lệ', 'employment_status' => 'active']);

        $this->withToken('test-token')->getJson('/api/integration/v1/users?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nhân sự hợp lệ');
    }
}
