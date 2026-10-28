<?php

namespace App\Support;

final class SsoTokenVerifier
{
    /** @return array<string, mixed>|null */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if ($secret === '' || count($parts) !== 3) return null;
        [$encodedHeader, $encodedPayload, $signature] = $parts;
        $expected = self::base64UrlEncode(hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $secret, true));
        if (! hash_equals($expected, $signature)) return null;
        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        $claims = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (! is_array($header) || ($header['alg'] ?? null) !== 'HS256' || ! is_array($claims)) return null;
        if (isset($claims['exp']) && (int) $claims['exp'] <= time()) return null;
        return $claims;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding) $value .= str_repeat('=', 4 - $padding);
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
