<?php

namespace App\Filament\Resources\CrmLookups\Tables;

use App\Filament\Resources\CrmLookups\Schemas\CrmLookupForm;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmLookupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->defaultSort('type')
            ->columns([
                IconColumn::make('is_active')->label('Dùng')->boolean(),
                TextColumn::make('type')
                    ->label('Nhóm')
                    ->formatStateUsing(fn (?string $state): string => CrmLookupForm::typeOptions()[$state] ?? ($state ?: '-'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')->label('Tên hiển thị')->searchable()->sortable(),
                TextColumn::make('key')->label('Mã')->badge()->searchable(),
                TextColumn::make('value')->label('Giá trị phụ')->placeholder('-')->toggleable(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Nhóm danh mục')
                    ->options(CrmLookupForm::typeOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn ($record): string => \App\Filament\Resources\CrmLookups\CrmLookupResource::getUrl('view', ['record' => $record])),
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
