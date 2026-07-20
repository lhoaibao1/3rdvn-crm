<?php

namespace Database\Seeders;

use App\Models\CrmModule;
use App\Models\SalesProject;
use Illuminate\Database\Seeder;

class CrmModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'Dashboard', 'slug' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'route_name' => 'dashboard', 'sort_order' => 10, 'required_permissions' => ['dashboard.view']],
            ['name' => 'Lead', 'slug' => 'leads', 'label' => 'Lead', 'icon' => 'user-plus', 'route_name' => 'leads.index', 'sort_order' => 20, 'required_permissions' => ['lead.view']],
            ['name' => 'Hot Lead', 'slug' => 'hot-leads', 'label' => 'Hot Lead', 'icon' => 'bell-alert', 'route_name' => 'hot-leads.index', 'sort_order' => 22, 'required_permissions' => ['hot_lead.view']],
            ['name' => 'Application', 'slug' => 'applications', 'label' => 'Application', 'icon' => 'rectangle-stack', 'route_name' => 'applications.acl-mix.index', 'sort_order' => 25, 'required_permissions' => ['application.view']],
            ['name' => 'Hồ sơ', 'slug' => 'profiles', 'label' => 'Hồ sơ', 'icon' => 'file', 'route_name' => 'profiles.index', 'sort_order' => 30, 'required_permissions' => ['profile.view']],
            ['name' => 'Phê duyệt', 'slug' => 'approvals', 'label' => 'Phê duyệt', 'icon' => 'check', 'route_name' => 'approvals.index', 'sort_order' => 40, 'required_permissions' => ['approval.view', 'profile.approve', 'profile.reject']],
            ['name' => 'API Mapping', 'slug' => 'api-mappings', 'label' => 'API Mapping', 'icon' => 'link', 'route_name' => 'api-mappings.index', 'sort_order' => 50, 'required_permissions' => ['api_mapping.view']],
            ['name' => 'Users', 'slug' => 'users', 'label' => 'Người dùng', 'icon' => 'users', 'route_name' => 'users.index', 'sort_order' => 60, 'required_permissions' => ['user.view'], 'required_roles' => ['Admin', 'ZD', 'AM', 'Team Leader']],
            ['name' => 'Roles', 'slug' => 'roles', 'label' => 'Vai trò', 'icon' => 'shield', 'route_name' => 'roles.index', 'sort_order' => 70, 'required_permissions' => ['role.view'], 'required_roles' => ['Admin']],
            ['name' => 'Settings', 'slug' => 'settings', 'label' => 'Cài đặt giao diện', 'icon' => 'settings', 'route_name' => 'settings.ui.edit', 'sort_order' => 80, 'required_permissions' => ['settings.view'], 'required_roles' => ['Admin']],
            ['name' => 'Lookups', 'slug' => 'lookups', 'label' => 'Danh mục user', 'icon' => 'list', 'route_name' => 'crm-lookups.index', 'sort_order' => 82, 'required_permissions' => ['lookup.view'], 'required_roles' => ['Admin']],
            ['name' => 'Sales Channels', 'slug' => 'sales-channels', 'label' => 'Kênh bán hàng', 'icon' => 'building', 'route_name' => 'sales-channels.index', 'sort_order' => 83, 'required_permissions' => ['sales_channel.view'], 'required_roles' => ['Admin']],
            ['name' => 'Reports', 'slug' => 'reports', 'label' => 'Báo cáo', 'icon' => 'chart', 'route_name' => 'reports.index', 'sort_order' => 90, 'required_permissions' => ['report.view'], 'is_active' => false],
        ];

        foreach ($modules as $module) {
            $record = CrmModule::query()->firstOrNew(['slug' => $module['slug']]);
            $data = $module + ['is_active' => true];

            if ($record->exists && filled($record->description)) {
                unset($data['description']);
            }

            $record->fill($data)->save();
        }

        $hotLeadModule = CrmModule::query()->where('slug', 'hot-leads')->first();

        if ($hotLeadModule) {
            $hotLead = SalesProject::query()->firstOrNew(['slug' => 'hot-lead']);
            $hotLead->fill([
                'crm_module_id' => $hotLeadModule->getKey(),
                'name' => 'Hot Lead',
                'code_prefix' => 'HOT',
                'description' => $hotLead->description ?: 'Dự án quyền truy cập module Hot Lead.',
                'sort_order' => 10,
                'is_active' => true,
            ]);

            if (blank($hotLead->lead_form_schema)) {
                $hotLead->lead_form_schema = [
                    ['field_key' => 'customer_name', 'label' => 'Họ tên', 'type' => 'text', 'required' => true],
                    ['field_key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'phone', 'required' => true],
                    ['field_key' => 'identity_number', 'label' => 'CCCD/CMND', 'type' => 'text', 'required' => false],
                    ['field_key' => 'product_interest', 'label' => 'Sản phẩm quan tâm', 'type' => 'text', 'required' => false],
                    ['field_key' => 'source_note', 'label' => 'Ghi chú nguồn', 'type' => 'textarea', 'required' => false],
                ];
            }

            if (blank($hotLead->module_form_schema)) {
                $hotLead->module_form_schema = [
                    ['field_key' => 'decision', 'label' => 'Quyết định', 'type' => 'select', 'required' => true, 'options' => "Thoả điều kiện\nKhông thoả điều kiện"],
                    ['field_key' => 'decision_note', 'label' => 'Ghi chú xử lý', 'type' => 'textarea', 'required' => false],
                ];
            }

            $hotLead->save();
        }

        $applicationModule = CrmModule::query()->where('slug', 'applications')->first();

        if ($applicationModule) {
            $aclMix = SalesProject::query()->firstOrNew(['slug' => 'acl-mix']);
            $aclMix->fill([
                'crm_module_id' => $applicationModule->getKey(),
                'name' => 'ACL Mix',
                'code_prefix' => 'ACL',
                'description' => $aclMix->description ?: 'Dự án xử lý hồ sơ ACL Mix trong Application.',
                'sort_order' => 10,
                'is_active' => true,
            ]);

            if (blank($aclMix->lead_form_schema)) {
                $aclMix->lead_form_schema = [
                    ['field_key' => 'customer_name', 'label' => 'Họ tên khách hàng', 'type' => 'text', 'required' => true],
                    ['field_key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'phone', 'required' => true],
                    ['field_key' => 'identity_number', 'label' => 'CCCD/CMND', 'type' => 'text', 'required' => false],
                    ['field_key' => 'loan_amount', 'label' => 'Số tiền vay', 'type' => 'number', 'required' => false],
                    ['field_key' => 'monthly_income', 'label' => 'Thu nhập tháng', 'type' => 'number', 'required' => false],
                    ['field_key' => 'address', 'label' => 'Địa chỉ chi tiết', 'type' => 'textarea', 'required' => false],
                ];
            }

            if (blank($aclMix->module_form_schema)) {
                $aclMix->module_form_schema = [
                    ['field_key' => 'verification_result', 'label' => 'Kết quả kiểm tra', 'type' => 'select', 'required' => true, 'options' => "Hợp lệ
Cần bổ sung
Không hợp lệ"],
                    ['field_key' => 'processing_note', 'label' => 'Ghi chú xử lý', 'type' => 'textarea', 'required' => false],
                    ['field_key' => 'approved_limit', 'label' => 'Hạn mức đề xuất', 'type' => 'number', 'required' => false],
                ];
            }

            $aclMix->save();

            $lotte = SalesProject::query()->firstOrNew(['slug' => 'lotte-finance']);
            $lotte->fill([
                'crm_module_id' => $applicationModule->getKey(),
                'name' => 'Lotte Finance',
                'code_prefix' => 'LOTTE',
                'description' => $lotte->description ?: 'Dự án Lotte Finance, lưu dữ liệu Lead, Scheme, tính khoản vay, OCR/eKYC và workflow API.',
                'sort_order' => 30,
                'is_active' => true,
            ]);

            $lotte->lead_form_schema = [
                ['field_key' => 'customer_name', 'label' => 'Họ tên khách hàng', 'type' => 'text', 'required' => true],
                ['field_key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'phone', 'required' => true],
                ['field_key' => 'identity_number', 'label' => 'CCCD/CMND', 'type' => 'text', 'required' => true],
                ['field_key' => 'birthday', 'label' => 'Ngày sinh', 'type' => 'date', 'required' => false],
                ['field_key' => 'scheme_code', 'label' => 'Scheme', 'type' => 'select', 'required' => true, 'options' => "AL001
4P73I
4P073"],
                ['field_key' => 'loan_amount', 'label' => 'Số tiền vay', 'type' => 'number', 'required' => true],
                ['field_key' => 'loan_term_months', 'label' => 'Tháng vay', 'type' => 'number', 'required' => true],
                ['field_key' => 'ocr_status', 'label' => 'Trạng thái OCR', 'type' => 'select', 'required' => false, 'options' => "pending
processing
success
failed"],
                ['field_key' => 'ekyc_status', 'label' => 'Trạng thái eKYC', 'type' => 'select', 'required' => false, 'options' => "pending
processing
success
failed"],
                ['field_key' => 'ocr_request_id', 'label' => 'OCR Request ID', 'type' => 'text', 'required' => false],
                ['field_key' => 'ekyc_request_id', 'label' => 'eKYC Request ID', 'type' => 'text', 'required' => false],
                ['field_key' => 'api_workflow_note', 'label' => 'Ghi chú API/OCR/eKYC', 'type' => 'textarea', 'required' => false],
            ];

            $lotte->module_form_schema = [
                ['field_key' => 'application_processing_note', 'label' => 'Ghi chú xử lý Lotte', 'type' => 'textarea', 'required' => false],
                ['field_key' => 'scheme_detail_status', 'label' => 'Trạng thái lấy chi tiết Scheme', 'type' => 'select', 'required' => false, 'options' => "Chưa gọi
Thành công
Lỗi"],
                ['field_key' => 'loan_calculation_status', 'label' => 'Trạng thái tính khoản vay', 'type' => 'select', 'required' => false, 'options' => "Chưa tính
Đã tính
Lỗi"],
                ['field_key' => 'ocr_push_status', 'label' => 'Trạng thái đẩy OCR', 'type' => 'select', 'required' => false, 'options' => "Chưa đẩy
Đã đẩy
Lỗi"],
                ['field_key' => 'ekyc_check_status', 'label' => 'Trạng thái Check DUP/eKYC', 'type' => 'select', 'required' => false, 'options' => "Chưa kiểm tra
Đã kiểm tra
Lỗi"],
            ];

            $lotte->save();

            $cbp = SalesProject::query()->firstOrNew(['slug' => 'cbp']);
            $cbp->fill([
                'crm_module_id' => $applicationModule->getKey(),
                'name' => 'CBP',
                'code_prefix' => 'CBP',
                'description' => $cbp->description ?: 'Dự án xử lý hồ sơ CBP trong Application.',
                'sort_order' => 20,
                'is_active' => true,
            ]);

            if (blank($cbp->lead_form_schema)) {
                $cbp->lead_form_schema = [
                    ['field_key' => 'customer_name', 'label' => 'Họ tên', 'type' => 'text', 'required' => true],
                    ['field_key' => 'identity_number', 'label' => 'CCCD', 'type' => 'text', 'required' => true],
                    ['field_key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'phone', 'required' => true],
                ];
            }

            if (blank($cbp->module_form_schema)) {
                $cbp->module_form_schema = [
                    ['field_key' => 'processing_note', 'label' => 'Ghi chú xử lý', 'type' => 'textarea', 'required' => false],
                ];
            }

            $cbp->save();

        }
    }
}
