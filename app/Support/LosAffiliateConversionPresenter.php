<?php

namespace App\Support;

use App\Models\AffiliateConversion;

class LosAffiliateConversionPresenter
{
    public static function make(AffiliateConversion $conversion): array
    {
        $status = $conversion->conversion_status ?: 'Mới ghi nhận';
        $campaign = $conversion->campaign_name ?: $conversion->offer_id ?: 'Affiliate';
        $creator = collect([
            $conversion->createdBy?->name,
            $conversion->createdBy?->employee_code,
        ])->filter()->unique()->implode(' · ') ?: ($conversion->aff_sub1 ?: '-');
        $fields = [
            self::field('Mã chuyển đổi', $conversion->conversion_id),
            self::field('Mã giao dịch', $conversion->transaction_id ?: '-'),
            self::field('Đối tác', strtoupper($conversion->partner)),
            self::field('Chiến dịch', $campaign),
            self::field('Trạng thái', $status, self::tone($status)),
            self::field('Sản phẩm', $conversion->product_name ?: '-'),
            self::field('Doanh số', self::money($conversion->sale_amount)),
            self::field('Nhân viên', $creator),
            self::field('Ngày ghi nhận', $conversion->conversion_time?->format('H:i d/m/Y') ?: '-'),
            self::field('Cập nhật', $conversion->updated_at?->format('H:i d/m/Y') ?: '-'),
        ];

        return [
            'id' => 'affiliate-'.$conversion->getKey(),
            'application_code' => $conversion->conversion_id,
            'project' => $campaign,
            'applicant_name' => $conversion->product_name ?: 'Kết quả Affiliate',
            'identity_number' => '-',
            'product' => $conversion->product_name ?: '-',
            'scheme' => '-',
            'loan_amount' => $conversion->sale_amount ? (int) $conversion->sale_amount : null,
            'creator' => $creator,
            'created_at' => $conversion->created_at?->format('H:i d/m/Y') ?: '-',
            'updated_at' => $conversion->updated_at?->format('H:i d/m/Y') ?: '-',
            'updated_timestamp' => $conversion->updated_at?->getTimestamp() ?? 0,
            'status_label' => $status,
            'status_tone' => self::tone($status),
            'summary_fields' => array_slice($fields, 0, 6),
            'application_fields' => $fields,
        ];
    }

    private static function field(string $label, mixed $value, ?string $tone = null): array
    {
        return ['label' => $label, 'value' => filled($value) ? (string) $value : '-', 'tone' => $tone, 'wide' => false];
    }

    private static function money(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value, 0, ',', '.').' đ' : '-';
    }

    private static function tone(string $status): string
    {
        return match (strtolower($status)) {
            'success', 'approved', 'confirmed' => 'success',
            'cancelled', 'canceled', 'rejected' => 'danger',
            default => 'warning',
        };
    }
}
