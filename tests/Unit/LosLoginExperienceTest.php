<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LosLoginExperienceTest extends TestCase
{
    public function test_los_login_uses_the_shared_crm_experience_without_changing_auth_flow(): void
    {
        $root = dirname(__DIR__, 2);
        $login = file_get_contents($root.'/resources/views/los/login.blade.php');
        $index = file_get_contents($root.'/resources/views/los/index.blade.php');

        self::assertStringContainsString('<x-auth.crm-login-shell', $login);
        self::assertStringContainsString('workspace="LOS Workspace"', $login);
        self::assertStringContainsString('data-crm-login-page', $login);
        self::assertStringContainsString("route('los.login.store')", $login);
        self::assertStringContainsString('@csrf', $login);
        self::assertStringNotContainsString("sessionStorage.setItem('3rdvn:login-entry'", $login);
        self::assertStringNotContainsString("@include('filament.hooks.login-entry-transition')", $login);
        self::assertStringNotContainsString("@include('filament.hooks.login-entry-transition')", $index);
    }

    public function test_shared_login_shell_resolves_environment_without_an_intro_animation(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = file_get_contents($root.'/resources/views/components/auth/crm-login-shell.blade.php');

        self::assertStringContainsString('$'.'environment ??= $'."isUat ? 'UAT' : 'PROD';", $shell);
        self::assertStringContainsString('{{ $environment }}', $shell);
        self::assertStringNotContainsString('<span class="crm-login-env">UAT</span>', $shell);
        self::assertStringNotContainsString('class="crm-login-intro"', $shell);
        self::assertStringNotContainsString('crm-login-dialog-in .9s', $shell);
    }
}
