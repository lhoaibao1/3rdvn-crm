<?php

namespace App\Filament\Widgets;

use App\Models\AffiliateCampaign;
use Filament\Widgets\Widget;

class AffiliateCampaignsWidget extends Widget
{
    protected string $view = 'filament.widgets.affiliate-campaigns';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'campaigns' => AffiliateCampaign::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'employeeCode' => trim((string) auth()->user()?->employee_code),
        ];
    }
}
