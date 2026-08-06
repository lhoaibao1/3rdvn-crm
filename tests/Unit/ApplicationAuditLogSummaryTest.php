<?php

namespace Tests\Unit;

use App\Support\Filament\ApplicationAuditLog;
use PHPUnit\Framework\TestCase;

class ApplicationAuditLogSummaryTest extends TestCase
{
    public function test_return_to_sale_summary_shows_resume_step(): void
    {
        $log = (object) [
            'action' => 'updated',
            'changes' => [
                'status' => ['old' => 'customer_capp', 'new' => 'returned_to_sale'],
                'payload' => [
                    'old' => [],
                    'new' => [
                        'workflow' => [
                            'return_to_sale' => [
                                'from' => 'customer_capp',
                                'resume_to' => 'customer_capp',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $summary = ApplicationAuditLog::businessSummary($log, fn (?string $status): string => match ($status) {
            'customer_capp' => 'Khách hàng thao tác CAPP',
            'returned_to_sale' => 'Trả về Sale',
            default => (string) $status,
        });

        $this->assertStringContainsString('Trả về Sale: Khách hàng thao tác CAPP → Trả về Sale', $summary);
        $this->assertStringContainsString('Bước bị trả về: Khách hàng thao tác CAPP', $summary);
        $this->assertStringContainsString('Bước sẽ quay lại sau khi Sale cập nhật: Khách hàng thao tác CAPP', $summary);
    }

    public function test_sale_resubmission_summary_shows_return_to_previous_step(): void
    {
        $log = (object) [
            'action' => 'updated',
            'changes' => [
                'status' => ['old' => 'returned_to_sale', 'new' => 'customer_capp'],
            ],
        ];

        $summary = ApplicationAuditLog::businessSummary($log, fn (?string $status): string => match ($status) {
            'customer_capp' => 'Khách hàng thao tác CAPP',
            'returned_to_sale' => 'Trả về Sale',
            default => (string) $status,
        });

        $this->assertSame('Quay về bước trước khi trả: Trả về Sale → Khách hàng thao tác CAPP', $summary);
    }
}
