<?php

namespace App\Support;

use App\Models\AffiliateConversion;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

class LosAffiliateConversionPresenter
{
    public static function make(AffiliateConversion $conversion): array
    {
        $raw = (array) ($conversion->raw_payload ?? []);

        $campaignName = self::cleanString(self::firstFilled([
            $conversion->campaign_name,
            $conversion->offer_id ? strtoupper($conversion->offer_id) : null,
            $raw['campaign_name'] ?? null,
            $raw['offer_id'] ?? null,
            'Chiến dịch Tiếp thị liên kết',
        ]));

        $applicantName = self::cleanString(self::firstFilled([
            $raw['customer_name'] ?? null,
            $raw['ten_khach_hang'] ?? null,
            $raw['applicant_name'] ?? null,
            $raw['lead_name'] ?? null,
            $raw['name'] ?? null,
            $conversion->aff_sub4 ? ('Khách hàng ' . self::maskIdentity($conversion->aff_sub4)) : null,
            'Khách hàng Tiếp thị',
        ]));

        $rawIdentity = self::firstFilled([
            $conversion->aff_sub4,
            $raw['aff_sub4'] ?? null,
            $raw['customer_id_no'] ?? null,
            $raw['identity_number'] ?? null,
            $raw['cccd'] ?? null,
            $raw['cmnd'] ?? null,
        ]);

        $rawPhone = self::firstFilled([
            $conversion->aff_sub3,
            $conversion->aff_sub2,
            $raw['aff_sub3'] ?? null,
            $raw['aff_sub2'] ?? null,
            $raw['customer_mobile'] ?? null,
            $raw['phone'] ?? null,
            $raw['so_dien_thoai'] ?? null,
        ]);

        // 🔒 MÃ HOÁ BẢO MẬT CCCD VÀ SỐ ĐIỆN THOẠI
        $maskedIdentity = self::maskIdentity($rawIdentity);
        $maskedPhone = self::maskPhone($rawPhone);

        $productName = self::cleanString(self::firstFilled([
            $conversion->product_name,
            $raw['product_name'] ?? null,
            $raw['app_type'] ?? null,
            $campaignName,
        ]));

        // ─── KHOẢN VAY PHÊ DUYỆT & ĐỀ XUẤT TIẾP THỊ ───
        $isApproved = in_array(strtolower((string)$conversion->conversion_status), ['approved', '1', 'success', 'confirmed', 'paid'])
            || in_array((string)$conversion->conversion_status_code, ['1', 'approved']);

        $saleAmt = self::money([
            $conversion->sale_amount,
            $raw['conversion_sale_amount'] ?? null,
            $raw['sale_amount'] ?? null,
            $raw['order_amount'] ?? null,
            $raw['amount'] ?? null,
        ]);

        if ($isApproved) {
            $approvedLoanAmount = self::money([
                $raw['approved_amount'] ?? null,
                $raw['disbursed_amt'] ?? null,
                $saleAmt,
            ]);
            $requestedLoanAmount = self::money([
                $raw['loan_amount'] ?? null,
                $raw['requested_amount'] ?? null,
                $raw['offer_amt'] ?? null,
            ]);
        } else {
            $requestedLoanAmount = self::money([
                $raw['loan_amount'] ?? null,
                $raw['requested_amount'] ?? null,
                $raw['offer_amt'] ?? null,
                $saleAmt,
            ]);
            // Chưa được phê duyệt -> Không có số tiền duyệt
            $approvedLoanAmount = null;
        }

        $statusLabel = self::cleanString(self::affiliateStatusLabel($conversion->conversion_status, $conversion->status_message));
        $statusTone = self::affiliateStatusTone($conversion->conversion_status);

        $appCode = self::cleanString(self::firstFilled([
            $conversion->transaction_id,
            $conversion->conversion_id,
            $raw['transaction_id'] ?? null,
            $raw['conversion_id'] ?? null,
            'CONV-' . str_pad((string)$conversion->getKey(), 6, '0', STR_PAD_LEFT),
        ]));

        $creatorUser = $conversion->relationLoaded('createdBy') ? $conversion->getRelation('createdBy') : null;
        $rawAffCode = self::firstFilled([
            $conversion->aff_sub1,
            $raw['aff_sub1'] ?? null,
            $raw['sale_code'] ?? null,
            $raw['nhan_vien'] ?? null,
            $raw['ma_gioi_thieu'] ?? null,
        ]);

        $resolvedName = self::getUserNameByCode($rawAffCode);

        $creator = self::cleanString(self::firstFilled([
            $creatorUser ? ($creatorUser->name . ' (' . ($creatorUser->employee_code ?: $creatorUser->uid) . ')') : null,
            $resolvedName,
            $rawAffCode,
            'NVKD Tiếp thị',
        ]));

        // ─── TAB 1: THÔNG TIN TIẾP THỊ & KHÁCH HÀNG ───
        $customerFields = [
            self::field('Mã giao dịch / Transaction ID', (string) ($conversion->transaction_id ?: $appCode)),
            self::field('Mã chuyển đổi / Conversion ID', (string) ($conversion->conversion_id ?: '-')),
            self::field('Chiến dịch / Đối tác', $campaignName),
            self::field('Sản phẩm / Gói vay', $productName),
            self::field('Họ và tên khách hàng', $applicantName),
            self::field('Số CCCD / CMND (Đã mã hóa)', $maskedIdentity),
            self::field('Số điện thoại (Đã mã hóa)', $maskedPhone),
            self::field('Mã Click ID', (string) ($conversion->click_id ?: ($raw['click_id'] ?? '-'))),
            self::field('Mã NV tiếp thị (Aff Sub 1)', (string) ($conversion->aff_sub1 ?: ($raw['aff_sub1'] ?? '-'))),
        ];

        if (filled($conversion->aff_sub2 ?: ($raw['aff_sub2'] ?? null))) {
            $customerFields[] = self::field('Chi nhánh / Aff Sub 2', (string) ($conversion->aff_sub2 ?: $raw['aff_sub2']));
        }
        if (filled($conversion->aff_sub3 ?: ($raw['aff_sub3'] ?? null))) {
            $customerFields[] = self::field('Kênh / Aff Sub 3', (string) ($conversion->aff_sub3 ?: $raw['aff_sub3']));
        }
        if (filled($conversion->landing_page ?: ($raw['landing_page'] ?? null))) {
            $customerFields[] = self::field('Landing Page đăng ký', (string) ($conversion->landing_page ?: $raw['landing_page']));
        }

        // ─── TAB 2: TRẠNG THÁI & KẾT QUẢ ĐỐI TÁC ───
        $statusFields = [
            self::field('Trạng thái chuyển đổi', $statusLabel, $statusTone),
            self::field('Mã trạng thái đối tác (Status Code)', (string) ($conversion->conversion_status_code ?? ($raw['conversion_status_code'] ?? '-'))),
            self::field('Shop Status Code', (string) ($conversion->shop_status_code ?? ($raw['shop_status_code'] ?? '-'))),
        ];

        if (filled($conversion->status_message ?: ($raw['status_message'] ?? null))) {
            $statusFields[] = self::field('Thông điệp đối tác / Lý do', (string) ($conversion->status_message ?: $raw['status_message']), $statusTone, true);
        }
        if (filled($conversion->event ?: ($raw['events'] ?? ($raw['event'] ?? null)))) {
            $statusFields[] = self::field('Sự kiện ghi nhận (Event)', (string) ($conversion->event ?: ($raw['events'] ?? $raw['event'])));
        }

        $statusFields[] = self::field('Khoản vay đề xuất (Nhu cầu)', self::moneyLabel($requestedLoanAmount), $requestedLoanAmount ? 'primary' : null);
        $statusFields[] = self::field('Khoản vay được phê duyệt', self::moneyLabel($approvedLoanAmount), $approvedLoanAmount ? 'success' : null);

        $statusFields[] = self::field('Thời gian click link', self::dateTime($conversion->click_time ?: ($raw['click_date'] ?? null)));
        $statusFields[] = self::field('Thời gian phát sinh chuyển đổi', self::dateTime($conversion->conversion_time ?: ($raw['conversion_date'] ?? null)));
        $statusFields[] = self::field('Thời gian cập nhật đối tác', self::dateTime($conversion->conversion_modified_time ?: ($raw['conversion_modified_date'] ?? null)));
        $statusFields[] = self::field('Thời gian đồng bộ hệ thống', self::dateTime($conversion->updated_at));

        $tabs = [
            [
                'id' => 'tab-customer',
                'title' => 'Thông tin tiếp thị & Khách hàng',
                'icon' => 'user',
                'fields' => $customerFields,
            ],
            [
                'id' => 'tab-status',
                'title' => 'Trạng thái & Đối tác',
                'icon' => 'shield',
                'fields' => $statusFields,
            ],
        ];

        $allFields = array_merge($customerFields, $statusFields);

        return [
            'id' => $conversion->getKey(),
            'application_code' => $appCode,
            'project' => $campaignName,
            'applicant_name' => $applicantName,
            'identity_number' => $maskedIdentity,
            'phone_number' => $maskedPhone,
            'dob' => '-',
            'product' => $productName,
            'scheme' => '-',
            'scheme_name' => '-',
            'scheme_or_product' => $productName,
            'requested_loan_amount' => $requestedLoanAmount,
            'approved_loan_amount' => $approvedLoanAmount,
            'requested_loan_amount_label' => self::moneyLabel($requestedLoanAmount),
            'approved_loan_amount_label' => self::moneyLabel($approvedLoanAmount),
            'creator' => $creator,
            'created_at' => self::dateTime($conversion->created_at),
            'updated_at' => self::dateTime($conversion->updated_at),
            'updated_timestamp' => $conversion->updated_at?->getTimestamp() ?? 0,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'tabs' => $tabs,
            'documents' => [],
            'timeline' => [],
            'application_fields' => $allFields,
        ];
    }

    public static function cleanString(?string $value): string
    {
        if ($value === null || $value === '') return '-';
        $clean = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        return filled($clean) ? trim($clean) : '-';
    }

    public static function maskIdentity(?string $value): string
    {
        $raw = self::cleanString($value);
        if ($raw === '' || $raw === '-') return '-';
        $digits = preg_replace('/[^0-9A-Za-z]/', '', $raw) ?: '';
        $len = strlen($digits);
        if ($len <= 4) return str_repeat('*', max(1, $len));
        if ($len === 9) return substr($digits, 0, 3) . '***' . substr($digits, -3);
        if ($len >= 12) return substr($digits, 0, 4) . '****' . substr($digits, -4);
        return substr($digits, 0, 2) . str_repeat('*', max(2, $len - 4)) . substr($digits, -2);
    }

    public static function maskPhone(?string $value): string
    {
        $raw = self::cleanString($value);
        if ($raw === '' || $raw === '-') return '-';
        $digits = preg_replace('/[^0-9]/', '', $raw) ?: '';
        $len = strlen($digits);
        if ($len < 7) return str_repeat('*', max(1, $len));
        return substr($digits, 0, 4) . '***' . substr($digits, -3);
    }

    private static function field(string $label, string $value, ?string $tone = null, bool $wide = false): array
    {
        return [
            'label' => self::cleanString($label),
            'value' => self::cleanString($value),
            'tone' => $tone,
            'wide' => $wide,
        ];
    }

    private static function firstFilled(array $values): string
    {
        $value = collect($values)->first(fn (mixed $candidate): bool => filled($candidate));
        return filled($value) ? trim((string) $value) : '-';
    }

    private static function money(array $values): ?int
    {
        $value = collect($values)->first(fn (mixed $candidate): bool => filled($candidate));
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }
        $str = (string) $value;
        if (str_contains($str, '.') && str_ends_with($str, '.00')) {
            $str = substr($str, 0, -3);
        }
        $digits = preg_replace('/[^0-9-]+/', '', $str) ?: '';
        return $digits !== '' ? (int) $digits : null;
    }

    private static function moneyLabel(?int $value): string
    {
        return is_null($value) ? '-' : number_format($value, 0, ',', '.').' VNĐ';
    }

    private static function dateTime(mixed $value): string
    {
        if (! filled($value)) return '-';
        if ($value instanceof CarbonInterface) {
            return $value->format('H:i d/m/Y');
        }
        try {
            return Carbon::parse($value)->format('H:i d/m/Y');
        } catch (Throwable) {
            return self::cleanString((string) $value);
        }
    }

    public static function affiliateStatusLabel(?string $status, ?string $msg = null): string
    {
        $s = strtolower(trim((string) $status));
        return match ($s) {
            'approved', '1', 'success', 'confirmed' => 'Phê duyệt thành công',
            'rejected', '-1', 'cancelled', 'canceled', 'failed' => 'Bị từ chối / Hủy' . (filled($msg) ? " ({$msg})" : ''),
            'pending', '0' => 'Chờ xử lý / Đang thẩm định',
            'paid' => 'Đã thanh toán hoa hồng',
            default => filled($status) ? (string)$status : 'Mới ghi nhận',
        };
    }

    public static function affiliateStatusTone(?string $status): string
    {
        $s = strtolower(trim((string) $status));
        return match ($s) {
            'approved', 'paid', '1', 'success', 'confirmed' => 'success',
            'rejected', '-1', 'cancelled', 'canceled', 'failed' => 'danger',
            'pending', '0' => 'warning',
            default => 'primary',
        };
    }

    protected static ?array $userMapCache = null;

    public static function getUserNameByCode(?string $code): ?string
    {
        if (empty($code) || $code === '-') return null;
        if (self::$userMapCache === null) {
            try {
                self::$userMapCache = [];
                $users = \App\Models\User::all(['id', 'name', 'employee_code', 'uid', 'username']);
                foreach ($users as $u) {
                    $displayName = $u->name . ($u->employee_code ? " ({$u->employee_code})" : '');
                    if ($u->employee_code) self::$userMapCache[strtoupper($u->employee_code)] = $displayName;
                    if ($u->uid) self::$userMapCache[strtoupper($u->uid)] = $displayName;
                    if ($u->username) self::$userMapCache[strtoupper($u->username)] = $displayName;
                    self::$userMapCache[(string)$u->id] = $displayName;
                }
            } catch (\Throwable $e) {
                self::$userMapCache = [];
            }
        }
        return self::$userMapCache[strtoupper(trim($code))] ?? null;
    }
}
