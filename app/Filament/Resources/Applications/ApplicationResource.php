<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Applications\Schemas\ApplicationForm;
use App\Filament\Resources\Applications\Schemas\ApplicationInfolist;
use App\Filament\Resources\Applications\Tables\ApplicationsTable;
use App\Models\Application;
use App\Models\SalesProject;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $slug = 'applications/acl-mix';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return static::projectName();
    }

    public static function getPluralModelLabel(): string
    {
        return static::projectName();
    }

    public static function getNavigationLabel(): string
    {
        return static::projectName();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Application';
    }

    public static function getNavigationSort(): ?int
    {
        return static::projectSort();
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        $project = static::applicationProject();

        return $project instanceof SalesProject
            && (Auth::user()?->can('application.view') ?? false)
            && SalesProjectAccess::canAccessProject(Auth::user(), $project);
    }

    public static function form(Schema $schema): Schema
    {
        return ApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApplicationsTable::configure(
            $table,
            static::projectSlug(),
            'applications.'.static::projectSlug(),
            static::projectSlug(),
            static::class,
        );
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['salesProject', 'lead', 'assignedSale', 'createdBy', 'team', 'teamLeader', 'am', 'zd', 'projectReport'])
            ->whereHas('salesProject', fn (Builder $query): Builder => $query->where('slug', static::projectSlug()));

        $user = Auth::user();

        if (! $user?->hasRole('Admin')) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);

            if ($slugs === []) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereHas('salesProject', fn (Builder $query): Builder => $query->whereIn('slug', $slugs));
        }

        return RecordVisibility::applyUserScope($query, $user, 'assigned_sale_id', 'assignedSale');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'view' => ViewApplication::route('/{record}'),
            'edit' => EditApplication::route('/{record}/edit'),
        ];
    }

    protected static function projectSlug(): string
    {
        return 'acl-mix';
    }

    protected static function projectName(): string
    {
        return 'ACL Mix';
    }

    protected static function projectSort(): int
    {
        return 10;
    }

    protected static function applicationProject(): ?SalesProject
    {
        return SalesProject::query()->where('slug', static::projectSlug())->where('is_active', true)->first();
    }
}
