<?php

namespace App\Filament\Resources\CrmModules;

use App\Filament\Resources\CrmModules\Pages\CreateCrmModule;
use App\Filament\Resources\CrmModules\Pages\EditCrmModule;
use App\Filament\Resources\CrmModules\Pages\ListCrmModules;
use App\Filament\Resources\CrmModules\Pages\ViewCrmModule;
use App\Filament\Resources\CrmModules\Schemas\CrmModuleForm;
use App\Filament\Resources\CrmModules\Schemas\CrmModuleInfolist;
use App\Filament\Resources\CrmModules\Tables\CrmModulesTable;
use App\Models\CrmModule;
use App\Support\Filament\AdminOnlyResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CrmModuleResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = CrmModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    public static function getModelLabel(): string
    {
        return 'Module';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Modules';
    }

    public static function getNavigationLabel(): string
    {
        return 'Modules';
    }

    public static function getNavigationGroup(): ?string
    {
        return \App\Support\Filament\AdminNavigation::GROUP;
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function form(Schema $schema): Schema
    {
        return CrmModuleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CrmModuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmModulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmModules::route('/'),
            'create' => CreateCrmModule::route('/create'),
            'view' => ViewCrmModule::route('/{record}'),
            'edit' => EditCrmModule::route('/{record}/edit'),
        ];
    }
}
