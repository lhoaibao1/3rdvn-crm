<?php

namespace App\Support\Filament\LeadCreate;

use App\Support\VietnamAddressCatalog;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class LeadAddressFields
{
    public static function make(): array
    {
        return [
            Textarea::make('address')
                ->label('Địa chỉ chi tiết')
                ->rows(2),
            Select::make('province_code')
                ->label('Tỉnh/Thành phố')
                ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                ->placeholder('Chọn tỉnh/thành phố')
                ->live()
                ->required()
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    $set('province_name', VietnamAddressCatalog::provinceName($state));
                    $set('district_code', null);
                    $set('district_name', null);
                    $set('ward_code', null);
                    $set('ward_name', null);
                })
                ->searchable()
                ->preload()
                ->native(false),
            Select::make('district_code')
                ->label('Quận/Huyện')
                ->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions($get('province_code')))
                ->placeholder('Chọn quận/huyện')
                ->disabled(fn (Get $get): bool => blank($get('province_code')))
                ->live()
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                    $set('district_name', VietnamAddressCatalog::districtName($get('province_code'), $state));
                    $set('ward_code', null);
                    $set('ward_name', null);
                })
                ->searchable()
                ->preload()
                ->native(false),
            Select::make('ward_code')
                ->label('Phường/Xã')
                ->options(fn (Get $get): array => VietnamAddressCatalog::wardOptions($get('district_code')))
                ->placeholder('Chọn phường/xã')
                ->disabled(fn (Get $get): bool => blank($get('district_code')))
                ->live()
                ->required()
                ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set('ward_name', VietnamAddressCatalog::wardName($get('district_code'), $state)))
                ->searchable()
                ->preload()
                ->native(false),
            Hidden::make('province_name')->dehydrated(),
            Hidden::make('district_name')->dehydrated(),
            Hidden::make('ward_name')->dehydrated(),
        ];
    }
}
