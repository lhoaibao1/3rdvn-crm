<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integration\Concerns\AuthorizesFeolBridge;
use App\Http\Requests\Integration\SyncFeolApplicationRequest;
use App\Models\Application;
use App\Support\Applications\FeolApplicationSync;
use Illuminate\Http\JsonResponse;

class FeolApplicationSyncController extends Controller
{
    use AuthorizesFeolBridge;

    public function __invoke(SyncFeolApplicationRequest $request, Application $application, FeolApplicationSync $sync): JsonResponse
    {
        $this->authorizeFeolBridge($request);
        $integration = $sync->sync($application, $request->validated());

        return response()->json(['data' => [
            'application_id' => $application->getKey(),
            'status' => $integration->sub_status,
            'sync_state' => $integration->sync_state->value,
            'has_b1_url' => filled($integration->b1_url),
            'has_deeplink_url' => filled($integration->deeplink_url),
            'last_synced_at' => $integration->last_synced_at?->toIso8601String(),
        ]]);
    }
}
