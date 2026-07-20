<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableColumnPreference extends Model
{
    protected $fillable = [
        'table_key',
        'column_order',
        'updated_by_id',
    ];

    protected $casts = [
        'column_order' => 'array',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
