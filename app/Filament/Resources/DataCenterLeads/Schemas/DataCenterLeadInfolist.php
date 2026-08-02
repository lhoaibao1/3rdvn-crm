<?php

namespace App\Filament\Resources\DataCenterLeads\Schemas;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\DataCenterLead;
use App\Models\RecordChangeLog;
use App\Support\DataCenter\DataCenterStatus;
use App\Support\Filament\ProcessTimeline;
use App\Support\Filament\RecordViewChrome;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class DataCenterLeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('data_center_header')
                ->hiddenLabel()
                ->state(fn (DataCenterLead $record): HtmlString => RecordViewChrome::dataCenter($record))
                ->html()
                ->columnSpanFull(),
            Tabs::make('Lead Referral detail')
                ->extraAttributes(['class' => 'crm-record-view-frame'])
                ->columnSpanFull()
                ->persistTabInQueryString('data_center_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::DocumentText)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin khách hàng')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('customer_name')->label('Họ tên khách hàng')->placeholder('-'),
                                    TextEntry::make('phone')->label('Số điện thoại')->placeholder('-'),
                                    TextEntry::make('identity_number')->label('CCCD/CMND')->placeholder('-'),
                                    TextEntry::make('date_of_birth')->label('Ngày sinh')->date('d/m/Y')->placeholder('-'),
                                    TextEntry::make('email')->label('Thư điện tử')->placeholder('-'),
                                    TextEntry::make('source')->label('Nguồn dữ liệu')->placeholder('-'),
                                    TextEntry::make('address')->label('Địa chỉ chi tiết')->placeholder('-')->columnSpanFull(),
                                    TextEntry::make('province_name')->label('Tỉnh/Thành phố')->placeholder('-'),
                                    TextEntry::make('district_name')->label('Quận/Huyện')->placeholder('-'),
                                    TextEntry::make('ward_name')->label('Phường/Xã')->placeholder('-'),
                                ]),
                            Section::make('Xử lý')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('status')
                                        ->label('Trạng thái')
                                        ->badge()
                                        ->formatStateUsing(fn (?string $state): string => DataCenterStatus::label($state))
                                        ->color(fn (?string $state): string => DataCenterStatus::color($state)),
                                    TextEntry::make('assignedUser.name')->label('Người xử lý')->placeholder('-'),
                                    TextEntry::make('contacted_at')->label('Lần gọi gần nhất')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('call_note')->label('Ghi chú cuộc gọi')->placeholder('-'),
                                ]),
                            Section::make('Line quản lý')
                                ->columnSpanFull()
                                ->columns(5)
                                ->schema([
                                    TextEntry::make('createdBy.name')->label('Người nhập')->placeholder('-'),
                                    TextEntry::make('team.name')->label('Team')->badge()->color('info')->placeholder('-'),
                                    TextEntry::make('teamLeader.name')->label('Team Leader')->placeholder('-'),
                                    TextEntry::make('am.name')->label('AM')->placeholder('-'),
                                    TextEntry::make('zd.name')->label('ZD')->placeholder('-'),
                                ]),
                        ]),
                    Tab::make('Dự án đã chuyển')
                        ->icon(Heroicon::ArrowRightCircle)
                        ->schema([
                            Section::make('Tối đa 2 dự án')
                                ->schema([
                                    TextEntry::make('data_center_conversions')
                                        ->hiddenLabel()
                                        ->state(fn (DataCenterLead $record): HtmlString => self::renderConversions($record))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Lịch sử thao tác')
                        ->icon(Heroicon::Clock)
                        ->schema([
                            Section::make('Tiến trình xử lý')
                                ->schema([
                                    TextEntry::make('data_center_history')
                                        ->hiddenLabel()
                                        ->state(fn (DataCenterLead $record): HtmlString => self::renderHistory($record))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ]);
    }

    private static function renderConversions(DataCenterLead $record): HtmlString
    {
        $record->loadMissing(['conversions.salesProject', 'conversions.lead', 'conversions.convertedBy']);

        if ($record->conversions->isEmpty()) {
            return new HtmlString('<div style="padding:18px;color:#64748b">Chưa chuyển sang dự án nào.</div>');
        }

        $rows = $record->conversions
            ->sortBy('converted_at')
            ->map(function ($conversion): string {
                $leadUrl = $conversion->lead ? LeadResource::getUrl('view', ['record' => $conversion->lead]) : '#';

                return '<tr>'
                    .'<td>'.e($conversion->salesProject?->name ?: '-').'</td>'
                    .'<td><a href="'.e($leadUrl).'" style="color:#2563eb;font-weight:700">'.e($conversion->lead?->lead_code ?: '-').'</a></td>'
                    .'<td>'.e($conversion->convertedBy?->name ?: '-').'</td>'
                    .'<td>'.e($conversion->converted_at?->format('H:i d/m/Y') ?: '-').'</td>'
                    .'</tr>';
            })
            ->implode('');

        return new HtmlString(
            '<div style="overflow:auto;overscroll-behavior:contain">'
            .'<table style="width:100%;min-width:640px;border-collapse:collapse">'
            .'<thead><tr><th>Dự án</th><th>Mã Lead</th><th>Người chuyển</th><th>Thời gian</th></tr></thead>'
            .'<tbody>'.$rows.'</tbody></table></div>'
            .'<style>.fi-in-entry-wrp-content table th,.fi-in-entry-wrp-content table td{padding:11px 12px;border:1px solid #e5e7eb;text-align:left;font-size:13px}.fi-in-entry-wrp-content table th{background:#f8fafc;color:#475569;font-weight:750}</style>',
        );
    }

    private static function renderHistory(DataCenterLead $record): HtmlString
    {
        $logs = $record->changeLogs()
            ->with('actor:id,name,uid,employee_code')
            ->limit(80)
            ->get();

        return ProcessTimeline::render(
            $logs,
            fn (RecordChangeLog $log): string => self::historyTitle($log),
            fn (RecordChangeLog $log): string => self::historyBody($log),
            fn (RecordChangeLog $log): array => self::historyTone($log),
        );
    }

    private static function historyTitle(RecordChangeLog $log): string
    {
        $changes = is_array($log->changes) ? $log->changes : [];

        if ($log->action === 'created') {
            return 'Nhập dữ liệu';
        }

        if (array_key_exists('assigned_user_id', $changes)) {
            return 'Phân người xử lý';
        }

        if (array_key_exists('status', $changes)) {
            return 'Cập nhật trạng thái';
        }

        return 'Cập nhật dữ liệu';
    }

    private static function historyBody(RecordChangeLog $log): string
    {
        $changes = is_array($log->changes) ? $log->changes : [];

        if ($log->action === 'created') {
            return 'Tạo mới Lead Referral.';
        }

        if (array_key_exists('status', $changes)) {
            return 'Trạng thái: '.DataCenterStatus::label($changes['status']['old'] ?? null)
                .' → '.DataCenterStatus::label($changes['status']['new'] ?? null);
        }

        if (array_key_exists('call_note', $changes)) {
            return 'Ghi chú: '.((string) ($changes['call_note']['new'] ?? '-') ?: '-');
        }

        return array_key_exists('assigned_user_id', $changes)
            ? 'Đã thay đổi người xử lý.'
            : 'Đã cập nhật thông tin.';
    }

    private static function historyTone(RecordChangeLog $log): array
    {
        $status = (string) data_get($log->changes, 'status.new', '');

        return match ($status) {
            DataCenterStatus::QUALIFIED, DataCenterStatus::CONVERTED_ONCE, DataCenterStatus::CONVERTED => ['label' => 'Đạt', 'color' => '#16a34a', 'soft' => '#dcfce7', 'border' => '#86efac'],
            DataCenterStatus::UNQUALIFIED => ['label' => 'Không đạt', 'color' => '#dc2626', 'soft' => '#fee2e2', 'border' => '#fecaca'],
            default => $log->action === 'created'
                ? ['label' => 'Tạo mới', 'color' => '#2563eb', 'soft' => '#dbeafe', 'border' => '#bfdbfe']
                : ['label' => 'Xử lý', 'color' => '#be185d', 'soft' => '#fce7f3', 'border' => '#f9a8d4'],
        };
    }
}
