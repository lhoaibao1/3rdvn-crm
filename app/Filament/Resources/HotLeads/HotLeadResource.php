<?php

namespace App\Filament\Resources\HotLeads;

use App\Filament\Resources\HotLeads\Pages\CreateHotLead;
use App\Filament\Resources\HotLeads\Pages\EditHotLead;
use App\Filament\Resources\HotLeads\Pages\ListHotLeads;
use App\Filament\Resources\HotLeads\Pages\ViewHotLead;
use App\Filament\Resources\HotLeads\Schemas\HotLeadInfolist;
use App\Filament\Resources\HotLeads\Tables\HotLeadsTable;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Models\Lead;
use App\Support\Filament\ModuleNavigation;
use App\Support\Permissions\HotLeadAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HotLeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    public static function getModelLabel(): string
    {
        return 'Lead nóng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lead nóng';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('hot-leads', 'Lead nóng');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return ModuleNavigation::visible('hot-leads', 'hot_lead.view');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CRM';
    }

    public static function getNavigationSort(): ?int
    {
        return 15;
    }

    public static function canViewAny(): bool
    {
        return HotLeadAccess::canAccessModule(Auth::user());
    }

    public static function canView(mixed $record): bool
    {
        return $record instanceof Lead && HotLeadAccess::canView(Auth::user(), $record);
    }

    public static function canCreate(): bool
    {
        return HotLeadAccess::canCreate(Auth::user());
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof Lead
            && (bool) Auth::user()?->hasRole('Admin')
            && HotLeadAccess::canView(Auth::user(), $record);
    }

    public static function canDelete(mixed $record): bool
    {
        return $record instanceof Lead && (bool) Auth::user()?->hasRole('Admin');
    }

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HotLeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HotLeadsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return HotLeadAccess::applyVisibleTo(
            parent::getEloquentQuery()->with([
                'salesProject',
                'assignedSale',
                'createdBy',
                'team',
                'teamLeader',
                'am',
                'zd',
                'application.salesProject',
                'application.assignedSale',
                'convertedSaleProfile',
                'convertedBy',
            ]),
            Auth::user()
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHotLeads::route('/'),
            'create' => CreateHotLead::route('/create'),
            'view' => ViewHotLead::route('/{record}'),
            'edit' => EditHotLead::route('/{record}/edit'),
        ];
    }
}
