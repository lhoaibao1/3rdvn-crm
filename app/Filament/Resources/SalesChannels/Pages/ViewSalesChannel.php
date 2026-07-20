<?php

namespace App\Filament\Resources\SalesChannels\Pages;

use App\Filament\Resources\SalesChannels\SalesChannelResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSalesChannel extends ViewRecord
{
    protected static string $resource = SalesChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Sửa kênh')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
