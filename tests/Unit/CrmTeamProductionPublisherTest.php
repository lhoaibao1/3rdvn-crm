<?php

namespace Tests\Unit;

use App\Models\CrmTeam;
use App\Services\CrmTeamProductionPublisher;
use LogicException;
use Tests\TestCase;

class CrmTeamProductionPublisherTest extends TestCase
{
    public function test_disabled_publication_never_opens_the_production_connection(): void
    {
        config(['crm.team_publication.enabled' => false]);

        $this->assertFalse(
            app(CrmTeamProductionPublisher::class)->publish(new CrmTeam),
        );
    }

    public function test_enabled_publication_is_rejected_during_tests(): void
    {
        config([
            'crm.team_publication.enabled' => true,
            'crm.team_publication.connection' => 'production_team_publish',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('khi đang chạy test');

        app(CrmTeamProductionPublisher::class)->publish(new CrmTeam);
    }

    public function test_team_pages_publish_only_after_member_sync(): void
    {
        $root = dirname(__DIR__, 2);

        foreach (['CreateCrmTeam.php', 'EditCrmTeam.php'] as $page) {
            $source = file_get_contents(
                $root.'/app/Filament/Resources/CrmTeams/Pages/'.$page,
            );

            $syncPosition = strpos($source, 'syncMembers(');
            $publishPosition = strpos($source, 'publishTeamToProduction();');

            $this->assertNotFalse($syncPosition);
            $this->assertNotFalse($publishPosition);
            $this->assertGreaterThan($syncPosition, $publishPosition);
        }
    }

    public function test_manager_is_resolved_without_becoming_a_team_member(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Services/CrmTeamProductionPublisher.php',
        );

        $this->assertStringContainsString('$memberCodes->concat([$managerCode])', $source);
        $this->assertStringNotContainsString('$memberCodes->push($managerCode)', $source);
    }
}
