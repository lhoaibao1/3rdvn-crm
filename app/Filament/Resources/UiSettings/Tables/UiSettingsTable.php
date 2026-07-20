<?php

namespace App\Filament\Resources\UiSettings\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UiSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(36),
                ImageColumn::make('favicon_path')
                    ->label('Favicon')
                    ->disk('public')
                    ->height(24),
                TextColumn::make('app_name')
                    ->label('Tên CRM')
                    ->searchable(),
                TextColumn::make('primary_color')
                    ->label('Màu chính')
                    ->badge(),
                TextColumn::make('login_title')
                    ->label('Login title')
                    ->limit(32),
                IconColumn::make('show_notifications')
                    ->label('Thông báo')
                    ->boolean(),
                IconColumn::make('smtp_enabled')
                    ->label('SMTP')
                    ->boolean(),
                IconColumn::make('show_search')
                    ->label('Tìm kiếm')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->since(),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn ($record): string => \App\Filament\Resources\UiSettings\UiSettingResource::getUrl('view', ['record' => $record])),
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
            ->toolbarActions([]);
    }
}
