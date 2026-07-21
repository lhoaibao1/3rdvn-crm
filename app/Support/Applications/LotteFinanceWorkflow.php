<?php

namespace App\Support\Applications;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Assignments\RecordAssignment;
use App\Support\LotteFinanceDocuments;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use App\Support\SalesLineSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotteFinanceWorkflow
{
    public const PRE_CHECK = 'lotte_pre_check';

    public const SALE_COMPLETION = 'lotte_sale_completion';

    public const UW_CALL = 'lotte_uw_call';

    public const UW_APPROVAL = 'lotte_uw_approval';

    public const UW_FIELD = 'lotte_uw_field';

    public const OP = 'lotte_op';

    public const ESIGN = 'lotte_esign';

    public const POST_APPROVAL = 'lotte_post_approval';

    public const REJECTED = 'lotte_rejected';

    public static function statusOptions(): array
    {
        return [
            self::PRE_CHECK => 'Pre-Check',
            self::SALE_COMPLETION => 'Chờ Sale hoàn thiện thông tin',
            self::UW_CALL => 'UW Call',
            self::UW_APPROVAL => 'UW Approval',
            self::UW_FIELD => 'UW Field',
            self::OP => 'OP',
            self::ESIGN => 'eSign',
            self::POST_APPROVAL => 'Post Approval',
            self::REJECTED => 'Không Pass',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::statusOptions()[$status] ?? ($status ?: '-');
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            self::PRE_CHECK => 'warning',
            self::SALE_COMPLETION => 'info',
            self::UW_CALL, self::UW_APPROVAL, self::UW_FIELD, self::OP, self::ESIGN => 'primary',
            self::POST_APPROVAL => 'success',
            self::REJECTED => 'danger',
            default => 'gray',
        };
    }

    public static function canCreate(?User $user): bool
    {
        $project = self::project();

        return $user instanceof User
            && $user->can('application.create')
            && $project instanceof SalesProject
            && SalesProjectAccess::canAccessProject($user, $project);
    }

    public static function create(array $data, User $creator, array $fieldKeys): Application
    {
        $project = self::project();

        if (! self::canCreate($creator) || ! $project instanceof SalesProject) {
            throw ValidationException::withMessages(['scheme_code' => 'Bạn chưa được phân quyền tạo hồ sơ Lotte Finance.']);
        }

        return DB::transaction(function () use ($data, $creator, $fieldKeys, $project): Application {
            $fields = collect($fieldKeys)
                ->filter(fn (string $key): bool => array_key_exists($key, $data))
                ->mapWithKeys(fn (string $key): array => [$key => $data[$key]])
                ->all();
            $moduleFields = array_filter([
                'customer_name' => $fields['customer_name'] ?? null,
                'phone' => $fields['phone'] ?? null,
                'cccd' => $fields['identity_number'] ?? null,
                'date_of_birth' => $fields['birthday'] ?? null,
                'identity_issued_date' => $fields['identity_issue_date'] ?? null,
                'identity_issued_place' => $fields['identity_issue_place'] ?? null,
                'identity_expiry_date' => $fields['identity_expiry_date'] ?? null,
                'current_address_line' => $fields['current_address'] ?? null,
                'permanent_address_line' => $fields['permanent_address'] ?? null,
            ], fn (mixed $value): bool => filled($value));
            $assignee = RecordAssignment::autoAssigneeForProject($project, $creator);
            $snapshot = SalesLineSnapshot::fromUser($creator);
            $snapshot['assigned_sale_id'] = $assignee?->getKey();

            $application = Application::query()->create([
                'sales_project_id' => $project->getKey(),
                'lead_id' => null,
                'application_code' => null,
                'applicant_name' => trim((string) ($fields['customer_name'] ?? '')),
                'phone' => trim((string) ($fields['phone'] ?? '')),
                'identity_number' => trim((string) ($fields['identity_number'] ?? '')),
                'status' => self::PRE_CHECK,
                ...$snapshot,
                'created_by_id' => $creator->getKey(),
                'payload' => [
                    'fields' => $fields,
                    'module_fields' => $moduleFields,
                    'workflow' => ['source' => 'lotte_finance_direct', 'created_at' => now()->toDateTimeString()],
                ],
            ]);

            LotteFinanceDocuments::normalizeUploads($application);

            return $application->refresh();
        });
    }

    public static function canEditData(?User $user, ?Application $application): bool
    {
        if (! $user instanceof User || ! $application instanceof Application || ! self::isLotteFinance($application)) {
            return false;
        }
        if ($user->hasRole('Admin')) {
            return ! in_array($application->status, [self::POST_APPROVAL, self::REJECTED], true);
        }
        if (! $user->can('application.update') || ! self::canView($user, $application) || $application->status !== self::SALE_COMPLETION) {
            return false;
        }

        return (int) $application->created_by_id === (int) $user->getKey()
            || ($user->hasRole('Team Leader') && (int) $application->team_leader_id === (int) $user->getKey())
            || ($user->hasRole('AM') && (int) $application->am_id === (int) $user->getKey())
            || ($user->hasRole('ZD') && (int) $application->zd_id === (int) $user->getKey());
    }

    public static function canProcess(?User $user, ?Application $application): bool
    {
        if (! $user instanceof User || ! $application instanceof Application || ! self::isLotteFinance($application)) {
            return false;
        }
        if (! $user->can('application.update') || ! self::canView($user, $application)) {
            return false;
        }
        if ($application->status === self::PRE_CHECK) {
            return $user->hasRole('Admin');
        }
        if (in_array($application->status, [self::SALE_COMPLETION, self::POST_APPROVAL, self::REJECTED], true)) {
            return false;
        }
        if ($user->hasRole('Admin')) {
            return true;
        }

        $application->loadMissing('assignedSale');

        return (int) $application->assigned_sale_id === (int) $user->getKey()
            || ($user->hasRole('Courier Manager')
                && (int) $application->assignedSale?->courier_manager_id === (int) $user->getKey());
    }

    public static function process(Application $application, User $actor, array $data): Application
    {
        return DB::transaction(function () use ($application, $actor, $data): Application {
            $application = Application::query()->lockForUpdate()->with(['salesProject', 'assignedSale'])->findOrFail($application->getKey());
            if (! self::canProcess($actor, $application)) {
                throw ValidationException::withMessages(['next_status' => 'Bạn không được phép xử lý hồ sơ ở bước hiện tại.']);
            }

            $currentStatus = $application->status;
            $payload = is_array($application->payload) ? $application->payload : [];
            $review = is_array($payload['review'] ?? null) ? $payload['review'] : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];

            if ($currentStatus === self::PRE_CHECK) {
                $passed = ($data['decision'] ?? null) === 'pass';
                $nextStatus = $passed ? self::SALE_COMPLETION : self::REJECTED;
                $applicationCode = trim((string) ($data['application_code'] ?? ''));
                self::validateApplicationCode($application, $applicationCode);
                if ($passed) {
                    foreach (['lf_grade', 'ml_grade', 'maximum_limit', 'estimated_interest_rate'] as $field) {
                        if (blank($data[$field] ?? null)) {
                            throw ValidationException::withMessages([$field => 'Vui lòng nhập đầy đủ kết quả Pre-Check.']);
                        }
                    }
                }

                $checkLabel = $passed ? 'Pass' : 'Không Pass';
                $review = array_replace($review, [
                    'decision' => $checkLabel,
                    'blacklist_check' => $checkLabel,
                    'b11t_check' => $checkLabel,
                    'aml_check' => $checkLabel,
                    'pcb_check' => $checkLabel,
                    'lf_grade' => $passed ? $data['lf_grade'] : 'Không Pass',
                    'ml_grade' => $passed ? $data['ml_grade'] : 'Không Pass',
                    'maximum_limit' => $passed ? self::digits($data['maximum_limit'] ?? null) : null,
                    'estimated_interest_rate' => $passed ? $data['estimated_interest_rate'] : null,
                    'review_note' => $data['processing_note'] ?? null,
                    'reviewed_by_id' => $actor->getKey(),
                    'reviewed_at' => now()->toDateTimeString(),
                ]);
                $application->application_code = $applicationCode;
            } else {
                $nextStatus = (string) ($data['next_status'] ?? '');
                self::validateTransition($currentStatus, $nextStatus);
            }

            $workflow['last_transition'] = [
                'from' => $currentStatus,
                'to' => $nextStatus,
                'actor_id' => $actor->getKey(),
                'at' => now()->toDateTimeString(),
                'note' => $data['processing_note'] ?? null,
            ];
            $payload['review'] = $review;
            $payload['workflow'] = $workflow;
            $application->forceFill([
                'status' => $nextStatus,
                'payload' => $payload,
                'note' => $data['processing_note'] ?? $application->note,
            ])->save();

            return $application->refresh();
        });
    }

    public static function submitSaleInformation(Application $application, User $actor): Application
    {
        return DB::transaction(function () use ($application, $actor): Application {
            $application = Application::query()->lockForUpdate()->with(['salesProject', 'assignedSale'])->findOrFail($application->getKey());
            if ($application->status !== self::SALE_COMPLETION || ! self::canEditData($actor, $application)) {
                throw ValidationException::withMessages(['status' => 'Bạn không được phép hoàn thiện hồ sơ này.']);
            }

            LotteFinanceDocuments::normalizeUploads($application);
            $application->refresh();
            $payload = is_array($application->payload) ? $application->payload : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];
            $workflow['last_transition'] = [
                'from' => self::SALE_COMPLETION,
                'to' => self::UW_CALL,
                'actor_id' => $actor->getKey(),
                'at' => now()->toDateTimeString(),
                'note' => 'Sale đã hoàn thiện thông tin và chứng từ.',
            ];
            $payload['workflow'] = $workflow;
            $application->forceFill(['status' => self::UW_CALL, 'payload' => $payload])->save();

            return $application->refresh();
        });
    }

    public static function isLotteFinance(?Application $application): bool
    {
        if (! $application instanceof Application) {
            return false;
        }
        $application->loadMissing('salesProject:id,slug');

        return $application->salesProject?->slug === 'lotte-finance';
    }

    private static function canView(User $user, Application $application): bool
    {
        return SalesProjectAccess::canAccessProject($user, $application->salesProject)
            && RecordVisibility::canAccessUserOwnedRecord($user, $application, 'assigned_sale_id', 'assignedSale');
    }

    private static function validateTransition(string $currentStatus, string $nextStatus): void
    {
        $allowed = match ($currentStatus) {
            self::UW_CALL => [self::UW_APPROVAL, self::UW_FIELD],
            self::UW_APPROVAL => [self::UW_FIELD, self::OP],
            self::UW_FIELD => [self::UW_APPROVAL, self::OP],
            self::OP => [self::ESIGN],
            self::ESIGN => [self::POST_APPROVAL],
            default => [],
        };
        if (! in_array($nextStatus, $allowed, true)) {
            throw ValidationException::withMessages(['next_status' => 'Bước xử lý không hợp lệ.']);
        }
    }

    private static function validateApplicationCode(Application $application, string $applicationCode): void
    {
        if ($applicationCode === '') {
            throw ValidationException::withMessages(['application_code' => 'Vui lòng nhập mã hồ sơ.']);
        }
        if (Application::withTrashed()->where('application_code', $applicationCode)->whereKeyNot($application->getKey())->exists()) {
            throw ValidationException::withMessages(['application_code' => 'Mã hồ sơ đã tồn tại.']);
        }
    }

    private static function project(): ?SalesProject
    {
        return SalesProject::query()->where('slug', 'lotte-finance')->where('is_active', true)->first();
    }

    private static function digits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return filled($digits) ? $digits : null;
    }
}
