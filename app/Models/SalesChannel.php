<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesChannel extends Model
{
    protected $fillable = [
        'company_name',
        'branch_name',
        'branch_code',
        'channel_name',
        'note',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
