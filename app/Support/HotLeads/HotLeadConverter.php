<?php

namespace App\Support\HotLeads;

use App\Models\Application;
use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\LeadPayload;
use App\Support\Assignments\RecordAssignment;
use App\Support\Permissions\SalesProjectAccess;
use App\Support\SalesLineSnapshot;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HotLeadConverter
{
    public const STAGE_HOT_LEAD = 'hot_lead';

    public const STAGE_LEAD = 'lead';

    public const STAGE_PROJECT = 'project';

    public static function isPromotedToLead(Lead $lead): bool
    {
        return $lead->salesProject?->slug === 'hot-lead'
            && data_get($lead->payload, 'workflow.stage') === self::STAGE_LEAD;
    }

    public static function promoteToLead(Lead $lead, User $actor, User $assignee): Lead
    {
        return DB::transaction(function () use ($lead, $actor, $assignee): Lead {
            $lead = Lead::query()
                ->lockForUpdate()
                ->with('salesProject')
                ->findOrFail($lead->getKey());

            if ($lead->salesProject?->slug !== 'hot-lead') {
                throw ValidationException::withMessages([
                    'assignee_id' => 'Bản ghi này không thuộc module Hot Lead.',
                ]);
            }

            $payload = is_array($lead->payload) ? $lead->payload : [];
            $payload['source_hot_lead'] = array_replace($payload['source_hot_lead'] ?? [], [
                'id' => $lead->getKey(),
                'code' => $lead->lead_code,
                'project' => $lead->salesProject?->name,
            ]);
            $payload['workflow'] = array_replace($payload['workflow'] ?? [], [
                'stage' => self::STAGE_LEAD,
                'promoted_at' => now()->toDateTimeString(),
                'promoted_by_id' => $actor->getKey(),
                'assigned_user_id' => $assignee?->getKey(),
            ]);

            $lead->forceFill([
                ...($assignee ? RecordAssignment::leadLikeAssignmentAttributes($assignee) : []),
                'status' => HotLeadStatus::PENDING_PROCESSING,
                'payload' => $payload,
            ])->save();

            return $lead->refresh()->load(['salesProject', 'assignedSale']);
        });
    }

    public static function moveToProject(Lead $lead, User $actor, int|string|null $projectId): Lead
    {
        return DB::transaction(function () use ($lead, $actor, $projectId): Lead {
            $lead = Lead::query()
                ->lockForUpdate()
                ->with('salesProject')
                ->findOrFail($lead->getKey());

            if (! self::isPromotedToLead($lead)) {
                return $lead;
            }

            $targetProject = SalesProject::query()
                ->with('crmModule')
                ->whereKey((int) $projectId)
                ->where('is_active', true)
                ->first();

            if (
                ! $targetProject instanceof SalesProject
                || $targetProject->crmModule?->slug !== 'applications'
                || ! SalesProjectAccess::canAccessProject($actor, $targetProject)
            ) {
                throw ValidationException::withMessages([
                    'target_sales_project_id' => 'Vui lòng chọn dự án bạn được phép xử lý.',
                ]);
            }

            $payload = is_array($lead->payload) ? $lead->payload : [];
            $payload['workflow'] = array_replace($payload['workflow'] ?? [], [
                'stage' => self::STAGE_PROJECT,
                'target_sales_project_id' => $targetProject->getKey(),
                'target_selected_at' => now()->toDateTimeString(),
                'target_selected_by_id' => $actor->getKey(),
            ]);

            $lead->forceFill([
                'sales_project_id' => $targetProject->getKey(),
                'payload' => $payload,
            ])->save();

            return $lead->refresh()->load(['salesProject.crmModule', 'assignedSale']);
        });
    }

    public static function process(Lead $lead, User $actor, array $data): ?Application
    {
        return DB::transaction(function () use ($lead, $actor, $data): ?Application {
            $lead = Lead::query()
                ->lockForUpdate()
                ->with(['salesProject', 'application', 'assignedSale'])
                ->findOrFail($lead->getKey());

            $decision = $data['decision'] ?? null;
            $targetProjectId = (int) ($data['target_sales_project_id'] ?? 0);
            $applicationCode = trim((string) ($data['application_code'] ?? ''));
            $note = trim((string) ($data['decision_note'] ?? ''));
            $payload = is_array($lead->payload) ? $lead->payload : [];
            $payload['review'] = array_replace($payload['review'] ?? [], [
                'decision' => $decision,
                'target_sales_project_id' => $targetProjectId ?: null,
                'application_code' => $applicationCode ?: null,
                'decision_note' => $note,
                'processed_by_id' => $actor->getKey(),
                'processed_at' => now()->toDateTimeString(),
            ]);

            if ($decision !== 'qualified') {
                $lead->forceFill([
                    'status' => 'Từ chối',
                    'note' => $note ?: $lead->note,
                    'payload' => $payload,
                ])->save();

                return null;
            }

            if ($lead->application instanceof Application) {
                return $lead->application;
            }

            $targetProject = SalesProject::query()
                ->with('crmModule')
                ->whereKey($targetProjectId)
                ->where('is_active', true)
                ->first();

            if (! $targetProject instanceof SalesProject || $targetProject->crmModule?->slug !== 'applications' || ! SalesProjectAccess::canAccessProject($actor, $targetProject)) {
                throw ValidationException::withMessages([
                    'target_sales_project_id' => 'Vui lòng chọn dự án xử lý hợp lệ.',
                ]);
            }

            if ($applicationCode === '') {
                throw ValidationException::withMessages([
                    'application_code' => 'Vui lòng nhập mã hồ sơ/Application.',
                ]);
            }

            if (Application::query()->where('application_code', $applicationCode)->exists()) {
                throw ValidationException::withMessages([
                    'application_code' => 'Mã hồ sơ/Application đã tồn tại.',
                ]);
            }

            $name = LeadPayload::primaryName($payload, $lead->lead_name);
            $phone = LeadPayload::phone($payload, $lead->phone);

            if (blank($name) || blank($phone)) {
                throw ValidationException::withMessages([
                    'decision' => 'Hot Lead cần có họ tên và số điện thoại trước khi chuyển sang dự án.',
                ]);
            }

            $assignee = $lead->assignedSale;

            if (! $assignee instanceof User || ! RecordAssignment::isEligibleForProject($assignee, $targetProject)) {
                $assignee = RecordAssignment::autoAssigneeForProject($targetProject);
            }
            $payload['source_hot_lead'] = [
                'id' => $lead->getKey(),
                'code' => $lead->lead_code,
                'project' => $lead->salesProject?->name,
            ];

            $application = Application::query()->create([
                'sales_project_id' => $targetProject->getKey(),
                'lead_id' => $lead->getKey(),
                'application_code' => $applicationCode,
                'applicant_name' => $name,
                'phone' => $phone,
                'identity_number' => LeadPayload::identityNumber($payload),
                'status' => 'processing',
                ...SalesLineSnapshot::fromLeadLike($lead),
                ...($assignee ? RecordAssignment::leadLikeAssignmentAttributes($assignee) : []),
                'assigned_sale_id' => $assignee?->getKey(),
                'payload' => $payload,
                'note' => $note ?: $lead->note,
            ]);

            $lead->forceFill([
                'status' => 'Khách hàng thoả mãn điều kiện',
                'note' => $note ?: $lead->note,
                'payload' => $payload,
                'converted_at' => now(),
                'converted_by_id' => $actor->getKey(),
            ])->save();

            return $application->load('salesProject');
        });
    }

    private static function addressFromPayload(array $payload): ?string
    {
        $fields = Arr::get($payload, 'fields', []);
        $parts = array_filter([
            $fields['address'] ?? null,
            $fields['ward_name'] ?? null,
            $fields['district_name'] ?? null,
            $fields['province_name'] ?? null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
