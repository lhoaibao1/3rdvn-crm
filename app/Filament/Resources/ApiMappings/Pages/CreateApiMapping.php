<?php

namespace App\Filament\Resources\ApiMappings\Pages;

use App\Filament\Resources\ApiMappings\ApiMappingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiMapping extends CreateRecord
{
    protected static string $resource = ApiMappingResource::class;
}
