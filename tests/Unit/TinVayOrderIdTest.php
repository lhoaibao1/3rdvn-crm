<?php

namespace Tests\Unit;

use App\Support\Affiliate\TinVayOrderId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TinVayOrderIdTest extends TestCase
{
    #[DataProvider('orderIds')]
    public function test_it_only_marks_padded_order_ids_as_incomplete(mixed $orderId, bool $expected): void
    {
        self::assertSame($expected, TinVayOrderId::isIncomplete($orderId));
    }

    public static function orderIds(): array
    {
        return [
            'temporary double padding' => ['P075bFRGUpOvNKRI3Xftvg==', true],
            'temporary single padding' => ['P075bFRGUpOvNKRI3Xftvg=', true],
            'temporary padding with whitespace' => ["P075bFRGUpOvNKRI3Xftvg== \n", true],
            'complete partner order' => ['P075bFRGUpOvNKRI3Xftvg', false],
            'empty order' => ['', false],
            'null order' => [null, false],
        ];
    }
}
