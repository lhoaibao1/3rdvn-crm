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

        self::assertStringContainsString('Thông tin đăng ký', $form);
        self::assertStringContainsString('width:min(100%,760px)', $landing);
        self::assertStringContainsString('width: min(100%, 760px);', file_get_contents($root.'resources/views/filament/feol/create-header.blade.php'));
        self::assertStringContainsString('applications.fe-deeplink.partner-v1', $resource);
        self::assertStringContainsString("? 'Tạo khách hàng' : 'Tạo hồ sơ'", $table);
        self::assertStringContainsString("->label('Copy link')", $table);
        self::assertStringContainsString("TextColumn::make('fe_customer_name')", $table);
        self::assertStringContainsString("TextColumn::make('fe_customer_phone')", $table);
        self::assertStringContainsString("Checkbox::make('payload.fields.customer_consent')", $form);
        self::assertStringContainsString('class="consent-mark"', $landing);
        self::assertStringContainsString('.consent{position:relative;display:flex;gap:10px;align-items:flex-start;margin:0;', $landing);
        self::assertStringContainsString('<h1 class="page-heading">Đăng ký khoản vay</h1>', $landing);
        self::assertStringContainsString('input:not([type="checkbox"]):not([type="radio"])', file_get_contents($root.'resources/views/filament/feol/create-header.blade.php'));
        self::assertStringContainsString('input[type="checkbox"]', file_get_contents($root.'resources/views/filament/feol/create-header.blade.php'));
        self::assertStringContainsString('data-money-mask', $landing);
        self::assertStringContainsString('RawJs::make("\\$money(\\$input, \',\', \'.\', 0)")', $form);
        self::assertStringContainsString("->stripCharacters('.')", $form);
        self::assertStringContainsString("->accepted()\n                            ->required()", $form);
        foreach (['payload.fields.date_of_birth', 'payload.fields.email', 'payload.fields.loan_amount', 'payload.fields.loan_term_months'] as $requiredField) {
            self::assertStringContainsString("make('{$requiredField}')", $form);
        }
        self::assertStringNotContainsString('name="referral_code"', $landing);
        self::assertStringContainsString("data_get(auth()->user()->sales_codes, 'fe-deeplink')", $form);

        foreach (['fee_amount', 'note'] as $partnerField) {
            self::assertStringContainsString("'{$partnerField}'", $request);
            self::assertStringContainsString("'{$partnerField}'", $sync);
        }
        self::assertStringNotContainsString("'pic' =>", $request);
        self::assertStringNotContainsString("'pic' => 'pic'", $sync);
    }
}
