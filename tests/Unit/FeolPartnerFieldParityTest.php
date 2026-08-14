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

        foreach (['Họ tên', 'SĐT', 'Số CCCD', 'Ngày tháng năm sinh', 'Địa chỉ Email', 'Số tiền vay', 'Thời hạn vay (tháng)', 'Mã giới thiệu', 'Mã nhân viên', 'Ngày tạo', 'Ngày giải ngân', 'Sản phẩm', 'Số tiền duyệt', 'App ID', 'Tên nhân viên (Tạo bởi)', 'Hành động'] as $title) {
            self::assertStringContainsString($title, $table);
        }

        self::assertStringContainsString('Thông tin khởi tạo Landing Page B1', $form);
        self::assertStringContainsString("->hiddenOn('create')", $form);
        self::assertStringNotContainsString('name="referral_code"', $landing);
        self::assertStringContainsString("data_get(auth()->user()->sales_codes, 'fe-deeplink')", $form);
    }
}
