<?php

namespace App\Support\Filament;

use App\Support\Filament\LeadCreate\CreateAclMixLeadAction;
use App\Support\Filament\LeadCreate\CreateCbpLeadAction;
use App\Support\Filament\LeadCreate\CreateLotteFinanceLeadAction;

class LeadCreateAction
{
    public static function make(): array
    {
        return [
            CreateAclMixLeadAction::make(),
            CreateCbpLeadAction::make(),
            CreateLotteFinanceLeadAction::make(),
        ];
    }
}
