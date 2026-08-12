<?php

namespace Tests\Unit;

use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use App\Support\Filament\WorkflowOverview;
use PHPUnit\Framework\TestCase;

class WorkflowConfigurationResourceTest extends TestCase
{
    public function test_workflow_and_config_resources_live_in_prod_not_uat(): void
    {
        $root = dirname(__DIR__, 2);
        $adminProvider = file_get_contents($root.'/app/Providers/Filament/AdminPanelProvider.php');
        $uatProvider = file_get_contents($root.'/app/Providers/Filament/UatPanelProvider.php');
        $resource = file_get_contents($root.'/app/Filament/Resources/WorkflowConfigurations/WorkflowConfigurationResource.php');
        $projectForm = file_get_contents($root.'/app/Filament/Resources/SalesProjects/Schemas/SalesProjectForm.php');
        $projectView = file_get_contents($root.'/app/Filament/Resources/SalesProjects/Schemas/SalesProjectInfolist.php');
        $projectResource = file_get_contents($root.'/app/Filament/Resources/SalesProjects/SalesProjectResource.php');
        $adminOnlyResource = file_get_contents($root.'/app/Support/Filament/AdminOnlyResource.php');

        self::assertStringContainsString('WorkflowConfigurationResource::class', $adminProvider);
        self::assertStringNotContainsString('WorkflowConfigurationResource::class', $uatProvider);
        self::assertStringContainsString("return 'Workflow';", $resource);
        self::assertStringContainsString('protected static ?string $slug = \'admin/workflow-configurations\';', $resource);
        self::assertStringContainsString("return 'Admin';", $resource);
        self::assertStringContainsString('use AdminOnlyResource;', $resource);
        self::assertStringContainsString("str_starts_with(\$appHost, 'uat-')", $adminOnlyResource);
        self::assertStringContainsString('CreateWorkflowConfiguration::route', $resource);
        self::assertStringNotContainsString('Chi tiết workflow', $projectForm);
        self::assertStringNotContainsString('Chi tiết workflow', $projectView);
        self::assertStringContainsString("return 'Cấu hình dự án';", $projectResource);
        self::assertStringNotContainsString("return 'Cấu hình dự án & Workflow';", $projectResource);
    }

    public function test_workflow_form_names_status_code_mode_transition_and_rule_explicitly(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/WorkflowConfigurations/Schemas/WorkflowConfigurationForm.php',
        );

        foreach ([
            'Mã trạng thái',
            'Tên hiển thị',
            'Cách xử lý',
            'Được chuyển đến',
            'Ghi chú / quy tắc nghiệp vụ',
        ] as $label) {
            self::assertStringContainsString($label, $source);
        }

        self::assertStringContainsString("Repeater::make('workflow_schema')", $source);
        self::assertStringContainsString('ProjectWorkflowConfiguration::forProject($record)', $source);
        self::assertStringContainsString('->addable()', $source);
        self::assertStringContainsString('->deletable()', $source);
        self::assertStringContainsString('->reorderable()', $source);
    }

    public function test_workflow_overview_shows_real_acl_statuses_and_transitions(): void
    {
        $project = new SalesProject([
            'name' => 'ACL Mix',
            'slug' => 'acl-mix',
        ]);

        $html = WorkflowOverview::render($project)->toHtml();

        self::assertStringContainsString('11 trạng thái', $html);
        self::assertStringContainsString('Đang kiểm tra', $html);
        self::assertStringContainsString('pending_initial_review', $html);
        self::assertStringContainsString('otp_required', $html);
        self::assertStringContainsString('Khách hàng thao tác CAPP', $html);
        self::assertStringContainsString('Không thoả điều kiện', $html);
        self::assertStringContainsString('Chờ Sale hoàn thiện thông tin', $html);
        self::assertStringContainsString('Tự động sau khi Sale lưu', $html);
        self::assertStringContainsString('Kết thúc quy trình', $html);
        self::assertStringContainsString('ĐƯỢC CHUYỂN ĐẾN', $html);
    }

    public function test_supported_projects_are_explicit(): void
    {
        self::assertSame(
            ['acl-mix', 'lotte-finance'],
            ProjectWorkflowConfiguration::supportedSlugs(),
        );
    }
}
