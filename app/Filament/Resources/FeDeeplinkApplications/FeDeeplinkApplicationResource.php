<?php

namespace App\Filament\Resources\FeDeeplinkApplications;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\FeDeeplinkApplications\Pages\CreateFeDeeplinkApplication;
use App\Filament\Resources\FeDeeplinkApplications\Pages\EditFeDeeplinkApplication;
use App\Filament\Resources\FeDeeplinkApplications\Pages\ListFeDeeplinkApplications;
use App\Filament\Resources\FeDeeplinkApplications\Pages\ViewFeDeeplinkApplication;
use App\Filament\Resources\FeDeeplinkApplications\Schemas\FeDeeplinkApplicationForm;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Schema;

class FeDeeplinkApplicationResource extends ApplicationResource
{
    protected static ?string $slug = 'applications/fe-deeplink';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function getPages(): array
    {
        return [
            'index' => ListFeDeeplinkApplications::route('/'),
            'create' => CreateFeDeeplinkApplication::route('/create'),
            'view' => ViewFeDeeplinkApplication::route('/{record}'),
            'edit' => EditFeDeeplinkApplication::route('/{record}/edit'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return FeDeeplinkApplicationForm::configure($schema);
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function canEdit(mixed $record): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
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
