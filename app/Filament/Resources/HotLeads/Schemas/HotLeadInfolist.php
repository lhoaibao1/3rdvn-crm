<?php

namespace App\Filament\Resources\HotLeads\Schemas;

use App\Models\Lead;
use App\Models\RecordChangeLog;
use App\Support\Filament\ProcessTimeline;
use App\Support\Filament\RecordViewChrome;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class HotLeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            TextEntry::make('hot_lead_record_view_header')
                ->hiddenLabel()
                ->state(fn (Lead $record): HtmlString => RecordViewChrome::hotLead($record))
                ->html()
                ->columnSpanFull(),
            Tabs::make('Lead nóng detail')
                ->columnSpanFull()
                ->persistTabInQueryString('hot_lead_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::DocumentText)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin Lead nóng')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('lead_code')->label('Mã Lead')->badge()->color('info')->placeholder('-'),
                                    TextEntry::make('status')->label('Trạng thái')->badge()->color(fn (?string $state): string => match ($state) {
                                        'Khách hàng thoả mãn điều kiện' => 'success',
                                        'Từ chối' => 'danger',
                                        'Chờ xử lý' => 'warning',
                                        default => 'gray',
                                    })->placeholder('-'),
                                    TextEntry::make('lead_name')->label('Họ tên')->placeholder('-'),
                                    TextEntry::make('phone')->label('Số điện thoại')->placeholder('-'),
                                    TextEntry::make('email')->label('Thư điện tử')->placeholder('-'),
                                    TextEntry::make('payload.fields.identity_number')->label('CCCD/CMND')->placeholder('-'),
                                    TextEntry::make('payload.fields.date_of_birth')->label('Ngày sinh')->placeholder('-'),
                                    TextEntry::make('payload.fields.product_interest')->label('Sản phẩm')->placeholder('-'),
                                    TextEntry::make('payload.fields.address')->label('Địa chỉ chi tiết')->placeholder('-'),
                                    TextEntry::make('payload.fields.province_name')->label('Tỉnh/Thành phố')->placeholder('-'),
                                    TextEntry::make('payload.fields.district_name')->label('Quận/Huyện')->placeholder('-'),
                                    TextEntry::make('payload.fields.ward_name')->label('Phường/Xã')->placeholder('-'),
                                    TextEntry::make('note')->label('Ghi chú')->columnSpanFull()->placeholder('-'),
                                ]),
                            Section::make('Phân xử lý')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('assignedSale.name')->label('Người được phân xử lý')->placeholder('-'),
                                    TextEntry::make('createdBy.name')->label('Người tạo')->placeholder('-'),
                                    TextEntry::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->placeholder('-'),
                                ]),
                            Section::make('Line quản lý')
                                ->columnSpanFull()
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('createdBy.name')->label('Người tạo Lead')->placeholder('-'),
                                    TextEntry::make('createdBy.uid')->label('UID')->placeholder('-'),
                                    TextEntry::make('createdBy.employee_code')->label('Mã nhân viên')->placeholder('-'),
                                    TextEntry::make('team.name')->label('Nhóm')->placeholder('-'),
                                    TextEntry::make('teamLeader.name')->label('Trưởng nhóm')->placeholder('-'),
                                    TextEntry::make('am.name')->label('AM')->placeholder('-'),
                                    TextEntry::make('zd.name')->label('ZD')->placeholder('-'),
                                ]),
                        ]),

                    Tab::make('Hồ sơ chuyển đổi')
                        ->icon(Heroicon::ClipboardDocumentList)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin Application')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('application.application_code')
                                        ->label('Mã hồ sơ/Application')
                                        ->badge()
                                        ->color(fn (mixed $state): string => filled($state) ? 'success' : 'gray')
                                        ->placeholder('-'),
                                    TextEntry::make('application.salesProject.name')->label('Dự án xử lý')->badge()->placeholder('-'),
                                    TextEntry::make('application.status')->label('Trạng thái Application')->placeholder('-')->badge(),
                                    TextEntry::make('application.applicant_name')->label('Khách hàng')->placeholder('-'),
                                    TextEntry::make('application.phone')->label('Số điện thoại')->placeholder('-'),
                                    TextEntry::make('application.identity_number')->label('CCCD/CMND')->placeholder('-'),
                                    TextEntry::make('application.note')->label('Ghi chú Application')->placeholder('-')->columnSpanFull(),
                                ]),
                            Section::make('Chuyển đổi')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('converted_at')->label('Thời điểm chuyển')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('convertedBy.name')->label('Người chuyển')->placeholder('-'),
                                    TextEntry::make('application.assignedSale.name')->label('Người xử lý')->placeholder('-'),
                                    TextEntry::make('payload.review.decision_note')->label('Ghi chú quyết định')->placeholder('-'),
                                ]),
                        ]),

                    Tab::make('Lịch sử thao tác')
                        ->icon(Heroicon::Clock)
                        ->schema([
                            Section::make('Nhật ký Lead nóng')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('hot_lead_history_timeline')
                                        ->hiddenLabel()
                                        ->state(fn (Lead $record): HtmlString => self::renderHistoryTimeline($record))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ];
    }


    private static function renderHistoryTimeline(Lead $record): HtmlString
    {
        $logs = $record->changeLogs()
            ->with('actor:id,name,uid,employee_code,email')
            ->latest()
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
            return 'Tạo Lead nóng';
        }

        if (array_key_exists('status', $changes)) {
            return 'Quyết định Lead nóng';
        }

        return match ($log->action) {
            'deleted' => 'Xóa Lead nóng',
            'restored' => 'Khôi phục Lead nóng',
            default => 'Cập nhật Lead nóng',
        };
    }

    private static function historyBody(RecordChangeLog $log): string
    {
        $changes = is_array($log->changes) ? $log->changes : [];

        if ($log->action === 'created') {
            return 'Tạo mới hồ sơ.';
        }

        if (array_key_exists('status', $changes)) {
            return 'Trạng thái: '.self::value($changes['status']['old'] ?? null).' → '.self::value($changes['status']['new'] ?? null);
        }

        foreach (['note', 'decision_note', 'review_note'] as $field) {
            if (array_key_exists($field, $changes)) {
                return 'Ghi chú: '.self::value($changes[$field]['new'] ?? null);
            }
        }

        return match ($log->action) {
            'deleted' => 'Đóng hồ sơ.',
            'restored' => 'Khôi phục hồ sơ.',
            default => 'Cập nhật hồ sơ.',
        };
    }

    private static function historyTone(RecordChangeLog $log): array
    {
        $status = (string) data_get(is_array($log->changes) ? $log->changes : [], 'status.new', '');

        if ($status === 'Khách hàng thoả mãn điều kiện') {
            return ['label' => 'Pass', 'color' => '#16a34a', 'soft' => '#dcfce7', 'border' => '#86efac'];
        }

        if ($status === 'Từ chối' || $log->action === 'deleted') {
            return ['label' => 'Đóng', 'color' => '#dc2626', 'soft' => '#fee2e2', 'border' => '#fecaca'];
        }

        if ($log->action === 'created') {
            return ['label' => 'Tạo mới', 'color' => '#2563eb', 'soft' => '#dbeafe', 'border' => '#bfdbfe'];
        }

        return ['label' => 'Xử lý', 'color' => '#be185d', 'soft' => '#fce7f3', 'border' => '#f9a8d4'];
    }

    private static function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return is_array($value) ? 'Đã cập nhật' : (string) $value;
    }

}
