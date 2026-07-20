<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'record_type',
    'record_id',
    'actor_id',
    'action',
    'changes',
    'ip_address',
    'user_agent',
])]
class RecordChangeLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function record(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
