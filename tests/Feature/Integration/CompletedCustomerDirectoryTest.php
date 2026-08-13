<?php

namespace Tests\Feature\Integration;

use App\Models\Application;
use App\Models\SalesProject;
use App\Support\Applications\AclMixWorkflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompletedCustomerDirectoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_only_completed_customers_are_exported_with_approved_amount(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);
        $project = SalesProject::query()->firstOrCreate(['slug' => 'ess-api-test'], ['name' => 'ESS API Test']);
        Application::factory()->create(['sales_project_id' => $project->id, 'status' => AclMixWorkflow::COMPLETED, 'applicant_name' => 'Nguyen Van A', 'payload' => ['review' => ['pre_approved_amount' => 12000000]]]);
        Application::factory()->create(['sales_project_id' => $project->id, 'status' => 'underwriting']);

        $this->withToken('test-token')->getJson('/api/integration/v1/completed-customers?project_slug=ess-api-test')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.approved_amount', 12000000);
    }
}
