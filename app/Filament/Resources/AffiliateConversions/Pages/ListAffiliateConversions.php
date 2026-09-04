<?php

namespace App\Filament\Resources\AffiliateConversions\Pages;

use App\Filament\Resources\AffiliateConversions\AffiliateConversionResource;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateConversions extends ListRecords
{
    protected static string $resource = AffiliateConversionResource::class;

    public function getHeading(): string
    {
        return 'Danh Sách Leads & Chuyển Đổi Tiếp Thị';
    }

    public function getSubheading(): ?string
    {
        return 'Theo dõi hồ sơ khách hàng, lượt đăng ký và kết quả giải ngân từ các chiến dịch tiếp thị liên kết.';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
