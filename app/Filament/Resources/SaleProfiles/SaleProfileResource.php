<?php

namespace App\Filament\Resources\SaleProfiles;

use App\Filament\Resources\SaleProfiles\Pages\CreateSaleProfile;
use App\Filament\Resources\SaleProfiles\Pages\EditSaleProfile;
use App\Filament\Resources\SaleProfiles\Pages\ListSaleProfiles;
use App\Filament\Resources\SaleProfiles\Pages\ViewSaleProfile;
use App\Filament\Resources\SaleProfiles\Schemas\SaleProfileForm;
use App\Filament\Resources\SaleProfiles\Schemas\SaleProfileInfolist;
use App\Filament\Resources\SaleProfiles\Tables\SaleProfilesTable;
use App\Models\SaleProfile;
use App\Support\Filament\ModuleNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SaleProfileResource extends Resource
{
    protected static ?string $model = SaleProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getModelLabel(): string
    {
        return 'Hồ sơ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Hồ sơ';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('profiles', 'Hồ sơ');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return ModuleNavigation::visible('profiles', 'profile.view');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CRM';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function form(Schema $schema): Schema
    {
        return SaleProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaleProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaleProfilesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyVisibleTo(
            parent::getEloquentQuery()->with(['saleOwner', 'processingOwner', 'approvedBy', 'sourceLead', 'team'])
        );
    }

    public static function canCreate(): bool
    {
        return false;
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
            'index' => ListSaleProfiles::route('/'),
            'create' => CreateSaleProfile::route('/create'),
            'view' => ViewSaleProfile::route('/{record}'),
            'edit' => EditSaleProfile::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::applyVisibleTo(
            parent::getRecordRouteBindingEloquentQuery()
                ->with(['saleOwner', 'processingOwner', 'approvedBy', 'sourceLead', 'team'])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
        );
    }

    private static function applyVisibleTo(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user): void {
            $scope
                ->where('sale_owner_id', $user->getKey())
                ->orWhere('processing_owner_id', $user->getKey());

            if ($user->hasRole('ZD')) {
                $scope
                    ->orWhereHas('saleOwner', fn (Builder $owner): Builder => $owner->where('zd_id', $user->getKey()))
                    ->orWhereHas('processingOwner', fn (Builder $owner): Builder => $owner->where('zd_id', $user->getKey()));
            } elseif ($user->hasRole('AM')) {
                $scope
                    ->orWhereHas('saleOwner', fn (Builder $owner): Builder => $owner->where('am_id', $user->getKey()))
                    ->orWhereHas('processingOwner', fn (Builder $owner): Builder => $owner->where('am_id', $user->getKey()));
            } elseif ($user->hasRole('Team Leader')) {
                $scope
                    ->orWhereHas('saleOwner', fn (Builder $owner): Builder => $owner->where('team_leader_id', $user->getKey()))
                    ->orWhereHas('processingOwner', fn (Builder $owner): Builder => $owner->where('team_leader_id', $user->getKey()));
            }
        });
    }
}
