<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Support\Applications\ApplicationFinancialData;
use App\Support\Applications\LotteFinanceWorkflow;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ApplicationDisbursementDateTest extends TestCase
{
    public function test_it_resolves_real_disbursement_dates_for_supported_projects(): void
    {
        $manual = new Application([
            'status' => 'approved',
            'payload' => ['fields' => ['disbursed_at' => '2026-08-14']],
        ]);
        $acl = new Application([
            'status' => 'completed',
            'payload' => ['workflow' => ['completed_at' => '2026-08-13 09:30:00']],
        ]);
        $lotte = new Application([
            'status' => LotteFinanceWorkflow::DISBURSED,
            'payload' => ['workflow' => ['last_transition' => ['at' => '2026-08-12 16:42:15']]],
        ]);

        self::assertSame('2026-08-14', ApplicationFinancialData::disbursedAt($manual)?->format('Y-m-d'));
        self::assertSame('2026-08-13', ApplicationFinancialData::disbursedAt($acl)?->format('Y-m-d'));
        self::assertSame('2026-08-12', ApplicationFinancialData::disbursedAt($lotte)?->format('Y-m-d'));
    }

    public function test_it_never_fabricates_disbursement_date_from_updated_at(): void
    {
        $application = new Application(['status' => 'processing', 'payload' => []]);
        $application->updated_at = CarbonImmutable::parse('2026-08-14 12:00:00');

        self::assertNull(ApplicationFinancialData::disbursedAt($application));
    }

    public function test_every_application_edit_form_exposes_the_admin_disbursement_field(): void
    {
        $root = dirname(__DIR__, 2).'/app/Filament/Resources/Applications/Schemas/';

        foreach (['ApplicationForm.php', 'AclMixApplicationForm.php', 'LotteFinanceApplicationForm.php'] as $form) {
            $source = file_get_contents($root.$form);
            self::assertStringContainsString("DatePicker::make('payload.fields.disbursed_at')", $source, $form);
            self::assertStringContainsString("->label('Ngày giải ngân')", $source, $form);
        }
    }
}
