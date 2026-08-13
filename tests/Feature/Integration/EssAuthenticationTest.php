<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EssAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.vpn_directory.token' => 'test-integration-token']);
    }

    public function test_active_user_can_authenticate_with_employee_code_and_receive_roles(): void
    {
        $user = User::factory()->create([
            'uid' => 'UID26000001',
            'employee_code' => 'EMP0001',
            'password' => Hash::make('Secret@123'),
            'employment_status' => User::STATUS_ACTIVE,
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));

        $response = $this->withToken('test-integration-token')->postJson(
            '/api/integration/v1/authenticate',
            ['identifier' => 'emp0001', 'password' => 'Secret@123'],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', (string) $user->id)
            ->assertJsonPath('data.uid', 'UID26000001')
            ->assertJsonPath('data.employee_code', 'EMP0001')
            ->assertJsonPath('data.roles.0', 'Admin')
            ->assertJsonMissingPath('data.password');
    }

    public function test_invalid_password_returns_generic_unauthorized_response(): void
    {
        User::factory()->create([
            'employee_code' => 'EMP0002',
            'password' => Hash::make('Secret@123'),
            'employment_status' => User::STATUS_ACTIVE,
        ]);

        $this->withToken('test-integration-token')->postJson(
            '/api/integration/v1/authenticate',
            ['identifier' => 'EMP0002', 'password' => 'wrong-password'],
        )->assertUnauthorized()->assertJson([
            'message' => 'Thông tin đăng nhập không đúng hoặc tài khoản đã ngừng hoạt động.',
        ]);
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->create([
            'employee_code' => 'EMP0003',
            'password' => Hash::make('Secret@123'),
            'employment_status' => User::STATUS_DEACTIVE,
        ]);

        $this->withToken('test-integration-token')->postJson(
            '/api/integration/v1/authenticate',
            ['identifier' => 'EMP0003', 'password' => 'Secret@123'],
        )->assertUnauthorized();
    }

    public function test_integration_token_is_required(): void
    {
        $this->postJson('/api/integration/v1/authenticate', [
            'identifier' => 'EMP0001',
            'password' => 'Secret@123',
        ])->assertUnauthorized();
    }
}
