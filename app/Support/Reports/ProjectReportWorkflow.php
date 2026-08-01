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
    public static function syncFromApplication(Application $application, ?User $actor = null): ?ProjectReport
    {
        $application->loadMissing(['salesProject.crmModule', 'assignedSale', 'createdBy']);
        $project = $application->salesProject;
        $owner = $application->createdBy ?: $application->assignedSale ?: $actor;
        $ownerLine = SalesLineSnapshot::hierarchyFromUser($owner);

        if (! $project instanceof SalesProject || ! $owner instanceof User) {
            return null;
        }

        $payload = is_array($application->payload) ? $application->payload : [];
        $fields = array_replace(
            is_array(data_get($payload, 'fields')) ? data_get($payload, 'fields') : [],
            is_array(data_get($payload, 'module_fields')) ? data_get($payload, 'module_fields') : [],
        );
        $review = is_array(data_get($payload, 'review')) ? data_get($payload, 'review') : [];
        $address = self::addressDefaults($fields);
        $productCode = self::productCode($project, $fields, $review)
            ?: self::firstFilled([$fields['scheme_code'] ?? null, $fields['product_code'] ?? null, $review['product'] ?? null]);
        $productName = filled($productCode) ? ProjectReportProductCatalog::label($project, (string) $productCode) : null;
        $productName = $productName ?: self::firstFilled([
            $fields['scheme_name'] ?? null,
            $fields['product_name'] ?? null,
            $review['product_name'] ?? null,
            $review['product'] ?? null,
            $productCode,
        ]);

        $report = ProjectReport::query()->firstOrNew(['application_id' => $application->getKey()]);

        if (! $report->exists) {
            $report->forceFill([
                'origin' => ProjectReport::ORIGIN_APPLICATION,
                'status' => ProjectReport::STATUS_PENDING,
                'converted_by_id' => $actor?->getKey() ?: $owner->getKey(),
                'converted_at' => now(),
            ]);
        }

        $report->forceFill([
            'sales_project_id' => $project->getKey(),
            'created_by_id' => $owner->getKey(),
            'team_id' => $application->team_id ?: $ownerLine['team_id'],
            'team_leader_id' => $application->team_leader_id ?: $ownerLine['team_leader_id'],
            'am_id' => $application->am_id ?: $ownerLine['am_id'],
            'zd_id' => $application->zd_id ?: $ownerLine['zd_id'],
            'customer_name' => $application->applicant_name,
            'identity_number' => $application->identity_number,
            'phone' => $application->phone,
            'product_code' => filled($productCode) ? (string) $productCode : null,
            'product_name' => filled($productName) ? (string) $productName : null,
            'loan_amount' => self::loanAmount($fields, $review),
            'approved_months' => self::integerValue($review['pre_approved_months'] ?? $fields['approved_months'] ?? $fields['loan_term_months'] ?? null),
            'approved_interest_rate' => self::decimalValue($review['pre_approved_interest_rate'] ?? $fields['approved_interest_rate'] ?? $fields['interest_rate'] ?? null),
            'sales_code' => self::salesCodeFor($owner, $project),
            'source_data' => [
                'application_code' => $application->application_code,
                'application_status' => $application->status,
                'fields' => is_array(data_get($payload, 'fields')) ? data_get($payload, 'fields') : [],
                'data' => $fields,
                'module_fields' => is_array(data_get($payload, 'module_fields')) ? data_get($payload, 'module_fields') : [],
                'review' => $review,
                'synced_at' => now()->toIso8601String(),
            ],
            ...$address,
        ])->save();

        return $report->refresh();
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

            return $report;
        });
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
        $provinceCode = (string) ($fields['province_code'] ?? $fields['current_province_code'] ?? '');
        $districtCode = (string) ($fields['district_code'] ?? $fields['current_district_code'] ?? '');

        if (blank($provinceCode) && filled(($fields['province_name'] ?? $fields['current_province_name'] ?? null) ?? null)) {
            $provinceCode = (string) collect(VietnamAddressCatalog::provinceOptions())
                ->search(fn (string $name): bool => mb_strtolower($name) === mb_strtolower(trim((string) ($fields['province_name'] ?? $fields['current_province_name'] ?? null))));
        }

        if (filled($provinceCode) && blank($districtCode) && filled(($fields['district_name'] ?? $fields['current_district_name'] ?? null) ?? null)) {
            $districtCode = (string) collect(VietnamAddressCatalog::districtOptions($provinceCode))
                ->search(fn (string $name): bool => mb_strtolower($name) === mb_strtolower(trim((string) ($fields['district_name'] ?? $fields['current_district_name'] ?? null))));
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
        foreach ([$fields['loan_amount'] ?? null, $fields['approved_limit'] ?? null, $fields['approved_amount'] ?? null, $review['pre_approved_amount'] ?? null] as $amount) {
            $amount = (int) preg_replace('/\D+/', '', (string) $amount);

            if ($amount > 0) {
                return $amount;
            }
        }

        return null;
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

    private static function integerValue(mixed $value): ?int
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return filled($digits) ? (int) $digits : null;
    }

    private static function decimalValue(mixed $value): ?float
    {
        $normalized = str_replace(',', '.', preg_replace('/[^0-9,.]+/', '', (string) $value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
