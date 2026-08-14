<?php

namespace App\Enums;

enum FeolSyncState: string
{
    case PENDING = 'pending';
    case POLLING = 'polling';
    case SYNCED = 'synced';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ đồng bộ',
            self::POLLING => 'Đang kiểm tra',
            self::SYNCED => 'Đã đồng bộ',
            self::FAILED => 'Lỗi đồng bộ',
        };
    }
}
