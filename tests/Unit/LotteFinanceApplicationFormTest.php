<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LotteFinanceApplicationFormTest extends TestCase
{
    public function test_admin_review_section_exposes_pre_check_and_approval_fields(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Applications/Schemas/LotteFinanceApplicationForm.php',
        );

        foreach ([
            "Section::make('Thông tin phê duyệt / Pre-Check')",
            'AdminWorkflowOverride::active()',
            'payload.review.decision',
            'payload.review.blacklist_check',
            'payload.review.b11t_check',
            'payload.review.aml_check',
            'payload.review.pcb_check',
            'payload.review.lf_grade',
            'payload.review.ml_grade',
            'payload.review.maximum_limit',
            'payload.review.estimated_interest_rate',
            'payload.review.approved_amount',
            'payload.review.review_note',
            'payload.review.approval_note',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_lotte_review_payload_is_only_mutable_by_admin_override(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Applications/Schemas/LotteFinanceApplicationForm.php',
        );

        $this->assertStringContainsString('$canEditReview = AdminWorkflowOverride::active($user);', $source);
        $this->assertStringContainsString('self::normalizeReviewData($data[\'payload\'])', $source);
        $this->assertStringContainsString('$data[\'payload\'][\'review\'] = $existingPayload[\'review\'] ?? [];', $source);
        $this->assertStringContainsString('foreach ([\'maximum_limit\', \'approved_amount\'] as $key)', $source);
    }
}
