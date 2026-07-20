<?php

namespace App\Filament\Resources\SaleProfiles\Pages;

use App\Filament\Resources\SaleProfiles\SaleProfileResource;
use App\Models\SaleProfile;
use App\Support\Filament\SaleProfileProcessAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSaleProfile extends ViewRecord
{
    protected static string $resource = SaleProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SaleProfileProcessAction::make(fn (): SaleProfile => $this->record),
            EditAction::make()->label('Sửa')->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
