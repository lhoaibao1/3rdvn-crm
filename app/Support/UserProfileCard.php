<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class UserProfileCard
{
    public static function render(?User $user, string $mode = 'view'): HtmlString
    {
        $name = e($user?->name ?: 'Người dùng mới');
        $uid = e($user?->uid ?: ($user?->exists ? str_pad((string) $user->getKey(), 3, '0', STR_PAD_LEFT) : 'Mới'));
        $employeeCode = e($user?->employee_code ?: 'Chưa có');
        $department = e($user?->department ?: 'Chưa có');
        $email = e($user?->email ?: 'Chưa có email');
        $roles = e($user?->exists ? ($user->roles()->pluck('name')->join(', ') ?: 'Chưa gán') : 'Chưa gán');
        $status = self::statusLabel($user?->employment_status ?: 'active');
        $initials = e(self::initials($user?->name ?: $user?->email ?: 'ND'));
        $avatar = $user?->avatar_path ? e(Storage::url($user->avatar_path)) : null;
        $avatarHtml = $avatar
            ? '<img class="crm-profile-avatar-img" src="'.$avatar.'" alt="'.$name.'">'
            : '<div class="crm-profile-avatar-initials">'.$initials.'</div>';

        return new HtmlString(<<<HTML
<div class="crm-user-record-header crm-profile-summary">
    <div class="crm-profile-avatar">
        {$avatarHtml}
    </div>
    <div class="crm-profile-main">
        <div class="crm-profile-name">{$name}</div>
        <div class="crm-profile-email">{$email}</div>
        <div class="crm-profile-tags">
            <span>UID {$uid}</span>
            <span>Mã NV {$employeeCode}</span>
            <span>Trạng thái: {$status}</span>
            <span>Vai trò: {$roles}</span>
            <span>Phòng ban: {$department}</span>
        </div>
    </div>
</div>
HTML);
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'Đang làm',
            'probation' => 'Thử việc',
            'inactive' => 'Tạm khóa',
            'resigned' => 'Đã nghỉ',
            default => 'Chưa cập nhật',
        };
    }

    private static function initials(string $value): string
    {
        $words = collect(preg_split('/\s+/u', trim($value)) ?: [])
            ->filter()
            ->values();

        if ($words->isEmpty()) {
            return 'ND';
        }

        if ($words->count() === 1) {
            return mb_strtoupper(mb_substr($words->first(), 0, 2));
        }

        return mb_strtoupper(mb_substr($words->first(), 0, 1).mb_substr($words->last(), 0, 1));
    }
}
