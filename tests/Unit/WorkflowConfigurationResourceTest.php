<?php

namespace Tests\Unit;

use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use App\Support\Filament\WorkflowOverview;
use PHPUnit\Framework\TestCase;

class WorkflowConfigurationResourceTest extends TestCase
{
    public function test_workflow_is_a_separate_uat_resource_instead_of_sales_project_content(): void
    {
        $root = dirname(__DIR__, 2);
        $provider = file_get_contents($root.'/app/Providers/Filament/UatPanelProvider.php');
        $resource = file_get_contents($root.'/app/Filament/Resources/WorkflowConfigurations/WorkflowConfigurationResource.php');
        $projectForm = file_get_contents($root.'/app/Filament/Resources/SalesProjects/Schemas/SalesProjectForm.php');
        $projectView = file_get_contents($root.'/app/Filament/Resources/SalesProjects/Schemas/SalesProjectInfolist.php');
        $projectResource = file_get_contents($root.'/app/Filament/Resources/SalesProjects/SalesProjectResource.php');

        self::assertStringContainsString('WorkflowConfigurationResource::class', $provider);
        self::assertStringContainsString("return 'Workflow';", $resource);
        self::assertStringContainsString("getId() === 'uat'", $resource);
        self::assertStringContainsString("->whereIn('slug', ProjectWorkflowConfiguration::supportedSlugs())", $resource);
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
            'Trạng thái hiện tại',
            'Mã trạng thái',
            'Cách xử lý',
            'Được chuyển đến',
            'Quy tắc áp dụng',
        ] as $label) {
            self::assertStringContainsString($label, $source);
        }

        self::assertStringContainsString("Repeater::make('workflow_schema')", $source);
        self::assertStringContainsString('ProjectWorkflowConfiguration::forProject($record)', $source);
        self::assertStringContainsString('->addable(false)', $source);
        self::assertStringContainsString('->deletable(false)', $source);
        self::assertStringContainsString('->reorderable(false)', $source);
    }

    public function test_workflow_overview_shows_real_acl_statuses_and_transitions(): void
    {
        $project = new SalesProject([
            'name' => 'ACL Mix',
            'slug' => 'acl-mix',
        ]);

        $html = WorkflowOverview::render($project)->toHtml();

        self::assertStringContainsString('8 trạng thái', $html);
        self::assertStringContainsString('Đang kiểm tra', $html);
        self::assertStringContainsString('pending_initial_review', $html);
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
