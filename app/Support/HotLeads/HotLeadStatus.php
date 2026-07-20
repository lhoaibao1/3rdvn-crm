<?php

namespace App\Support\HotLeads;

use App\Models\ProcessingAssignmentConfig;
use App\Support\Permissions\HotLeadAccess;

class HotLeadStatus
{
    public const PENDING_ASSIGNMENT = 'Chờ phân bổ';

    public const PENDING_PROCESSING = 'Chờ xử lý';

    public static function options(): array
    {
        $options = [
            self::PENDING_ASSIGNMENT => self::PENDING_ASSIGNMENT,
            'Mới' => 'Mới',
            'Đang liên hệ' => 'Đang liên hệ',
            'Chờ bổ sung' => 'Chờ bổ sung',
            'Từ chối' => 'Từ chối',
        ];

        $configured = ProcessingAssignmentConfig::query()
            ->whereHas('salesProject', fn ($query) => $query->where('slug', HotLeadAccess::PROJECT_SLUG))
            ->first()?->statuses;

        foreach (is_array($configured) ? $configured : [] as $status) {
            $value = trim((string) (is_array($status) ? ($status['status'] ?? $status['value'] ?? '') : $status));

            if ($value !== '') {
                $options[$value] = $value;
            }
        }

        return $options;
    }

    public static function color(?string $status): string
    {
        return match ($status) {
            self::PENDING_ASSIGNMENT => 'gray',
            self::PENDING_PROCESSING, 'Đang liên hệ', 'Chờ bổ sung' => 'warning',
            'Từ chối' => 'danger',
            default => 'info',
        };
    }
}
