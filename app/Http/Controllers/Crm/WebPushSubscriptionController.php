<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebPushSubscriptionRequest;
use App\Models\WebPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebPushSubscriptionController extends Controller
{
    public function store(StoreWebPushSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $endpoint = (string) $data['endpoint'];

        $subscription = WebPushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => $request->user()->getKey(),
                'endpoint' => $endpoint,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['content_encoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'ok' => true,
            'subscription_id' => $subscription->getKey(),
        ], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:2048'],
        ]);

        WebPushSubscription::query()
            ->where('user_id', $request->user()->getKey())
            ->where('endpoint_hash', hash('sha256', (string) $data['endpoint']))
            ->delete();

        return response()->json(['ok' => true]);
    }
}
