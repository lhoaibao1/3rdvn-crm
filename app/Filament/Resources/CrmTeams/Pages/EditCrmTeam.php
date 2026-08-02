<?php

namespace App\Filament\Resources\CrmTeams\Pages;

use App\Filament\Resources\CrmTeams\CrmTeamResource;
use App\Filament\Resources\CrmTeams\Pages\Concerns\PublishesCrmTeamToProduction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCrmTeam extends EditRecord
{
    use PublishesCrmTeamToProduction;

    protected static string $resource = CrmTeamResource::class;

    protected function afterSave(): void
    {
        $memberIds = $this->form->getRawState()['member_ids'] ?? [];

        $this->getRecord()->syncMembers(is_array($memberIds) ? $memberIds : []);
        $this->publishTeamToProduction();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Xem Team')
                ->icon(Heroicon::OutlinedEye),
        ];
    }
}
