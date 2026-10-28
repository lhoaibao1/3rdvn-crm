<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateFromSsoCookie;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StandaloneAuthenticationTest extends TestCase
{
    public function test_legacy_login_redirects_to_local_panel(): void
    {
        $this->get('/login')->assertRedirect('/authen/login');
    }

    public function test_login_form_is_available_without_sso(): void
    {
        $this->get('/authen/login')->assertOk()->assertSee('password');
    }

    public function test_logout_stays_inside_crm(): void
    {
        $this->get('/logout')->assertRedirect('/authen/login');
        $this->post('/logout')->assertRedirect('/authen/login');
    }

    public function test_protected_route_uses_local_login(): void
    {
        $this->get('/lead-referral/import-template')->assertRedirect('/authen/login');
    }

    public function test_shared_sso_cookie_does_not_authenticate(): void
    {
        $request = Request::create('/', 'GET', [], ['sso_token' => 'untrusted-token']);
        $response = (new AuthenticateFromSsoCookie)->handle($request, fn () => new Response('local'));
        $this->assertSame('local', $response->getContent());
        $this->assertGuest();
    }
}
