<?php

namespace App\Filament\Resources\WorkflowConfigurations\Schemas;

use App\Models\SalesProject;
use App\Support\Filament\WorkflowOverview;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WorkflowConfigurationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-workflow-view'])
            ->columns(1)
            ->components([
                Section::make('Trạng thái và chuyển bước')
                    ->description('Workflow đang được PROD áp dụng trực tiếp cho dự án.')
                    ->schema([
                        TextEntry::make('workflow_overview')
                            ->hiddenLabel()
                            ->state(fn (SalesProject $record): HtmlString => WorkflowOverview::render($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
