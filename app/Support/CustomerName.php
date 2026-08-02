<?php

namespace App\Support;

class CustomerName
{
    public static function normalize(mixed $value): ?string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $value === '' ? null : mb_strtoupper($value, 'UTF-8');
    }
}
