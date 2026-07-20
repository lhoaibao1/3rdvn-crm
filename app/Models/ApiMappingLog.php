<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiMappingLog extends Model
{
    protected $fillable = [
        'api_mapping_id',
        'sale_profile_id',
        'target_system',
        'endpoint_url',
        'request_payload',
        'response_body',
        'status_code',
        'result',
        'error_message',
        'created_by_id',
    ];

    protected $casts = ['request_payload' => 'array'];
}
