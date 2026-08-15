<?php

namespace App\Support\Notifications;

use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushSender
{
    public function __construct(private readonly VapidKeyStore $keys) {}

    public function send(WebPushSubscription $stored, array $payload): void
    {
        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) config('services.web_push.subject'),
                    'publicKey' => $this->keys->publicKey(),
                    'privateKey' => $this->keys->privateKey(),
                ],
            ], [
                'TTL' => (int) config('services.web_push.ttl', 300),
                'urgency' => 'high',
            ]);

            $report = $webPush->sendOneNotification(
                Subscription::create([
                    'endpoint' => $stored->endpoint,
                    'publicKey' => $stored->public_key,
                    'authToken' => $stored->auth_token,
                    'contentEncoding' => $stored->content_encoding,
                ]),
                json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );

            if ($report->isSubscriptionExpired()) {
                $stored->delete();
                return;
            }

            if (! $report->isSuccess()) {
                Log::warning('Web Push delivery failed.', [
                    'subscription_id' => $stored->getKey(),
                    'reason' => $report->getReason(),
                ]);
                return;
            }

            $stored->forceFill(['last_used_at' => now()])->save();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
