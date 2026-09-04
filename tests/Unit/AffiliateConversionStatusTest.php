<?php

namespace Tests\Unit;

use App\Support\AffiliateConversionStatus;
use PHPUnit\Framework\TestCase;

class AffiliateConversionStatusTest extends TestCase
{
    public function test_business_status_mapping_matches_partner_semantics(): void
    {
        $this->assertSame('Chờ duyệt', AffiliateConversionStatus::label('pending', null));
        $this->assertSame('Đã duyệt – Chờ giải ngân', AffiliateConversionStatus::label('pending', 20_000_000));
        $this->assertSame('Giải ngân thành công', AffiliateConversionStatus::label('approved', 20_000_000));
        $this->assertSame('Bị từ chối / Hủy', AffiliateConversionStatus::label('rejected', 20_000_000));
        $this->assertSame('Đã giải ngân', AffiliateConversionStatus::label('pending', 20_000_000, 'Tin Vay'));
        $this->assertSame('Đã giải ngân', AffiliateConversionStatus::label('0', 10_000_000, 'tinvay-vietcredit'));
        $this->assertSame('Bị từ chối / Hủy', AffiliateConversionStatus::label('rejected', 20_000_000, 'Tin Vay'));
        $this->assertSame('success', AffiliateConversionStatus::tone('pending', 20_000_000, 'Tin Vay'));
    }
}
