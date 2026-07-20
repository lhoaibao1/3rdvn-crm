<?php

namespace App\Filament\Resources\SalesChannels\Pages;

use App\Filament\Resources\SalesChannels\SalesChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSalesChannel extends EditRecord
{
    protected static string $resource = SalesChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Xem kênh')
                ->icon(Heroicon::OutlinedEye),
            DeleteAction::make()
                ->label('Xóa kênh')
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
