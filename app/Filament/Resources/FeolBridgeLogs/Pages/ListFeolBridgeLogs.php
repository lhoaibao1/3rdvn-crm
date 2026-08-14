<?php

namespace App\Filament\Resources\FeolBridgeLogs\Pages;

use App\Filament\Resources\FeolBridgeLogs\FeolBridgeLogResource;
use Filament\Resources\Pages\ListRecords;

class ListFeolBridgeLogs extends ListRecords
{
    protected static string $resource = FeolBridgeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
