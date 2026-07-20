<?php

namespace App\Filament\Resources\CrmModules\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CrmModuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Module')->schema([
                TextEntry::make('label')->label('Tên hiển thị'),
                TextEntry::make('slug')->label('Slug'),
                TextEntry::make('route_name')->label('Route'),
                TextEntry::make('sort_order')->label('Thứ tự')->numeric(),
                IconEntry::make('is_active')->label('Đang bật')->boolean(),
            ])->columns(3),
            Section::make('Map role/quyền')->schema([
                TextEntry::make('required_roles')->label('Roles')->badge()->placeholder('-'),
                TextEntry::make('required_permissions')->label('Permissions')->badge()->placeholder('-'),
            ]),
        ]);
    }
}
