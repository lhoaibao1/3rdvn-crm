<?php

namespace App\Models;

use App\Enums\FeolSyncState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id', 'partner_lead_id', 'partner_app_id', 'main_status', 'sub_status',
    'b1_url', 'deeplink_url', 'sync_state', 'sync_requested_at', 'last_synced_at',
    'next_sync_at', 'last_error', 'raw_payload', 'version',
])]
class FeolApplicationIntegration extends Model
{
    protected function casts(): array
    {
        return [
            'sync_state' => FeolSyncState::class,
            'sync_requested_at' => 'immutable_datetime',
            'last_synced_at' => 'immutable_datetime',
            'next_sync_at' => 'immutable_datetime',
            'raw_payload' => 'array',
            'version' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
