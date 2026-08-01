<?php

namespace Tests\Unit;

use App\Filament\Resources\Users\Schemas\UserForm;
use PHPUnit\Framework\TestCase;

class UserFormDateNormalizationTest extends TestCase
{
    public function test_it_normalizes_vietnamese_dates_without_swapping_day_and_month(): void
    {
        $data = UserForm::normalizeDateFields([
            'date_of_birth' => '31/01/1995',
            'identity_issued_date' => '12/11/2020',
            'hire_date' => '2026-08-01',
        ]);

        $this->assertSame('1995-01-31', $data['date_of_birth']);
        $this->assertSame('2020-11-12', $data['identity_issued_date']);
        $this->assertSame('2026-08-01', $data['hire_date']);
    }

    public function test_it_normalizes_date_objects_to_database_format(): void
    {
        $data = UserForm::normalizeDateFields([
            'hire_date' => new \DateTimeImmutable('2026-08-01'),
        ]);

        $this->assertSame('2026-08-01', $data['hire_date']);
    }
}
