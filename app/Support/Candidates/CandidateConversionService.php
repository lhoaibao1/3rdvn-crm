<?php

namespace App\Support\Candidates;

use App\Models\CandidateApplication;
use App\Models\User;
use App\Support\RoleHierarchy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateConversionService
{
    public function convert(CandidateApplication $candidate, array $data, User $actor): User
    {
        abort_unless(CandidateWorkflow::canIssueCode($candidate, $actor), 403);

        if ($candidate->converted_user_id) {
            throw ValidationException::withMessages([
                'email' => 'Ứng viên này đã được cấp mã nhân sự.',
            ]);
        }

        if ($candidate->status !== CandidateApplication::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'email' => 'Hồ sơ phải được Admin/ZD phê duyệt tuyển dụng trước khi cấp mã.',
            ]);
        }

        if (User::query()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email này đã tồn tại trong Người dùng.',
            ]);
        }

        if (filled($data['phone'] ?? null) && User::query()->where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'Số điện thoại này đã tồn tại trong Người dùng.',
            ]);
        }

        if (! RoleHierarchy::canAssignRole($actor, $data['role'] ?? null)) {
            throw ValidationException::withMessages([
                'role' => 'Vai trò không hợp lệ.',
            ]);
        }

        $managerData = RoleHierarchy::normalizeManagerFields([
            'zd_id' => $data['zd_id'] ?? null,
            'am_id' => $data['am_id'] ?? null,
            'team_leader_id' => $data['team_leader_id'] ?? null,
        ], $actor, $data['role']);

        RoleHierarchy::validateManagerFields($managerData, $actor, $data['role']);

        return DB::transaction(function () use ($candidate, $data, $managerData, $actor): User {
            $candidate = CandidateApplication::query()
                ->lockForUpdate()
                ->findOrFail($candidate->getKey());

            if ($candidate->converted_user_id || $candidate->status !== CandidateApplication::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'email' => 'Hồ sơ đã thay đổi trạng thái. Vui lòng tải lại trước khi cấp mã.',
                ]);
            }

            $user = User::create([
                'name' => $candidate->full_name,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? $candidate->phone,
                'password' => $data['password'],
                'date_of_birth' => $candidate->date_of_birth,
                'gender' => $candidate->gender,
                'position' => $data['position'] ?? $candidate->applied_position,
                'department' => $data['department'] ?? null,
                'employment_status' => User::STATUS_ACTIVE,
                'hire_date' => $data['hire_date'] ?? now()->toDateString(),
                'office' => $data['office'] ?? null,
                'contract_type' => $data['contract_type'] ?? null,
                'address_line' => $candidate->address_line,
                'province_code' => $candidate->province_code,
                'province_name' => $candidate->province_name,
                'district_code' => $candidate->district_code,
                'district_name' => $candidate->district_name,
                'ward_code' => $candidate->ward_code,
                'ward_name' => $candidate->ward_name,
                'created_by_id' => $actor->getKey(),
                ...Arr::only($managerData, ['zd_id', 'am_id', 'team_leader_id']),
            ]);

            $user->syncRoles([$data['role']]);

            $candidate->forceFill([
                'status' => CandidateApplication::STATUS_CONVERTED,
                'reviewed_by_id' => $actor->getKey(),
                'reviewed_at' => now(),
                'converted_user_id' => $user->getKey(),
                'converted_at' => now(),
            ])->save();

            return $user->refresh();
        });
    }
}
