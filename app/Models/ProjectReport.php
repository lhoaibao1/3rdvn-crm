<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectReport extends Model
{
    public const ORIGIN_MANUAL = 'manual';

    public const ORIGIN_APPLICATION = 'application';

    public const STATUS_PENDING = 'Chờ xử lý';

    public const STATUS_PROCESSED = 'Đã xử lý';

    public const STATUS_REJECTED = 'Từ chối';

    protected $fillable = [
        'sales_project_id',
        'created_by_id',
        'team_id',
        'team_leader_id',
        'am_id',
        'zd_id',
        'customer_name',
        'application_id',
        'origin',
        'province_code',
        'province_name',
        'district_code',
        'district_name',
        'identity_number',
        'phone',
        'product_code',
        'product_name',
        'loan_amount',
        'approved_months',
        'approved_interest_rate',
        'source_data',
        'sales_code',
        'status',
        'status_updated_by_id',
        'status_updated_at',
        'converted_by_id',
        'converted_at',
    ];

    protected $casts = [
        'team_id' => 'integer',
        'team_leader_id' => 'integer',
        'am_id' => 'integer',
        'zd_id' => 'integer',
        'loan_amount' => 'integer',
        'approved_months' => 'integer',
        'approved_interest_rate' => 'decimal:4',
        'source_data' => 'array',
        'status_updated_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => self::STATUS_PENDING,
            self::STATUS_PROCESSED => self::STATUS_PROCESSED,
            self::STATUS_REJECTED => self::STATUS_REJECTED,
        ];
    }

    public function salesProject(): BelongsTo
    {
        return $this->belongsTo(SalesProject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(CrmTeam::class, 'team_id');
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function am(): BelongsTo
    {
        return $this->belongsTo(User::class, 'am_id');
    }

    public function zd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zd_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by_id');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }
}
