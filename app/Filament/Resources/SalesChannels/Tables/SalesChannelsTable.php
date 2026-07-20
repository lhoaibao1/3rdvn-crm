<?php

namespace App\Filament\Resources\SalesChannels\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->defaultSort('company_name')
            ->columns([
                IconColumn::make('is_active')->label('Dùng')->boolean(),
                TextColumn::make('company_name')->label('Tên công ty')->searchable()->sortable(),
                TextColumn::make('branch_name')->label('Chi nhánh')->searchable()->sortable(),
                TextColumn::make('branch_code')->label('Mã chi nhánh')->badge()->searchable(),
                TextColumn::make('channel_name')->label('Kênh')->badge()->searchable()->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn ($record): string => \App\Filament\Resources\SalesChannels\SalesChannelResource::getUrl('view', ['record' => $record])),
                    EditAction::make()->label('Sửa'),
                ])
                    ->iconButton()
                    ->label('Hành động')
                    ->tooltip('Hành động')
                    ->color('gray')
                    ->size('sm')
                    ->dropdownPlacement('bottom-end')
                    ->icon(Heroicon::EllipsisVertical),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Xóa đã chọn'),
                ]),
            ]);
    }
}
