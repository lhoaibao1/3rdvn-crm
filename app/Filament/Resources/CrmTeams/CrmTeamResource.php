<?php

namespace App\Filament\Resources\CrmTeams;

use App\Filament\Resources\CrmTeams\Pages\CreateCrmTeam;
use App\Filament\Resources\CrmTeams\Pages\EditCrmTeam;
use App\Filament\Resources\CrmTeams\Pages\ListCrmTeams;
use App\Filament\Resources\CrmTeams\Pages\ViewCrmTeam;
use App\Filament\Resources\CrmTeams\Schemas\CrmTeamForm;
use App\Filament\Resources\CrmTeams\Schemas\CrmTeamInfolist;
use App\Filament\Resources\CrmTeams\Tables\CrmTeamsTable;
use App\Models\CrmTeam;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CrmTeamResource extends Resource
{
    protected static ?string $model = CrmTeam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getModelLabel(): string
    {
        return 'Team';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Teams';
    }

    public static function getNavigationLabel(): string
    {
        return 'Teams';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Config Modules';
    }

    public static function getNavigationSort(): ?int
    {
        return 84;
    }

    public static function form(Schema $schema): Schema
    {
        return CrmTeamForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CrmTeamInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmTeamsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmTeams::route('/'),
            'create' => CreateCrmTeam::route('/create'),
            'view' => ViewCrmTeam::route('/{record}'),
            'edit' => EditCrmTeam::route('/{record}/edit'),
        ];
    }
}
