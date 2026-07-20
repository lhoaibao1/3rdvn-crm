<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Filament\AclMixDecisionAction;
use App\Support\Filament\RecordAssignAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AclMixDecisionAction::make(),
            RecordAssignAction::make('assignApplicationProcessor'),
            EditAction::make()
                ->label('Cập nhật thông tin')
                ->visible(fn (Application $record): bool => $record->salesProject?->slug !== 'acl-mix'
                    || AclMixWorkflow::canEditData(auth()->user(), $record)),
        ];
    }
}
