<?php

namespace App\Filament\Resources\CrmTeams\Pages;

use App\Filament\Resources\CrmTeams\CrmTeamResource;
use App\Filament\Resources\CrmTeams\Pages\Concerns\PublishesCrmTeamToProduction;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmTeam extends CreateRecord
{
    use PublishesCrmTeamToProduction;

    protected static string $resource = CrmTeamResource::class;

    protected function afterCreate(): void
    {
        $memberIds = $this->form->getRawState()['member_ids'] ?? [];

        $this->getRecord()->syncMembers(is_array($memberIds) ? $memberIds : []);
        $this->publishTeamToProduction();
    }
}
