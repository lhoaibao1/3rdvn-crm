<?php

namespace App\Filament\Resources\SalesChannels\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Kênh bán hàng')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Tên công ty')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('branch_name')
                            ->label('Chi nhánh')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('branch_code')
                            ->label('Mã chi nhánh')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('channel_name')
                            ->label('Kênh')
                            ->required()
                            ->maxLength(100),
                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Đang dùng')
                            ->default(true),
                    ]),
            ]);
    }
}
