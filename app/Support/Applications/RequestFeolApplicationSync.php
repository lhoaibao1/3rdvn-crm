<?php

namespace App\Support\Applications;

use App\Enums\FeolSyncState;
use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RequestFeolApplicationSync
{
    public function handle(Application $application): void
    {
        Gate::authorize('update', $application);

        DB::transaction(function () use ($application): void {
            $integration = $application->feolIntegration()->lockForUpdate()->firstOrNew();
            $integration->fill([
                'sync_state' => FeolSyncState::PENDING,
                'sync_requested_at' => now(),
                'next_sync_at' => now(),
                'last_error' => null,
            ])->save();

            $application->changeLogs()->create([
                'actor_id' => auth()->id(),
                'action' => 'feol_sync_requested',
                'changes' => ['sync_requested_at' => ['old' => null, 'new' => now()->toIso8601String()]],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        }, 3);
    }
}
