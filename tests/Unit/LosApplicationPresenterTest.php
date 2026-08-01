<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\LosApplicationPresenter;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class LosApplicationPresenterTest extends TestCase
{
    public function test_it_presents_lotte_application_fields_for_los(): void
    {
        $application = new Application;
        $application->setDateFormat('Y-m-d H:i:s');
        $application->forceFill([
            'id' => 18,
            'application_code' => 'LOTTE-0018',
            'applicant_name' => 'Nguyễn Văn A',
            'identity_number' => '012345678901',
            'payload' => [
                'fields' => [
                    'scheme_product' => 'Cash Loan',
                    'scheme_code' => 'SC-01',
                    'loan_amount' => '30.000.000',
                ],
            ],
            'created_at' => Carbon::parse('2026-07-30 08:15:00'),
            'updated_at' => Carbon::parse('2026-07-31 09:20:00'),
        ]);
        $application->setRelation('salesProject', (new SalesProject)->forceFill(['name' => 'Lotte Finance', 'slug' => 'lotte-finance']));
        $application->setRelation('createdBy', (new User)->forceFill(['name' => 'Sale One']));

        $result = LosApplicationPresenter::make($application);

        $this->assertSame('LOTTE-0018', $result['application_code']);
        $this->assertSame('Lotte Finance', $result['project']);
        $this->assertSame('Nguyễn Văn A', $result['applicant_name']);
        $this->assertSame('012345678901', $result['identity_number']);
        $this->assertSame('Cash Loan', $result['product']);
        $this->assertSame('SC-01', $result['scheme']);
        $this->assertSame(30000000, $result['loan_amount']);
        $this->assertSame('Sale One', $result['creator']);
        $this->assertSame('08:15 30/07/2026', $result['created_at']);
        $this->assertSame('09:20 31/07/2026', $result['updated_at']);

        $applicationFields = collect($result['application_fields'])->keyBy('label');

        $this->assertSame('Cash Loan', $applicationFields['Sản phẩm']['value']);
        $this->assertSame('30.000.000 VNĐ', $applicationFields['Số tiền vay']['value']);
        $this->assertArrayHasKey('Số tiền được phê duyệt', $applicationFields->all());
        $this->assertArrayHasKey('Pre-Check', $applicationFields->all());
    }

    public function test_it_falls_back_to_legacy_application_payload(): void
    {
        $application = new Application;
        $application->forceFill([
            'id' => 20,
            'application_code' => 'ACL-0020',
            'payload' => [
                'module_fields' => [
                    'customer_name' => 'Trần Thị B',
                    'cccd' => '987654321',
                ],
                'review' => [
                    'product' => 'ACL Mix',
                    'pre_approved_amount' => 15000000,
                    'pre_approved_months' => 24,
                    'pre_approved_interest_rate' => 18.5,
                ],
            ],
        ]);
        $application->setRelation('salesProject', (new SalesProject)->forceFill(['name' => 'ACL Mix', 'slug' => 'acl-mix']));
        $application->setRelation('createdBy', null);

        $result = LosApplicationPresenter::make($application);

        $this->assertSame('Trần Thị B', $result['applicant_name']);
        $this->assertSame('987654321', $result['identity_number']);
        $this->assertSame('ACL Mix', $result['product']);
        $this->assertSame(15000000, $result['loan_amount']);
        $this->assertSame('-', $result['scheme']);
        $this->assertSame('-', $result['creator']);

        $summary = collect($result['summary_fields'])->keyBy('label');
        $applicationFields = collect($result['application_fields'])->keyBy('label');

        $this->assertSame('15.000.000 VNĐ', $summary['Số tiền phê duyệt sơ bộ']['value']);
        $this->assertSame('24 tháng', $summary['Thời hạn phê duyệt']['value']);
        $this->assertSame('18.5%', $summary['Lãi suất phê duyệt']['value']);
        $this->assertSame('ACL Mix', $applicationFields['Sản phẩm']['value']);
        $this->assertArrayNotHasKey('Scheme', $applicationFields->all());
        $this->assertArrayNotHasKey('Lead ID', $applicationFields->all());
    }
}
