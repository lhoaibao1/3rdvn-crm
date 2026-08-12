<?php

namespace App\Filament\Resources\SalesChannels;

use App\Filament\Resources\SalesChannels\Pages\CreateSalesChannel;
use App\Filament\Resources\SalesChannels\Pages\EditSalesChannel;
use App\Filament\Resources\SalesChannels\Pages\ListSalesChannels;
use App\Filament\Resources\SalesChannels\Pages\ViewSalesChannel;
use App\Filament\Resources\SalesChannels\Schemas\SalesChannelForm;
use App\Filament\Resources\SalesChannels\Schemas\SalesChannelInfolist;
use App\Filament\Resources\SalesChannels\Tables\SalesChannelsTable;
use App\Models\SalesChannel;
use App\Support\Filament\AdminOnlyResource;
use App\Support\Filament\ModuleNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalesChannelResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = SalesChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function getModelLabel(): string
    {
        return 'Kênh bán hàng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kênh bán hàng';
    }

    public static function getNavigationLabel(): string
    {
        return ModuleNavigation::label('sales-channels', 'Kênh bán hàng');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Config Modules';
    }

    public static function getNavigationSort(): ?int
    {
        return 83;
    }

    public static function form(Schema $schema): Schema
    {
        return SalesChannelForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesChannelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesChannelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesChannels::route('/'),
            'create' => CreateSalesChannel::route('/create'),
            'view' => ViewSalesChannel::route('/{record}'),
            'edit' => EditSalesChannel::route('/{record}/edit'),
        ];
    }
}
