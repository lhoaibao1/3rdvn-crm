<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmLookup extends Model
{
    protected $fillable = [
        'type',
        'key',
        'label',
        'value',
        'note',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
