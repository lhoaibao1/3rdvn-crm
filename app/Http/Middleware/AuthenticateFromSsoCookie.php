<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Kept for route compatibility. Shared SSO cookies cannot authenticate CRM. */
class AuthenticateFromSsoCookie
{
    public const COOKIE = 'sso_token';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
