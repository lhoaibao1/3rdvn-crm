<?php

namespace App\Filament\Resources\ApiMappings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ApiMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('mapping_name')
                    ->required(),
                TextInput::make('target_system')
                    ->required(),
                TextInput::make('endpoint_url')
                    ->url(),
                TextInput::make('method')
                    ->required()
                    ->default('POST'),
                TextInput::make('auth_type')
                    ->required()
                    ->default('None'),
                TextInput::make('request_headers_json'),
                TextInput::make('field_mapping_json'),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }
}
