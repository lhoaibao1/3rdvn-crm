<?php

namespace App\Filament\Resources\SalesProjects\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\CrmModule;
use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SalesProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dự án bán hàng')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên dự án')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set): mixed => filled($state) ? $set('slug', Str::slug($state)) : null)
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Đường dẫn dự án')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(120),
                        Select::make('crm_module_id')
                            ->label('Module sử dụng')
                            ->options(fn (): array => CrmModule::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->orderBy('label')
                                ->pluck('label', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('code_prefix')
                            ->label('Tiền tố mã bán hàng')
                            ->maxLength(40),
                        TextInput::make('sort_order')
                            ->label('Thứ tự')
                            ->numeric()
                            ->default(100),
                        Toggle::make('is_active')
                            ->label('Đang dùng')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Ghi chú')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Chi tiết workflow')
                    ->description('Danh sách này là nguồn cấu hình các bước được phép chuyển. Admin vẫn phải đi đúng workflow, chỉ được bỏ qua dữ liệu bắt buộc.')
                    ->visible(fn (?SalesProject $record): bool => Filament::getCurrentPanel()?->getId() === 'uat'
                        && ProjectWorkflowConfiguration::supports($record?->slug))
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('workflow_schema')
                            ->hiddenLabel()
                            ->afterStateHydrated(function (Repeater $component, ?SalesProject $record): void {
                                if ($record instanceof SalesProject && ProjectWorkflowConfiguration::supports($record->slug)) {
                                    $component->state(ProjectWorkflowConfiguration::forProject($record));
                                }
                            })
                            ->schema([
                                Hidden::make('status'),
                                Hidden::make('mode'),
                                Hidden::make('label'),
                                Hidden::make('note'),
                                Placeholder::make('status_label')
                                    ->label('Trạng thái')
                                    ->content(fn (Get $get): string => (string) ($get('label') ?: $get('status') ?: '-'))
                                    ->columnSpan(3),
                                Placeholder::make('mode_label')
                                    ->label('Cách chuyển')
                                    ->content(fn (Get $get): string => ProjectWorkflowConfiguration::modeLabel((string) $get('mode')))
                                    ->columnSpan(2),
                                Select::make('next_statuses')
                                    ->label('Được chuyển đến')
                                    ->options(fn (?SalesProject $record): array => ProjectWorkflowConfiguration::statusOptions($record?->slug))
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->disabled(fn (Get $get): bool => ! in_array($get('mode'), [
                                        ProjectWorkflowConfiguration::MANUAL,
                                        ProjectWorkflowConfiguration::LEGACY,
                                    ], true))
                                    ->dehydrated()
                                    ->columnSpan(4),
                                Placeholder::make('workflow_note')
                                    ->label('Quy tắc')
                                    ->content(fn (Get $get): string => (string) ($get('note') ?: '-'))
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->itemLabel(fn (array $state): string => (string) ($state['label'] ?? $state['status'] ?? 'Bước workflow'))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
