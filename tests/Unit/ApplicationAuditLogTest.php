<?php

namespace Tests\Unit;

use App\Support\Filament\ApplicationAuditLog;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ApplicationAuditLogTest extends TestCase
{
    public function test_it_expands_nested_payload_changes_into_field_level_audit_entries(): void
    {
        $changes = ApplicationAuditLog::changes(
            $this->updatedLog(),
            $this->statusResolver(),
        );

        self::assertSame(
            ['status', 'payload.fields.loan_amount', 'payload.review.approval_note'],
            array_column($changes, 'path'),
        );
        self::assertSame('UW Call', $changes[0]['old']);
        self::assertSame('UW Approval', $changes[0]['new']);
        self::assertSame('120.000.000 VNĐ', $changes[1]['new']);
        self::assertSame('Hồ sơ đủ điều kiện', $changes[2]['new']);
    }

    public function test_business_history_includes_transition_note_and_changed_field(): void
    {
        $summary = ApplicationAuditLog::businessSummary(
            $this->updatedLog(),
            $this->statusResolver(),
        );

        self::assertStringContainsString('Chuyển bước: UW Call → UW Approval', $summary);
        self::assertStringContainsString('Ghi chú Approval: Hồ sơ đủ điều kiện', $summary);
        self::assertStringContainsString('Số tiền vay: 100.000.000 VNĐ → 120.000.000 VNĐ', $summary);
    }

    public function test_it_renders_a_compact_expandable_and_escaped_audit_log(): void
    {
        $log = $this->updatedLog();
        $log->actor->name = 'Nguyễn <script>alert(1)</script>';

        $html = ApplicationAuditLog::render([$log], $this->statusResolver())->toHtml();

        self::assertStringContainsString('crm-audit-log', $html);
        self::assertStringContainsString('<details class="crm-audit-changes">', $html);
        self::assertStringContainsString('3 thay đổi', $html);
        self::assertStringContainsString('crm-audit-old', $html);
        self::assertStringContainsString('crm-audit-new', $html);
        self::assertStringContainsString('10.20.30.40', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_otp_audit_is_compact_and_hides_technical_metadata(): void
    {
        $changes = ApplicationAuditLog::changes((object) [
            'action' => 'updated',
            'changes' => [
                'payload' => [
                    'old' => ['review' => ['otp' => '111111']],
                    'new' => ['review' => [
                        'otp' => '222222',
                        'otp_updated_by_id' => 7,
                        'otp_updated_at' => '2026-08-02 14:00:00',
                    ]],
                ],
            ],
        ], $this->statusResolver());

        self::assertSame(['payload.review.otp'], array_column($changes, 'path'));
        self::assertSame('OTP', $changes[0]['label']);
        self::assertSame('111111', $changes[0]['old']);
        self::assertSame('222222', $changes[0]['new']);
    }

    public function test_application_view_exposes_processing_history_documents_and_audit_log(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Applications/Schemas/ApplicationInfolist.php',
        );

        self::assertStringContainsString("Tab::make('Chứng từ')", $source);
        self::assertStringContainsString("Section::make('Thư mục chứng từ')", $source);
        self::assertStringContainsString("->visible(fn (Application \$record): bool => \$record->salesProject?->slug === 'lotte-finance')", $source);
        self::assertStringContainsString("Tab::make('Lịch sử xử lý')", $source);
        self::assertStringContainsString("Tab::make('Audit Log')", $source);
        self::assertStringContainsString('DocumentPreview::lotteDocuments(', $source);
        self::assertStringContainsString('ApplicationAuditLog::businessSummary(', $source);
        self::assertStringContainsString('self::historyBody($log, $record)', $source);
        self::assertStringContainsString('ApplicationAuditLog::render(', $source);
    }

    private function updatedLog(): object
    {
        return (object) [
            'action' => 'updated',
            'actor' => (object) [
                'name' => 'Nguyễn Văn Sale',
                'uid' => 'UID2607001',
                'employee_code' => null,
                'email' => 'sale@3rdvn.io.vn',
            ],
            'created_at' => CarbonImmutable::parse('2026-08-02 13:20:30'),
            'ip_address' => '10.20.30.40',
            'changes' => [
                'status' => [
                    'old' => 'uw_call',
                    'new' => 'uw_approval',
                ],
                'payload' => [
                    'old' => json_encode([
                        'fields' => ['loan_amount' => 100000000],
                        'review' => ['approval_note' => null],
                    ], JSON_UNESCAPED_UNICODE),
                    'new' => json_encode([
                        'fields' => ['loan_amount' => 120000000],
                        'review' => ['approval_note' => 'Hồ sơ đủ điều kiện'],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];
    }

    private function statusResolver(): callable
    {
        return fn (?string $status): string => [
            'uw_call' => 'UW Call',
            'uw_approval' => 'UW Approval',
        ][$status] ?? ($status ?: '-');
    }
}
