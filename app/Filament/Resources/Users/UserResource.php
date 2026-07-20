<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Models\SalesProject;
use App\Support\Filament\ModuleNavigation;
use App\Support\RoleHierarchy;
use BackedEnum;
use Closure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordRouteKeyName = 'uid';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getModelLabel(): string
    {
        return 'Người dùng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Người dùng';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('users', 'Người dùng');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return ModuleNavigation::visible('users', 'user.view');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Employee - Work';
    }

    public static function getNavigationSort(): ?int
    {
        return 80;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return RoleHierarchy::applyVisibilityScope(parent::getEloquentQuery()->with('roles'));
    }

    public static function resolveRecordRouteBinding(int | string $key, ?Closure $modifyQuery = null): ?Model
    {
        $query = static::getRecordRouteBindingEloquentQuery();

        if ($modifyQuery) {
            $query = $modifyQuery($query) ?? $query;
        }

        return $query
            ->where(function (Builder $query) use ($key): void {
                $query->where('uid', $key);

                if (ctype_digit((string) $key)) {
                    $query->orWhere('id', (int) $key);
                }
            })
            ->first();
    }

    public static function normalizeSalesCodes(array $data): array
    {
        if (! array_key_exists('sales_projects', $data) && ! array_key_exists('sales_codes', $data)) {
            return $data;
        }

        $projects = collect($data['sales_projects'] ?? [])
            ->filter(fn (mixed $project): bool => is_string($project) && filled($project))
            ->unique()
            ->values();

        $validProjects = SalesProject::query()
            ->where('is_active', true)
            ->whereIn('slug', $projects->all())
            ->pluck('slug')
            ->values();

        $codes = collect($data['sales_codes'] ?? []);

        $data['sales_projects'] = $validProjects->all();
        $data['sales_codes'] = $validProjects
            ->mapWithKeys(fn (string $project): array => [$project => $codes->get($project)])
            ->filter(fn (mixed $code): bool => filled($code))
            ->all();

        return $data;
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
