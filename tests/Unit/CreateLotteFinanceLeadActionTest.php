<?php

namespace Tests\Unit;

use App\Support\Filament\LeadCreate\CreateLotteFinanceLeadAction;
use Filament\Schemas\Components\Wizard;
use PHPUnit\Framework\TestCase;

class CreateLotteFinanceLeadActionTest extends TestCase
{
    public function test_it_renders_a_submit_action_for_the_last_wizard_step(): void
    {
        $schema = CreateLotteFinanceLeadAction::schema();
        $wizard = $schema[0];

        $this->assertInstanceOf(Wizard::class, $wizard);

        $reflection = new \ReflectionClass($wizard);
        $property = $reflection->getProperty('submitAction');
        $property->setAccessible(true);

        $this->assertNotNull($property->getValue($wizard));
    }

    public function test_it_persists_the_new_employer_and_address_fields(): void
    {
        $keys = CreateLotteFinanceLeadAction::fieldKeys();

        $this->assertContains('employer_tax_code', $keys);
        $this->assertContains('employer_province_code', $keys);
        $this->assertContains('employer_district_code', $keys);
        $this->assertContains('employer_ward_code', $keys);
        $this->assertContains('employer_address', $keys);
        $this->assertContains('employment_type', $keys);
        $this->assertContains('working_years', $keys);
        $this->assertContains('working_months', $keys);
        $this->assertContains('monthly_income', $keys);
        $this->assertContains('permanent_province_code', $keys);
        $this->assertContains('permanent_district_code', $keys);
        $this->assertContains('permanent_ward_code', $keys);
    }

    public function test_it_returns_bank_defaults_from_user_profile(): void
    {
        $user = new class
        {
            public string $bank_name = 'Vietcombank';

            public string $bank_account_number = '0123456789';

            public string $bank_account_name = 'NGUYEN VAN A';
        };

        $defaults = CreateLotteFinanceLeadAction::defaultBankFields($user);

        $this->assertSame('bank', $defaults['disbursement_method']);
        $this->assertSame('VCB', $defaults['bank_name']);
        $this->assertSame('0123456789', $defaults['bank_account_number']);
        $this->assertSame('NGUYEN VAN A', $defaults['bank_account_name']);
    }
}
