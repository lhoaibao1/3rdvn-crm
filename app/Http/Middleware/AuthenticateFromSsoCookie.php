<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs a visitor in from the shared `sso_token` cookie issued by the SSO portal
 * on the `.3rdvn.io.vn` domain, so an authenticated SSO session carries over to
 * this application without another password prompt.
 */
class AuthenticateFromSsoCookie
{
    public const COOKIE = 'sso_token';

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $user = $this->resolveUser($request);

            if ($user) {
                Auth::login($user);
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }

    protected function resolveUser(Request $request): ?User
    {
        $secret = (string) config('services.sso.jwt_secret');
        $token = (string) $request->cookies->get(self::COOKIE, '');

        if ($secret === '' || $token === '') {
            return null;
        }

        try {
            $claims = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Throwable) {
            return null;
        }

        $uid = $claims->uid ?? null;

        if (! is_int($uid) && ! ctype_digit((string) $uid)) {
            return null;
        }

        $user = User::find((int) $uid);

        if (! $user) {
            return null;
        }

        $blocked = ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED];

        if (in_array($user->employment_status, $blocked, true)) {
            return null;
        }

        return $user;
    }
}
