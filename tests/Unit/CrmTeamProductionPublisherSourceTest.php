<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CrmTeamProductionPublisherSourceTest extends TestCase
{
    public function test_member_codes_are_deduplicated_on_base_collection(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Services/CrmTeamProductionPublisher.php',
        );

        $this->assertStringContainsString('->members', $source);
        $this->assertStringContainsString('->toBase()', $source);
        $this->assertStringContainsString('->unique()', $source);
    }
}
