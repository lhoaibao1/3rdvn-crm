<?php

namespace App\Support\Applications;

use App\Models\Application;
use App\Models\ProcessingAssignmentConfig;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\AdminWorkflowOverride;
use App\Support\CustomerName;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use App\Support\SalesLineSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AclMixWorkflow
{
    public const PENDING_INITIAL_REVIEW = 'pending_initial_review';

    public const OTP_REQUIRED = 'otp_required';

    public const CUSTOMER_CAPP = 'customer_capp';

    public const INELIGIBLE = 'ineligible';

    public const SALE_COMPLETION = 'sale_completion';

    public const CALL_RECORDING = 'call_recording';

    public const UNDERWRITING = 'underwriting';

    public const RETURNED_TO_SALE = 'returned_to_sale';

    public const AWAITING_CONTRACT = 'awaiting_contract';

    public const COMPLETED = 'completed';

    public const REJECTED = 'rejected';

    public static function statusOptions(): array
    {
        return [
            self::PENDING_INITIAL_REVIEW => 'Chờ kiểm tra',
            self::OTP_REQUIRED => 'Đang kiểm tra',
            self::CUSTOMER_CAPP => 'Khách hàng thao tác CAPP',
            self::INELIGIBLE => 'Không thoả điều kiện',
            self::SALE_COMPLETION => 'Chờ Sale hoàn thiện thông tin',
            self::CALL_RECORDING => 'Đang thực hiện cuộc gọi ghi âm với Khách hàng',
            self::UNDERWRITING => 'Đang thẩm định',
            self::RETURNED_TO_SALE => 'Trả về Sale',
            self::AWAITING_CONTRACT => 'Chờ khách hàng ký hợp đồng',
            self::COMPLETED => 'Hoàn thành',
            self::REJECTED => 'Từ chối',
        ];
    }

    /** @return array<int, string> */
    public static function returnableStatuses(): array
    {
        return [
            self::PENDING_INITIAL_REVIEW,
            self::OTP_REQUIRED,
            self::CUSTOMER_CAPP,
            self::CALL_RECORDING,
            self::UNDERWRITING,
            self::AWAITING_CONTRACT,
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::statusOptions()[$status] ?? ($status ?: '-');
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            self::PENDING_INITIAL_REVIEW => 'warning',
            self::OTP_REQUIRED, self::CUSTOMER_CAPP => 'primary',
            self::SALE_COMPLETION, self::RETURNED_TO_SALE => 'info',
            self::CALL_RECORDING, self::UNDERWRITING, self::AWAITING_CONTRACT => 'primary',
            self::COMPLETED => 'success',
            self::INELIGIBLE, self::REJECTED => 'danger',
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
            : new SalesProject(['slug' => 'acl-mix']);

        $options = ProjectWorkflowConfiguration::nextStatusOptions($project, (string) $application->status);

        return match ($application->status) {
            self::PENDING_INITIAL_REVIEW => array_intersect_key([
                self::INELIGIBLE => 'Không thoả điều kiện',
                self::OTP_REQUIRED => 'Yêu cầu OTP',
                self::RETURNED_TO_SALE => 'Trả về Sale',
            ], $options),
            self::CUSTOMER_CAPP => array_intersect_key([
                self::SALE_COMPLETION => 'Khách hàng thoả mãn điều kiện',
                self::REJECTED => 'Từ chối',
                self::RETURNED_TO_SALE => 'Trả về Sale',
            ], $options),
            default => $options,
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

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $moduleFields
     * @return array<string, mixed>
     */
    public static function creationPayload(array $data, array $moduleFields): array
    {
        $payload = [
            'module_fields' => array_filter($moduleFields, fn (mixed $value): bool => filled($value)),
            'workflow' => [
                'source' => 'acl_mix_direct',
                'created_at' => now()->toDateTimeString(),
            ],
        ];

        if (filled($data['consent_6088'] ?? null)) {
            $payload['documents']['consent_6088'] = $data['consent_6088'];
        }

        return $payload;
    }

    public static function create(array $data, User $creator): Application
    {
        $project = self::project();

        if (! self::canCreate($creator) || ! $project instanceof SalesProject) {
            throw ValidationException::withMessages([
                'applicant_name' => 'Bạn chưa được phân quyền tạo hồ sơ ACL Mix.',
            ]);
        }

        return DB::transaction(function () use ($data, $creator, $project): Application {
            $moduleFields = [
                'customer_name' => CustomerName::normalize($data['applicant_name'] ?? null),
                'phone' => trim((string) ($data['phone'] ?? '')),
                'cccd' => trim((string) ($data['identity_number'] ?? '')),
                'date_of_birth' => $data['birthday'] ?? null,
                'identity_issued_place' => $data['identity_issued_place'] ?? null,
                'identity_issued_date' => $data['identity_issued_date'] ?? null,
                'current_address_line' => $data['address'] ?? null,
                'current_province_code' => $data['province_code'] ?? null,
                'current_province_name' => $data['province_name'] ?? null,
                'current_district_code' => $data['district_code'] ?? null,
                'current_district_name' => $data['district_name'] ?? null,
                'current_ward_code' => $data['ward_code'] ?? null,
                'current_ward_name' => $data['ward_name'] ?? null,
            ];

            $payload = self::creationPayload($data, $moduleFields);

            $assignee = self::configuredAutoAssignee($project);
            $snapshot = SalesLineSnapshot::fromUser($creator);
            $snapshot['assigned_sale_id'] = $assignee?->getKey();

            return Application::query()->create([
                'sales_project_id' => $project->getKey(),
                'lead_id' => null,
                'application_code' => null,
                'applicant_name' => $moduleFields['customer_name'],
                'phone' => $moduleFields['phone'],
                'identity_number' => $moduleFields['cccd'],
                'status' => self::PENDING_INITIAL_REVIEW,
                ...$snapshot,
                'created_by_id' => $creator->getKey(),
                'payload' => $payload,
            ]);
        });
    }

    public static function canEditData(?User $user, ?Application $application): bool
    {
        if (! $user instanceof User || ! $application instanceof Application || ! self::isAclMix($application)) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        if (! self::canView($user, $application)) {
            return false;
        }

        if (! in_array($application->status, [self::SALE_COMPLETION, self::RETURNED_TO_SALE], true)) {
            return false;
        }

        return (int) $application->created_by_id === (int) $user->getKey()
            || ($user->hasRole('Team Leader') && (int) $application->team_leader_id === (int) $user->getKey())
            || ($user->hasRole('AM') && (int) $application->am_id === (int) $user->getKey())
            || ($user->hasRole('ZD') && (int) $application->zd_id === (int) $user->getKey());
    }

    public static function canProcess(?User $user, ?Application $application): bool
    {
        if (! $user instanceof User || ! $application instanceof Application || ! self::isAclMix($application)) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return in_array($application->status, [
                self::PENDING_INITIAL_REVIEW,
                self::OTP_REQUIRED,
                self::CUSTOMER_CAPP,
                self::CALL_RECORDING,
                self::UNDERWRITING,
                self::AWAITING_CONTRACT,
            ], true);
        }

        if (! $user->can('application.update') || ! self::canView($user, $application)) {
            return false;
        }

        if ($application->status === self::COMPLETED) {
            return false;
        }

        if (! in_array($application->status, [
            self::PENDING_INITIAL_REVIEW,
            self::OTP_REQUIRED,
            self::CUSTOMER_CAPP,
            self::CALL_RECORDING,
            self::UNDERWRITING,
            self::AWAITING_CONTRACT,
        ], true)) {
            return false;
        }

        $application->loadMissing('assignedSale');

        return ($user->hasRole('Courier')
                && (int) $application->assigned_sale_id === (int) $user->getKey())
            || ($user->hasRole('Courier Manager')
                && (int) $application->assignedSale?->courier_manager_id === (int) $user->getKey());
    }

    public static function canUpdateOtp(?User $user, ?Application $application): bool
    {
        return $application instanceof Application
            && $application->status === self::OTP_REQUIRED
            && self::canProcess($user, $application);
    }

    public static function updateOtp(Application $application, User $actor, string $otp): Application
    {
        return DB::transaction(function () use ($application, $actor, $otp): Application {
            $application = Application::query()
                ->lockForUpdate()
                ->with(['salesProject', 'assignedSale'])
                ->findOrFail($application->getKey());

            if (! self::canUpdateOtp($actor, $application)) {
                throw ValidationException::withMessages([
                    'otp' => 'Bạn không được phép cập nhật OTP ở bước hiện tại.',
                ]);
            }

            $otp = trim($otp);

            if ($otp === '' || mb_strlen($otp) > 20) {
                throw ValidationException::withMessages([
                    'otp' => 'OTP phải có từ 1 đến 20 ký tự.',
                ]);
            }

            $payload = is_array($application->payload) ? $application->payload : [];
            $review = is_array($payload['review'] ?? null) ? $payload['review'] : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];
            $review['otp'] = $otp;
            $review['otp_updated_by_id'] = $actor->getKey();
            $review['otp_updated_at'] = now()->toDateTimeString();
            $workflow['last_otp_update'] = [
                'actor_id' => $actor->getKey(),
                'at' => now()->toDateTimeString(),
            ];
            $payload['review'] = $review;
            $payload['workflow'] = $workflow;

            $application->forceFill(['payload' => $payload])->save();

            return $application->refresh();
        });
    }

    public static function process(Application $application, User $actor, array $data): Application
    {
        return DB::transaction(function () use ($application, $actor, $data): Application {
            $application = Application::query()
                ->lockForUpdate()
                ->with(['salesProject', 'assignedSale'])
                ->findOrFail($application->getKey());
            $currentStatus = (string) $application->status;

            if (! self::canProcess($actor, $application)) {
                throw ValidationException::withMessages([
                    'next_status' => 'Bạn không được phép xử lý hồ sơ ở bước hiện tại.',
                ]);
            }

            $nextStatus = (string) ($data['next_status'] ?? '');
            self::validateTransition($application, $nextStatus, $data, $actor);

            if ($application->status === self::CUSTOMER_CAPP && $nextStatus === self::SALE_COMPLETION) {
                $applicationCode = trim((string) ($data['application_code'] ?? ''));
                self::validateApplicationCode($application, $applicationCode, $actor);
                $application->application_code = $applicationCode !== '' ? $applicationCode : null;
            }

            $payload = is_array($application->payload) ? $application->payload : [];
            $review = is_array($payload['review'] ?? null) ? $payload['review'] : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];

            if ($application->status === self::OTP_REQUIRED && filled($data['otp'] ?? null)) {
                $review['otp'] = trim((string) $data['otp']);
                $review['otp_updated_by_id'] = $actor->getKey();
                $review['otp_updated_at'] = now()->toDateTimeString();
            }

            if ($application->status === self::CUSTOMER_CAPP && $nextStatus === self::SALE_COMPLETION) {
                $review = array_replace($review, [
                    'decision' => 'Khách hàng thoả mãn điều kiện',
                    'product' => $data['product'] ?? null,
                    'pre_approved_amount' => self::digits($data['pre_approved_amount'] ?? null),
                    'pre_approved_months' => $data['pre_approved_months'] ?? null,
                    'pre_approved_interest_rate' => $data['pre_approved_interest_rate'] ?? null,
                    'review_note' => $data['processing_note'] ?? null,
                    'reviewed_by_id' => $actor->getKey(),
                    'reviewed_at' => now()->toDateTimeString(),
                ]);
            } elseif ($nextStatus === self::INELIGIBLE) {
                $review['decision'] = 'Không thoả điều kiện';
                $review['review_note'] = $data['processing_note'] ?? null;
                $review['reviewed_by_id'] = $actor->getKey();
                $review['reviewed_at'] = now()->toDateTimeString();
            } elseif ($nextStatus === self::REJECTED) {
                $review['decision'] = 'Từ chối';
                $review['review_note'] = $data['processing_note'] ?? null;
                $review['reviewed_by_id'] = $actor->getKey();
                $review['reviewed_at'] = now()->toDateTimeString();
            }

            if ($nextStatus === self::COMPLETED) {
                $workflow['contract_number'] = trim((string) ($data['contract_number'] ?? ''));
                $workflow['completed_by_id'] = $actor->getKey();
                $workflow['completed_at'] = now()->toDateTimeString();
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
            $application = Application::query()
                ->lockForUpdate()
                ->with(['salesProject', 'assignedSale'])
                ->findOrFail($application->getKey());

            if (! in_array($application->status, [self::SALE_COMPLETION, self::RETURNED_TO_SALE], true)
                || ! self::canEditData($actor, $application)) {
                throw ValidationException::withMessages([
                    'status' => 'Bạn không được phép cập nhật và chuyển bước hồ sơ này.',
                ]);
            }

            $currentStatus = (string) $application->status;
            $nextStatus = $currentStatus === self::RETURNED_TO_SALE
                ? self::resumeStatusAfterSaleReturn($application)
                : self::CALL_RECORDING;
            $payload = is_array($application->payload) ? $application->payload : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];
            $workflow['last_transition'] = [
                'from' => $currentStatus,
                'to' => $nextStatus,
                'actor_id' => $actor->getKey(),
                'at' => now()->toDateTimeString(),
                'note' => 'Sale đã cập nhật đầy đủ thông tin hồ sơ.',
            ];
            $payload['workflow'] = $workflow;

            $application->forceFill([
                'status' => $nextStatus,
                'payload' => $payload,
            ])->save();

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

        return self::CALL_RECORDING;
    }

    private static function resumeStatusForReturnSource(string $status): string
    {
        return in_array($status, self::returnableStatuses(), true)
            ? $status
            : self::CALL_RECORDING;
    }

    public static function isAclMix(?Application $application): bool
    {
        if (! $application instanceof Application) {
            return false;
        }

        if (! $application->relationLoaded('salesProject') && $application->exists) {
            $application->loadMissing('salesProject:id,slug');
        }

        return $application->salesProject?->slug === 'acl-mix';
    }

    private static function canView(User $user, Application $application): bool
    {
        return SalesProjectAccess::canAccessProject($user, $application->salesProject)
            && RecordVisibility::canAccessUserOwnedRecord($user, $application, 'assigned_sale_id', 'assignedSale');
    }

    private static function validateTransition(Application $application, string $nextStatus, array $data, User $actor): void
    {
        $allowed = array_keys(self::nextStatusOptions($application));

        if (! in_array($nextStatus, $allowed, true)) {
            throw ValidationException::withMessages(['next_status' => 'Bước xử lý không hợp lệ.']);
        }

        if ($application->status === self::OTP_REQUIRED
            && $nextStatus === self::CUSTOMER_CAPP
            && blank(data_get($application->payload, 'review.otp'))) {
            throw ValidationException::withMessages([
                'next_status' => 'Vui lòng cập nhật OTP trước khi chuyển sang Khách hàng thao tác CAPP.',
            ]);
        }

        if ($application->status === self::CUSTOMER_CAPP && $nextStatus === self::SALE_COMPLETION) {
            foreach (['product', 'pre_approved_amount', 'pre_approved_months', 'pre_approved_interest_rate'] as $field) {
                if (! AdminWorkflowOverride::active($actor)
                    && blank($data[$field] ?? null)) {
                    throw ValidationException::withMessages([$field => 'Vui lòng nhập đầy đủ thông tin phê duyệt sơ bộ.']);
                }
            }
        }

        if (! AdminWorkflowOverride::active($actor)
            && $nextStatus === self::COMPLETED
            && blank($data['contract_number'] ?? null)) {
            throw ValidationException::withMessages(['contract_number' => 'Vui lòng nhập số hợp đồng.']);
        }
    }

    private static function project(): ?SalesProject
    {
        return SalesProject::query()->where('slug', 'acl-mix')->where('is_active', true)->first();
    }

    private static function configuredAutoAssignee(SalesProject $project): ?User
    {
        $config = ProcessingAssignmentConfig::query()->where('sales_project_id', $project->getKey())->first();

        if (! $config?->is_enabled || ! $config->auto_assign) {
            return null;
        }

        $users = $config->configuredUsers();

        return $users->isEmpty() ? null : $users->random();
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

    private static function digits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return filled($digits) ? $digits : null;
    }
}
