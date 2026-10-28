<?php

namespace App\Support\Affiliate;

final class TinVayOrderId
{
    public static function isIncomplete(mixed $value): bool
    {
        $orderId = trim((string) $value);

        return $orderId !== '' && preg_match('/=+$/', $orderId) === 1;
    }
}
