<?php

namespace App\Filament\Resources\ApiMappings\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ApiMappingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->columns([
                TextColumn::make('mapping_name')
                    ->searchable(),
                TextColumn::make('target_system')
                    ->searchable(),
                TextColumn::make('endpoint_url')
                    ->searchable(),
                TextColumn::make('method')
                    ->searchable(),
                TextColumn::make('auth_type')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn ($record): string => \App\Filament\Resources\ApiMappings\ApiMappingResource::getUrl('view', ['record' => $record])),
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
                    ForceDeleteBulkAction::make()->label('Xóa vĩnh viễn'),
                    RestoreBulkAction::make()->label('Khôi phục'),
                ]),
            ]);
    }
}
