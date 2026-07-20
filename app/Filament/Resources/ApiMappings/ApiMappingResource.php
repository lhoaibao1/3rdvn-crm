<?php

namespace App\Filament\Resources\ApiMappings;

use App\Filament\Resources\ApiMappings\Pages\CreateApiMapping;
use App\Filament\Resources\ApiMappings\Pages\EditApiMapping;
use App\Filament\Resources\ApiMappings\Pages\ListApiMappings;
use App\Filament\Resources\ApiMappings\Pages\ViewApiMapping;
use App\Filament\Resources\ApiMappings\Schemas\ApiMappingForm;
use App\Filament\Resources\ApiMappings\Schemas\ApiMappingInfolist;
use App\Filament\Resources\ApiMappings\Tables\ApiMappingsTable;
use App\Models\ApiMapping;
use App\Support\Filament\ModuleNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApiMappingResource extends Resource
{
    protected static ?string $model = ApiMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function getModelLabel(): string
    {
        return 'API Mapping';
    }

    public static function getPluralModelLabel(): string
    {
        return 'API Mapping';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('api-mappings', 'API Mapping');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return ModuleNavigation::visible('api-mappings', 'api_mapping.view');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Config Modules';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function form(Schema $schema): Schema
    {
        return ApiMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApiMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiMappingsTable::configure($table);
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
            'index' => ListApiMappings::route('/'),
            'create' => CreateApiMapping::route('/create'),
            'view' => ViewApiMapping::route('/{record}'),
            'edit' => EditApiMapping::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
