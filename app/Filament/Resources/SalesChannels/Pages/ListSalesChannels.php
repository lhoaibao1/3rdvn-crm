<?php

namespace App\Filament\Resources\SalesChannels\Pages;

use App\Filament\Resources\SalesChannels\SalesChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSalesChannels extends ListRecords
{
    protected static string $resource = SalesChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo kênh')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
