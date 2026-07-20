<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StalwartMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MailSsoController extends Controller
{
    public function __invoke(Request $request, StalwartMailService $mailService): RedirectResponse|Response
    {
        try {
            $user = $request->user();

            if (! $user instanceof User) {
                throw new RuntimeException('Phiên đăng nhập không hợp lệ.');
            }

            $user = $mailService->ensureMailbox($user);
            $secret = (string) config('services.stalwart.sso_secret');
            $issuer = (string) config('services.stalwart.sso_issuer', '3rdvn-crm');
            $webmailUrl = rtrim((string) config('services.stalwart.webmail_url'), '/');

            if (strlen($secret) < 32 || $issuer === '' || $webmailUrl === '' || blank($user->mail_address)) {
                throw new RuntimeException('Cấu hình đăng nhập Mail chưa hoàn tất.');
            }

            $now = time();
            $token = $this->jwt([
                'iss' => $issuer,
                'iat' => $now,
                'exp' => $now + 60,
                'jti' => (string) Str::uuid(),
                'mailbox' => (string) $user->mail_address,
                'actor_user_id' => (string) $user->getKey(),
            ], $secret);

            return redirect()->away(
                $webmailUrl.'/api/auth/impersonate?token='.rawurlencode($token),
                302,
                [
                    'Cache-Control' => 'no-store, private',
                    'Pragma' => 'no-cache',
                    'Referrer-Policy' => 'no-referrer',
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->view('mail.sso-error', status: 503)
                ->header('Cache-Control', 'no-store, private');
        }
    }

    private function jwt(array $claims, string $secret): string
    {
        $header = $this->base64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $header.'.'.$payload, $secret, true);

        return $header.'.'.$payload.'.'.$this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
