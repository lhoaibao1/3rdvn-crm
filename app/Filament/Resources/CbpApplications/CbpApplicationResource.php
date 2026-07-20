<?php

namespace App\Filament\Resources\CbpApplications;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\CbpApplications\Pages\EditCbpApplication;
use App\Filament\Resources\CbpApplications\Pages\ListCbpApplications;
use App\Filament\Resources\CbpApplications\Pages\ViewCbpApplication;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class CbpApplicationResource extends ApplicationResource
{
    protected static ?string $slug = 'applications/cbp';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function getPages(): array
    {
        return [
            'index' => ListCbpApplications::route('/'),
            'view' => ViewCbpApplication::route('/{record}'),
            'edit' => EditCbpApplication::route('/{record}/edit'),
        ];
    }

    protected static function projectSlug(): string
    {
        return 'cbp';
    }

    protected static function projectName(): string
    {
        return 'CBP';
    }

    protected static function projectSort(): int
    {
        return 20;
    }
}
