<?php

namespace App\Filament\Resources\CrmLookups\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CrmLookupInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Danh mục')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('type')
                            ->label('Nhóm')
                            ->formatStateUsing(fn (?string $state): string => CrmLookupForm::typeOptions()[$state] ?? ($state ?: '-')),
                        TextEntry::make('key')->label('Mã')->badge(),
                        TextEntry::make('label')->label('Tên hiển thị'),
                        TextEntry::make('value')->label('Giá trị phụ')->placeholder('-'),
                        TextEntry::make('sort_order')->label('Thứ tự'),
                        IconEntry::make('is_active')->label('Đang dùng')->boolean(),
                        TextEntry::make('note')->label('Ghi chú')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
