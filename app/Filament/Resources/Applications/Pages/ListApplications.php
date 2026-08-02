<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    public function getHeading(): string
    {
        return '';
    }

    #[On('applicationRecordsChanged')]
    public function refreshApplicationRecords(): void {}
}
