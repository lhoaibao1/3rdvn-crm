<?php

namespace App\Models;

use App\Services\StalwartMailService;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Wirechat\Wirechat\Contracts\WirechatUser as WirechatUserContract;
use Wirechat\Wirechat\Panel as WirechatPanel;
use Wirechat\Wirechat\Traits\InteractsWithWirechat;

#[Fillable([
    'uid', 'username', 'name', 'email', 'employee_code', 'team_id', 'avatar_path', 'phone', 'password',
    'document_type', 'date_of_birth', 'gender', 'identity_number', 'identity_issued_date', 'identity_issued_place',
    'department', 'position', 'employment_status', 'hire_date', 'office', 'contract_type',
    'sales_projects', 'sales_codes', 'company_name', 'branch_name', 'branch_code', 'sales_channel',
    'team_leader_id', 'am_id', 'zd_id', 'created_by_id',
    'address_line', 'province_code', 'province_name', 'district_code', 'district_name', 'ward_code', 'ward_name',
    'bank_code', 'bank_name', 'bank_account_number', 'bank_account_name', 'bank_branch',
    'tax_code', 'social_insurance_number', 'emergency_contact_name', 'emergency_contact_phone',
    'mail_address', 'mail_account_id', 'mail_status', 'mail_quota_mb', 'mail_provisioned_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, WirechatUserContract
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEACTIVE = 'deactive';

    public const STATUS_DELETED = 'deleted';

    public const MAIL_STATUS_NOT_CREATED = 'not_created';

    public const MAIL_STATUS_ACTIVE = 'active';

    public const MAIL_STATUS_SUSPENDED = 'suspended';

    private static array $auditPendingChanges = [];

    private static array $auditPendingActions = [];

    private const AUDIT_IGNORED_FIELDS = [
        'updated_at',
        'remember_token',
        'password',
    ];

    private const AUDIT_FIELD_LABELS = [
        'uid' => 'UID',
        'employee_code' => 'Employee Code',
        'username' => 'Username',
        'name' => 'Họ tên',
        'email' => 'Email',
        'phone' => 'SĐT',
        'document_type' => 'Loại giấy tờ',
        'date_of_birth' => 'Ngày sinh',
        'gender' => 'Giới tính',
        'identity_number' => 'CCCD/CMND/Hộ chiếu',
        'identity_issued_date' => 'Ngày cấp giấy tờ',
        'identity_issued_place' => 'Nơi cấp',
        'department' => 'Phòng ban',
        'position' => 'Chức danh',
        'employment_status' => 'Trạng thái',
        'hire_date' => 'Ngày vào làm',
        'office' => 'Office',
        'contract_type' => 'Loại hợp đồng',
        'sales_projects' => 'Dự án bán hàng',
        'sales_codes' => 'Mã bán hàng',
        'company_name' => 'Tên công ty',
        'branch_name' => 'Chi nhánh',
        'branch_code' => 'Mã chi nhánh',
        'sales_channel' => 'Kênh',
        'team_leader_id' => 'Team Leader',
        'am_id' => 'AM',
        'zd_id' => 'ZD',
        'address_line' => 'Địa chỉ chi tiết',
        'province_name' => 'Tỉnh/Thành phố',
        'district_name' => 'Quận/Huyện',
        'ward_name' => 'Phường/Xã',
        'bank_code' => 'Mã ngân hàng',
        'bank_name' => 'Ngân hàng',
        'bank_account_number' => 'Số tài khoản',
        'bank_account_name' => 'Chủ tài khoản',
        'bank_branch' => 'Chi nhánh ngân hàng',
        'tax_code' => 'Mã số thuế',
        'social_insurance_number' => 'Mã BHXH',
        'emergency_contact_name' => 'Người liên hệ khẩn cấp',
        'emergency_contact_phone' => 'SĐT khẩn cấp',
        'email_verified_at' => 'Xác thực email',
        'mail_address' => 'Địa chỉ email doanh nghiệp',
        'mail_account_id' => 'Mã hộp thư',
        'mail_status' => 'Trạng thái hộp thư',
        'mail_quota_mb' => 'Dung lượng hộp thư',
        'mail_provisioned_at' => 'Ngày cấp hộp thư',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithWirechat, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'identity_issued_date' => 'date',
            'hire_date' => 'date',
            'sales_projects' => 'array',
            'sales_codes' => 'array',
            'mail_provisioned_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRouteKey(): mixed
    {
        return $this->uid ?: (string) $this->getKey();
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (in_array($this->employment_status, ['inactive', self::STATUS_DEACTIVE, 'resigned', self::STATUS_DELETED], true)) {
            return false;
        }

        if ($panel->getId() === 'uat') {
            return $this->hasRole('Admin');
        }

        return $this->hasRole('Admin') || $this->can('dashboard.view');
    }

    public function canAccessWirechatPanel(WirechatPanel $panel): bool
    {
        return $this->isActiveForWirechat();
    }

    public function canCreateChats(): bool
    {
        return $this->isActiveForWirechat();
    }

    public function canCreateGroups(): bool
    {
        return $this->isActiveForWirechat();
    }

    public function getWirechatNameAttribute(): ?string
    {
        $name = trim((string) $this->name) ?: 'Người dùng';
        $role = $this->getRoleNames()->first() ?: 'User';
        $organization = collect([$this->company_name, $this->branch_name])
            ->filter(fn ($value): bool => filled($value))
            ->unique()
            ->implode('/');

        return implode(' - ', array_filter([$name, $role, $organization]));
    }

    public function getWirechatAvatarUrlAttribute(): ?string
    {
        return filled($this->avatar_path)
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    public function getWirechatProfileUrlAttribute(): ?string
    {
        return null;
    }

    private function isActiveForWirechat(): bool
    {
        return ! in_array(
            $this->employment_status,
            ['inactive', self::STATUS_DEACTIVE, 'resigned', self::STATUS_DELETED],
            true,
        );
    }

    public static function normalizeUsername(?string $value): string
    {
        $normalized = Str::lower(Str::ascii(trim((string) $value)));
        $normalized = preg_replace('/[^a-z0-9._-]+/', '.', $normalized) ?? '';

        return Str::limit(trim($normalized, '._-'), 63, '');
    }

    public static function uniqueUsername(?string $value): string
    {
        $base = static::normalizeUsername($value) ?: 'user';
        $candidate = $base;
        $suffix = 1;

        while (static::query()->where('username', $candidate)->exists()) {
            $suffix++;
            $tail = '-'.$suffix;
            $candidate = Str::limit($base, 63 - strlen($tail), '').$tail;
        }

        return $candidate;
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $sequence = static::nextUserSequence($user);

            $codes = static::accessCodesForSequence($sequence);

            $user->uid ??= $codes['uid'];
            $user->employee_code ??= $codes['employee_code'];
            $usernameSeed = $user->username
                ?: Str::before((string) $user->email, '@')
                ?: Str::lower((string) $user->uid);
            $user->username = static::uniqueUsername($usernameSeed);
            $user->created_by_id ??= auth()->id();
            $user->hire_date ??= now()->toDateString();
            $user->employment_status ??= 'active';
        });

        static::created(function (User $user): void {
            static::writeAuditLog($user, 'created', [[
                'field' => 'created',
                'label' => 'Tạo người dùng',
                'old' => null,
                'new' => $user->name,
            ]]);
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('username')) {
                $user->username = static::normalizeUsername($user->username);
            }

            $changes = static::captureAuditChanges($user);

            if ($changes === []) {
                return;
            }

            $key = spl_object_id($user);
            static::$auditPendingChanges[$key] = $changes;
            static::$auditPendingActions[$key] = static::auditActionForChanges($changes);
        });

        static::updated(function (User $user): void {
            $key = spl_object_id($user);
            $changes = static::$auditPendingChanges[$key] ?? [];
            $action = static::$auditPendingActions[$key] ?? 'updated';

            unset(static::$auditPendingChanges[$key], static::$auditPendingActions[$key]);

            $mailProfileFields = ['name', 'email', 'phone', 'uid', 'employee_code', 'position', 'department', 'company_name', 'branch_name'];

            if (filled($user->mail_account_id)
                && array_intersect(array_keys($changes), $mailProfileFields) !== []) {
                app(StalwartMailService::class)->scheduleProfileSync($user);
            }

            if ($changes === []) {
                return;
            }

            static::writeAuditLog($user, $action, array_values($changes));
        });
    }

    private static function captureAuditChanges(User $user): array
    {
        $changes = [];

        foreach (array_diff_key($user->getDirty(), array_flip(self::AUDIT_IGNORED_FIELDS)) as $field => $newValue) {
            $oldValue = $user->getOriginal($field);

            if (static::auditNormalizeValue($oldValue) === static::auditNormalizeValue($newValue)) {
                continue;
            }

            $changes[$field] = [
                'field' => $field,
                'label' => self::AUDIT_FIELD_LABELS[$field] ?? str($field)->replace('_', ' ')->title()->toString(),
                'old' => static::auditNormalizeValue($oldValue),
                'new' => static::auditNormalizeValue($newValue),
            ];
        }

        return $changes;
    }

    private static function writeAuditLog(User $user, string $action, array $changes): void
    {
        UserChangeLog::query()->create([
            'user_id' => $user->getKey(),
            'actor_id' => auth()->id(),
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private static function auditActionForChanges(array $changes): string
    {
        $status = $changes['employment_status']['new'] ?? null;
        $uidNew = $changes['uid']['new'] ?? 'unchanged';
        $employeeCodeNew = $changes['employee_code']['new'] ?? 'unchanged';

        if ($status === self::STATUS_DELETED && $uidNew === null && $employeeCodeNew === null) {
            return 'deleted_access';
        }

        if ($status === self::STATUS_ACTIVE && filled($uidNew) && filled($employeeCodeNew)) {
            return 'reissued_access';
        }

        return 'updated';
    }

    private static function auditNormalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y H:i:s');
        }

        if (is_array($value)) {
            ksort($value);

            return $value;
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }

    private static function nextUserSequence(User $user): int
    {
        if ($user->getKey()) {
            return (int) $user->getKey();
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            $sequenceName = DB::selectOne("select pg_get_serial_sequence('users', 'id') as sequence_name")?->sequence_name;

            if ($sequenceName) {
                $sequence = (int) DB::selectOne('select nextval(CAST(? AS regclass)) as value', [$sequenceName])->value;
                $user->id = $sequence;

                return $sequence;
            }
        }

        return ((int) static::query()->max('id')) + 1;
    }

    public static function issueAccessCodes(): array
    {
        return static::accessCodesForSequence(static::nextAccessSequence());
    }

    public function markAccessDeleted(): void
    {
        $this->forceFill([
            'uid' => null,
            'employee_code' => null,
            'employment_status' => self::STATUS_DELETED,
        ])->save();
    }

    public function reissueAccessCodes(): void
    {
        $this->forceFill([
            ...static::issueAccessCodes(),
            'employment_status' => self::STATUS_ACTIVE,
        ])->save();
    }

    private static function accessCodesForSequence(int $sequence): array
    {
        return [
            'uid' => 'UID'.now()->format('ym').str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'employee_code' => 'RD'.now()->format('y').str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
        ];
    }

    private static function nextAccessSequence(): int
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $sequenceName = DB::selectOne("select pg_get_serial_sequence('users', 'id') as sequence_name")?->sequence_name;

            if ($sequenceName) {
                return (int) DB::selectOne('select nextval(CAST(? AS regclass)) as value', [$sequenceName])->value;
            }
        }

        return ((int) static::query()->max('id')) + 1;
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(UserChangeLog::class)->latest();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(CrmTeam::class, 'team_id');
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(self::class, 'team_leader_id');
    }

    public function am(): BelongsTo
    {
        return $this->belongsTo(self::class, 'am_id');
    }

    public function zd(): BelongsTo
    {
        return $this->belongsTo(self::class, 'zd_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by_id');
    }
}
