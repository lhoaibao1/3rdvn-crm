<?php

namespace App\Filament\Resources\SalesProjects\Pages;

use App\Filament\Resources\SalesProjects\SalesProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSalesProject extends EditRecord
{
    protected static string $resource = SalesProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Xem dự án')
                ->icon(Heroicon::OutlinedEye),
            DeleteAction::make()
                ->label('Xóa dự án')
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
