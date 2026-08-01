<?php

namespace App\Filament\Resources\CrmTeams\Pages;

use App\Filament\Resources\CrmTeams\CrmTeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCrmTeams extends ListRecords
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo Team')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
