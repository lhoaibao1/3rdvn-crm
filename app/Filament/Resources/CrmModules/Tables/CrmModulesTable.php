<?php

namespace App\Filament\Resources\CrmModules\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->defaultSort('sort_order')
            ->columns([
                IconColumn::make('is_active')->label('Bật')->boolean(),
                TextColumn::make('label')->label('Module')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->badge()->searchable(),
                TextColumn::make('route_name')->label('Route')->searchable()->toggleable(),
                TextColumn::make('required_roles')->label('Roles')->badge()->placeholder('-'),
                TextColumn::make('required_permissions')->label('Quyền')->badge()->limitList(3)->expandableLimitedList()->placeholder('-'),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn ($record): string => \App\Filament\Resources\CrmModules\CrmModuleResource::getUrl('view', ['record' => $record])),
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
