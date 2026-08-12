<?php

namespace App\Filament\Resources\CrmLookups;

use App\Filament\Resources\CrmLookups\Pages\CreateCrmLookup;
use App\Filament\Resources\CrmLookups\Pages\EditCrmLookup;
use App\Filament\Resources\CrmLookups\Pages\ListCrmLookups;
use App\Filament\Resources\CrmLookups\Pages\ViewCrmLookup;
use App\Filament\Resources\CrmLookups\Schemas\CrmLookupForm;
use App\Filament\Resources\CrmLookups\Schemas\CrmLookupInfolist;
use App\Filament\Resources\CrmLookups\Tables\CrmLookupsTable;
use App\Models\CrmLookup;
use App\Support\Filament\AdminOnlyResource;
use App\Support\Filament\ModuleNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CrmLookupResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = CrmLookup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public static function getModelLabel(): string
    {
        return 'Danh mục user';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Danh mục user';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('lookups', 'Danh mục user');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Config Modules';
    }

    public static function getNavigationSort(): ?int
    {
        return 82;
    }

    public static function form(Schema $schema): Schema
    {
        return CrmLookupForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CrmLookupInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmLookupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmLookups::route('/'),
            'create' => CreateCrmLookup::route('/create'),
            'view' => ViewCrmLookup::route('/{record}'),
            'edit' => EditCrmLookup::route('/{record}/edit'),
        ];
    }
}
