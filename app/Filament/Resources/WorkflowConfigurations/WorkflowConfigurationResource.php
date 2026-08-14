<?php

namespace App\Filament\Resources\WorkflowConfigurations;

use App\Filament\Resources\WorkflowConfigurations\Pages\CreateWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\Pages\EditWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\Pages\ListWorkflowConfigurations;
use App\Filament\Resources\WorkflowConfigurations\Pages\ViewWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\Schemas\WorkflowConfigurationForm;
use App\Filament\Resources\WorkflowConfigurations\Schemas\WorkflowConfigurationInfolist;
use App\Filament\Resources\WorkflowConfigurations\Tables\WorkflowConfigurationsTable;
use App\Models\SalesProject;
use App\Support\Filament\AdminOnlyResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkflowConfigurationResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = SalesProject::class;

    protected static ?string $slug = 'admin/workflow-configurations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function getModelLabel(): string
    {
        return 'Workflow dự án';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Workflow';
    }

    public static function getNavigationLabel(): string
    {
        return 'Workflow';
    }

    public static function getNavigationGroup(): ?string
    {
        return \App\Support\Filament\AdminNavigation::GROUP;
    }

    public static function getNavigationSort(): ?int
    {
        return 21;
    }

    public static function form(Schema $schema): Schema
    {
        return WorkflowConfigurationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WorkflowConfigurationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkflowConfigurationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowConfigurations::route('/'),
            'create' => CreateWorkflowConfiguration::route('/create'),
            'view' => ViewWorkflowConfiguration::route('/{record}'),
            'edit' => EditWorkflowConfiguration::route('/{record}/edit'),
        ];
    }
}
