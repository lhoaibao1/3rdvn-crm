<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\StalwartMailService;

class ResetUserPassword
{
    public const FALLBACK_PASSWORD = '123456Aa@';

    public static function defaultPassword(): string
    {
        $password = trim((string) config('crm.users.default_password', self::FALLBACK_PASSWORD));

        return $password !== '' ? $password : self::FALLBACK_PASSWORD;
    }

    public function execute(User $user): void
    {
        $password = self::defaultPassword();

        $user->forceFill(['password' => $password])->save();

        app(StalwartMailService::class)->scheduleCredentialSync($user, $password);
    }
}
