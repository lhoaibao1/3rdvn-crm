<?php

namespace App\Filament\Resources\CrmTeams\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CrmTeamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Thông tin Team')
                    ->columnSpan(5)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Tên Team'),
                        TextEntry::make('code')->label('Mã Team')->badge(),
                        TextEntry::make('manager.name')->label('Trưởng Team')->placeholder('-')->columnSpanFull(),
                        IconEntry::make('is_active')->label('Đang hoạt động')->boolean(),
                        TextEntry::make('members_count')->label('Số thành viên')->counts('members')->numeric(),
                        TextEntry::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y'),
                        TextEntry::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y'),
                    ]),
                Section::make('Thành viên')
                    ->columnSpan(7)
                    ->schema([
                        TextEntry::make('members.name')
                            ->label('Danh sách nhân viên')
                            ->badge()
                            ->placeholder('Chưa có thành viên'),
                    ]),
            ]);
    }
}
