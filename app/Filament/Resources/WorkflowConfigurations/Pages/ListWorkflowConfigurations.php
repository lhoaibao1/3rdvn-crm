<?php

namespace App\Filament\Resources\WorkflowConfigurations\Pages;

use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowConfigurations extends ListRecords
{
    protected static string $resource = WorkflowConfigurationResource::class;

    public function getTitle(): string
    {
        return 'Workflow dự án';
    }
}
