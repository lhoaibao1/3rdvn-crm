<?php

namespace Tests\Unit;

use App\Enums\FeDeeplinkStatus;
use PHPUnit\Framework\TestCase;

class FeDeeplinkStatusTest extends TestCase
{
    public function test_fe_has_only_reject_and_end_statuses(): void
    {
        self::assertSame([
            'reject' => 'Reject',
            'end' => 'END',
        ], FeDeeplinkStatus::options());
        self::assertSame('danger', FeDeeplinkStatus::REJECT->color());
        self::assertSame('success', FeDeeplinkStatus::END->color());
    }

    public function test_fe_form_creation_and_directory_use_the_status_enum(): void
    {
        $root = dirname(__DIR__, 2).'/app/';
        $form = file_get_contents($root.'Filament/Resources/FeDeeplinkApplications/Schemas/FeDeeplinkApplicationForm.php');
        $create = file_get_contents($root.'Filament/Resources/FeDeeplinkApplications/Pages/CreateFeDeeplinkApplication.php');
        $directory = file_get_contents($root.'Http/Controllers/Integration/CompletedCustomerDirectoryController.php');

        self::assertStringContainsString("NativeSelect::make('status')", $form);
        self::assertStringContainsString('FeDeeplinkStatus::options()', $form);
        self::assertStringContainsString('FeDeeplinkStatus::END->value', $form);
        self::assertStringContainsString('FeDeeplinkStatus::from', $create);
        self::assertStringContainsString("where('status', FeDeeplinkStatus::END->value)", $directory);
    }

    public function test_status_migration_is_reversible(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_08_14_140000_normalize_fe_deeplink_statuses.php');

        self::assertStringContainsString('public function up(): void', $migration);
        self::assertStringContainsString('public function down(): void', $migration);
        self::assertStringContainsString('FeDeeplinkStatus::END->value', $migration);
        self::assertStringContainsString('FeDeeplinkStatus::REJECT->value', $migration);
    }
}
