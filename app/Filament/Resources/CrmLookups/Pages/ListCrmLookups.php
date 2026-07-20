<?php

namespace App\Filament\Resources\CrmLookups\Pages;

use App\Filament\Resources\CrmLookups\CrmLookupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCrmLookups extends ListRecords
{
    protected static string $resource = CrmLookupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo danh mục')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
