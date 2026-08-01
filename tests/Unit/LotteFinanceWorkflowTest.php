<?php

namespace Tests\Unit;

use App\Support\Applications\LotteFinanceWorkflow;
use Tests\TestCase;

class LotteFinanceWorkflowTest extends TestCase
{
    public function test_sale_submission_note_prefers_module_note_when_present(): void
    {
        $payload = [
            'module_fields' => ['note' => 'Đã chuẩn bị đầy đủ chứng từ'],
            'fields' => ['note' => 'Không dùng ghi chú này'],
        ];

        $this->assertSame('Đã chuẩn bị đầy đủ chứng từ', LotteFinanceWorkflow::saleSubmissionNote($payload));
    }

    public function test_sale_submission_note_falls_back_to_default_when_missing(): void
    {
        $payload = [
            'fields' => ['customer_name' => 'Nguyễn Văn A'],
        ];

        $this->assertSame('Sale đã hoàn thiện thông tin và chứng từ.', LotteFinanceWorkflow::saleSubmissionNote($payload));
    }
}
