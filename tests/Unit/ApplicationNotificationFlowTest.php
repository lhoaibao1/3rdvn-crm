<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\SalesProject;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Notifications\ApplicationNotificationSender;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ApplicationNotificationFlowTest extends TestCase
{
    public function test_application_observer_covers_create_assignment_and_status_events(): void
    {
        $root = dirname(__DIR__, 2);
        $observer = file_get_contents($root.'/app/Observers/ApplicationNotificationObserver.php');
        $provider = file_get_contents($root.'/app/Providers/AppServiceProvider.php');

        $this->assertStringContainsString('implements ShouldHandleEventsAfterCommit', $observer);
        $this->assertStringContainsString('public function created(Application $application)', $observer);
        $this->assertStringContainsString("wasChanged('assigned_sale_id')", $observer);
        $this->assertStringContainsString("wasChanged('status')", $observer);
        $this->assertStringContainsString(
            'Application::observe(ApplicationNotificationObserver::class);',
            $provider,
        );
    }

    public function test_lotte_status_and_application_identity_are_presented_in_notification_content(): void
    {
        $application = (new Application)->forceFill([
            'id' => 195745,
            'application_code' => 'APL0200195745',
            'applicant_name' => 'Bùi Thị Hường',
            'status' => LotteFinanceWorkflow::UW_APPROVAL,
        ]);
        $application->setRelation('salesProject', (new SalesProject)->forceFill([
            'name' => 'Lotte Finance',
            'slug' => 'lotte-finance',
        ]));

        $statusLabel = new ReflectionMethod(ApplicationNotificationSender::class, 'statusLabel');
        $title = new ReflectionMethod(ApplicationNotificationSender::class, 'title');
        $body = new ReflectionMethod(ApplicationNotificationSender::class, 'body');

        $this->assertSame('UW Approval', $statusLabel->invoke(null, $application, $application->status));
        $this->assertSame(
            'Hồ sơ - Lotte Finance - APL0200195745 - Bùi Thị Hường',
            $title->invoke(null, $application),
        );

        $html = $body->invoke(
            null,
            $application,
            'Chuyển bước hồ sơ',
            'Trạng thái: UW Call → UW Approval',
            'Admin UAT',
            '09:15 01/08/2026',
        )->toHtml();

        $this->assertStringContainsString('Chuyển bước hồ sơ', $html);
        $this->assertStringContainsString('APL0200195745', $html);
        $this->assertStringContainsString('Lotte Finance', $html);
        $this->assertStringContainsString('UW Call → UW Approval', $html);
        $this->assertStringContainsString('Admin UAT', $html);
    }
}
