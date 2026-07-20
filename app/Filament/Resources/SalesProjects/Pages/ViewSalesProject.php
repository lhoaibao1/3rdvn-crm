<?php

namespace App\Filament\Resources\SalesProjects\Pages;

use App\Filament\Resources\SalesProjects\SalesProjectResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSalesProject extends ViewRecord
{
    protected static string $resource = SalesProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Sửa dự án')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
