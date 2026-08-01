<?php

namespace App\Filament\Resources\CrmTeams\Tables;

use App\Filament\Resources\CrmTeams\CrmTeamResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrmTeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
                TextColumn::make('code')->label('Mã Team')->badge()->searchable()->sortable(),
                TextColumn::make('name')->label('Tên Team')->weight('bold')->searchable()->sortable(),
                TextColumn::make('manager.name')->label('Trưởng Team')->searchable()->sortable()->placeholder('-'),
                TextColumn::make('members_count')->label('Thành viên')->counts('members')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem')
                        ->url(fn ($record): string => CrmTeamResource::getUrl('view', ['record' => $record])),
                    EditAction::make()->label('Sửa'),
                ])
                    ->iconButton()
                    ->label('Hành động')
                    ->tooltip('Hành động')
                    ->color('gray')
                    ->size('sm')
                    ->dropdownPlacement('bottom-end')
                    ->icon(Heroicon::EllipsisVertical),
            ]);
    }
}
