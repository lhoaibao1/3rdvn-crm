<?php

namespace App\Filament\Resources\ApiMappings\Schemas;

use App\Models\ApiMapping;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ApiMappingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('mapping_name'),
                TextEntry::make('target_system'),
                TextEntry::make('endpoint_url')
                    ->placeholder('-'),
                TextEntry::make('method'),
                TextEntry::make('auth_type'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ApiMapping $record): bool => $record->trashed()),
            ]);
    }
}
