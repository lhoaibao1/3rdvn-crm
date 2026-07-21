<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JobVacancy extends Model
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'code', 'slug', 'sales_project_id', 'banner_path', 'title', 'department',
        'work_location', 'employment_type', 'quantity', 'experience_level', 'salary_min', 'salary_max', 'salary_negotiable',
        'application_deadline', 'status', 'is_published', 'is_featured', 'sort_order',
        'contact_email', 'short_description', 'description', 'requirements', 'benefits',
        'published_at', 'auto_assignee_id', 'created_by_id', 'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'sales_project_id' => 'integer',
            'quantity' => 'integer',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'salary_negotiable' => 'boolean',
            'application_deadline' => 'date',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $vacancy): void {
            $vacancy->created_by_id ??= auth()->id();
            $vacancy->updated_by_id ??= auth()->id();

            if ($vacancy->is_published && ! $vacancy->published_at) {
                $vacancy->published_at = now();
            }
        });

        static::updating(function (self $vacancy): void {
            $vacancy->updated_by_id = auth()->id() ?: $vacancy->updated_by_id;

            if ($vacancy->isDirty('is_published') && $vacancy->is_published && ! $vacancy->published_at) {
                $vacancy->published_at = now();
            }
        });

        static::created(function (self $vacancy): void {
            $vacancy->forceFill([
                'code' => $vacancy->code ?: 'TD'.now()->format('ym').str_pad((string) $vacancy->getKey(), 4, '0', STR_PAD_LEFT),
                'slug' => $vacancy->slug ?: Str::slug($vacancy->title).'-'.$vacancy->getKey(),
            ])->saveQuietly();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Còn tuyển',
            self::STATUS_CLOSED => 'Ngưng tuyển',
        ];
    }

    public static function employmentTypeOptions(): array
    {
        return [
            'full_time' => 'Toàn thời gian',
            'part_time' => 'Bán thời gian',
            'contract' => 'Hợp đồng',
            'internship' => 'Thực tập',
            'remote' => 'Làm việc từ xa',
        ];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('status', self::STATUS_OPEN)
            ->where(function (Builder $query): void {
                $query->whereNull('application_deadline')
                    ->orWhereDate('application_deadline', '>=', today());
            });
    }

    public function isOpenForApplications(): bool
    {
        return $this->is_published
            && $this->status === self::STATUS_OPEN
            && (! $this->application_deadline || $this->application_deadline->gte(today()));
    }

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_OPEN
            && $this->application_deadline
            && $this->application_deadline->lt(today())) {
            return 'Đã hết hạn';
        }

        return self::statusOptions()[$this->status] ?? 'Không xác định';
    }

    public function employmentTypeLabel(): string
    {
        return self::employmentTypeOptions()[$this->employment_type] ?? $this->employment_type;
    }

    public function salaryLabel(): string
    {
        if ($this->salary_negotiable || (! $this->salary_min && ! $this->salary_max)) {
            return 'Thỏa thuận';
        }

        if ($this->salary_min && $this->salary_max) {
            return number_format($this->salary_min, 0, ',', '.').' - '.number_format($this->salary_max, 0, ',', '.').' VNĐ';
        }

        return ($this->salary_min ? 'Từ ' : 'Đến ')
            .number_format((int) ($this->salary_min ?: $this->salary_max), 0, ',', '.').' VNĐ';
    }

    public function salesProject(): BelongsTo
    {
        return $this->belongsTo(SalesProject::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CandidateApplication::class);
    }

    public function autoAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auto_assignee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
