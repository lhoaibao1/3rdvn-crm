<?php

namespace App\Support\Reports;

use App\Models\Application;
use App\Models\ProjectReport;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\SalesLineSnapshot;
use App\Support\VietnamAddressCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectReportWorkflow
{
    public static function canConvertApplication(Application $application, ?User $actor): bool
    {
        if (! $actor instanceof User || ! ProjectReportAccess::canUseProject($actor, $application->sales_project_id)) {
            return false;
        }

        $project = $application->relationLoaded('salesProject')
            ? $application->salesProject
            : $application->salesProject()->with('crmModule')->first();

        return $project instanceof SalesProject
            && $project->is_active
            && $project->crmModule?->slug === 'applications'
            && ! ProjectReport::query()->where('application_id', $application->getKey())->exists();
    }

    public static function applicationDefaults(Application $application, User $actor): array
    {
        $application->loadMissing(['salesProject.crmModule', 'assignedSale', 'createdBy']);
        $project = $application->salesProject;
        $owner = self::applicationOwner($application, $actor);
        $fields = is_array(data_get($application->payload, 'fields')) ? data_get($application->payload, 'fields') : [];
        $review = is_array(data_get($application->payload, 'review')) ? data_get($application->payload, 'review') : [];
        $address = self::addressDefaults($fields);
        $productCode = self::productCode($project, $fields, $review);

        return [
            'sales_project_id' => $application->sales_project_id,
            'sales_code' => self::salesCodeFor($owner, $project) ?: self::salesCodeFor($actor, $project),
            'customer_name' => $application->applicant_name,
            'identity_number' => $application->identity_number,
            'phone' => $application->phone,
            'product_code' => $productCode,
            'loan_amount' => self::loanAmount($fields, $review),
            ...$address,
        ];
    }

    public static function convertApplication(Application $application, User $actor, array $data): ProjectReport
    {
        return DB::transaction(function () use ($application, $actor, $data): ProjectReport {
            $application = Application::query()
                ->lockForUpdate()
                ->with(['salesProject.crmModule', 'assignedSale', 'createdBy', 'projectReport'])
                ->findOrFail($application->getKey());

            if ($application->projectReport instanceof ProjectReport) {
                throw ValidationException::withMessages([
                    'application_id' => 'Hồ sơ này đã được chuyển sang Báo cáo.',
                ]);
            }

            if (! self::canConvertApplication($application, $actor)) {
                throw ValidationException::withMessages([
                    'application_id' => 'Bạn không có quyền chuyển hồ sơ này sang Báo cáo.',
                ]);
            }

            $project = $application->salesProject;
            $owner = self::applicationOwner($application, $actor);
            $normalized = self::normalizeReportData($project, $data);
            $salesCode = self::salesCodeFor($owner, $project) ?: self::salesCodeFor($actor, $project);

            if (blank($salesCode)) {
                throw ValidationException::withMessages([
                    'sales_code' => 'Người tạo hồ sơ chưa có mã bán hàng của dự án.',
                ]);
            }

            $report = ProjectReport::query()->create([
                'sales_project_id' => $project->getKey(),
                'application_id' => $application->getKey(),
                'origin' => ProjectReport::ORIGIN_APPLICATION,
                'created_by_id' => $owner->getKey(),
                'sales_code' => $salesCode,
                'status' => ProjectReport::STATUS_PENDING,
                'converted_by_id' => $actor->getKey(),
                'converted_at' => now(),
                ...$normalized,
            ]);

            $application->forceFill(['status' => self::applicationStatus(ProjectReport::STATUS_PENDING)])->save();

            return $report;
        });
    }

    public static function canConvertToApplication(ProjectReport $report, ?User $actor): bool
    {
        if (! $actor instanceof User || $report->application_id !== null) {
            return false;
        }

        if (! $actor->hasRole('Admin') && (int) $report->created_by_id !== (int) $actor->getKey()) {
            return false;
        }

        $project = $report->relationLoaded('salesProject')
            ? $report->salesProject
            : $report->salesProject()->with('crmModule')->first();

        return $project instanceof SalesProject
            && $project->is_active
            && $project->crmModule?->slug === 'applications'
            && ProjectReportAccess::canUseProject($actor, $project->getKey());
    }

    public static function convertToApplication(ProjectReport $report, User $actor): Application
    {
        return DB::transaction(function () use ($report, $actor): Application {
            $report = ProjectReport::query()
                ->lockForUpdate()
                ->with(['salesProject.crmModule', 'createdBy', 'application'])
                ->findOrFail($report->getKey());

            if (! self::canConvertToApplication($report, $actor)) {
                throw ValidationException::withMessages([
                    'application_id' => 'Báo cáo này đã được chuyển hoặc không thuộc dự án Application.',
                ]);
            }

            $project = $report->salesProject;
            $owner = $report->createdBy ?: $actor;
            $fields = [
                'customer_name' => $report->customer_name,
                'phone' => $report->phone,
                'identity_number' => $report->identity_number,
                'province_code' => $report->province_code,
                'province_name' => $report->province_name,
                'district_code' => $report->district_code,
                'district_name' => $report->district_name,
                'product_code' => $report->product_code,
                'product_name' => $report->product_name,
                'scheme_code' => $report->product_code,
                'scheme_name' => $report->product_name,
                'loan_amount' => $report->loan_amount,
            ];

            $application = Application::query()->create([
                'sales_project_id' => $project->getKey(),
                'application_code' => self::nextApplicationCode($project),
                'applicant_name' => $report->customer_name,
                'phone' => $report->phone,
                'identity_number' => $report->identity_number,
                'status' => self::applicationStatus($report->status),
                ...SalesLineSnapshot::fromUser($owner),
                'payload' => [
                    'fields' => $fields,
                    'review' => [
                        'product' => $report->product_code,
                        'pre_approved_amount' => $report->loan_amount,
                        'project_report_id' => $report->getKey(),
                    ],
                    'module_fields' => [],
                ],
                'note' => 'Chuyển từ Báo cáo #'.$report->getKey().'.',
            ]);

            $report->forceFill([
                'application_id' => $application->getKey(),
                'converted_by_id' => $actor->getKey(),
                'converted_at' => now(),
            ])->save();

            return $application;
        });
    }

    public static function updateStatus(ProjectReport $report, User $admin, string $status): ProjectReport
    {
        if (! $admin->hasRole('Admin')) {
            abort(403);
        }

        if (! array_key_exists($status, ProjectReport::statusOptions())) {
            throw ValidationException::withMessages([
                'status' => 'Trạng thái không hợp lệ.',
            ]);
        }

        return DB::transaction(function () use ($report, $admin, $status): ProjectReport {
            $report = ProjectReport::query()
                ->lockForUpdate()
                ->with('application')
                ->findOrFail($report->getKey());

            $report->forceFill([
                'status' => $status,
                'status_updated_by_id' => $admin->getKey(),
                'status_updated_at' => now(),
            ])->save();

            if ($report->application instanceof Application) {
                $report->application->forceFill([
                    'status' => self::applicationStatus($status),
                ])->save();
            }

            return $report;
        });
    }

    private static function normalizeReportData(SalesProject $project, array $data): array
    {
        $productCode = trim((string) ($data['product_code'] ?? ''));
        $productName = ProjectReportProductCatalog::label($project, $productCode);
        $provinceCode = (string) ($data['province_code'] ?? '');
        $districtCode = (string) ($data['district_code'] ?? '');
        $provinceName = VietnamAddressCatalog::provinceName($provinceCode);
        $districtName = VietnamAddressCatalog::districtName($provinceCode, $districtCode);

        if (blank($productName)) {
            throw ValidationException::withMessages([
                'product_code' => 'Sản phẩm/Scheme không thuộc dự án của hồ sơ.',
            ]);
        }

        if (blank($provinceName) || blank($districtName)) {
            throw ValidationException::withMessages([
                'district_code' => 'Tỉnh/Thành phố hoặc Quận/Huyện không hợp lệ.',
            ]);
        }

        return [
            'customer_name' => trim((string) ($data['customer_name'] ?? '')),
            'province_code' => $provinceCode,
            'province_name' => $provinceName,
            'district_code' => $districtCode,
            'district_name' => $districtName,
            'identity_number' => trim((string) ($data['identity_number'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'product_code' => $productCode,
            'product_name' => $productName,
            'loan_amount' => (int) ($data['loan_amount'] ?? 0),
        ];
    }

    private static function applicationOwner(Application $application, User $actor): User
    {
        return $application->assignedSale ?: $application->createdBy ?: $actor;
    }

    private static function salesCodeFor(?User $user, ?SalesProject $project): ?string
    {
        if (! $user instanceof User || ! $project instanceof SalesProject) {
            return null;
        }

        $code = data_get($user->sales_codes ?? [], $project->slug);

        return filled($code) ? trim((string) $code) : null;
    }

    private static function addressDefaults(array $fields): array
    {
        $provinceCode = (string) ($fields['province_code'] ?? '');
        $districtCode = (string) ($fields['district_code'] ?? '');

        if (blank($provinceCode) && filled($fields['province_name'] ?? null)) {
            $provinceCode = (string) collect(VietnamAddressCatalog::provinceOptions())
                ->search(fn (string $name): bool => mb_strtolower($name) === mb_strtolower(trim((string) $fields['province_name'])));
        }

        if (filled($provinceCode) && blank($districtCode) && filled($fields['district_name'] ?? null)) {
            $districtCode = (string) collect(VietnamAddressCatalog::districtOptions($provinceCode))
                ->search(fn (string $name): bool => mb_strtolower($name) === mb_strtolower(trim((string) $fields['district_name'])));
        }

        return [
            'province_code' => $provinceCode ?: null,
            'province_name' => VietnamAddressCatalog::provinceName($provinceCode),
            'district_code' => $districtCode ?: null,
            'district_name' => VietnamAddressCatalog::districtName($provinceCode, $districtCode),
        ];
    }

    private static function productCode(?SalesProject $project, array $fields, array $review): ?string
    {
        if (! $project instanceof SalesProject) {
            return null;
        }

        foreach ([$fields['scheme_code'] ?? null, $fields['product_code'] ?? null, $review['product'] ?? null] as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '' && filled(ProjectReportProductCatalog::label($project, $candidate))) {
                return $candidate;
            }
        }

        return null;
    }

    private static function loanAmount(array $fields, array $review): ?int
    {
        foreach ([$fields['loan_amount'] ?? null, $review['pre_approved_amount'] ?? null] as $amount) {
            $amount = (int) preg_replace('/\D+/', '', (string) $amount);

            if ($amount > 0) {
                return $amount;
            }
        }

        return null;
    }

    private static function applicationStatus(string $reportStatus): string
    {
        return match ($reportStatus) {
            ProjectReport::STATUS_PROCESSED => 'approved',
            ProjectReport::STATUS_REJECTED => 'rejected',
            default => 'pending_approval',
        };
    }

    private static function nextApplicationCode(SalesProject $project): string
    {
        $prefix = strtoupper(trim((string) ($project->code_prefix ?: $project->slug)));
        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix) ?: 'APP';
        $base = $prefix.now()->format('ymd');

        for ($sequence = 1; $sequence <= 9999; $sequence++) {
            $code = $base.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            if (! Application::withTrashed()->where('application_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'application_code' => 'Không thể cấp mã hồ sơ mới trong ngày hôm nay.',
        ]);
    }
}
