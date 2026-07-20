<?php

namespace App\Filament\Resources\CrmLookups\Pages;

use App\Filament\Resources\CrmLookups\CrmLookupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCrmLookup extends EditRecord
{
    protected static string $resource = CrmLookupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Xem danh mục')
                ->icon(Heroicon::OutlinedEye),
            DeleteAction::make()
                ->label('Xóa danh mục')
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
