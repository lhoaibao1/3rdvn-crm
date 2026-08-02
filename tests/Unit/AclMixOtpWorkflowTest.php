<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\AclMixWorkflow;
use PHPUnit\Framework\TestCase;

class AclMixOtpWorkflowTest extends TestCase
{
    public function test_otp_can_only_be_updated_while_acl_mix_is_pending_initial_review(): void
    {
        $admin = new class extends User
        {
            public function hasAnyRole(...$roles): bool
            {
                return true;
            }
        };
        $application = new Application(['status' => AclMixWorkflow::PENDING_INITIAL_REVIEW]);
        $application->setRelation('salesProject', new SalesProject(['slug' => 'acl-mix']));

        $this->assertTrue(AclMixWorkflow::canUpdateOtp($admin, $application));

        $application->status = AclMixWorkflow::CALL_RECORDING;

        $this->assertFalse(AclMixWorkflow::canUpdateOtp($admin, $application));
    }
}
