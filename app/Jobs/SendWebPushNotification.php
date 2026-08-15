<?php

namespace App\Jobs;

use App\Models\WebPushSubscription;
use App\Support\Notifications\WebPushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWebPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 90];

    public function __construct(
        public readonly array $recipientIds,
        public readonly array $payload,
    ) {
        $this->afterCommit();
        $this->onQueue('default');
    }

    public function handle(WebPushSender $sender): void
    {
        WebPushSubscription::query()
            ->whereIn('user_id', $this->recipientIds)
            ->orderBy('id')
            ->eachById(fn (WebPushSubscription $subscription) => $sender->send($subscription, $this->payload));
    }
}
