<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LatestNotificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $notification = $request->user()?->unreadNotifications()->latest()->first();

        if (! $notification) {
            return response()->json(['notification' => null]);
        }

        $data = $notification->data;
        $body = preg_replace('/<br\s*\/?\s*>/i', "\n", (string) ($data['body'] ?? '')) ?: '';
        $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace('/^\s*(Mail|Hồ sơ|Hệ thống)\s*/u', '', $body) ?: $body;

        return response()->json([
            'notification' => [
                'id' => $notification->getKey(),
                'title' => (string) ($data['title'] ?? 'Thông báo mới'),
                'body' => trim($body),
                'url' => data_get($data, 'actions.0.url', url('/')),
                'status' => (string) ($data['status'] ?? 'info'),
            ],
        ]);
    }
}
