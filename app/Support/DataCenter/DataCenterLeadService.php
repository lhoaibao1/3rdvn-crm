<?php

namespace App\Support\DataCenter;

use App\Models\DataCenterConversion;
use App\Models\DataCenterLead;
use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Assignments\RecordAssignment;
use App\Support\Notifications\DataCenterNotificationSender;
use App\Support\Permissions\DataCenterAccess;
use App\Support\Permissions\SalesProjectAccess;
use App\Support\SalesLineSnapshot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataCenterLeadService
{
    public static function create(array $data, User $actor, User $assignee, bool $notify = true): DataCenterLead
    {
        if (! DataCenterAccess::canAssignUser($actor, $assignee)) {
            throw ValidationException::withMessages(['assigned_user_id' => 'Người xử lý không thuộc phạm vi quản lý của bạn.']);
        }

        $snapshot = SalesLineSnapshot::fromUser($assignee);

        $record = DataCenterLead::query()->create([
            'customer_name' => trim((string) ($data['customer_name'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'identity_number' => filled($data['identity_number'] ?? null) ? trim((string) $data['identity_number']) : null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'address' => $data['address'] ?? null,
            'province_code' => $data['province_code'] ?? null,
            'province_name' => $data['province_name'] ?? null,
            'district_code' => $data['district_code'] ?? null,
            'district_name' => $data['district_name'] ?? null,
            'ward_code' => $data['ward_code'] ?? null,
            'ward_name' => $data['ward_name'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => DataCenterStatus::PENDING,
            'assigned_user_id' => $assignee->getKey(),
            'created_by_id' => $actor->getKey(),
            'team_id' => $snapshot['team_id'],
            'team_leader_id' => $snapshot['team_leader_id'],
            'am_id' => $snapshot['am_id'],
            'zd_id' => $snapshot['zd_id'],
            'payload' => $data['payload'] ?? null,
        ]);

        if ($notify) {
            DataCenterNotificationSender::created($record);
        }

        return $record;
    }

    public static function assign(DataCenterLead $record, User $actor, User $assignee, bool $notify = true): DataCenterLead
    {
        if (! DataCenterAccess::canDistribute($actor) || ! DataCenterAccess::canView($actor, $record)) {
            abort(403);
        }

        if (! DataCenterAccess::canAssignUser($actor, $assignee)) {
            throw ValidationException::withMessages(['assigned_user_id' => 'Người xử lý không thuộc phạm vi quản lý của bạn.']);
        }

        $snapshot = SalesLineSnapshot::fromUser($assignee);

        $record->forceFill([
            'assigned_user_id' => $assignee->getKey(),
            'team_id' => $snapshot['team_id'],
            'team_leader_id' => $snapshot['team_leader_id'],
            'am_id' => $snapshot['am_id'],
            'zd_id' => $snapshot['zd_id'],
        ])->save();

        if ($notify) {
            DataCenterNotificationSender::assigned($record->refresh());
        }

        return $record;
    }

    public static function unassign(DataCenterLead $record, User $actor): DataCenterLead
    {
        if (! DataCenterAccess::canDistribute($actor) || ! DataCenterAccess::canView($actor, $record)) {
            abort(403);
        }

        $record->forceFill([
            'assigned_user_id' => null,
            'team_id' => null,
            'team_leader_id' => null,
            'am_id' => null,
            'zd_id' => null,
        ])->save();

        return $record->refresh();
    }

    public static function assignMany(Collection $records, User $actor, ?User $assignee): int
    {
        $count = DB::transaction(function () use ($records, $actor, $assignee): int {
            $count = 0;

            foreach ($records as $record) {
                if (! $record instanceof DataCenterLead) {
                    continue;
                }

                if ($assignee instanceof User) {
                    self::assign($record, $actor, $assignee, notify: false);
                } else {
                    self::unassign($record, $actor);
                }

                $count++;
            }

            return $count;
        });

        if ($assignee instanceof User && $count > 0) {
            DataCenterNotificationSender::imported($assignee, $actor, $count);
        }

        return $count;
    }

    public static function updateResult(DataCenterLead $record, User $actor, array $data): DataCenterLead
    {
        if (! DataCenterAccess::canUpdateResult($actor, $record)) {
            abort(403);
        }

        $status = (string) ($data['status'] ?? '');

        if (! array_key_exists($status, DataCenterStatus::resultOptions())) {
            throw ValidationException::withMessages(['status' => 'Trạng thái xử lý không hợp lệ.']);
        }

        $record->forceFill([
            'status' => $status,
            'call_note' => trim((string) ($data['call_note'] ?? '')) ?: null,
            'contacted_at' => now(),
        ])->save();

        DataCenterNotificationSender::resultUpdated($record->refresh());

        return $record;
    }

    public static function convert(DataCenterLead $record, User $actor, array $projectIds): array
    {
        return DB::transaction(function () use ($record, $actor, $projectIds): array {
            $record = DataCenterLead::query()
                ->lockForUpdate()
                ->with(['assignedUser', 'conversions'])
                ->findOrFail($record->getKey());

            if (! DataCenterAccess::canConvert($actor, $record)) {
                throw ValidationException::withMessages([
                    'sales_project_ids' => 'Data phải đủ điều kiện và được phân cho bạn trước khi chuyển dự án.',
                ]);
            }

            $remaining = 2 - $record->conversions->count();
            $projectIds = collect($projectIds)
                ->map(fn (mixed $id): int => (int) $id)
                ->filter()
                ->unique()
                ->take($remaining)
                ->values();

            if ($projectIds->isEmpty()) {
                throw ValidationException::withMessages(['sales_project_ids' => 'Vui lòng chọn ít nhất một dự án.']);
            }

            $existingProjectIds = $record->conversions->pluck('sales_project_id');
            $projects = SalesProject::query()
                ->with('crmModule')
                ->whereIn('id', $projectIds)
                ->where('is_active', true)
                ->get()
                ->filter(fn (SalesProject $project): bool => $project->crmModule?->slug === 'applications'
                    && SalesProjectAccess::canAccessProject($actor, $project)
                    && ! $existingProjectIds->contains($project->getKey()));

            if ($projects->count() !== $projectIds->count()) {
                throw ValidationException::withMessages([
                    'sales_project_ids' => 'Có dự án không hợp lệ, đã chuyển trước đó hoặc bạn chưa được cấp quyền.',
                ]);
            }

            $assignee = $record->assignedUser ?: $actor;
            $created = [];

            foreach ($projects as $project) {
                $payload = [
                    'source_data_center' => [
                        'id' => $record->getKey(),
                        'code' => $record->referral_code,
                        'source' => $record->source,
                        'created_by_id' => $record->created_by_id,
                    ],
                    'fields' => [
                        'customer_name' => $record->customer_name,
                        'phone' => $record->phone,
                        'email' => $record->email,
                        'identity_number' => $record->identity_number,
                        'date_of_birth' => $record->date_of_birth?->format('d/m/Y'),
                        'address' => $record->address,
                        'province_code' => $record->province_code,
                        'province_name' => $record->province_name,
                        'district_code' => $record->district_code,
                        'district_name' => $record->district_name,
                        'ward_code' => $record->ward_code,
                        'ward_name' => $record->ward_name,
                    ],
                ];

                $lead = Lead::query()->create([
                    'sales_project_id' => $project->getKey(),
                    'lead_name' => $record->customer_name,
                    'phone' => $record->phone,
                    'email' => $record->email,
                    'source' => 'Lead Referral',
                    'status' => 'Chờ xử lý',
                    'note' => $record->call_note,
                    'payload' => $payload,
                    ...SalesLineSnapshot::fromUser($assignee),
                    ...RecordAssignment::leadLikeAssignmentAttributes($assignee),
                    'created_by_id' => $actor->getKey(),
                ]);

                DataCenterConversion::query()->create([
                    'data_center_lead_id' => $record->getKey(),
                    'sales_project_id' => $project->getKey(),
                    'lead_id' => $lead->getKey(),
                    'converted_by_id' => $actor->getKey(),
                    'converted_at' => now(),
                ]);

                $created[] = $lead;
            }

            $count = $record->conversions()->count();
            $record->forceFill([
                'status' => $count >= 2 ? DataCenterStatus::CONVERTED : DataCenterStatus::CONVERTED_ONCE,
            ])->save();

            DataCenterNotificationSender::converted($record->refresh(), $count);

            return $created;
        });
    }

    public static function projectOptions(User $actor, DataCenterLead $record): array
    {
        $used = $record->conversions()->pluck('sales_project_id');

        return SalesProject::query()
            ->with('crmModule')
            ->where('is_active', true)
            ->whereNotIn('id', $used)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (SalesProject $project): bool => $project->crmModule?->slug === 'applications'
                && SalesProjectAccess::canAccessProject($actor, $project))
            ->mapWithKeys(fn (SalesProject $project): array => [$project->getKey() => $project->name])
            ->all();
    }
}
