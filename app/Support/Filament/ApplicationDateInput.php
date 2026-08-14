<?php

namespace App\Support\Filament;

use Carbon\CarbonImmutable;
use Filament\Forms\Components\TextInput;

class ApplicationDateInput
{
    public static function make(string $path, string $label = 'Ngày giải ngân'): TextInput
    {
        return TextInput::make($path)
            ->label($label)
            ->mask('99/99/9999 99:99')
            ->placeholder('dd/mm/yyyy HH:mm')
            ->extraInputAttributes(['inputmode' => 'numeric', 'autocomplete' => 'off'])
            ->maxLength(16)
            ->rule('date_format:d/m/Y H:i')
            ->formatStateUsing(fn (?string $state): ?string => filled($state)
                ? CarbonImmutable::parse($state)->format('d/m/Y H:i')
                : null)
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                ? CarbonImmutable::createFromFormat('d/m/Y H:i', $state)->format('Y-m-d H:i:00')
                : null);
    }
}
