<?php

namespace App\Filament\Resources\SalesChannels\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesChannelInfolist
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
                        TextEntry::make('company_name')->label('Tên công ty'),
                        TextEntry::make('branch_name')->label('Chi nhánh'),
                        TextEntry::make('branch_code')->label('Mã chi nhánh')->badge(),
                        TextEntry::make('channel_name')->label('Kênh')->badge(),
                        IconEntry::make('is_active')->label('Đang dùng')->boolean(),
                        TextEntry::make('note')->label('Ghi chú')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
