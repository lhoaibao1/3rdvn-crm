<?php

namespace App\Filament\Resources\SalesProjects;

use App\Filament\Resources\SalesProjects\Pages\CreateSalesProject;
use App\Filament\Resources\SalesProjects\Pages\EditSalesProject;
use App\Filament\Resources\SalesProjects\Pages\ListSalesProjects;
use App\Filament\Resources\SalesProjects\Pages\ViewSalesProject;
use App\Filament\Resources\SalesProjects\Schemas\SalesProjectForm;
use App\Filament\Resources\SalesProjects\Schemas\SalesProjectInfolist;
use App\Filament\Resources\SalesProjects\Tables\SalesProjectsTable;
use App\Models\SalesProject;
use App\Support\Filament\AdminOnlyResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalesProjectResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = SalesProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function getModelLabel(): string
    {
        return 'Cấu hình dự án';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cấu hình dự án';
    }

    public static function getNavigationLabel(): string
    {
        return 'Cấu hình dự án';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Config Modules';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function form(Schema $schema): Schema
    {
        return SalesProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesProjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesProjects::route('/'),
            'create' => CreateSalesProject::route('/create'),
            'view' => ViewSalesProject::route('/{record}'),
            'edit' => EditSalesProject::route('/{record}/edit'),
        ];
    }
}
