<?php

namespace App\Filament\Resources\CrmLookups\Pages;

use App\Filament\Resources\CrmLookups\CrmLookupResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCrmLookup extends ViewRecord
{
    protected static string $resource = CrmLookupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Sửa danh mục')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
