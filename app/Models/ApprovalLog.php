<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    protected $fillable = [
        'sale_profile_id', 'action', 'actor_id', 'action_at',
        'previous_status', 'new_status', 'reason', 'note',
    ];

    protected $casts = ['action_at' => 'datetime'];
}
