<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_name', 'phone', 'email', 'identity_number', 'address',
        'product_interest', 'sale_owner_id', 'team_id', 'status', 'approval_status',
        'note', 'source_lead_id', 'rejection_reason', 'approved_by_id',
        'approved_at', 'processing_owner_id', 'processing_status', 'completed_at',
    ];

    protected $casts = ['approved_at' => 'datetime', 'completed_at' => 'datetime'];

    public function saleOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sale_owner_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function processingOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processing_owner_id');
    }

    public function sourceLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'source_lead_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(CrmTeam::class, 'team_id');
    }
}
