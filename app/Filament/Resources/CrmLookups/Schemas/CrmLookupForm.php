<?php

namespace App\Filament\Resources\CrmLookups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CrmLookupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Danh mục')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        Select::make('type')
                            ->label('Nhóm danh mục')
                            ->options(self::typeOptions())
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->columnSpan(4),
                        TextInput::make('key')
                            ->label('Mã')
                            ->required()
                            ->maxLength(120)
                            ->columnSpan(4),
                        TextInput::make('label')
                            ->label('Tên hiển thị')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(4),
                        TextInput::make('value')
                            ->label('Giá trị phụ')
                            ->maxLength(255)
                            ->columnSpan(6),
                        Grid::make(2)
                            ->columnSpan(6)
                            ->schema([
                                TextInput::make('sort_order')
                                    ->label('Thứ tự')
                                    ->numeric()
                                    ->default(100),
                                Toggle::make('is_active')
                                    ->label('Đang dùng')
                                    ->default(true),
                            ]),
                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function typeOptions(): array
    {
        return [
            'document_type' => 'Loại giấy tờ',
            'issued_place' => 'Nơi cấp giấy tờ',
            'department' => 'Phòng ban',
            'employment_status' => 'Trạng thái nhân sự',
            'office' => 'Office',
            'contract_type' => 'Loại hợp đồng',
            'sales_code' => 'Mã bán hàng',
        ];
    }
}
