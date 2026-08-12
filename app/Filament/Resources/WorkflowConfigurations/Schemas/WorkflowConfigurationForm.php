<?php

namespace App\Filament\Resources\WorkflowConfigurations\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\CrmModule;
use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use App\Support\Filament\WorkflowOverview;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WorkflowConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame crm-workflow-form'])
            ->columns(1)
            ->components([
                Section::make('Dự án áp dụng')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên dự án')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set): mixed => filled($state) ? $set('slug', Str::slug($state)) : null)
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Mã dự án')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->maxLength(120),
                        Select::make('crm_module_id')
                            ->label('Module sử dụng')
                            ->options(fn (): array => CrmModule::query()->where('is_active', true)->orderBy('sort_order')->pluck('label', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('code_prefix')->label('Tiền tố mã')->maxLength(40),
                        TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(100),
                        Toggle::make('is_active')->label('Đang áp dụng')->default(true),
                    ]),
                Section::make(fn (?SalesProject $record): string => 'Sơ đồ trạng thái · '.($record?->name ?: 'Workflow mới'))
                    ->description('Sơ đồ này được PROD áp dụng trực tiếp sau khi Admin lưu.')
                    ->visible(fn (?SalesProject $record): bool => $record instanceof SalesProject)
                    ->schema([
                        Placeholder::make('workflow_overview')
                            ->hiddenLabel()
                            ->content(fn (SalesProject $record) => WorkflowOverview::render($record))
                            ->columnSpanFull(),
                    ]),
                Section::make('Trạng thái và chuyển bước')
                    ->description('Admin có thể thêm, sửa, xóa và sắp xếp trạng thái. “Được chuyển đến” quyết định các bước tiếp theo được phép chọn.')
                    ->schema([
                        Repeater::make('workflow_schema')
                            ->hiddenLabel()
                            ->afterStateHydrated(function (Repeater $component, ?SalesProject $record): void {
                                if ($record instanceof SalesProject) {
                                    $component->state(ProjectWorkflowConfiguration::forProject($record));
                                }
                            })
                            ->schema([
                                TextInput::make('status')
                                    ->label('Mã trạng thái')
                                    ->required()
                                    ->distinct()
                                    ->regex('/^[a-z0-9]+(?:_[a-z0-9]+)*$/')
                                    ->helperText('Chữ thường, số và dấu gạch dưới; ví dụ: awaiting_contract.')
                                    ->live(onBlur: true)
                                    ->columnSpan(3),
                                TextInput::make('label')
                                    ->label('Tên hiển thị')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                Select::make('mode')
                                    ->label('Cách xử lý')
                                    ->options([
                                        ProjectWorkflowConfiguration::MANUAL => 'Xử lý thủ công',
                                        ProjectWorkflowConfiguration::AUTOMATIC => 'Tự động sau khi Sale lưu',
                                        ProjectWorkflowConfiguration::SPECIAL => 'Quy tắc nghiệp vụ riêng',
                                        ProjectWorkflowConfiguration::TERMINAL => 'Điểm kết thúc',
                                        ProjectWorkflowConfiguration::LEGACY => 'Hồ sơ cũ',
                                    ])
                                    ->required()
                                    ->default(ProjectWorkflowConfiguration::MANUAL)
                                    ->native(false)
                                    ->columnSpan(3),
                                Select::make('next_statuses')
                                    ->label('Được chuyển đến')
                                    ->options(function (Get $get, ?SalesProject $record): array {
                                        $steps = $get('../../workflow_schema');

                                        if (! is_array($steps)) {
                                            $steps = $record instanceof SalesProject
                                                ? ProjectWorkflowConfiguration::forProject($record)
                                                : [];
                                        }

                                        return collect($steps)
                                            ->filter(fn (mixed $step): bool => is_array($step) && filled($step['status'] ?? null))
                                            ->mapWithKeys(fn (array $step): array => [
                                                (string) $step['status'] => (string) ($step['label'] ?? $step['status']),
                                            ])
                                            ->all();
                                    })
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpan(3),
                                Textarea::make('note')
                                    ->label('Ghi chú / quy tắc nghiệp vụ')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->itemLabel(fn (array $state): string => (string) ($state['label'] ?? $state['status'] ?? 'Trạng thái mới'))
                            ->addActionLabel('Thêm trạng thái')
                            ->addable()
                            ->deletable()
                            ->reorderable()
                            ->cloneable()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
