<?php

namespace App\Filament\Resources\WorkflowConfigurations\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use App\Support\Filament\WorkflowOverview;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WorkflowConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame crm-workflow-form'])
            ->columns(1)
            ->components([
                Section::make(fn (?SalesProject $record): string => 'Sơ đồ trạng thái · '.($record?->name ?: 'Workflow'))
                    ->description('Mỗi dòng là một trạng thái thực tế. Phần “Được chuyển đến” chính là các bước người xử lý được phép chọn.')
                    ->schema([
                        Placeholder::make('workflow_overview')
                            ->hiddenLabel()
                            ->content(fn (SalesProject $record) => WorkflowOverview::render($record))
                            ->columnSpanFull(),
                    ]),
                Section::make('Cấu hình chuyển bước')
                    ->description('Các bước xử lý thủ công và bước đặc biệt được phép sửa. Bước tự động được khóa vì hệ thống tự chuyển sau khi Sale lưu; riêng Trả về Sale sẽ quay về bước trước khi trả.')
                    ->schema([
                        Repeater::make('workflow_schema')
                            ->hiddenLabel()
                            ->afterStateHydrated(function (Repeater $component, ?SalesProject $record): void {
                                if ($record instanceof SalesProject) {
                                    $component->state(ProjectWorkflowConfiguration::forProject($record));
                                }
                            })
                            ->schema([
                                Hidden::make('status'),
                                Hidden::make('mode'),
                                Hidden::make('label'),
                                Hidden::make('note'),
                                Placeholder::make('status_label')
                                    ->label('Trạng thái hiện tại')
                                    ->content(fn (Get $get): string => (string) ($get('label') ?: $get('status') ?: '-'))
                                    ->columnSpan(3),
                                Placeholder::make('status_code')
                                    ->label('Mã trạng thái')
                                    ->content(fn (Get $get): string => (string) ($get('status') ?: '-'))
                                    ->columnSpan(2),
                                Placeholder::make('mode_label')
                                    ->label('Cách xử lý')
                                    ->content(fn (Get $get): string => ProjectWorkflowConfiguration::modeLabel((string) $get('mode')))
                                    ->columnSpan(2),
                                Select::make('next_statuses')
                                    ->label('Được chuyển đến')
                                    ->options(fn (?SalesProject $record): array => ProjectWorkflowConfiguration::statusOptions($record?->slug))
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->visible(fn (Get $get): bool => ! ProjectWorkflowConfiguration::isDynamicReturnStep((string) $get('status')))
                                    ->disabled(fn (Get $get): bool => ! in_array(
                                        $get('mode'),
                                        ProjectWorkflowConfiguration::configurableModes(),
                                        true,
                                    ))
                                    ->dehydrated()
                                    ->columnSpan(5),
                                Placeholder::make('dynamic_return_step')
                                    ->label('Được chuyển đến')
                                    ->content('Quay về bước trước khi trả')
                                    ->visible(fn (Get $get): bool => ProjectWorkflowConfiguration::isDynamicReturnStep((string) $get('status')))
                                    ->columnSpan(5),
                                Placeholder::make('workflow_note')
                                    ->label('Quy tắc áp dụng')
                                    ->content(fn (Get $get): string => (string) ($get('note') ?: '-'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->itemLabel(fn (array $state): string => (string) (($state['label'] ?? $state['status'] ?? 'Trạng thái').' · '.ProjectWorkflowConfiguration::modeLabel((string) ($state['mode'] ?? ''))))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
