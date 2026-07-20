<?php

namespace App\Filament\Resources\UiSettings;

use App\Filament\Resources\UiSettings\Pages\CreateUiSetting;
use App\Filament\Resources\UiSettings\Pages\EditUiSetting;
use App\Filament\Resources\UiSettings\Pages\ListUiSettings;
use App\Filament\Resources\UiSettings\Pages\ViewUiSetting;
use App\Filament\Resources\UiSettings\Schemas\UiSettingForm;
use App\Filament\Resources\UiSettings\Schemas\UiSettingInfolist;
use App\Filament\Resources\UiSettings\Tables\UiSettingsTable;
use App\Models\UiSetting;
use App\Support\Filament\ModuleNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UiSettingResource extends Resource
{
    protected static ?string $model = UiSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function getModelLabel(): string
    {
        return 'Cài đặt giao diện';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cài đặt giao diện';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('settings', 'Cài đặt giao diện');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return ModuleNavigation::visible('settings', 'settings.view');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'UAT';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public static function form(Schema $schema): Schema
    {
        return UiSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UiSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UiSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUiSettings::route('/'),
            'create' => CreateUiSetting::route('/create'),
            'view' => ViewUiSetting::route('/{record}'),
            'edit' => EditUiSetting::route('/{record}/edit'),
        ];
    }
}
