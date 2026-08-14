<?php

namespace App\Http\Controllers\Integration\Concerns;

use Illuminate\Http\Request;

trait AuthorizesFeolBridge
{
    private function authorizeFeolBridge(Request $request): void
    {
        $expected = (string) config('services.feol_bridge.token', '');
        $provided = (string) $request->bearerToken();

        abort_unless($expected !== '' && $provided !== '' && hash_equals($expected, $provided), 401, 'Invalid FEOL bridge token.');
    }
}
