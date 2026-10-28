<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StandalonePanelLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_admin_can_log_in_without_sso(): void
    {
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['allowed_apps' => ['crm']]);
        $user->assignRole('Admin');
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_local_password_is_rejected(): void
    {
        $user = User::factory()->create(['allowed_apps' => ['crm']]);
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
        $this->assertGuest();
    }

    public function test_inactive_local_user_is_rejected(): void
    {
        $user = User::factory()->create(['employment_status' => 'deactive', 'allowed_apps' => ['crm']]);
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
        $this->assertGuest();
    }
}
