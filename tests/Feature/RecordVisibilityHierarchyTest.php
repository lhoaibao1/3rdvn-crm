<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CrmTeam;
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

    public function test_affiliate_team_leader_does_not_inherit_every_sale_from_the_am_shared_team(): void
    {
        foreach (['AM', 'Team Leader', 'Direct Sale'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $am = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RD260117',
            'employment_status' => 'active',
        ]));
        $am->assignRole('AM');

        $team = CrmTeam::query()->create([
            'name' => '3P - Ngo, Nha Dong',
            'code' => '3P-NND',
            'manager_id' => $am->getKey(),
            'is_active' => true,
        ]);

        $leader = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RD260132',
            'team_id' => $team->getKey(),
            'am_id' => $am->getKey(),
            'created_by_id' => $am->getKey(),
            'employment_status' => 'active',
        ]));
        $leader->assignRole('Team Leader');

        $saleOwnedByAm = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RD260133',
            'team_id' => $team->getKey(),
            'team_leader_id' => null,
            'am_id' => $am->getKey(),
            'created_by_id' => $am->getKey(),
            'employment_status' => 'active',
        ]));
        $saleOwnedByAm->assignRole('Direct Sale');

        $saleOwnedByLeader = User::withoutEvents(fn () => User::factory()->create([
            'employee_code' => 'RD260134',
            'team_id' => $team->getKey(),
            'team_leader_id' => $leader->getKey(),
            'am_id' => $am->getKey(),
            'created_by_id' => $leader->getKey(),
            'employment_status' => 'active',
        ]));
        $saleOwnedByLeader->assignRole('Direct Sale');

        $login = $this->postJson('/api/integration/v1/affiliate-portal/login', [
            'identifier' => $leader->employee_code,
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/integration/v1/affiliate-portal/members?per_page=100')
            ->assertOk();

        $visibleIds = collect($response->json('data'))->pluck('id');

        $this->assertTrue($visibleIds->contains($leader->getKey()));
        $this->assertTrue($visibleIds->contains($saleOwnedByLeader->getKey()));
        $this->assertFalse($visibleIds->contains($saleOwnedByAm->getKey()));
        $this->assertFalse($visibleIds->contains($am->getKey()));
    }
}
