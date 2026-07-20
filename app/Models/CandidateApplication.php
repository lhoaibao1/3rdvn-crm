<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateApplication extends Model
{
    use SoftDeletes;

    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_INTERVIEWING = 'interviewing';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'application_code', 'job_vacancy_id', 'full_name', 'email', 'phone', 'date_of_birth', 'gender',
        'applied_position', 'current_position', 'latest_company', 'experience_years',
        'education_level', 'expected_salary', 'available_from', 'address_line',
        'province_code', 'province_name', 'district_code', 'district_name',
        'ward_code', 'ward_name', 'cover_letter', 'cv_path', 'source', 'status',
        'internal_note', 'reviewed_by_id', 'reviewed_at', 'converted_user_id',
        'converted_at', 'consented_at', 'ip_address', 'user_agent',
        'assigned_to_id', 'assigned_by_id', 'assigned_at', 'interview_at',
        'interview_note', 'interview_recommendation', 'submitted_at',
        'approved_by_id', 'approved_at', 'approval_note',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'available_from' => 'date',
            'expected_salary' => 'integer',
            'reviewed_at' => 'datetime',
            'converted_at' => 'datetime',
            'consented_at' => 'datetime',
            'assigned_at' => 'datetime',
            'interview_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $candidate): void {
            if (blank($candidate->application_code)) {
                $candidate->forceFill([
                    'application_code' => 'CV'.now()->format('ym').str_pad((string) $candidate->getKey(), 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'Mới tiếp nhận',
            self::STATUS_REVIEWING => 'Đang xem xét',
            self::STATUS_ASSIGNED => 'Đã phân công phỏng vấn',
            self::STATUS_INTERVIEWING => 'Đang phỏng vấn',
            self::STATUS_PENDING_APPROVAL => 'Chờ phê duyệt tuyển dụng',
            self::STATUS_APPROVED => 'Đã phê duyệt tuyển dụng',
            self::STATUS_REJECTED => 'Không phù hợp',
            self::STATUS_CONVERTED => 'Đã cấp mã nhân sự',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::statusOptions()[$status] ?? ($status ?: '-');
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            self::STATUS_NEW => 'info',
            self::STATUS_REVIEWING,
            self::STATUS_ASSIGNED,
            self::STATUS_INTERVIEWING,
            self::STATUS_PENDING_APPROVAL => 'warning',
            self::STATUS_APPROVED,
            self::STATUS_ACCEPTED,
            self::STATUS_CONVERTED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'gray',
        };
    }

    public static function recommendationOptions(): array
    {
        return [
            'hire' => 'Đề xuất tuyển dụng',
            'reject' => 'Không đề xuất tuyển dụng',
        ];
    }

    public static function recommendationLabel(?string $recommendation): string
    {
        return self::recommendationOptions()[$recommendation] ?? ($recommendation ?: '-');
    }

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function convertedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
