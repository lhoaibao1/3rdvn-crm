<?php
namespace App\Filament\Resources\AffiliateConversions\Pages;

use App\Filament\Resources\AffiliateConversions\AffiliateConversionResource;
use App\Filament\Widgets\AffiliateCampaignsWidget;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateConversions extends ListRecords
{
    protected static string $resource = AffiliateConversionResource::class;

    public function getHeading(): string
    {
        return 'Tiếp thị liên kết';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AffiliateCampaignsWidget::class,
        ];
    }
}
