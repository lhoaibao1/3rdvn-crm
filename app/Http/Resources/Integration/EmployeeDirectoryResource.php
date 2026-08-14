<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDirectoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'uid' => $this->uid,
            'employee_code' => $this->employee_code,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'document_type' => $this->document_type,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'identity_number' => $this->identity_number,
            'identity_issued_date' => $this->identity_issued_date?->toDateString(),
            'identity_issued_place' => $this->identity_issued_place,
            'department' => $this->department,
            'position' => $this->position,
            'employment_status' => $this->employment_status ?: 'active',
            'hire_date' => $this->hire_date?->toDateString(),
            'office' => $this->office,
            'contract_type' => $this->contract_type,
            'sales_projects' => $this->sales_projects ?: [],
            'sales_codes' => $this->sales_codes ?: [],
            'sales_channel' => $this->sales_channel,
            'company_name' => $this->company_name,
            'branch_name' => $this->branch_name,
            'branch_code' => $this->branch_code,
            'team' => $this->team ? ['id' => $this->team->id, 'name' => $this->team->name] : null,
            'team_leader' => $this->person($this->teamLeader),
            'courier_manager' => $this->person($this->courierManager),
            'am' => $this->person($this->am),
            'zd' => $this->person($this->zd),
            'created_by' => $this->person($this->creator),
            'address_line' => $this->address_line,
            'province_code' => $this->province_code,
            'province_name' => $this->province_name,
            'district_code' => $this->district_code,
            'district_name' => $this->district_name,
            'ward_code' => $this->ward_code,
            'ward_name' => $this->ward_name,
            'bank_code' => $this->bank_code,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
            'bank_branch' => $this->bank_branch,
            'tax_code' => $this->tax_code,
            'social_insurance_number' => $this->social_insurance_number,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'mail_address' => $this->mail_address,
            'mail_status' => $this->mail_status,
            'mail_quota_mb' => $this->mail_quota_mb,
            'mail_provisioned_at' => $this->mail_provisioned_at?->toIso8601String(),
            'roles' => $this->roles->pluck('name')->values()->all(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function person(mixed $user): ?array
    {
        return $user ? [
            'id' => (string) $user->id,
            'uid' => $user->uid,
            'employee_code' => $user->employee_code,
            'name' => $user->name,
        ] : null;
    }
}
