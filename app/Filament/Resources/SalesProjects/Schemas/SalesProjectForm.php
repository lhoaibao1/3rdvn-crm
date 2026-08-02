<?php

namespace App\Filament\Resources\SalesProjects\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\CrmModule;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SalesProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])
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
            ]);
    }
}
