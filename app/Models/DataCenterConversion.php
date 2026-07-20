<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataCenterConversion extends Model
{
    protected $fillable = [
        'data_center_lead_id', 'sales_project_id', 'lead_id', 'converted_by_id', 'converted_at',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    public function dataCenterLead(): BelongsTo
    {
        return $this->belongsTo(DataCenterLead::class);
    }

    public function salesProject(): BelongsTo
    {
        return $this->belongsTo(SalesProject::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }
}
