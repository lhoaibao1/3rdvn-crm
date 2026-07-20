<?php

namespace App\Filament\Resources\DataCenterLeads;

use App\Filament\Resources\DataCenterLeads\Pages\ListDataCenterLeads;
use App\Filament\Resources\DataCenterLeads\Pages\ViewDataCenterLead;
use App\Filament\Resources\DataCenterLeads\Schemas\DataCenterLeadInfolist;
use App\Filament\Resources\DataCenterLeads\Tables\DataCenterLeadsTable;
use App\Models\DataCenterLead;
use App\Support\Permissions\DataCenterAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DataCenterLeadResource extends Resource
{
    protected static ?string $model = DataCenterLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function getModelLabel(): string
    {
        return 'Lead Referral';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lead Referral';
    }

    public static function getNavigationLabel(): string
    {
        return 'Lead Referral';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CRM';
    }

    public static function getNavigationSort(): ?int
    {
        return 18;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return DataCenterAccess::canAccessModule(Auth::user());
    }

    public static function canViewAny(): bool
    {
        return DataCenterAccess::canAccessModule(Auth::user());
    }

    public static function canView(mixed $record): bool
    {
        return $record instanceof DataCenterLead && DataCenterAccess::canView(Auth::user(), $record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return DataCenterLeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataCenterLeadsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return DataCenterAccess::applyVisibleTo(
            parent::getEloquentQuery()->with([
                'assignedUser', 'createdBy', 'team', 'teamLeader', 'am', 'zd',
                'conversions.salesProject', 'conversions.lead', 'conversions.convertedBy',
            ]),
            Auth::user(),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataCenterLeads::route('/'),
            'view' => ViewDataCenterLead::route('/{record}'),
        ];
    }
}
