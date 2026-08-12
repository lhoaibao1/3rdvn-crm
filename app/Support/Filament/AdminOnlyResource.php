<?php

namespace App\Support\Filament;

trait AdminOnlyResource
{
    protected static function currentUserIsAdmin(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canView(mixed $record): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canCreate(): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canEdit(mixed $record): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canDelete(mixed $record): bool
    {
        return static::currentUserIsAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::currentUserIsAdmin();
    }
}
