<?php

namespace App\Filament\Resources\LotteFinanceApplications;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\LotteFinanceApplications\Pages\EditLotteFinanceApplication;
use App\Filament\Resources\LotteFinanceApplications\Pages\ListLotteFinanceApplications;
use App\Filament\Resources\LotteFinanceApplications\Pages\ViewLotteFinanceApplication;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class LotteFinanceApplicationResource extends ApplicationResource
{
    protected static ?string $slug = 'applications/lotte-finance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getPages(): array
    {
        return [
            'index' => ListLotteFinanceApplications::route('/'),
            'view' => ViewLotteFinanceApplication::route('/{record}'),
            'edit' => EditLotteFinanceApplication::route('/{record}/edit'),
        ];
    }

    protected static function projectSlug(): string
    {
        return 'lotte-finance';
    }

    protected static function projectName(): string
    {
        return 'Lotte Finance';
    }

    protected static function projectSort(): int
    {
        return 30;
    }
}
