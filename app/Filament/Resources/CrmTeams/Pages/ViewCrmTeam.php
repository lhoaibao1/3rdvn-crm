<?php

namespace App\Filament\Resources\CrmTeams\Pages;

use App\Filament\Resources\CrmTeams\CrmTeamResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCrmTeam extends ViewRecord
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Sửa Team')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
