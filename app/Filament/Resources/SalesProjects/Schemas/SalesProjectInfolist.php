<?php

namespace App\Filament\Resources\SalesProjects\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dự án bán hàng')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Tên dự án'),
                        TextEntry::make('slug')->label('Mã dự án')->badge(),
                        TextEntry::make('crmModule.label')->label('Module sử dụng')->placeholder('-'),
                        TextEntry::make('code_prefix')->label('Prefix mã')->placeholder('-'),
                        TextEntry::make('sort_order')->label('Thứ tự')->numeric(),
                        IconEntry::make('is_active')->label('Đang dùng')->boolean(),
                        TextEntry::make('description')->label('Ghi chú')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
