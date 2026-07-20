<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmModule extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'label',
        'description',
        'icon',
        'route_name',
        'sort_order',
        'is_active',
        'required_permissions',
        'required_roles',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'required_permissions' => 'array',
        'required_roles' => 'array',
    ];

    public function salesProjects(): HasMany
    {
        return $this->hasMany(SalesProject::class, 'crm_module_id');
    }
}
