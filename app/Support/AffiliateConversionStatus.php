<?php

namespace App\Support;

final class AffiliateConversionStatus
{
    public const PENDING = 'pending';
    public const APPROVED_WAITING_DISBURSEMENT = 'approved_waiting_disbursement';
    public const DISBURSED = 'disbursed';
    public const REJECTED = 'rejected';

    public static function isTinVay(?string $campaign): bool
    {
        $hay = strtolower((string) $campaign);

        return str_contains($hay, 'tinvay')
            || str_contains($hay, 'tin vay')
            || str_contains($hay, 'vietcredit')
            || str_contains($hay, 'vcredit');
    }

    public static function phase(?string $partnerStatus, mixed $approvedAmount = null, ?string $campaign = null): string
    {
        $status = strtolower(trim((string) $partnerStatus));

        if (in_array($status, ['rejected', 'cancelled', 'canceled', 'failed', 'declined', 'trash', '-1'], true)) {
            return self::REJECTED;
        }

        // Tin Vay: partner "tạm duyệt" (AT status 0) still means the order exists.
        // Show it as disbursed instead of waiting for đối soát / "đã duyệt".
        if (self::isTinVay($campaign)) {
            return self::DISBURSED;
        }

        // For affiliate partners, their final "approved" callback means the
        // loan was disbursed, not merely credit-approved.
        if (in_array($status, ['approved', 'success', 'disbursed', 'completed', 'paid', 'confirmed', '1'], true)) {
            return self::DISBURSED;
        }

        if (is_numeric($approvedAmount) && (float) $approvedAmount > 0) {
            return self::APPROVED_WAITING_DISBURSEMENT;
        }

        return self::PENDING;
    }

    public static function label(?string $partnerStatus, mixed $approvedAmount = null, ?string $campaign = null): string
    {
        return match (self::phase($partnerStatus, $approvedAmount, $campaign)) {
            self::APPROVED_WAITING_DISBURSEMENT => 'Đã duyệt – Chờ giải ngân',
            self::DISBURSED => self::isTinVay($campaign) ? 'Đã giải ngân' : 'Giải ngân thành công',
            self::REJECTED => 'Bị từ chối / Hủy',
            default => 'Chờ duyệt',
        };
    }

    public static function tone(?string $partnerStatus, mixed $approvedAmount = null, ?string $campaign = null): string
    {
        return match (self::phase($partnerStatus, $approvedAmount, $campaign)) {
            self::DISBURSED => 'success',
            self::APPROVED_WAITING_DISBURSEMENT => 'info',
            self::REJECTED => 'danger',
            default => 'warning',
        };
    }
}
