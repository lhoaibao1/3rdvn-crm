<?php

namespace App\Enums;

enum FeolSubmitState: string
{
    case AWAITING_CUSTOMER = 'awaiting_customer';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case SUBMITTED = 'submitted';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_CUSTOMER => 'Chờ khách hàng nhập',
            self::QUEUED => 'Chờ gửi đối tác',
            self::PROCESSING => 'Đang gửi đối tác',
            self::SUBMITTED => 'Đã gửi đối tác',
            self::FAILED => 'Gửi đối tác lỗi',
        };
    }
}
