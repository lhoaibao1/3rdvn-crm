<?php

namespace App\Filament\Resources\ProjectReports;

use App\Filament\Resources\ProjectReports\Pages\CreateProjectReport;
use App\Filament\Resources\ProjectReports\Pages\EditProjectReport;
use App\Filament\Resources\ProjectReports\Pages\ListProjectReports;
use App\Filament\Resources\ProjectReports\Pages\ViewProjectReport;
use App\Filament\Resources\ProjectReports\Schemas\ProjectReportForm;
use App\Filament\Resources\ProjectReports\Schemas\ProjectReportInfolist;
use App\Filament\Resources\ProjectReports\Tables\ProjectReportsTable;
use App\Models\ProjectReport;
use App\Support\Reports\ProjectReportAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProjectReportResource extends Resource
{
    protected static ?string $model = ProjectReport::class;

    protected static ?string $slug = 'bao-cao';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public static function getModelLabel(): string
    {
        return 'Báo cáo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Báo cáo';
    }

    public static function getNavigationLabel(): string
    {
        return 'Báo cáo';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CRM';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return ProjectReportAccess::projectOptions(Auth::user()) !== [];
    }

    public static function canViewAny(): bool
    {
        return ProjectReportAccess::projectOptions(Auth::user()) !== [];
    }

    public static function canView(mixed $record): bool
    {
        $user = Auth::user();

        return $record instanceof ProjectReport
            && ($user?->hasRole('Admin') || $record->created_by_id === $user?->getKey());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ProjectReport && (bool) Auth::user()?->hasRole('Admin');
    }

    public static function canDelete(mixed $record): bool
    {
        return $record instanceof ProjectReport && (bool) Auth::user()?->hasRole('Admin');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectReportsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return ProjectReportAccess::applyVisibleTo(
            parent::getEloquentQuery()->with(['salesProject.crmModule', 'application', 'createdBy', 'statusUpdatedBy', 'convertedBy']),
            Auth::user(),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectReports::route('/'),
            'create' => CreateProjectReport::route('/create'),
            'view' => ViewProjectReport::route('/{record}'),
            'edit' => EditProjectReport::route('/{record}/edit'),
        ];
    }
}
