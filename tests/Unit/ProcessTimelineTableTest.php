<?php

namespace Tests\Unit;

use App\Support\Filament\ProcessTimeline;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ProcessTimelineTableTest extends TestCase
{
    public function test_it_renders_a_searchable_paginated_processing_history_table_in_chronological_order(): void
    {
        $actor = (object) [
            'name' => 'Nguyễn Văn Sale',
            'uid' => 'UID2607001',
            'employee_code' => null,
            'email' => 'sale@3rdvn.io.vn',
        ];
        $updated = (object) [
            'action' => 'updated',
            'actor' => $actor,
            'created_at' => CarbonImmutable::parse('2026-07-28 11:10:00'),
        ];
        $created = (object) [
            'action' => 'created',
            'actor' => $actor,
            'created_at' => CarbonImmutable::parse('2026-07-28 11:00:00'),
        ];

        $html = ProcessTimeline::render(
            [$updated, $created],
            fn (object $log): string => $log->action === 'created' ? 'Tạo mới hồ sơ' : 'Cập nhật hồ sơ',
            fn (object $log): string => $log->action === 'created' ? 'Trạng thái: Mới' : 'Ghi chú: <script>alert(1)</script>',
            fn (object $log): array => $log->action === 'created'
                ? ['label' => 'Tạo mới', 'color' => '#2563eb', 'soft' => '#dbeafe', 'border' => '#bfdbfe']
                : ['label' => 'Cập nhật', 'color' => '#475569', 'soft' => '#f1f5f9', 'border' => '#cbd5e1'],
        )->toHtml();

        self::assertStringContainsString('crm-history-table', $html);
        self::assertStringContainsString('Tìm trong lịch sử...', $html);
        self::assertStringContainsString('x-model.number="perPage"', $html);
        self::assertStringContainsString('Nội dung xử lý', $html);
        self::assertStringContainsString('Người xử lý', $html);
        self::assertStringContainsString('Thời lượng', $html);
        self::assertStringContainsString('10 phút', $html);
        self::assertStringContainsString('Nguyễn Văn Sale', $html);
        self::assertStringContainsString('UID2607001', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('alert(1)', $html);
        self::assertLessThan(strpos($html, 'Cập nhật hồ sơ'), strpos($html, 'Tạo mới hồ sơ'));
    }

    public function test_it_renders_a_clean_empty_state(): void
    {
        $html = ProcessTimeline::render([], fn (): string => '', fn (): string => '', fn (): array => [], 'Chưa có lịch sử thay đổi.')->toHtml();

        self::assertStringContainsString('crm-history-empty', $html);
        self::assertStringContainsString('Chưa có hoạt động', $html);
        self::assertStringContainsString('Chưa có lịch sử thay đổi.', $html);
    }

    public function test_all_processing_history_screens_use_the_shared_table_renderer(): void
    {
        $root = dirname(__DIR__, 2).'/app/Filament/Resources/';

        foreach ([
            'Applications/Schemas/ApplicationInfolist.php',
            'Leads/Schemas/LeadInfolist.php',
            'HotLeads/Schemas/HotLeadInfolist.php',
            'DataCenterLeads/Schemas/DataCenterLeadInfolist.php',
            'Users/Schemas/UserInfolist.php',
        ] as $screen) {
            $source = file_get_contents($root.$screen);

            self::assertStringContainsString('ProcessTimeline::render(', $source, $screen);
        }
    }
}
