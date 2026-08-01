<?php

namespace App\Filament\Resources\ProjectReports\Pages;

use App\Filament\Resources\ProjectReports\ProjectReportResource;
use App\Models\ProjectReport;
use App\Support\Reports\ProjectReportAccess;
use App\Support\Reports\ProjectReportProductCatalog;
use App\Support\SalesLineSnapshot;
use App\Support\VietnamAddressCatalog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProjectReport extends CreateRecord
{
    protected static string $resource = ProjectReportResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Tạo báo cáo';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'created_by_id' => auth()->id(),
            'status' => ProjectReport::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        $projectId = $data['sales_project_id'] ?? null;
        $salesLine = SalesLineSnapshot::hierarchyFromUser($user);

        if (! ProjectReportAccess::canUseProject($user, $projectId)) {
            throw ValidationException::withMessages([
                'sales_project_id' => 'Bạn chưa được cấp quyền sử dụng dự án này.',
            ]);
        }

        $project = ProjectReportAccess::project($projectId);
        $salesCode = ProjectReportAccess::salesCode($user, $projectId);

        if (blank($salesCode)) {
            throw ValidationException::withMessages([
                'sales_code' => 'Bạn chưa có mã bán hàng của dự án đã chọn.',
            ]);
        }

        $productCode = trim((string) ($data['product_code'] ?? ''));
        $productName = ProjectReportProductCatalog::label($project, $productCode);
        $provinceName = VietnamAddressCatalog::provinceName($data['province_code'] ?? null);
        $districtName = VietnamAddressCatalog::districtName(
            $data['province_code'] ?? null,
            $data['district_code'] ?? null,
        );

        if (blank($productName)) {
            throw ValidationException::withMessages([
                'product_code' => 'Sản phẩm/Scheme không thuộc dự án đã chọn.',
            ]);
        }

        if (blank($provinceName) || blank($districtName)) {
            throw ValidationException::withMessages([
                'district_code' => 'Tỉnh/Thành phố hoặc Quận/Huyện không hợp lệ.',
            ]);
        }

        return DB::transaction(fn (): ProjectReport => ProjectReport::query()->create([
            'sales_project_id' => $project->getKey(),
            'created_by_id' => $user->getKey(),
            ...$salesLine,
            'customer_name' => trim((string) $data['customer_name']),
            'province_code' => (string) $data['province_code'],
            'province_name' => $provinceName,
            'district_code' => (string) $data['district_code'],
            'district_name' => $districtName,
            'identity_number' => trim((string) $data['identity_number']),
            'phone' => trim((string) $data['phone']),
            'product_code' => $productCode,
            'product_name' => $productName,
            'loan_amount' => (int) $data['loan_amount'],
            'sales_code' => $salesCode,
            'status' => ProjectReport::STATUS_PENDING,
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return ProjectReportResource::getUrl('index');
    }
}
