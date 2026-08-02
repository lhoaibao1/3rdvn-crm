<?php

namespace Tests\Unit;

use App\Support\Filament\StableTablePolling;
use PHPUnit\Framework\TestCase;

class StableTablePollingTest extends TestCase
{
    public function test_polling_pauses_while_records_are_selected_or_an_action_is_open(): void
    {
        $idle = (object) ['selectedTableRecords' => [], 'mountedActions' => []];
        $selected = (object) ['selectedTableRecords' => [1, 2], 'mountedActions' => []];
        $actionOpen = (object) ['selectedTableRecords' => [], 'mountedActions' => ['process']];

        $this->assertSame('5s', StableTablePolling::interval($idle));
        $this->assertNull(StableTablePolling::interval($selected));
        $this->assertNull(StableTablePolling::interval($actionOpen));
    }
}
