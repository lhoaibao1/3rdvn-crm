<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserChangeLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UserAuditObserver
{
    /** @var array<int, array<string, array<string, mixed>>> */
    private array $pendingChanges = [];

    /** @var array<int, string> */
    private array $pendingActions = [];

    private const IGNORED_FIELDS = [
        'updated_at',
        'remember_token',
        'password',
    ];

    private const FIELD_LABELS = [
        'uid' => 'UID',
        'employee_code' => 'Employee Code',
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
    ];

    public function created(User $user): void
    {
        UserChangeLog::query()->create([
            'user_id' => $user->getKey(),
            'actor_id' => Auth::id(),
            'action' => 'created',
            'changes' => [[
                'field' => 'created',
                'label' => 'Tạo người dùng',
                'old' => null,
                'new' => $user->name,
            ]],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function updating(User $user): void
    {
        $changes = [];

        foreach (Arr::except($user->getDirty(), self::IGNORED_FIELDS) as $field => $newValue) {
            $oldValue = $user->getOriginal($field);

            if ($this->normalizeValue($oldValue) === $this->normalizeValue($newValue)) {
                continue;
            }

            $changes[$field] = [
                'field' => $field,
                'label' => self::FIELD_LABELS[$field] ?? str($field)->replace('_', ' ')->title()->toString(),
                'old' => $this->normalizeValue($oldValue),
                'new' => $this->normalizeValue($newValue),
            ];
        }

        if ($changes === []) {
            return;
        }

        $key = spl_object_id($user);
        $this->pendingChanges[$key] = $changes;
        $this->pendingActions[$key] = $this->guessAction($changes);
    }

    public function updated(User $user): void
    {
        $key = spl_object_id($user);
        $changes = $this->pendingChanges[$key] ?? [];

        unset($this->pendingChanges[$key]);

        if ($changes === []) {
            unset($this->pendingActions[$key]);

            return;
        }

        UserChangeLog::query()->create([
            'user_id' => $user->getKey(),
            'actor_id' => Auth::id(),
            'action' => $this->pendingActions[$key] ?? 'updated',
            'changes' => array_values($changes),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        unset($this->pendingActions[$key]);
    }

    private function guessAction(array $changes): string
    {
        $status = $changes['employment_status']['new'] ?? null;
        $uidNew = $changes['uid']['new'] ?? 'unchanged';
        $employeeCodeNew = $changes['employee_code']['new'] ?? 'unchanged';

        if ($status === User::STATUS_DELETED && $uidNew === null && $employeeCodeNew === null) {
            return 'deleted_access';
        }

        if ($status === User::STATUS_ACTIVE && filled($uidNew) && filled($employeeCodeNew)) {
            return 'reissued_access';
        }

        return 'updated';
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y H:i:s');
        }

        if (is_array($value)) {
            ksort($value);

            return $value;
        }

        if (is_bool($value)) {
            return $value;
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
