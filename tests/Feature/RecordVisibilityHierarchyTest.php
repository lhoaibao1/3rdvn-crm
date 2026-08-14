<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Permissions\RecordVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecordVisibilityHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_higher_managers_can_see_sale_record_through_team_leader_chain(): void
    {
        foreach (['ZD', 'AM', 'Team Leader', 'Direct Sale'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $zd = User::factory()->create();
        $zd->assignRole('ZD');
        $am = User::factory()->create(['zd_id' => $zd->getKey()]);
        $am->assignRole('AM');
        $leader = User::factory()->create(['am_id' => $am->getKey(), 'zd_id' => $zd->getKey()]);
        $leader->assignRole('Team Leader');
        $sale = User::factory()->create([
            'team_leader_id' => $leader->getKey(),
            'am_id' => null,
            'zd_id' => null,
        ]);
        $sale->assignRole('Direct Sale');

        $project = SalesProject::query()->create([
            'name' => 'FE Deeplink',
            'slug' => 'fe-deeplink',
            'is_active' => true,
        ]);
        $application = Application::query()->create([
            'sales_project_id' => $project->getKey(),
            'application_code' => 'FEDL-HIERARCHY-001',
            'applicant_name' => 'Khách hàng nhánh sale',
            'status' => 'pending_submission',
            'assigned_sale_id' => $sale->getKey(),
            'created_by_id' => $sale->getKey(),
            'team_leader_id' => $leader->getKey(),
            'am_id' => null,
            'zd_id' => null,
            'payload' => ['fields' => []],
        ]);

        foreach ([$leader, $am, $zd] as $manager) {
            $visible = RecordVisibility::applyUserScope(
                Application::query(),
                $manager,
                'assigned_sale_id',
                'assignedSale',
            )->whereKey($application->getKey())->exists();

            $this->assertTrue($visible);
            $this->assertTrue(RecordVisibility::canAccessUserOwnedRecord(
                $manager,
                $application,
                'assigned_sale_id',
                'assignedSale',
            ));
        }
    }

    public function test_manager_from_another_branch_cannot_see_the_record(): void
    {
        Role::findOrCreate('AM', 'web');
        Role::findOrCreate('Team Leader', 'web');
        Role::findOrCreate('Direct Sale', 'web');

        $manager = User::factory()->create();
        $manager->assignRole('AM');
        $otherManager = User::factory()->create();
        $otherManager->assignRole('AM');
        $leader = User::factory()->create(['am_id' => $manager->getKey()]);
        $leader->assignRole('Team Leader');
        $sale = User::factory()->create(['team_leader_id' => $leader->getKey(), 'am_id' => null]);
        $sale->assignRole('Direct Sale');
        $project = SalesProject::query()->create(['name' => 'FE Deeplink', 'slug' => 'fe-deeplink', 'is_active' => true]);
        $application = Application::query()->create([
            'sales_project_id' => $project->getKey(),
            'application_code' => 'FEDL-HIERARCHY-002',
            'applicant_name' => 'Khách hàng nhánh khác',
            'status' => 'pending_submission',
            'assigned_sale_id' => $sale->getKey(),
            'created_by_id' => $sale->getKey(),
            'team_leader_id' => $leader->getKey(),
            'payload' => ['fields' => []],
        ]);

        $this->assertFalse(RecordVisibility::applyUserScope(
            Application::query(),
            $otherManager,
            'assigned_sale_id',
            'assignedSale',
        )->whereKey($application->getKey())->exists());
        $this->assertFalse(RecordVisibility::canAccessUserOwnedRecord(
            $otherManager,
            $application,
            'assigned_sale_id',
            'assignedSale',
        ));
    }
}
