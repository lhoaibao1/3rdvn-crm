<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use PHPUnit\Framework\TestCase;

class ReturnedToSaleResumeTest extends TestCase
{
    public function test_acl_returned_to_sale_resumes_to_recorded_source_step(): void
    {
        $application = new Application([
            'status' => AclMixWorkflow::RETURNED_TO_SALE,
            'payload' => [
                'workflow' => [
                    'return_to_sale' => ['resume_to' => AclMixWorkflow::OTP_REQUIRED],
                ],
            ],
        ]);

        $this->assertSame(AclMixWorkflow::OTP_REQUIRED, AclMixWorkflow::resumeStatusAfterSaleReturn($application));

        $legacyApplication = new Application([
            'status' => AclMixWorkflow::RETURNED_TO_SALE,
            'payload' => [
                'workflow' => [
                    'last_transition' => [
                        'from' => AclMixWorkflow::CUSTOMER_CAPP,
                        'to' => AclMixWorkflow::RETURNED_TO_SALE,
                    ],
                ],
            ],
        ]);

        $this->assertSame(AclMixWorkflow::CUSTOMER_CAPP, AclMixWorkflow::resumeStatusAfterSaleReturn($legacyApplication));
    }

    public function test_acl_returned_to_sale_falls_back_to_call_recording_when_source_is_missing(): void
    {
        $application = new Application([
            'status' => AclMixWorkflow::RETURNED_TO_SALE,
            'payload' => ['workflow' => []],
        ]);

        $this->assertSame(AclMixWorkflow::CALL_RECORDING, AclMixWorkflow::resumeStatusAfterSaleReturn($application));
    }

    public function test_lotte_returned_to_sale_resumes_to_recorded_source_step(): void
    {
        $application = new Application([
            'status' => LotteFinanceWorkflow::RETURNED_TO_SALE,
            'payload' => [
                'workflow' => [
                    'return_to_sale' => ['resume_to' => LotteFinanceWorkflow::ESIGN],
                ],
            ],
        ]);

        $this->assertSame(LotteFinanceWorkflow::ESIGN, LotteFinanceWorkflow::resumeStatusAfterSaleReturn($application));

        $legacyApplication = new Application([
            'status' => LotteFinanceWorkflow::RETURNED_TO_SALE,
            'payload' => [
                'workflow' => [
                    'last_transition' => [
                        'from' => LotteFinanceWorkflow::POST_APPROVAL,
                        'to' => LotteFinanceWorkflow::RETURNED_TO_SALE,
                    ],
                ],
            ],
        ]);

        $this->assertSame(LotteFinanceWorkflow::POST_APPROVAL, LotteFinanceWorkflow::resumeStatusAfterSaleReturn($legacyApplication));
    }
}
