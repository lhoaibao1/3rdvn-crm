<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Select;

class SearchableSelect extends Select
{
    protected string $view = 'forms.components.searchable-select';

    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false);
    }
}

