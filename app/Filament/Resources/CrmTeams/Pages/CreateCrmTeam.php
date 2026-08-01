<?php

namespace App\Filament\Resources\CrmTeams\Pages;

use App\Filament\Resources\CrmTeams\CrmTeamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmTeam extends CreateRecord
{
    protected static string $resource = CrmTeamResource::class;

    protected function afterCreate(): void
    {
        $memberIds = $this->form->getRawState()['member_ids'] ?? [];

        $this->getRecord()->syncMembers(is_array($memberIds) ? $memberIds : []);
    }
}
