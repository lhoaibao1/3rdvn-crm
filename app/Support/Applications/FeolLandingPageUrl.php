<?php

namespace App\Support\Applications;

use Illuminate\Support\Str;
use RuntimeException;

final class FeolLandingPageUrl
{
    public function generate(): string
    {
        $origin = rtrim((string) config('services.feol_bridge.landing_origin'), '/');
        $campaign = (string) config('services.feol_bridge.landing_campaign');
        $saleCode = (string) config('services.feol_bridge.landing_sale_code');
        $key = (string) config('services.feol_bridge.landing_encrypt_key');

        if ($origin === '' || $campaign === '' || $saleCode === '' || strlen($key) !== 16) {
            throw new RuntimeException('Cấu hình Landing Page B1 FEOL chưa đầy đủ.');
        }

        $query = http_build_query([
            'cam' => $campaign,
            'sale' => $saleCode,
            'rid' => Str::lower(Str::random(16)),
        ], '', '&', PHP_QUERY_RFC3986);

        $encrypted = openssl_encrypt('/landing?'.$query, 'AES-128-CBC', $key, 0, $key);

        if ($encrypted === false) {
            throw new RuntimeException('Không thể tạo Landing Page B1 FEOL.');
        }

        return $origin.'/landing?data='.rawurlencode($encrypted);
    }
}
