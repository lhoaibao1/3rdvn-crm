<?php

namespace App\Filament\Resources\WorkflowConfigurations;

use App\Filament\Resources\WorkflowConfigurations\Pages\EditWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\Pages\ListWorkflowConfigurations;
use App\Filament\Resources\WorkflowConfigurations\Pages\ViewWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\Schemas\WorkflowConfigurationForm;
use App\Filament\Resources\WorkflowConfigurations\Schemas\WorkflowConfigurationInfolist;
use App\Filament\Resources\WorkflowConfigurations\Tables\WorkflowConfigurationsTable;
use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkflowConfigurationResource extends Resource
{
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
        return 'Admin';
    }

    public static function getNavigationSort(): ?int
    {
        return 21;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'uat'
            && (auth()->user()?->hasRole('Admin') ?? false);
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('slug', ProjectWorkflowConfiguration::supportedSlugs())
            ->orderBy('sort_order');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowConfigurations::route('/'),
            'view' => ViewWorkflowConfiguration::route('/{record}'),
            'edit' => EditWorkflowConfiguration::route('/{record}/edit'),
        ];
    }
}
