<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;

class CompletedCustomerDirectoryTest extends TestCase
{
    public function test_integration_token_is_required(): void
    {
        config(['services.vpn_directory.token' => 'test-token']);
        $this->getJson('/api/integration/v1/completed-customers')->assertUnauthorized();
    }
}
