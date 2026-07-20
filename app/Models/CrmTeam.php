<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmTeam extends Model
{
    protected $fillable = ['name', 'code', 'manager_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
