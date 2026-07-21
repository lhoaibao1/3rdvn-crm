<?php

namespace App\Filament\Resources\SalesProjects\Tables;

use App\Filament\Resources\SalesProjects\SalesProjectResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->defaultSort('sort_order')
            ->columns([
                IconColumn::make('is_active')->label('Dùng')->boolean(),
                TextColumn::make('name')->label('Tên dự án')->searchable()->sortable(),
                TextColumn::make('slug')->label('Mã dự án')->badge()->searchable(),
                TextColumn::make('crmModule.label')->label('Module')->searchable()->sortable(),
                TextColumn::make('code_prefix')->label('Prefix mã')->placeholder('-')->toggleable(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn ($record): string => SalesProjectResource::getUrl('view', ['record' => $record])),
                    EditAction::make()->label('Sửa'),
                    DeleteAction::make()->label('Xóa')->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
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
