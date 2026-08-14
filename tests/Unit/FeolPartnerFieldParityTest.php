<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FeolPartnerFieldParityTest extends TestCase
{
    public function test_crm_and_public_landing_use_the_partner_field_titles(): void
    {
        $root = dirname(__DIR__, 2).'/';
        $form = file_get_contents($root.'app/Filament/Resources/FeDeeplinkApplications/Schemas/FeDeeplinkApplicationForm.php');
        $landing = file_get_contents($root.'resources/views/feol/landing.blade.php');

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
            self::assertStringContainsString($title, $form);
            self::assertStringContainsString($title, $landing);
        }

        self::assertStringNotContainsString('name="referral_code"', $landing);
        self::assertStringContainsString("data_get(auth()->user()->sales_codes, 'fe-deeplink')", $form);
    }
}
