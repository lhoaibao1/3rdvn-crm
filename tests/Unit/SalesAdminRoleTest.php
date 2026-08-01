<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use App\Support\AdminWorkflowOverride;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\RoleHierarchy;
use Mockery;
use PHPUnit\Framework\TestCase;

class SalesAdminRoleTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_sales_admin_is_directly_below_admin_in_the_hierarchy(): void
    {
        $user = new User;
        $user->setRelation('roles', collect([(object) ['name' => 'Sales Admin']]));

        $this->assertSame('Sales Admin', RoleHierarchy::primaryRole($user));
        $this->assertSame('Admin', RoleHierarchy::ORDER[0]);
        $this->assertSame('Sales Admin', RoleHierarchy::ORDER[1]);
        $this->assertSame([
            'ZD',
            'AM',
            'Team Leader',
            'Courier Manager',
            'Courier',
            'Direct Sale',
            'Telesale',
            'CTV',
        ], RoleHierarchy::assignableRoles($user));
    }

    public function test_sales_admin_has_global_operational_access_without_admin_override(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasAnyRole')
            ->with(RoleHierarchy::OPERATIONAL_ADMIN_ROLES)
            ->andReturnTrue();
        $user->shouldReceive('hasRole')->with('Admin')->andReturnFalse();

        $this->assertTrue(RoleHierarchy::isOperationalAdmin($user));
        $this->assertTrue(AdminWorkflowOverride::required($user));
        $this->assertFalse((new ApplicationPolicy)->deleteAny($user));
    }

    public function test_sales_admin_can_process_acl_and_lotte_manual_steps(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasAnyRole')
            ->with(['Admin', 'Sales Admin'])
            ->twice()
            ->andReturnTrue();

        $acl = new class(['status' => AclMixWorkflow::UNDERWRITING]) extends Application
        {
            public function loadMissing($relations)
            {
                return $this;
            }
        };
        $acl->setRelation('salesProject', new SalesProject(['slug' => 'acl-mix']));

        $lotte = new class(['status' => LotteFinanceWorkflow::UW_CALL]) extends Application
        {
            public function loadMissing($relations)
            {
                return $this;
            }
        };
        $lotte->setRelation('salesProject', new SalesProject(['slug' => 'lotte-finance']));

        $this->assertTrue(AclMixWorkflow::canProcess($user, $acl));
        $this->assertTrue(LotteFinanceWorkflow::canProcess($user, $lotte));
    }
}
