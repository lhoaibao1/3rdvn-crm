<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserDefaultPasswordTest extends TestCase
{
    public function test_create_user_uses_the_configured_default_password_without_an_input(): void
    {
        $root = dirname(__DIR__, 2);
        $form = file_get_contents($root.'/app/Filament/Resources/Users/Schemas/UserForm.php');
        $create = file_get_contents($root.'/app/Filament/Resources/Users/Pages/CreateUser.php');
        $config = file_get_contents($root.'/config/crm.php');

        self::assertStringNotContainsString("TextInput::make('password')", $form);
        self::assertStringContainsString("Placeholder::make('default_password_notice')", $form);
        self::assertStringContainsString('ResetUserPassword::defaultPassword()', $form);
        self::assertStringContainsString('$data[\'password\'] = $this->plainPassword;', $create);
        self::assertStringContainsString("'123456Aa@'", $config);
        self::assertStringContainsString('CRM_USER_DEFAULT_PASSWORD', $config);
    }

    public function test_reset_password_action_is_available_in_all_user_action_menus(): void
    {
        $root = dirname(__DIR__, 2).'/app/Filament/Resources/Users/';

        foreach ([
            'Tables/UsersTable.php',
            'Pages/EditUser.php',
            'Pages/ViewUser.php',
        ] as $screen) {
            $source = file_get_contents($root.$screen);

            self::assertStringContainsString('UserPasswordResetAction::make()', $source, $screen);
        }

        $action = file_get_contents(
            dirname(__DIR__, 2).'/app/Support/Filament/UserPasswordResetAction.php',
        );

        self::assertStringContainsString("->label('Reset mật khẩu')", $action);
        self::assertStringContainsString('->requiresConfirmation()', $action);
        self::assertStringContainsString('ResetUserPassword::class', $action);
        self::assertStringContainsString("hasRole('Admin')", $action);
    }

    public function test_password_reset_business_logic_updates_crm_and_mail_credentials(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Actions/Users/ResetUserPassword.php',
        );

        self::assertStringContainsString("public const FALLBACK_PASSWORD = '123456Aa@';", $source);
        self::assertStringContainsString("config('crm.users.default_password'", $source);
        self::assertStringContainsString("forceFill(['password' => \$password])->save()", $source);
        self::assertStringContainsString('scheduleCredentialSync($user, $password)', $source);
    }
}
