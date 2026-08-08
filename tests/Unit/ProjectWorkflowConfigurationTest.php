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

    public function test_uat_configuration_can_limit_manual_transitions(): void
    {
        $project = new SalesProject([
            'slug' => 'acl-mix',
            'workflow_schema' => [[
                'status' => AclMixWorkflow::UNDERWRITING,
                'next_statuses' => [AclMixWorkflow::AWAITING_CONTRACT],
            ]],
        ]);

        $this->assertSame([
            AclMixWorkflow::AWAITING_CONTRACT => 'Chờ khách hàng ký hợp đồng',
            AclMixWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            AclMixWorkflow::UNDERWRITING,
        ));
    }

    public function test_workflow_configuration_is_grouped_under_admin_path(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/WorkflowConfigurations/WorkflowConfigurationResource.php',
        );

        $this->assertStringContainsString('protected static ?string $slug = \'admin/workflow-configurations\';', $source);
        $this->assertStringContainsString("return 'Admin';", $source);
    }

    public function test_return_to_sale_steps_are_dynamic_resume_steps(): void
    {
        $this->assertTrue(ProjectWorkflowConfiguration::isDynamicReturnStep(AclMixWorkflow::RETURNED_TO_SALE));
        $this->assertTrue(ProjectWorkflowConfiguration::isDynamicReturnStep(LotteFinanceWorkflow::RETURNED_TO_SALE));
        $this->assertFalse(ProjectWorkflowConfiguration::isDynamicReturnStep(AclMixWorkflow::CUSTOMER_CAPP));
    }

    public function test_acl_special_steps_can_be_configured_from_uat(): void
    {
        $project = new SalesProject([
            'slug' => 'acl-mix',
            'workflow_schema' => [[
                'status' => AclMixWorkflow::OTP_REQUIRED,
                'next_statuses' => [AclMixWorkflow::RETURNED_TO_SALE],
            ]],
        ]);

        $this->assertSame([
            AclMixWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            AclMixWorkflow::OTP_REQUIRED,
        ));
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

    public function test_automatic_and_terminal_steps_cannot_be_overridden(): void
    {
        $project = new SalesProject([
            'slug' => 'lotte-finance',
            'workflow_schema' => [
                [
                    'status' => LotteFinanceWorkflow::SALE_COMPLETION,
                    'next_statuses' => [LotteFinanceWorkflow::DISBURSED],
                ],
                [
                    'status' => LotteFinanceWorkflow::DISBURSED,
                    'next_statuses' => [LotteFinanceWorkflow::UW_CALL],
                ],
            ],
        ]);

        $this->assertSame([
            LotteFinanceWorkflow::UW_CALL => 'UW Call',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            LotteFinanceWorkflow::SALE_COMPLETION,
        ));
        $this->assertSame([], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            LotteFinanceWorkflow::DISBURSED,
        ));
    }
}
