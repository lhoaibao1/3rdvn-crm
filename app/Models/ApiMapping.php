<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiMapping extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mapping_name', 'target_system', 'endpoint_url', 'method',
        'auth_type', 'request_headers_json', 'field_mapping_json',
        'is_active', 'note',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
