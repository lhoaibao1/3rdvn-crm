<?php

namespace App\Filament\Resources\WorkflowConfigurations\Tables;

use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkflowConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Dự án')->weight('bold')->searchable(),
                TextColumn::make('slug')->label('Mã dự án')->badge()->color('info'),
                TextColumn::make('workflow_count')
                    ->label('Số trạng thái')
                    ->state(fn (SalesProject $record): int => count(ProjectWorkflowConfiguration::forProject($record)))
                    ->badge()
                    ->color('primary'),
                TextColumn::make('workflow_statuses')
                    ->label('Danh sách trạng thái')
                    ->state(fn (SalesProject $record): string => collect(ProjectWorkflowConfiguration::forProject($record))->pluck('label')->implode(' → '))
                    ->wrap(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem workflow')
                        ->url(fn (SalesProject $record): string => WorkflowConfigurationResource::getUrl('view', ['record' => $record])),
                    EditAction::make()->label('Cấu hình chuyển bước'),
                    DeleteAction::make()->label('Xóa Workflow'),
                ])
                    ->iconButton()
                    ->label('Hành động')
                    ->tooltip('Hành động')
                    ->color('gray')
                    ->size('sm')
                    ->dropdownPlacement('bottom-end')
                    ->icon(Heroicon::EllipsisVertical),
            ]);
    }
}
