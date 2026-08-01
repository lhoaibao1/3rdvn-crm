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
            AclMixWorkflow::SALE_COMPLETION => 'Chờ Sale hoàn thiện thông tin',
            AclMixWorkflow::REJECTED => 'Từ chối',
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
                'next_statuses' => [AclMixWorkflow::RETURNED_TO_SALE],
            ]],
        ]);

        $this->assertSame([
            AclMixWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], ProjectWorkflowConfiguration::nextStatusOptions(
            $project,
            AclMixWorkflow::UNDERWRITING,
        ));
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
