<?php

namespace Tests\Unit;

use App\Enums\FeDeeplinkStatus;
use PHPUnit\Framework\TestCase;

class FeDeeplinkStatusTest extends TestCase
{
    public function test_fe_uses_the_full_partner_status_catalogue(): void
    {
        self::assertCount(17, FeDeeplinkStatus::options());
        self::assertSame('Eligible', FeDeeplinkStatus::options()['eligible']);
        self::assertSame('PL Disbursed', FeDeeplinkStatus::options()['pl_disbursed']);
        self::assertSame('danger', FeDeeplinkStatus::HARD_REJECT->color());
        self::assertSame('success', FeDeeplinkStatus::ELIGIBLE->color());
        self::assertSame(FeDeeplinkStatus::PENDING_ESIGN, FeDeeplinkStatus::fromPartnerLabel('Pending eSign'));
        self::assertSame(FeDeeplinkStatus::DROP_OFF, FeDeeplinkStatus::fromPartnerLabel('Drop-Off'));
        self::assertTrue(FeDeeplinkStatus::ELIGIBLE->permitsFirstDeeplinkCapture());
        self::assertFalse(FeDeeplinkStatus::INELIGIBLE->permitsFirstDeeplinkCapture());
    }

    public function test_fe_form_creation_and_directory_use_the_status_enum(): void
    {
        $root = dirname(__DIR__, 2).'/app/';
        $form = file_get_contents($root.'Filament/Resources/FeDeeplinkApplications/Schemas/FeDeeplinkApplicationForm.php');
        $create = file_get_contents($root.'Filament/Resources/FeDeeplinkApplications/Pages/CreateFeDeeplinkApplication.php');
        $directory = file_get_contents($root.'Http/Controllers/Integration/CompletedCustomerDirectoryController.php');

        self::assertStringContainsString("Select::make('status')", $form);
        self::assertStringContainsString("Select::make('payload.fields.product')", $form);
        self::assertStringNotContainsString('NativeSelect', $form);
        self::assertSame(2, substr_count($form, '->searchable()'));
        self::assertSame(2, substr_count($form, '->preload()'));
        self::assertStringContainsString('FeDeeplinkStatus::options()', $form);
        self::assertStringContainsString('FeDeeplinkStatus::PENDING_SUBMISSION->value', $form);
        self::assertStringContainsString('FeDeeplinkStatus::PENDING_SUBMISSION->value', $create);
        self::assertStringContainsString('FeolSubmitState::QUEUED', $create);
        self::assertStringContainsString('SubmitFeolApplicationToPartner::dispatch', $create);
        self::assertStringContainsString("data_set(\$payload, 'fields.customer_consent', true)", $create);
        self::assertStringNotContainsString('FeolSubmitState::AWAITING_CUSTOMER', $create);
        self::assertStringContainsString("where('status', FeDeeplinkStatus::PL_DISBURSED->value)", $directory);
    }

    public function test_status_migration_is_reversible(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_08_14_140000_normalize_fe_deeplink_statuses.php');

        self::assertStringContainsString('public function up(): void', $migration);
        self::assertStringContainsString('public function down(): void', $migration);
        self::assertStringContainsString('FeDeeplinkStatus::PL_DISBURSED->value', $migration);
        self::assertStringContainsString('FeDeeplinkStatus::HARD_REJECT->value', $migration);
    }
}
