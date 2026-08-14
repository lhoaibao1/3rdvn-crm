<?php

namespace App\Filament\Resources\FeDeeplinkApplications;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\FeDeeplinkApplications\Pages\EditFeDeeplinkApplication;
use App\Filament\Resources\FeDeeplinkApplications\Pages\ListFeDeeplinkApplications;
use App\Filament\Resources\FeDeeplinkApplications\Pages\ViewFeDeeplinkApplication;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class FeDeeplinkApplicationResource extends ApplicationResource
{
    protected static ?string $slug = 'applications/fe-deeplink';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function getPages(): array
    {
        return [
            'index' => ListFeDeeplinkApplications::route('/'),
            'view' => ViewFeDeeplinkApplication::route('/{record}'),
            'edit' => EditFeDeeplinkApplication::route('/{record}/edit'),
        ];
    }

    protected static function projectSlug(): string
    {
        return 'fe-deeplink';
    }

    protected static function projectName(): string
    {
        return 'FE Deeplink';
    }

    protected static function projectSort(): int
    {
        return 40;
    }
}
