<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
use App\Support\Filament\ModuleNavigation;
use App\Support\Permissions\LeadAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    public static function getModelLabel(): string
    {
        return 'Lead';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lead';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('leads', 'Lead');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        // Lead cũ được giữ để tương thích dữ liệu/liên kết, nhưng không còn hiển thị như một module.
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'CRM';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return LeadAccess::applyVisibleTo(parent::getEloquentQuery(), Auth::user());
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
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return LeadAccess::applyVisibleTo(
            parent::getRecordRouteBindingEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]),
            Auth::user()
        );
    }
}
