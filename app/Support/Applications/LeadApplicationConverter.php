<?php

namespace App\Support\Applications;

use App\Models\Application;
use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Assignments\RecordAssignment;
use App\Support\SalesLineSnapshot;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeadApplicationConverter
{
    public static function convert(Lead $lead, User $actor, ?string $applicationCode = null): Application
    {
        return DB::transaction(function () use ($lead, $actor, $applicationCode): Application {
            $lead = Lead::query()
                ->lockForUpdate()
                ->with(['salesProject.crmModule', 'application'])
                ->findOrFail($lead->getKey());

            if ($lead->application instanceof Application) {
                self::syncApplicationFromLead($lead->application, $lead);

                return $lead->application;
            }

            if (blank($applicationCode)) {
                throw ValidationException::withMessages([
                    'application_code' => 'Vui lòng nhập mã hồ sơ trước khi chuyển Application.',
                ]);
            }

            if (in_array($lead->status, ['Từ chối', 'Khách hàng bị trùng'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Lead đã đóng nên không được chuyển sang Application.',
                ]);
            }

            if ($lead->status !== 'Khách hàng thoả mãn điều kiện') {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lead có trạng thái Khách hàng thoả mãn điều kiện mới được chuyển Application.',
                ]);
            }

            $project = $lead->salesProject;

            if (! $project instanceof SalesProject || ! $project->is_active || $project->crmModule?->slug !== 'applications') {
                throw ValidationException::withMessages([
                    'sales_project_id' => 'Lead này chưa thuộc dự án Application đang hoạt động.',
                ]);
            }

            $payload = self::applicationPayloadFromLead($lead);
            $applicantName = LeadPayload::primaryName($payload, $lead->lead_name);
            $phone = LeadPayload::phone($payload, $lead->phone);

            if (blank($applicantName) || blank($phone)) {
                throw ValidationException::withMessages([
                    'lead_name' => 'Lead cần có tên khách hàng và số điện thoại trước khi chuyển Application.',
                ]);
            }

            $assignee = $lead->assignedSale ?: RecordAssignment::autoAssigneeForProject($project, $actor) ?: $actor;

            $application = Application::query()->create([
                'sales_project_id' => $project->getKey(),
                'lead_id' => $lead->getKey(),
                'application_code' => $applicationCode,
                'applicant_name' => $applicantName,
                'phone' => $phone,
                'identity_number' => LeadPayload::identityNumber($payload),
                'status' => 'processing',
                ...SalesLineSnapshot::fromLeadLike($lead),
                ...RecordAssignment::leadLikeAssignmentAttributes($assignee),
                'assigned_sale_id' => $assignee->getKey(),
                'payload' => $payload,
                'note' => $lead->note,
            ]);

            $lead->forceFill([
                'converted_at' => now(),
                'converted_by_id' => $actor->getKey(),
            ])->save();

            return $application;
        });
    }

    public static function syncApplicationFromLead(Application $application, Lead $lead): void
    {
        $payload = self::applicationPayloadFromLead($lead, $application->payload);
        $assignee = $lead->assignedSale ?: $application->assignedSale;

        $application->forceFill([
            'applicant_name' => LeadPayload::primaryName($payload, $lead->lead_name) ?: $application->applicant_name,
            'phone' => LeadPayload::phone($payload, $lead->phone),
            'identity_number' => LeadPayload::identityNumber($payload, $application->identity_number),
            ...SalesLineSnapshot::fromLeadLike($lead),
            ...($assignee ? RecordAssignment::leadLikeAssignmentAttributes($assignee) : []),
            'assigned_sale_id' => $assignee?->getKey() ?: $application->assigned_sale_id,
            'payload' => $payload,
            'note' => $lead->note,
        ])->save();
    }

    private static function applicationPayloadFromLead(Lead $lead, mixed $existingPayload = []): array
    {
        $leadPayload = is_array($lead->payload) ? $lead->payload : [];
        $existingPayload = is_array($existingPayload) ? $existingPayload : [];
        $payload = array_replace_recursive($existingPayload, $leadPayload);
        $fields = Arr::get($leadPayload, 'fields', Arr::get($existingPayload, 'fields', []));
        $review = Arr::get($leadPayload, 'review', Arr::get($existingPayload, 'review', []));

        $payload['fields'] = is_array($fields) ? $fields : [];
        $payload['review'] = is_array($review) ? $review : [];
        $payload['module_fields'] = Arr::get($existingPayload, 'module_fields', Arr::get($leadPayload, 'module_fields', []));
        $payload['module_fields'] = is_array($payload['module_fields']) ? $payload['module_fields'] : [];

        $payload['source_lead'] = Arr::get($existingPayload, 'source_lead', [
            'id' => $lead->getKey(),
            'lead_code' => $lead->lead_code,
            'lead_name' => $lead->lead_name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'source' => $lead->source,
            'sales_project_id' => $lead->sales_project_id,
            'created_by_id' => $lead->created_by_id,
            'assigned_sale_id' => $lead->assigned_sale_id,
            'team_id' => $lead->team_id,
            'team_leader_id' => $lead->team_leader_id,
            'am_id' => $lead->am_id,
            'zd_id' => $lead->zd_id,
            'status' => $lead->status,
            'note' => $lead->note,
            'fields' => $payload['fields'],
            'review' => $payload['review'],
            'created_at' => $lead->created_at?->toIso8601String(),
            'updated_at' => $lead->updated_at?->toIso8601String(),
            'captured_at' => now()->toIso8601String(),
        ]);

        if ($lead->salesProject?->slug === 'acl-mix') {
            $payload = self::seedAclMixModuleFields($lead, $payload);
        }

        return $payload;
    }

    private static function seedAclMixModuleFields(Lead $lead, array $payload): array
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $moduleFields = is_array($payload['module_fields'] ?? null) ? $payload['module_fields'] : [];
        $customerName = self::firstFilled([$fields['customer_name'] ?? null, $fields['lead_name'] ?? null, $lead->lead_name]);
        $seed = [
            'customer_name' => $customerName,
            'cccd' => self::firstFilled([$fields['cccd'] ?? null, $fields['identity_number'] ?? null]),
            'cmnd' => self::firstFilled([$fields['cmnd'] ?? null]),
            'date_of_birth' => self::firstFilled([$fields['date_of_birth'] ?? null, $fields['birthday'] ?? null]),
            'identity_issued_date' => self::firstFilled([$fields['identity_issued_date'] ?? null, $fields['identity_issue_date'] ?? null]),
            'identity_issued_place' => self::firstFilled([$fields['identity_issued_place'] ?? null, $fields['identity_issue_place'] ?? null]),
            'identity_expiry_date' => self::firstFilled([$fields['identity_expiry_date'] ?? null]),
            'phone' => self::firstFilled([$fields['phone'] ?? null, $lead->phone]),
            'bank_account_name' => filled($customerName) ? Str::upper(Str::ascii((string) $customerName)) : null,
            'current_province_code' => self::firstFilled([$fields['current_province_code'] ?? null, $fields['province_code'] ?? null]),
            'current_province_name' => self::firstFilled([$fields['current_province_name'] ?? null, $fields['province_name'] ?? null]),
            'current_district_code' => self::firstFilled([$fields['current_district_code'] ?? null, $fields['district_code'] ?? null]),
            'current_district_name' => self::firstFilled([$fields['current_district_name'] ?? null, $fields['district_name'] ?? null]),
            'current_ward_code' => self::firstFilled([$fields['current_ward_code'] ?? null, $fields['ward_code'] ?? null]),
            'current_ward_name' => self::firstFilled([$fields['current_ward_name'] ?? null, $fields['ward_name'] ?? null]),
            'current_address_line' => self::firstFilled([$fields['current_address_line'] ?? null, $fields['current_address'] ?? null, $fields['address_line'] ?? null, $fields['address'] ?? null]),
            'permanent_province_code' => self::firstFilled([$fields['permanent_province_code'] ?? null]),
            'permanent_province_name' => self::firstFilled([$fields['permanent_province_name'] ?? null]),
            'permanent_district_code' => self::firstFilled([$fields['permanent_district_code'] ?? null]),
            'permanent_district_name' => self::firstFilled([$fields['permanent_district_name'] ?? null]),
            'permanent_ward_code' => self::firstFilled([$fields['permanent_ward_code'] ?? null]),
            'permanent_ward_name' => self::firstFilled([$fields['permanent_ward_name'] ?? null]),
            'permanent_address_line' => self::firstFilled([$fields['permanent_address_line'] ?? null, $fields['permanent_address'] ?? null]),
        ];

        foreach ($seed as $key => $value) {
            if (! array_key_exists($key, $moduleFields) && filled($value)) {
                $moduleFields[$key] = $value;
            }
        }

        $payload['module_fields'] = $moduleFields;

        return $payload;
    }

    private static function firstFilled(array $values): mixed
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
