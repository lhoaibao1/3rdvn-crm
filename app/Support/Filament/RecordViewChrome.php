<?php

namespace App\Support\Filament;

use App\Models\Application;
use App\Models\DataCenterLead;
use App\Models\Lead;
use App\Models\ProjectReport;
use App\Models\User;
use App\Support\DataCenter\DataCenterStatus;
use Illuminate\Support\HtmlString;

class RecordViewChrome
{
    public static function lead(Lead $record): HtmlString
    {
        $record->loadMissing(['salesProject', 'application', 'convertedSaleProfile', 'assignedSale', 'createdBy', 'team', 'convertedBy']);

        return new HtmlString(self::style().self::header(
            breadcrumb: ['Trang chủ', 'Lead', self::value($record->lead_code)],
            title: self::value($record->lead_name ?: $record->lead_code),
            items: [
                ['Lead ID', $record->lead_code],
                ['Tiến trình', self::leadStatus($record->status)],
                ['Ngày tạo', self::date($record->created_at)],
                ['Mã hồ sơ', $record->application?->application_code ?: ($record->convertedSaleProfile?->id ? 'HS #'.$record->convertedSaleProfile->id : '-')],
                ['Dự án', $record->salesProject?->name],
                ['Team', $record->team?->name],
                ['Ngày sửa', self::date($record->updated_at)],
                ['Phân công', self::user($record->assignedSale)],
                ['Tạo bởi', self::user($record->createdBy)],
                ['Ngày chuyển', self::date($record->converted_at)],
            ],
        ));
    }

    public static function application(Application $record): HtmlString
    {
        $record->loadMissing(['salesProject', 'assignedSale', 'createdBy', 'team']);

        return new HtmlString(self::style().self::header(
            breadcrumb: ['Trang chủ', 'Application', self::value($record->application_code)],
            title: self::value($record->applicant_name ?: $record->application_code),
            items: [
                ['Mã hồ sơ', $record->application_code],
                ['Tiến trình', self::applicationStatus($record->status)],
                ['Ngày tạo', self::date($record->created_at)],
                ['Số tiền duyệt', self::money(data_get($record->payload, 'review.pre_approved_amount'))],
                ['Ngày sửa', self::date($record->updated_at)],
                ['Phân công', self::user($record->assignedSale)],
                ['Dự án', $record->salesProject?->name],
                ['Team', $record->team?->name],
                ['Kỳ hạn duyệt', filled(data_get($record->payload, 'review.pre_approved_months')) ? data_get($record->payload, 'review.pre_approved_months').' tháng' : '-'],
            ],
        ));
    }

    public static function projectReport(ProjectReport $record): HtmlString
    {
        $record->loadMissing(['salesProject', 'application', 'createdBy', 'team', 'statusUpdatedBy', 'convertedBy']);

        return new HtmlString(self::style().self::header(
            breadcrumb: ['Trang chủ', 'Báo cáo', '#'.$record->getKey()],
            title: self::value($record->customer_name),
            items: [
                ['Mã báo cáo', '#'.$record->getKey()],
                ['Trạng thái', $record->status],
                ['Ngày tạo', self::date($record->created_at)],
                ['Dự án', $record->salesProject?->name],
                ['Mã bán hàng', $record->sales_code],
                ['Người tạo', self::user($record->createdBy)],
                ['Team', $record->team?->name],
                ['Mã hồ sơ', $record->application?->application_code],
                ['Ngày sửa', self::date($record->updated_at)],
                ['Nguồn', $record->origin === ProjectReport::ORIGIN_APPLICATION ? 'Từ dự án' : 'Nhập báo cáo'],
            ],
        ));
    }

    public static function hotLead(Lead $record): HtmlString
    {
        $record->loadMissing(['salesProject', 'convertedSaleProfile', 'assignedSale', 'createdBy', 'team']);

        return new HtmlString(self::style().self::header(
            breadcrumb: ['Trang chủ', 'Hot Lead', self::value($record->lead_code)],
            title: self::value($record->lead_name ?: $record->lead_code),
            items: [
                ['Mã Lead', $record->lead_code],
                ['Tiến trình', self::leadStatus($record->status)],
                ['Ngày tạo', self::date($record->created_at)],
                ['Mã hồ sơ', $record->convertedSaleProfile?->id ? 'HS #'.$record->convertedSaleProfile->id : '-'],
                ['Sản phẩm', data_get($record->payload, 'fields.product_interest')],
                ['Ngày sửa', self::date($record->updated_at)],
                ['Phân công', self::user($record->assignedSale)],
                ['Tạo bởi', self::user($record->createdBy)],
                ['Team', $record->team?->name],
                ['Dự án', $record->salesProject?->name],
            ],
        ));
    }

    public static function dataCenter(DataCenterLead $record): HtmlString
    {
        $record->loadMissing(['assignedUser', 'createdBy', 'team', 'teamLeader', 'am', 'zd', 'conversions']);

        return new HtmlString(self::style().self::header(
            breadcrumb: ['Trang chủ', 'Lead Referral', self::value($record->referral_code)],
            title: self::value($record->customer_name ?: $record->referral_code),
            items: [
                ['Mã Lead Referral', $record->referral_code],
                ['Trạng thái', DataCenterStatus::label($record->status)],
                ['Ngày tạo', self::date($record->created_at)],
                ['Số điện thoại', $record->phone],
                ['Người xử lý', self::user($record->assignedUser)],
                ['Đã chuyển', $record->conversions->count().'/2 dự án'],
                ['Team', $record->team?->name],
                ['Team Leader', self::user($record->teamLeader)],
                ['AM', self::user($record->am)],
                ['ZD', self::user($record->zd)],
            ],
        ));
    }

    public static function userProfile(User $record): HtmlString
    {
        $record->loadMissing(['roles', 'team', 'managedTeam', 'teamLeader', 'am', 'zd', 'creator']);

        $role = $record->roles->pluck('name')->filter()->join(', ');

        return new HtmlString(self::style().self::header(
            breadcrumb: ['Trang chủ', 'Người dùng', self::value($record->uid ?: $record->employee_code)],
            title: self::value($record->name ?: $record->email),
            items: [
                ['UID', $record->uid],
                ['Employee Code', $record->employee_code],
                ['Trạng thái', self::userStatus($record->employment_status)],
                ['Vai trò', $role],
                ['Email', $record->email],
                ['SĐT', $record->phone],
                ['Team', $record->team?->name ?: $record->managedTeam?->name],
                ['Team Leader', self::user($record->teamLeader)],
                ['AM', self::user($record->am)],
                ['ZD', self::user($record->zd)],
                ['Tạo bởi', self::user($record->creator)],
                ['Ngày tạo', self::date($record->created_at)],
                ['Ngày sửa', self::date($record->updated_at)],
            ],
        ));
    }

    private static function header(array $breadcrumb, string $title, array $items): string
    {
        $crumbs = collect($breadcrumb)
            ->filter(fn (mixed $item): bool => filled($item))
            ->map(fn (mixed $item): string => '<span>'.e((string) $item).'</span>')
            ->join('<i>/</i>');

        $summary = collect($items)
            ->map(fn (array $item): string => self::item((string) $item[0], $item[1] ?? null))
            ->join('');

        return '<div class="crm-record-view-shell">'
            .'<div class="crm-record-breadcrumb">'.$crumbs.'</div>'
            .'<div class="crm-record-title"><span class="crm-record-title-icon"><svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6V2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg></span><h2>'.e($title).'</h2></div>'
            .'<div class="crm-record-summary">'.$summary.'</div>'
            .'</div>';
    }

    private static function item(string $label, mixed $value): string
    {
        return '<div class="crm-record-summary-item"><span>'.e($label).'</span><b>'.e(self::value($value)).'</b></div>';
    }

    private static function user(mixed $user): string
    {
        if (! $user) {
            return '-';
        }

        return $user->uid ?: ($user->employee_code ?: ($user->name ?: $user->email));
    }

    private static function date(mixed $date): string
    {
        return $date ? $date->format('d/m/Y H:i:s') : '-';
    }

    private static function money(mixed $value): string
    {
        if (! filled($value)) {
            return '-';
        }

        $number = (int) preg_replace('/\D+/', '', (string) $value);

        return $number > 0 ? number_format($number, 0, ',', '.') : '-';
    }

    private static function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return is_array($value) ? 'Đã cập nhật' : (string) $value;
    }

    private static function leadStatus(?string $status): string
    {
        return match ($status) {
            'Đã chuyển Application' => 'Khách hàng thoả mãn điều kiện',
            null, '' => '-',
            default => $status,
        };
    }

    private static function applicationStatus(?string $status): string
    {
        return match ($status) {
            'processing' => 'Đang xử lý',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            null, '' => '-',
            default => $status,
        };
    }

    private static function userStatus(?string $status): string
    {
        return match ($status) {
            User::STATUS_ACTIVE => 'Hoạt động',
            User::STATUS_DEACTIVE, 'inactive', 'resigned' => 'Không hoạt động',
            User::STATUS_DELETED => 'Đã xoá',
            null, '' => '-',
            default => $status,
        };
    }

    private static function style(): string
    {
        return <<<'HTML'
<style>
.crm-record-view-shell{display:grid;gap:10px;margin:-4px 0 14px;padding:0 0 12px;border-bottom:1px solid #e5e7eb;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.crm-record-breadcrumb{display:flex;align-items:center;gap:7px;color:#94a3b8;font-size:13px;font-weight:620}.crm-record-breadcrumb i{font-style:normal;color:#cbd5e1}.crm-record-title{display:flex;align-items:center;gap:10px;min-width:0}.crm-record-title-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:999px;background:#e0f2fe;color:#0284c7}.crm-record-title-icon svg{width:20px;height:20px;fill:currentColor}.crm-record-title h2{margin:0;color:#111827;font-size:22px;line-height:1.15;font-weight:840;letter-spacing:0;text-transform:uppercase;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.crm-record-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px 48px;margin-top:6px}.crm-record-summary-item{display:grid;grid-template-columns:minmax(92px,auto) minmax(0,1fr);gap:8px;min-height:26px;align-items:center;color:#334155;font-size:13px}.crm-record-summary-item span{color:#475569;font-weight:650}.crm-record-summary-item b{min-width:0;color:#111827;font-weight:680;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.fi-modal-window:has(.crm-record-view-shell),.fi-main:has(.crm-record-view-shell){--crm-view-accent:#b83280}.fi-modal-window:has(.crm-record-view-shell) .fi-tabs,.fi-main:has(.crm-record-view-shell) .fi-tabs{border:0!important;border-bottom:1px solid #e5e7eb!important;border-radius:0!important;background:#fff!important;box-shadow:none!important;padding:0!important;overflow-x:auto!important}.fi-modal-window:has(.crm-record-view-shell) .fi-tabs-tab,.fi-main:has(.crm-record-view-shell) .fi-tabs-tab{min-height:40px!important;border-radius:0!important;color:#374151!important;font-size:14px!important;font-weight:700!important;white-space:nowrap!important}.fi-modal-window:has(.crm-record-view-shell) .fi-tabs-tab[aria-selected="true"],.fi-modal-window:has(.crm-record-view-shell) .fi-tabs-tab.fi-active,.fi-main:has(.crm-record-view-shell) .fi-tabs-tab[aria-selected="true"],.fi-main:has(.crm-record-view-shell) .fi-tabs-tab.fi-active{color:var(--crm-view-accent)!important;box-shadow:inset 0 -3px 0 var(--crm-view-accent)!important;background:#fff!important}.fi-modal-window:has(.crm-record-view-shell) .fi-section,.fi-main:has(.crm-record-view-shell) .fi-section{border:0!important;border-radius:0!important;background:#fff!important;box-shadow:none!important}.fi-modal-window:has(.crm-record-view-shell) .fi-section-header,.fi-main:has(.crm-record-view-shell) .fi-section-header{padding:14px 0 8px!important;border:0!important}.fi-modal-window:has(.crm-record-view-shell) .fi-section-header-heading,.fi-main:has(.crm-record-view-shell) .fi-section-header-heading{color:#111827!important;font-size:16px!important;font-weight:820!important}.fi-modal-window:has(.crm-record-view-shell) .fi-section-content,.fi-main:has(.crm-record-view-shell) .fi-section-content{padding:0!important}.fi-modal-window:has(.crm-record-view-shell) .fi-grid,.fi-main:has(.crm-record-view-shell) .fi-grid{gap:0!important}.fi-modal-window:has(.crm-record-view-shell) .fi-in-entry-wrp,.fi-main:has(.crm-record-view-shell) .fi-in-entry-wrp{display:grid!important;grid-template-columns:minmax(130px,.46fr) minmax(0,1fr)!important;align-items:center!important;min-height:38px!important;margin:-1px 0 0 -1px!important;padding:8px 10px!important;border:1px solid #edf2f7!important;background:#fff!important}.fi-modal-window:has(.crm-record-view-shell) .fi-in-entry-wrp-label,.fi-main:has(.crm-record-view-shell) .fi-in-entry-wrp-label{margin:0!important}.fi-modal-window:has(.crm-record-view-shell) .fi-in-entry-wrp-label span,.fi-main:has(.crm-record-view-shell) .fi-in-entry-wrp-label span{color:#475569!important;font-size:13px!important;font-weight:650!important}.fi-modal-window:has(.crm-record-view-shell) .fi-in-entry-wrp-content,.fi-main:has(.crm-record-view-shell) .fi-in-entry-wrp-content{min-width:0!important;color:#111827!important;font-size:13px!important;font-weight:650!important;overflow-wrap:anywhere!important}@media(max-width:900px){.crm-record-summary{grid-template-columns:1fr;gap:4px}.crm-record-title h2{white-space:normal;font-size:19px}.fi-modal-window:has(.crm-record-view-shell) .fi-in-entry-wrp,.fi-main:has(.crm-record-view-shell) .fi-in-entry-wrp{grid-template-columns:1fr!important;gap:3px!important;min-height:auto!important}.crm-record-summary-item{grid-template-columns:115px minmax(0,1fr)}}
</style>
HTML;
    }
}
