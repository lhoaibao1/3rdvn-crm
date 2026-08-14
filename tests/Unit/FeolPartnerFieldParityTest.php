<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FeolPartnerFieldParityTest extends TestCase
{
    public function test_public_landing_uses_partner_field_titles_and_crm_list_uses_operational_titles(): void
    {
        $root = dirname(__DIR__, 2).'/';
        $form = file_get_contents($root.'app/Filament/Resources/FeDeeplinkApplications/Schemas/FeDeeplinkApplicationForm.php');
        $landing = file_get_contents($root.'resources/views/feol/landing.blade.php');
        $table = file_get_contents($root.'app/Filament/Resources/Applications/Tables/ApplicationsTable.php');
        $resource = file_get_contents($root.'app/Filament/Resources/Applications/ApplicationResource.php');
        $request = file_get_contents($root.'app/Http/Requests/Integration/SyncFeolApplicationRequest.php');
        $sync = file_get_contents($root.'app/Support/Applications/FeolApplicationSync.php');

        foreach ([
            'Họ và tên',
            'Số điện thoại',
            'Số CCCD',
            'Ngày tháng năm sinh',
            'Địa chỉ Email',
            'Số tiền vay',
            'Thời hạn vay (tháng)',
            'Mã giới thiệu',
            'Mã nhân viên',
        ] as $title) {
            self::assertStringContainsString($title, $landing);
        }

        foreach (['ID', 'Chiến dịch', 'Tên khách hàng', 'Số điện thoại', 'Nhân viên', 'Quản lý', 'Mã giới thiệu', 'Trạng thái chính', 'Trạng thái phụ', 'App id', 'App type', 'Offer Amt', 'Disbursed Amt', 'Topup Amt', 'Insurance Amt', 'Fee Amt', 'Disbursed Date', 'Ghi chú', 'PIC', 'Thời gian cập nhật', 'Hành động'] as $title) {
            self::assertStringContainsString($title, $table);
        }

        self::assertStringContainsString('Thông tin khởi tạo Landing Page B1', $form);
        self::assertStringContainsString('applications.fe-deeplink.partner-v1', $resource);
        self::assertStringContainsString("? 'Thêm KH' : 'Tạo hồ sơ'", $table);
        self::assertStringContainsString("TextColumn::make('fe_customer_name')", $table);
        self::assertStringContainsString("TextColumn::make('fe_customer_phone')", $table);
        self::assertStringContainsString("->hiddenOn('create')", $form);
        self::assertStringNotContainsString('name="referral_code"', $landing);
        self::assertStringContainsString("data_get(auth()->user()->sales_codes, 'fe-deeplink')", $form);

        foreach (['fee_amount', 'note', 'pic'] as $partnerField) {
            self::assertStringContainsString("'{$partnerField}'", $request);
            self::assertStringContainsString("'{$partnerField}'", $sync);
        }
    }
}
