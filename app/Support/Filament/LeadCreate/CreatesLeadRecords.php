<?php

namespace App\Support\Filament\LeadCreate;

use App\Models\Lead;
use App\Models\SalesProject;
use App\Support\Applications\LeadPayload;
use App\Support\Assignments\RecordAssignment;
use App\Support\Permissions\LeadAccess;
use App\Support\SalesLineSnapshot;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait CreatesLeadRecords
{
    protected static function canCreateForProject(string $projectSlug): bool
    {
        $project = SalesProject::query()
            ->where('slug', $projectSlug)
            ->where('is_active', true)
            ->first();

        return $project instanceof SalesProject
            && LeadAccess::canUseProjectId(auth()->user(), $project->getKey());
    }

    /** @param array<int, string> $fieldKeys */
    protected static function createLeadForProject(array $data, string $projectSlug, array $fieldKeys, mixed $livewire, bool $showNd13Consent = false): void
    {
        $project = SalesProject::query()
            ->where('slug', $projectSlug)
            ->where('is_active', true)
            ->first();

        if (! $project instanceof SalesProject || ! LeadAccess::canUseProjectId(auth()->user(), $project->getKey())) {
            throw ValidationException::withMessages([
                'sales_project_id' => 'Bạn chưa được phân quyền tạo Lead cho dự án này.',
            ]);
        }

        $leadData = self::normalizeLeadData($data, $project, $fieldKeys);
        $leadData = array_replace($leadData, SalesLineSnapshot::fromUser(auth()->user()));
        $assignee = RecordAssignment::autoAssigneeForProject($project, auth()->user());

        if ($assignee) {
            $leadData = array_replace($leadData, RecordAssignment::leadLikeAssignmentAttributes($assignee));
        }

        $leadData['created_by_id'] = auth()->id();

        $lead = Lead::query()->create($leadData);

        if ($showNd13Consent) {
            self::dispatchNd13ConsentPopup($livewire, $lead);
        }

        Notification::make()
            ->title('Đã tạo Lead')
            ->body('Lead '.$lead->lead_code.' đã được gửi kiểm tra.')
            ->success()
            ->send();
    }

    /** @param array<int, string> $fieldKeys */
    private static function normalizeLeadData(array $data, SalesProject $project, array $fieldKeys): array
    {
        $fields = [];

        foreach ($fieldKeys as $key) {
            if (array_key_exists($key, $data)) {
                $fields[$key] = $data[$key];
            }
        }

        $payload = ['fields' => $fields];

        return self::syncPayloadToLeadColumns([
            'sales_project_id' => $project->getKey(),
            'payload' => $payload,
        ], $project);
    }

    private static function syncPayloadToLeadColumns(array $data, SalesProject $project): array
    {
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        $data['lead_name'] = LeadPayload::primaryName($payload, $data['lead_name'] ?? null)
            ?: LeadPayload::firstFilledValue($payload)
            ?: 'Lead '.now()->format('d/m/Y H:i');
        $data['phone'] = LeadPayload::phone($payload, $data['phone'] ?? null);
        $data['email'] = LeadPayload::email($payload, $data['email'] ?? null);
        $data['source'] = $project->name;
        $data['status'] = 'Chờ kiểm tra';

        return $data;
    }

    protected static function dispatchNd13ConsentPopup(mixed $livewire, Lead $lead): void
    {
        $identityNumber = preg_replace('/\D+/', '', (string) data_get($lead->payload, 'fields.identity_number'));
        $suffix = strlen($identityNumber) >= 6 ? substr($identityNumber, -6) : $identityNumber;
        $suffix = $suffix !== '' ? $suffix : 'xxxxxx';
        $message = 'SF '.$suffix.' Toi da doc hieu ro va tu nguyen dong y Chinh sach Du lieu ca nhan hien hanh cua SHBFinance va dong y nhan cuoc goi, sms, email quang cao den 20h.';

        if (method_exists($livewire, 'dispatch')) {
            $livewire->dispatch(
                'crm-nd13-consent',
                title: 'Thông báo đồng ý NĐ13',
                leadCode: $lead->lead_code,
                suffix: $suffix,
                message: $message,
            );
        }
    }
}
