<?php

namespace App\Support\Applications;

use App\Models\Application;
use App\Models\ProcessingAssignmentConfig;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use App\Support\SalesLineSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AclMixWorkflow
{
    public const PENDING_INITIAL_REVIEW = 'pending_initial_review';

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
            self::PENDING_INITIAL_REVIEW => 'Đang kiểm tra',
            self::SALE_COMPLETION => 'Chờ Sale hoàn thiện thông tin',
            self::CALL_RECORDING => 'Đang thực hiện cuộc gọi ghi âm với Khách hàng',
            self::UNDERWRITING => 'Đang thẩm định',
            self::RETURNED_TO_SALE => 'Trả về Sale',
            self::AWAITING_CONTRACT => 'Chờ khách hàng ký hợp đồng',
            self::COMPLETED => 'Hoàn thành',
            self::REJECTED => 'Từ chối',
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
            self::SALE_COMPLETION, self::RETURNED_TO_SALE => 'info',
            self::CALL_RECORDING, self::UNDERWRITING, self::AWAITING_CONTRACT => 'primary',
            self::COMPLETED => 'success',
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
                'customer_name' => trim((string) ($data['applicant_name'] ?? '')),
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

            $assignee = self::configuredAutoAssignee($project);
            $snapshot = SalesLineSnapshot::fromUser($creator);
            $snapshot['assigned_sale_id'] = $assignee?->getKey();

            return Application::query()->create([
                'sales_project_id' => $project->getKey(),
                'lead_id' => null,
                'application_code' => self::nextApplicationCode(),
                'applicant_name' => $moduleFields['customer_name'],
                'phone' => $moduleFields['phone'],
                'identity_number' => $moduleFields['cccd'],
                'status' => self::PENDING_INITIAL_REVIEW,
                ...$snapshot,
                'created_by_id' => $creator->getKey(),
                'payload' => [
                    'module_fields' => array_filter($moduleFields, fn (mixed $value): bool => filled($value)),
                    'workflow' => [
                        'source' => 'acl_mix_direct',
                        'created_at' => now()->toDateTimeString(),
                    ],
                ],
            ]);
        });
    }

    public static function canEditData(?User $user, ?Application $application): bool
    {
        if (! $user instanceof User || ! $application instanceof Application || ! self::isAclMix($application)) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return ! in_array($application->status, [self::COMPLETED, self::REJECTED], true);
        }

        if (! $user->can('application.update') || ! self::canView($user, $application)) {
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

        if (! $user->can('application.update') || ! self::canView($user, $application)) {
            return false;
        }

        if ($application->status === self::COMPLETED) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return ! in_array($application->status, [
                self::SALE_COMPLETION,
                self::RETURNED_TO_SALE,
                self::COMPLETED,
            ], true);
        }

        if (! in_array($application->status, [
            self::PENDING_INITIAL_REVIEW,
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

    public static function process(Application $application, User $actor, array $data): Application
    {
        return DB::transaction(function () use ($application, $actor, $data): Application {
            $application = Application::query()
                ->lockForUpdate()
                ->with(['salesProject', 'assignedSale'])
                ->findOrFail($application->getKey());

            if (! self::canProcess($actor, $application)) {
                throw ValidationException::withMessages([
                    'next_status' => 'Bạn không được phép xử lý hồ sơ ở bước hiện tại.',
                ]);
            }

            $nextStatus = (string) ($data['next_status'] ?? '');
            self::validateTransition($application, $nextStatus, $data, $actor);

            $payload = is_array($application->payload) ? $application->payload : [];
            $review = is_array($payload['review'] ?? null) ? $payload['review'] : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];

            if ($application->status === self::PENDING_INITIAL_REVIEW && $nextStatus === self::SALE_COMPLETION) {
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

            $workflow['last_transition'] = [
                'from' => $application->status,
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

            $payload = is_array($application->payload) ? $application->payload : [];
            $workflow = is_array($payload['workflow'] ?? null) ? $payload['workflow'] : [];
            $workflow['last_transition'] = [
                'from' => $application->status,
                'to' => self::CALL_RECORDING,
                'actor_id' => $actor->getKey(),
                'at' => now()->toDateTimeString(),
                'note' => 'Sale đã cập nhật đầy đủ thông tin hồ sơ.',
            ];
            $payload['workflow'] = $workflow;

            $application->forceFill([
                'status' => self::CALL_RECORDING,
                'payload' => $payload,
            ])->save();

            return $application->refresh();
        });
    }

    public static function isAclMix(?Application $application): bool
    {
        if (! $application instanceof Application) {
            return false;
        }

        $application->loadMissing('salesProject:id,slug');

        return $application->salesProject?->slug === 'acl-mix';
    }

    private static function canView(User $user, Application $application): bool
    {
        return SalesProjectAccess::canAccessProject($user, $application->salesProject)
            && RecordVisibility::canAccessUserOwnedRecord($user, $application, 'assigned_sale_id', 'assignedSale');
    }

    private static function validateTransition(Application $application, string $nextStatus, array $data, User $actor): void
    {
        $allowed = match ($application->status) {
            self::PENDING_INITIAL_REVIEW => [self::SALE_COMPLETION, self::REJECTED],
            self::CALL_RECORDING => [self::UNDERWRITING],
            self::UNDERWRITING => [self::RETURNED_TO_SALE, self::AWAITING_CONTRACT, self::REJECTED],
            self::AWAITING_CONTRACT => [self::RETURNED_TO_SALE, self::COMPLETED, self::REJECTED],
            self::REJECTED => $actor->hasRole('Admin') ? [self::RETURNED_TO_SALE] : [],
            default => [],
        };

        if (! in_array($nextStatus, $allowed, true)) {
            throw ValidationException::withMessages(['next_status' => 'Bước xử lý không hợp lệ.']);
        }

        if ($application->status === self::PENDING_INITIAL_REVIEW && $nextStatus === self::SALE_COMPLETION) {
            foreach (['product', 'pre_approved_amount', 'pre_approved_months', 'pre_approved_interest_rate'] as $field) {
                if (blank($data[$field] ?? null)) {
                    throw ValidationException::withMessages([$field => 'Vui lòng nhập đầy đủ thông tin phê duyệt sơ bộ.']);
                }
            }
        }

        if ($nextStatus === self::COMPLETED && blank($data['contract_number'] ?? null)) {
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

    private static function nextApplicationCode(): string
    {
        $prefix = 'ACL'.now()->format('ymd');
        $next = Application::withTrashed()->where('application_code', 'like', $prefix.'%')->count() + 1;

        for ($sequence = $next; $sequence < $next + 1000; $sequence++) {
            $code = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            if (! Application::withTrashed()->where('application_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'application_code' => 'Không thể cấp mã hồ sơ ACL Mix. Vui lòng thử lại.',
        ]);
    }

    private static function digits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return filled($digits) ? $digits : null;
    }
}
