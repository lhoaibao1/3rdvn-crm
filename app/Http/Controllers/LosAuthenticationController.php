<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StalwartMailService;
use App\Support\SsoTokenVerifier;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LosAuthenticationController extends Controller
{
    public function create(): RedirectResponse
    {
        // Cross-domain SSO Automatic Login
        $ssoToken = request()->cookie('sso_token');
        if ($ssoToken) {
            try {
                $secret = config('services.sso.jwt_secret', config('app.jwt_secret', ''));
                $decoded = SsoTokenVerifier::decode($ssoToken, $secret);
                if ($decoded) {
                    $uid = $decoded['uid'] ?? $decoded['id'] ?? null;
                    if ($uid) {
                        $ssoUser = User::find($uid);
                        if ($ssoUser && !in_array($ssoUser->employment_status, ['inactive', 'deactive', 'resigned', 'deleted'], true) && $ssoUser->canAccessApp('los')) {
                            Auth::login($ssoUser, true);
                            request()->session()->regenerate();
                            return redirect()->intended(route('los.index'));
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore invalid or expired SSO tokens
            }
        }

        if (Auth::check()) {
            return redirect()->route('los.index');
        }

        // 100% redirect unauthenticated visitors to SSO login with return_to
        $returnTo = rawurlencode(route('los.index'));
        return redirect()->away("https://sso.3rdvn.io.vn/login?return_to={$returnTo}");
    }

    public function store(Request $request, StalwartMailService $mailService): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'identifier.required' => 'Vui lòng nhập User/UID.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $identifier = trim($data['identifier']);
        $normalizedPhone = preg_replace('/\D+/', '', $identifier) ?: $identifier;
        $normalizedIdentifier = mb_strtolower($identifier);

        $user = User::query()
            ->where(function ($query) use ($identifier, $normalizedIdentifier, $normalizedPhone): void {
                $query
                    ->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(username) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(name) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(uid) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(employee_code) = ?', [$normalizedIdentifier])
                    ->orWhere('identity_number', $identifier)
                    ->orWhere('phone', $identifier)
                    ->orWhere('phone', $normalizedPhone);
            })
            ->first();

        $isInactive = $user && in_array($user->employment_status, [
            'inactive',
            User::STATUS_DEACTIVE,
            'resigned',
            User::STATUS_DELETED,
        ], true);

        if (! $user || $isInactive || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withInput($request->only('identifier'))
                ->withErrors(['identifier' => 'Thông tin đăng nhập không đúng hoặc tài khoản đã ngừng hoạt động.']);
        }

        Auth::login($user, (bool) ($data['remember'] ?? false));
        $request->session()->regenerate();
        try {
            $mailService->scheduleCredentialSync($user, $data['password']);
        } catch (\Throwable $e) {}

        // Issue SSO JWT Cookie cross-domain
        $secret = config('services.sso.jwt_secret', config('app.jwt_secret', ''));
        $ssoJwt = JWT::encode([
            'uid' => $user->id,
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->getRoleNames()->first() ?: 'Direct Sale',
            'exp' => time() + (30 * 86400),
        ], $secret, 'HS256');

        $cookieCross = cookie('sso_token', $ssoJwt, 60 * 24 * 30, '/', '.3rdvn.io.vn', true, false, false, 'Lax');
        $cookieLocal = cookie('sso_token', $ssoJwt, 60 * 24 * 30, '/', null, true, false, false, 'Lax');

        return redirect()->intended(route('los.index'))
            ->withCookie($cookieCross)
            ->withCookie($cookieLocal);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $returnTo = rawurlencode('https://los.3rdvn.io.vn');
        $ssoLogoutUrl = "https://sso.3rdvn.io.vn/logout?return_to={$returnTo}";

        $cookieCross = cookie('sso_token', '', -2628000, '/', '.3rdvn.io.vn', true, false, false, 'Lax');
        $cookieLocal = cookie('sso_token', '', -2628000, '/', null, true, false, false, 'Lax');

        return redirect()->away($ssoLogoutUrl)
            ->withCookie($cookieCross)
            ->withCookie($cookieLocal);
    }
}
