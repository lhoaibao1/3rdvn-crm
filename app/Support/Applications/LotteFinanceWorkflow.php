<?php

namespace App\Support\Applications;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\AdminWorkflowOverride;
use App\Support\Assignments\RecordAssignment;
use App\Support\CustomerName;
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

    public const RETURNED_TO_SALE = 'lotte_returned_to_sale';

    public const UW_CALL = 'lotte_uw_call';

    public const UW_APPROVAL = 'lotte_uw_approval';

    public const UW_REJECTED = 'lotte_uw_rejected';

    public const UW_FIELD = 'lotte_uw_field';

    /** Legacy status kept only so existing OP records can continue to eSign. */
    public const OP = 'lotte_op';

    public const ESIGN = 'lotte_esign';

    public const POST_APPROVAL = 'lotte_post_approval';

    public const DISBURSED = 'lotte_disbursed';

    public const REJECTED = 'lotte_rejected';

    public static function statusOptions(): array
    {
        return [
            self::PRE_CHECK => 'Pre-Check',
            self::SALE_COMPLETION => 'Chờ Sale bổ sung thông tin',
            self::RETURNED_TO_SALE => 'Trả về Sale',
            self::UW_CALL => 'UW Call',
            self::UW_APPROVAL => 'UW Approval',
            self::UW_REJECTED => 'UW Rej',
            self::UW_FIELD => 'UW Field',
            self::ESIGN => 'eSign',
            self::POST_APPROVAL => 'Post Approval',
            self::DISBURSED => 'Đã giải ngân',
            self::REJECTED => 'Không Pass',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        $status = self::normalizeLegacyStatus($status);

        if ($status === self::OP) {
            return 'OP';
        }

        return self::statusOptions()[$status] ?? ($status ?: '-');
    }

    /** @return array<int, string> */
    public static function returnableStatuses(): array
    {
        return [
            self::UW_CALL,
            self::UW_APPROVAL,
            self::OP,
            self::ESIGN,
            self::POST_APPROVAL,
        ];
    }

    public static function statusColor(?string $status): string
    {
        $status = self::normalizeLegacyStatus($status);

        return match ($status) {
            self::PRE_CHECK => 'warning',
            self::SALE_COMPLETION => 'info',
            self::RETURNED_TO_SALE => 'warning',
            self::UW_CALL, self::UW_APPROVAL, self::UW_FIELD, self::OP, self::ESIGN, self::POST_APPROVAL => 'primary',
            self::DISBURSED => 'success',
            self::UW_REJECTED, self::REJECTED => 'danger',
            default => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function nextStatusOptions(Application $application): array
    {
        if (! $application->relationLoaded('salesProject') && $application->exists) {
            $application->loadMissing('salesProject');
        }

        $project = $application->relationLoaded('salesProject')
            ? $application->getRelation('salesProject')
            : null;
        $project = $project instanceof SalesProject
            ? $project
            : new SalesProject(['slug' => 'lotte-finance']);

        return ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            (string) self::normalizeLegacyStatus($application->status),
        );
    }

    public static function normalizeLegacyStatus(?string $status): ?string
    {
        return match ($status) {
            'processing' => self::SALE_COMPLETION,
            'rejected' => self::REJECTED,
            default => $status,
        };
    }

    public static function canCreate(?User $user): bool
    {
        $project = self::project();

        return $user instanceof User
            && ($user->hasAnyRole(['Admin', 'Sales Admin']) || $user->can('application.create'))
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
            if (array_key_exists('customer_name', $fields)) {
                $fields['customer_name'] = CustomerName::normalize($fields['customer_name']);
            }

            $moduleFields = array_filter([
                'customer_name' => $fields['customer_name'] ?? null,
                'phone' => $fields['phone'] ?? null,
                'cccd' => $fields['cccd'] ?? $fields['identity_number'] ?? null,
                'cmnd' => $fields['cmnd'] ?? null,
                'date_of_birth' => $fields['birthday'] ?? $fields['date_of_birth'] ?? null,
                'identity_issued_date' => $fields['identity_issue_date'] ?? $fields['identity_issued_date'] ?? null,
                'identity_issued_place' => $fields['identity_issue_place'] ?? $fields['identity_issued_place'] ?? null,
                'identity_expiry_date' => $fields['identity_expiry_date'] ?? null,
                'education' => $fields['education'] ?? null,
                'marital_status' => $fields['marital_status'] ?? null,
                'residence_type' => $fields['residence_type'] ?? null,
                'residence_duration_years' => $fields['residence_duration_years'] ?? null,
                'residence_duration_months' => $fields['residence_duration_months'] ?? null,
                'current_address_line' => $fields['current_address'] ?? null,
                'permanent_address_line' => $fields['permanent_address'] ?? null,
                'employer_name' => $fields['employer_name'] ?? null,
                'employer_tax_code' => $fields['employer_tax_code'] ?? null,
                'employer_phone' => $fields['employer_phone'] ?? null,
                'contract_type' => $fields['contract_type'] ?? null,
                'working_years' => $fields['working_years'] ?? null,
                'working_months' => $fields['working_months'] ?? null,
                'experience_years' => $fields['experience_years'] ?? null,
                'experience_months' => $fields['experience_months'] ?? null,
                'spouse_name' => $fields['spouse_name'] ?? null,
                'spouse_identity_number' => $fields['spouse_identity_number'] ?? null,
                'spouse_phone' => $fields['spouse_phone'] ?? null,
                'reference_1_name' => $fields['reference_1_name'] ?? null,
                'reference_1_relationship' => $fields['reference_1_relationship'] ?? null,
                'reference_1_phone' => $fields['reference_1_phone'] ?? null,
                'reference_2_name' => $fields['reference_2_name'] ?? null,
                'reference_2_relationship' => $fields['reference_2_relationship'] ?? null,
                'reference_2_phone' => $fields['reference_2_phone'] ?? null,
                'disbursement_method' => $fields['disbursement_method'] ?? null,
                'bank_name' => $fields['bank_name'] ?? null,
                'bank_account_number' => $fields['bank_account_number'] ?? null,
                'bank_account_name' => $fields['bank_account_name'] ?? null,
                'note' => $fields['note'] ?? null,
            ], fn (mixed $value): bool => filled($value));
            $assignee = RecordAssignment::autoAssigneeForProject($project, $creator);
            $snapshot = SalesLineSnapshot::fromUser($creator);
            $snapshot['assigned_sale_id'] = $assignee?->getKey();

            $application = Application::query()->create([
                'sales_project_id' => $project->getKey(),
                'lead_id' => null,
                'application_code' => null,
                'applicant_name' => CustomerName::normalize($fields['customer_name'] ?? null),
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
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }
        if (! self::canView($user, $application) || ! in_array($application->status, [self::SALE_COMPLETION, self::RETURNED_TO_SALE], true)) {
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
        if (in_array($application->status, [
            self::SALE_COMPLETION,
            self::RETURNED_TO_SALE,
            self::UW_REJECTED,
            self::UW_FIELD,
            self::DISBURSED,
            self::REJECTED,
        ], true)) {
            return false;
        }
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }
        if (! $user->can('application.update') || ! self::canView($user, $application)) {
            return false;
        }
        if ($application->status === self::PRE_CHECK) {
            return false;
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

            if ($currentStatus === self::PRE_CHECK && ! array_key_exists('next_status', $data)) {
                $passed = ($data['decision'] ?? null) === 'pass';
                $nextStatus = $passed ? self::SALE_COMPLETION : self::REJECTED;
                $applicationCode = trim((string) ($data['application_code'] ?? ''));
                self::validateApplicationCode($application, $applicationCode, $actor);
                if ($passed && ! AdminWorkflowOverride::active($actor)) {
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
                    'lf_grade' => $passed ? ($data['lf_grade'] ?? null) : 'Không Pass',
                    'ml_grade' => $passed ? ($data['ml_grade'] ?? null) : 'Không Pass',
                    'maximum_limit' => $passed ? self::digits($data['maximum_limit'] ?? null) : null,
                    'estimated_interest_rate' => $passed ? ($data['estimated_interest_rate'] ?? null) : null,
                    'review_note' => $data['processing_note'] ?? null,
                    'reviewed_by_id' => $actor->getKey(),
                    'reviewed_at' => now()->toDateTimeString(),
                ]);
                $application->application_code = $applicationCode !== '' ? $applicationCode : null;
            } else {
                $nextStatus = (string) ($data['next_status'] ?? '');
                self::validateTransition($application, $nextStatus, $actor);

                if (array_key_exists('approved_amount', $data) || $nextStatus === self::UW_APPROVAL) {
                    $approvedAmount = self::digits($data['approved_amount'] ?? null);

                    if ($nextStatus === self::UW_APPROVAL && blank($approvedAmount) && ! AdminWorkflowOverride::active($actor)) {
                        throw ValidationException::withMessages([
                            'approved_amount' => 'Vui lòng nhập số tiền được phê duyệt.',
                        ]);
                    }

                    if (filled($approvedAmount)) {
                        $review['approved_amount'] = $approvedAmount;
                    }

                    if ($nextStatus === self::UW_APPROVAL) {
                        $approvalNote = $data['processing_note'] ?? null;
                        $review['approval_note'] = filled($approvalNote)
                            ? $approvalNote
                            : ($review['approval_note'] ?? null);
                        $review['approved_by_id'] = $actor->getKey();
                        $review['approved_at'] = now()->toDateTimeString();
                    }
                }
            }

            if ($nextStatus === self::RETURNED_TO_SALE) {
                $workflow['return_to_sale'] = [
                    'from' => $currentStatus,
                    'resume_to' => self::resumeStatusForReturnSource($currentStatus),
                    'returned_by_id' => $actor->getKey(),
                    'returned_at' => now()->toDateTimeString(),
                    'note' => $data['processing_note'] ?? null,
                ];
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
            $currentStatus = $application->status;
            if (! in_array($application->status, [self::SALE_COMPLETION, self::RETURNED_TO_SALE], true) || ! self::canEditData($actor, $application)) {
                throw ValidationException::withMessages(['status' => 'Bạn không được phép hoàn thiện hồ sơ này.']);
            }

            LotteFinanceDocuments::normalizeUploads($application);
            $application->refresh();
            $nextStatus = $currentStatus === self::RETURNED_TO_SALE
                ? self::resumeStatusAfterSaleReturn($application)
                : self::UW_CALL;
            $payload = is_array($application->payload) ? $application->payload : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];
            $workflow['last_transition'] = [
                'from' => $currentStatus,
                'to' => $nextStatus,
                'actor_id' => $actor->getKey(),
                'at' => now()->toDateTimeString(),
                'note' => self::saleSubmissionNote($payload),
            ];
            $payload['workflow'] = $workflow;
            $application->forceFill(['status' => $nextStatus, 'payload' => $payload])->save();

            return $application->refresh();
        });
    }

    public static function resumeStatusAfterSaleReturn(Application $application): string
    {
        $payload = is_array($application->payload) ? $application->payload : [];
        $resumeTo = data_get($payload, 'workflow.return_to_sale.resume_to');

        if (is_string($resumeTo) && in_array($resumeTo, self::returnableStatuses(), true)) {
            return $resumeTo;
        }

        $lastTransition = data_get($payload, 'workflow.last_transition');

        if (is_array($lastTransition) && ($lastTransition['to'] ?? null) === self::RETURNED_TO_SALE) {
            $from = (string) ($lastTransition['from'] ?? '');

            if (in_array($from, self::returnableStatuses(), true)) {
                return $from;
            }
        }

        return self::UW_CALL;
    }

    private static function resumeStatusForReturnSource(string $status): string
    {
        return in_array($status, self::returnableStatuses(), true)
            ? $status
            : self::UW_CALL;
    }

    public static function isLotteFinance(?Application $application): bool
    {
        if (! $application instanceof Application) {
            return false;
        }
        $application->loadMissing('salesProject:id,slug');

        return $application->salesProject?->slug === 'lotte-finance';
    }

    public static function saleSubmissionNote(array $payload): string
    {
        $moduleNote = data_get($payload, 'module_fields.note');
        $fieldNote = data_get($payload, 'fields.note');

        return filled($moduleNote)
            ? (string) $moduleNote
            : (filled($fieldNote) ? (string) $fieldNote : 'Sale đã hoàn thiện thông tin và chứng từ.');
    }

    private static function canView(User $user, Application $application): bool
    {
        return SalesProjectAccess::canAccessProject($user, $application->salesProject)
            && RecordVisibility::canAccessUserOwnedRecord($user, $application, 'assigned_sale_id', 'assignedSale');
    }

    private static function validateTransition(Application $application, string $nextStatus, User $actor): void
    {
        $allowed = array_keys(self::nextStatusOptions($application));

        if (! in_array($nextStatus, $allowed, true)) {
            throw ValidationException::withMessages(['next_status' => 'Bước xử lý không hợp lệ.']);
        }
    }

    private static function validateApplicationCode(Application $application, string $applicationCode, User $actor): void
    {
        if ($applicationCode === '' && ! AdminWorkflowOverride::active($actor)) {
            throw ValidationException::withMessages(['application_code' => 'Vui lòng nhập mã hồ sơ.']);
        }
        if ($applicationCode !== ''
            && Application::withTrashed()->where('application_code', $applicationCode)->whereKeyNot($application->getKey())->exists()) {
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
