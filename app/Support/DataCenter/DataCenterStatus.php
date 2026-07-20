<?php

namespace App\Support\DataCenter;

class DataCenterStatus
{
    public const PENDING = 'pending';

    public const CONTACTED = 'contacted';

    public const QUALIFIED = 'qualified';

    public const UNQUALIFIED = 'unqualified';

    public const CONVERTED_ONCE = 'converted_once';

    public const CONVERTED = 'converted';

    public static function options(): array
    {
        return [
            self::PENDING => 'Chờ gọi',
            self::CONTACTED => 'Đã liên hệ',
            self::QUALIFIED => 'Đủ điều kiện',
            self::UNQUALIFIED => 'Không đủ điều kiện',
            self::CONVERTED_ONCE => 'Đã chuyển 1 dự án',
            self::CONVERTED => 'Đã chuyển 2 dự án',
        ];
    }

    public static function resultOptions(): array
    {
        return [
            self::CONTACTED => 'Đã liên hệ - cần gọi lại',
            self::QUALIFIED => 'Đủ điều kiện',
            self::UNQUALIFIED => 'Không đủ điều kiện',
        ];
    }

    public static function label(?string $status): string
    {
        return self::options()[$status] ?? ($status ?: '-');
    }

    public static function color(?string $status): string
    {
        return match ($status) {
            self::QUALIFIED, self::CONVERTED_ONCE, self::CONVERTED => 'success',
            self::UNQUALIFIED => 'danger',
            self::CONTACTED => 'info',
            default => 'warning',
        };
    }
}
