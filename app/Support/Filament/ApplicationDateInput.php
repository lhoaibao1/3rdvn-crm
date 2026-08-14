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
            ->mask('99/99/9999')
            ->placeholder('dd/mm/yyyy')
            ->extraInputAttributes(['inputmode' => 'numeric', 'autocomplete' => 'off'])
            ->maxLength(10)
            ->rule('date_format:d/m/Y')
            ->formatStateUsing(fn (?string $state): ?string => filled($state)
                ? CarbonImmutable::parse($state)->format('d/m/Y')
                : null)
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                ? CarbonImmutable::createFromFormat('d/m/Y', $state)->format('Y-m-d')
                : null);
    }
}
