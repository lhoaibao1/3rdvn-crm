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

        $payload['fields'] = Arr::get($leadPayload, 'fields', Arr::get($existingPayload, 'fields', []));
        $payload['review'] = Arr::get($leadPayload, 'review', Arr::get($existingPayload, 'review', []));
        $payload['module_fields'] = Arr::get($existingPayload, 'module_fields', Arr::get($leadPayload, 'module_fields', []));

        return $payload;
    }
}
