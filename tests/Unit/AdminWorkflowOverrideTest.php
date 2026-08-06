<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use App\Policies\ProjectReportPolicy;
use App\Support\AdminWorkflowOverride;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use Mockery;
use PHPUnit\Framework\TestCase;

class AdminWorkflowOverrideTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_admin_can_skip_required_fields_but_keeps_regular_transition_options(): void
    {
        $admin = $this->user(true);
        $options = [
            'draft' => 'Draft',
            'done' => 'Done',
            'rejected' => 'Rejected',
        ];

        $this->assertFalse(AdminWorkflowOverride::required($admin));
        $this->assertSame(
            ['done' => 'Done'],
            AdminWorkflowOverride::transitionOptions($options, ['done' => 'Done'], 'draft', $admin),
        );
    }

    public function test_non_admin_keeps_regular_transition_rules(): void
    {
        $user = $this->user(false);

        $this->assertTrue(AdminWorkflowOverride::required($user));
        $this->assertSame(
            ['done' => 'Done'],
            AdminWorkflowOverride::transitionOptions(
                ['draft' => 'Draft', 'done' => 'Done', 'rejected' => 'Rejected'],
                ['done' => 'Done'],
                'draft',
                $user,
            ),
        );
    }

    public function test_admin_can_skip_business_fields_only_on_valid_workflow_transitions(): void
    {
        $admin = $this->user(true);
        $acl = new Application(['status' => AclMixWorkflow::CUSTOMER_CAPP]);
        $acl->setRelation('salesProject', new SalesProject(['slug' => 'acl-mix']));
        $lotte = new Application(['status' => LotteFinanceWorkflow::POST_APPROVAL]);
        $lotte->setRelation('salesProject', new SalesProject(['slug' => 'lotte-finance']));

        $aclMethod = new \ReflectionMethod(AclMixWorkflow::class, 'validateTransition');
        $aclMethod->invoke(null, $acl, AclMixWorkflow::SALE_COMPLETION, [], $admin);

        $lotteMethod = new \ReflectionMethod(LotteFinanceWorkflow::class, 'validateTransition');
        $lotteMethod->invoke(null, $lotte, LotteFinanceWorkflow::DISBURSED, $admin);

        $this->addToAssertionCount(2);
    }

    public function test_lotte_regular_workflow_uses_the_required_transitions(): void
    {
        $user = $this->user(false);
        $method = new \ReflectionMethod(LotteFinanceWorkflow::class, 'validateTransition');
        $transitions = [
            [LotteFinanceWorkflow::UW_CALL, LotteFinanceWorkflow::UW_APPROVAL],
            [LotteFinanceWorkflow::UW_CALL, LotteFinanceWorkflow::UW_REJECTED],
            [LotteFinanceWorkflow::UW_CALL, LotteFinanceWorkflow::UW_FIELD],
            [LotteFinanceWorkflow::UW_CALL, LotteFinanceWorkflow::RETURNED_TO_SALE],
            [LotteFinanceWorkflow::UW_APPROVAL, LotteFinanceWorkflow::ESIGN],
            [LotteFinanceWorkflow::UW_APPROVAL, LotteFinanceWorkflow::RETURNED_TO_SALE],
            [LotteFinanceWorkflow::ESIGN, LotteFinanceWorkflow::POST_APPROVAL],
            [LotteFinanceWorkflow::ESIGN, LotteFinanceWorkflow::RETURNED_TO_SALE],
            [LotteFinanceWorkflow::POST_APPROVAL, LotteFinanceWorkflow::DISBURSED],
            [LotteFinanceWorkflow::POST_APPROVAL, LotteFinanceWorkflow::RETURNED_TO_SALE],
        ];

        foreach ($transitions as [$from, $to]) {
            $application = new Application(['status' => $from]);
            $application->setRelation(
                'salesProject',
                new SalesProject(['slug' => 'lotte-finance']),
            );
            $method->invoke(null, $application, $to, $user);
        }

        $this->addToAssertionCount(count($transitions));
    }

    public function test_admin_delete_policies_allow_single_and_bulk_deletes(): void
    {
        $admin = $this->user(true);

        $this->assertTrue((new ApplicationPolicy)->deleteAny($admin));
        $this->assertTrue((new ProjectReportPolicy)->deleteAny($admin));
    }

    private function user(bool $isAdmin): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasRole')->with('Admin')->andReturn($isAdmin);
        $user->shouldReceive('hasAnyRole')->with(['Admin', 'Sales Admin'])->andReturn($isAdmin);

        return $user;
    }
}
