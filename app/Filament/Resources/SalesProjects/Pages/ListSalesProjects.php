<?php

namespace App\Filament\Resources\SalesProjects\Pages;

use App\Filament\Resources\SalesProjects\SalesProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSalesProjects extends ListRecords
{
    protected static string $resource = SalesProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo dự án')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
