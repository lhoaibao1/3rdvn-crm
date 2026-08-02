<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\AclMixWorkflow;
use PHPUnit\Framework\TestCase;

class AclMixOtpWorkflowTest extends TestCase
{
    public function test_otp_can_only_be_updated_while_acl_mix_is_waiting_for_otp(): void
    {
        $admin = new class extends User
        {
            public function hasAnyRole(...$roles): bool
            {
                return true;
            }
        };
        $application = new Application(['status' => AclMixWorkflow::OTP_REQUIRED]);
        $application->setRelation('salesProject', new SalesProject(['slug' => 'acl-mix']));

        $this->assertTrue(AclMixWorkflow::canUpdateOtp($admin, $application));

        $application->status = AclMixWorkflow::CALL_RECORDING;

        $this->assertFalse(AclMixWorkflow::canUpdateOtp($admin, $application));
    }

    public function test_acl_initial_otp_and_capp_transitions_use_the_required_labels(): void
    {
        $application = new Application(['status' => AclMixWorkflow::PENDING_INITIAL_REVIEW]);
        $application->setRelation('salesProject', new SalesProject(['slug' => 'acl-mix']));

        $this->assertSame([
            AclMixWorkflow::INELIGIBLE => 'Không thoả điều kiện',
            AclMixWorkflow::OTP_REQUIRED => 'Yêu cầu OTP',
        ], AclMixWorkflow::nextStatusOptions($application));

        $application->status = AclMixWorkflow::OTP_REQUIRED;

        $this->assertSame([
            AclMixWorkflow::CUSTOMER_CAPP => 'Khách hàng thao tác CAPP',
        ], AclMixWorkflow::nextStatusOptions($application));

        $application->status = AclMixWorkflow::CUSTOMER_CAPP;

        $this->assertSame([
            AclMixWorkflow::SALE_COMPLETION => 'Khách hàng thoả mãn điều kiện',
            AclMixWorkflow::REJECTED => 'Từ chối',
        ], AclMixWorkflow::nextStatusOptions($application));

        $application->status = AclMixWorkflow::INELIGIBLE;

        $this->assertSame([], AclMixWorkflow::nextStatusOptions($application));
        $this->assertSame('Chờ kiểm tra', AclMixWorkflow::statusLabel(AclMixWorkflow::PENDING_INITIAL_REVIEW));
        $this->assertSame('Đang kiểm tra', AclMixWorkflow::statusLabel(AclMixWorkflow::OTP_REQUIRED));
        $this->assertSame('Khách hàng thao tác CAPP', AclMixWorkflow::statusLabel(AclMixWorkflow::CUSTOMER_CAPP));
    }
}
