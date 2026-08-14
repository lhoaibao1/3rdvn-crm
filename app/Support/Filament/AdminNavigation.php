<?php

namespace App\Support\Filament;

final class AdminNavigation
{
    public const GROUP = 'Admin & Config';

    /**
     * @return list<string>
     */
    public static function groups(): array
    {
        return [
            'CRM',
            'Application',
            'Employee - Work',
            'Service',
            self::GROUP,
        ];
    }
}
