<?php

namespace App\Support\Applications;

use RuntimeException;

final class FeolPartnerLanding
{
    public function encryptedUrl(): string
    {
        $url = (string) config('services.feol_bridge.partner_landing_url');

        if ($url === '' || ! str_starts_with($url, 'https://os.saigonbpo.vn/landing?data=')) {
            throw new RuntimeException('FEOL_PARTNER_LANDING_URL chưa được cấu hình đúng.');
        }

        return $url;
    }

    public function originalUrl(): string
    {
        $url = $this->encryptedUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $encrypted = $query['data'] ?? null;
        $key = (string) config('services.feol_bridge.landing_encrypt_key');

        if (! is_string($encrypted) || strlen($key) !== 16) {
            throw new RuntimeException('Không thể đọc token Landing Page FEOL.');
        }

        $path = openssl_decrypt($encrypted, 'AES-128-CBC', $key, 0, $key);

        if (! is_string($path) || ! str_starts_with($path, '/landing?')) {
            throw new RuntimeException('Token Landing Page FEOL không hợp lệ.');
        }

        return 'https://os.saigonbpo.vn'.$path;
    }
}
