<?php

namespace Tests\Unit;

use App\Models\SalesProject;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Applications\ProjectWorkflowConfiguration;
use PHPUnit\Framework\TestCase;

class ProjectWorkflowConfigurationTest extends TestCase
{
    public function test_acl_defaults_expose_the_real_transition_matrix(): void
    {
        $project = new SalesProject(['slug' => 'acl-mix']);

        $this->assertSame([
            AclMixWorkflow::INELIGIBLE => 'Không thoả điều kiện',
            AclMixWorkflow::OTP_REQUIRED => 'Đang kiểm tra',
            AclMixWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            AclMixWorkflow::PENDING_INITIAL_REVIEW,
        ));

        $this->assertSame([], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            AclMixWorkflow::COMPLETED,
        ));
    }

    public function test_admin_configuration_is_the_authoritative_workflow(): void
    {
        $project = new SalesProject([
            'slug' => 'acl-mix',
            'workflow_schema' => [
                [
                    'status' => 'custom_review',
                    'label' => 'Duyệt tùy chỉnh',
                    'mode' => ProjectWorkflowConfiguration::MANUAL,
                    'note' => 'Admin tạo trực tiếp trên PROD.',
                    'next_statuses' => ['custom_done'],
                ],
                [
                    'status' => 'custom_done',
                    'label' => 'Đã xong tùy chỉnh',
                    'mode' => ProjectWorkflowConfiguration::TERMINAL,
                    'note' => '',
                    'next_statuses' => [],
                ],
            ],
        ]);

        $this->assertSame([
            'custom_done' => 'Đã xong tùy chỉnh',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            'custom_review',
        ));
        $this->assertCount(2, ProjectWorkflowConfiguration::forProject($project));
    }

    public function test_return_to_sale_steps_are_dynamic_resume_steps(): void
    {
        $this->assertTrue(ProjectWorkflowConfiguration::isDynamicReturnStep(AclMixWorkflow::RETURNED_TO_SALE));
        $this->assertTrue(ProjectWorkflowConfiguration::isDynamicReturnStep(LotteFinanceWorkflow::RETURNED_TO_SALE));
        $this->assertFalse(ProjectWorkflowConfiguration::isDynamicReturnStep(AclMixWorkflow::CUSTOMER_CAPP));
    }

    public function test_status_fields_are_fully_persisted_and_normalized(): void
    {
        $project = new SalesProject([
            'slug' => 'acl-mix',
            'workflow_schema' => [
                [
                    'status' => 'first',
                    'label' => 'Bước đầu',
                    'mode' => ProjectWorkflowConfiguration::SPECIAL,
                    'note' => 'Quy tắc riêng',
                    'next_statuses' => ['second', 'missing', 'first'],
                ],
                [
                    'status' => 'second',
                    'label' => 'Bước cuối',
                    'mode' => ProjectWorkflowConfiguration::TERMINAL,
                    'note' => '',
                    'next_statuses' => [],
                ],
            ],
        ]);

        $stored = ProjectWorkflowConfiguration::normalizeForStorage($project);

        $this->assertSame('Bước đầu', $stored[0]['label']);
        $this->assertSame(ProjectWorkflowConfiguration::SPECIAL, $stored[0]['mode']);
        $this->assertSame('Quy tắc riêng', $stored[0]['note']);
        $this->assertSame(['second'], $stored[0]['next_statuses']);
    }

    public function test_every_processable_acl_step_can_return_to_sale(): void
    {
        $project = new SalesProject(['slug' => 'acl-mix']);

        foreach (AclMixWorkflow::returnableStatuses() as $status) {
            $this->assertArrayHasKey(
                AclMixWorkflow::RETURNED_TO_SALE,
                ProjectWorkflowConfiguration::nextStatusOptions($project, $status),
                'Thiếu Trả về Sale ở trạng thái '.$status,
            );
        }

        foreach ([AclMixWorkflow::INELIGIBLE, AclMixWorkflow::COMPLETED, AclMixWorkflow::REJECTED] as $status) {
            $this->assertSame([], ProjectWorkflowConfiguration::nextStatusOptions($project, $status));
        }
    }

    public function test_admin_can_configure_automatic_and_terminal_transitions(): void
    {
        $project = new SalesProject([
            'slug' => 'lotte-finance',
            'workflow_schema' => [
                [
                    'status' => LotteFinanceWorkflow::SALE_COMPLETION,
                    'label' => 'Tự động tùy chỉnh',
                    'mode' => ProjectWorkflowConfiguration::AUTOMATIC,
                    'note' => '',
                    'next_statuses' => [LotteFinanceWorkflow::DISBURSED],
                ],
                [
                    'status' => LotteFinanceWorkflow::DISBURSED,
                    'label' => 'Giải ngân tùy chỉnh',
                    'mode' => ProjectWorkflowConfiguration::TERMINAL,
                    'note' => '',
                    'next_statuses' => [LotteFinanceWorkflow::UW_CALL],
                ],
                [
                    'status' => LotteFinanceWorkflow::UW_CALL,
                    'label' => 'UW Call',
                    'mode' => ProjectWorkflowConfiguration::MANUAL,
                    'note' => '',
                    'next_statuses' => [],
                ],
            ],
        ]);

        $this->assertSame([
            LotteFinanceWorkflow::DISBURSED => 'Giải ngân tùy chỉnh',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            LotteFinanceWorkflow::SALE_COMPLETION,
        ));
        $this->assertSame([
            LotteFinanceWorkflow::UW_CALL => 'UW Call',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            LotteFinanceWorkflow::DISBURSED,
        ));
    }
}
