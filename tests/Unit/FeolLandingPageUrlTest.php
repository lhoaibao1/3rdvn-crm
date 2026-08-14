<?php

namespace Tests\Unit;

use App\Support\Applications\FeolLandingPageUrl;
use Tests\TestCase;

class FeolLandingPageUrlTest extends TestCase
{
    public function test_it_generates_a_partner_compatible_encrypted_b1_url(): void
    {
        config()->set('services.feol_bridge.landing_origin', 'https://os.saigonbpo.vn');
        config()->set('services.feol_bridge.landing_campaign', 'fe-cashloan-deeplink');
        config()->set('services.feol_bridge.landing_sale_code', 'SGBOCTV13765');
        config()->set('services.feol_bridge.landing_encrypt_key', '3MQSbZ3xuwbmSHpo');

        $url = app(FeolLandingPageUrl::class)->generate();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $plain = openssl_decrypt($query['data'], 'AES-128-CBC', '3MQSbZ3xuwbmSHpo', 0, '3MQSbZ3xuwbmSHpo');

        $this->assertStringStartsWith('/landing?cam=fe-cashloan-deeplink&sale=SGBOCTV13765&rid=', (string) $plain);
    }
}
