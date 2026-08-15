<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebPushSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_and_refresh_mobile_subscription(): void
    {
        $user = User::factory()->create();
        $payload = [
            'endpoint' => 'https://push.example.test/subscription/device-1',
            'keys' => [
                'p256dh' => str_repeat('a', 88),
                'auth' => str_repeat('b', 24),
            ],
            'content_encoding' => 'aes128gcm',
        ];

        $this->actingAs($user)
            ->postJson(route('crm.push-subscriptions.store'), $payload)
            ->assertCreated()
            ->assertJson(['ok' => true]);

        $this->actingAs($user)
            ->postJson(route('crm.push-subscriptions.store'), $payload)
            ->assertOk();

        $this->assertDatabaseCount('web_push_subscriptions', 1);
        $this->assertDatabaseHas('web_push_subscriptions', [
            'user_id' => $user->getKey(),
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_subscription_endpoint_is_reassigned_to_current_device_user(): void
    {
        [$first, $second] = User::factory()->count(2)->create();
        $payload = [
            'endpoint' => 'https://push.example.test/subscription/shared-device',
            'keys' => ['p256dh' => str_repeat('a', 88), 'auth' => str_repeat('b', 24)],
        ];

        $this->actingAs($first)->postJson(route('crm.push-subscriptions.store'), $payload)->assertCreated();
        $this->actingAs($second)->postJson(route('crm.push-subscriptions.store'), $payload)->assertOk();

        $this->assertSame($second->getKey(), WebPushSubscription::query()->sole()->user_id);
    }

    public function test_guest_cannot_register_push_subscription(): void
    {
        $this->postJson(route('crm.push-subscriptions.store'), [])->assertRedirect('/authen/login');
    }
}
