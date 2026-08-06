<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class AdminWorkflowOverride
{
    public static function active(?User $user = null): bool
    {
        if (! $user instanceof User) {
            $container = Container::getInstance();

            if (! $container->bound(AuthFactory::class)) {
                return false;
            }

            $user = $container->make(AuthFactory::class)->guard()->user();
        }

        return $user instanceof User && $user->hasAnyRole(['Admin', 'Sales Admin']);
    }

    public static function required(?User $user = null): bool
    {
        return ! self::active($user);
    }

    /**
     * @param  array<string, string>  $allOptions
     * @param  array<string, string>  $regularOptions
     * @return array<string, string>
     */
    public static function transitionOptions(
        array $allOptions,
        array $regularOptions,
        ?string $currentStatus,
        ?User $user = null,
    ): array {
        return $regularOptions;
    }
}
