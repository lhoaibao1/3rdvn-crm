<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use App\Models\RecordChangeLog;
use App\Support\Filament\DocumentPreview;
use App\Support\Filament\LeadFormFieldFactory;
use App\Support\Filament\ProcessTimeline;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            Tabs::make('Lead detail')
                ->columnSpanFull()
                ->persistTabInQueryString('lead_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::DocumentText)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin chính')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('lead_code')->label('Lead ID')->placeholder('-'),
                                    TextEntry::make('salesProject.name')->label('Dự án')->placeholder('-'),
                                    TextEntry::make('lead_name')->label('Khách hàng')->placeholder('-'),
                                    TextEntry::make('phone')->label('Số điện thoại')->placeholder('-'),
                                    TextEntry::make('email')->label('Email')->placeholder('-'),
                                    TextEntry::make('payload.fields.identity_number')->label('CCCD/CMND')->placeholder('-'),
                                    TextEntry::make('assignedSale.name')->label('Sale phụ trách')->placeholder('-'),
                                    TextEntry::make('status')
                                        ->label('Trạng thái hồ sơ')
                                        ->badge()
                                        ->formatStateUsing(fn (?string $state): string => self::publicStatus($state))
                                        ->placeholder('-'),
                                ]),
                            Section::make('Hệ thống')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('application.application_code')->label('Mã hồ sơ')->placeholder('-'),
                                    TextEntry::make('converted_at')->label('Thời điểm chuyển')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('convertedBy.name')->label('Người chuyển')->placeholder('-'),
                                    TextEntry::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->placeholder('-'),
                                ]),
                            Section::make('Dữ liệu Lead')
                                ->columnSpanFull()
                                ->columns(3)
                                ->schema(fn (Lead $record): array => LeadFormFieldFactory::entriesForProject($record->sales_project_id, 'lead', 'payload.fields')),
                            Section::make('Thông tin nhân viên bán hàng')
                                ->columnSpanFull()
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('assignedSale.name')->label('NVKD')->placeholder('-'),
                                    TextEntry::make('assignedSale.uid')->label('UID')->placeholder('-'),
                                    TextEntry::make('assignedSale.employee_code')->label('Employee Code')->placeholder('-'),
                                    TextEntry::make('team.name')->label('Team')->placeholder('-'),
                                    TextEntry::make('teamLeader.name')->label('Team Leader')->placeholder('-'),
                                    TextEntry::make('am.name')->label('AM')->placeholder('-'),
                                    TextEntry::make('zd.name')->label('ZD')->placeholder('-'),
                                    TextEntry::make('createdBy.name')->label('Người tạo Lead')->placeholder('-'),
                                    TextEntry::make('created_at')->label('Thời gian tạo')->dateTime('H:i d/m/Y')->placeholder('-'),
                                ]),
                        ]),

                    Tab::make('Chứng từ')
                        ->icon(Heroicon::DocumentText)
                        ->schema([
                            Section::make('CCCD/OCR')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('lotte_documents')
                                        ->hiddenLabel()
                                        ->state(fn (Lead $record): HtmlString => DocumentPreview::lotteDocuments($record->payload ?? []))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Thông tin phê duyệt')
                        ->icon(Heroicon::ClipboardDocumentCheck)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin phê duyệt')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('status')
                                        ->label('Trạng thái hồ sơ')
                                        ->badge()
                                        ->formatStateUsing(fn (?string $state): string => self::publicStatus($state))
                                        ->placeholder('-'),
                                    TextEntry::make('payload.review.product')->label('Sản phẩm')->placeholder('-'),
                                    TextEntry::make('payload.review.pre_approved_amount')
                                        ->label('Số tiền phê duyệt sơ bộ')
                                        ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((int) preg_replace('/\D+/', '', (string) $state), 0, ',', '.').' VNĐ' : '-')
                                        ->placeholder('-'),
                                    TextEntry::make('payload.review.pre_approved_months')->label('Số tháng phê duyệt')->placeholder('-'),
                                    TextEntry::make('payload.review.pre_approved_interest_rate')
                                        ->label('Lãi suất phê duyệt')
                                        ->formatStateUsing(fn (mixed $state): string => filled($state) ? rtrim(rtrim((string) $state, '0'), '.').'%' : '-')
                                        ->placeholder('-'),
                                    TextEntry::make('payload.review.review_note')->label('Ghi chú kiểm tra')->placeholder('-')->columnSpanFull(),
                                ]),
                            Section::make('Ghi chú tự động')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('payload.review.auto_note')->label('Nội dung')->placeholder('-'),
                                ]),
                        ]),

                    Tab::make('Application')
                        ->icon(Heroicon::RectangleStack)
                        ->columns(12)
                        ->schema([
                            Section::make('Hồ sơ đã chuyển')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('application.application_code')->label('Mã hồ sơ')->placeholder('-'),
                                    TextEntry::make('salesProject.name')->label('Dự án')->placeholder('-'),
                                    TextEntry::make('converted_at')->label('Thời điểm chuyển')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('convertedBy.name')->label('Người chuyển')->placeholder('-'),
                                ]),
                        ]),

                    Tab::make('Lịch sử thao tác')
                        ->icon(Heroicon::Clock)
                        ->schema([
                            Section::make('Nhật ký hồ sơ')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('lead_history_timeline')
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
            fn (RecordChangeLog $log): string => self::formatLogTitle($log->action, $log),
            fn (RecordChangeLog $log): string => self::formatLogBody($log->changes, $log, $record),
            fn (RecordChangeLog $log): array => self::timelineTone($log),
        );
    }

    private static function timelineBodyHtml(string $body): string
    {
        $lines = collect(preg_split('/\R+/', trim($body)) ?: [])
            ->filter(fn (string $line): bool => trim($line) !== '')
            ->map(function (string $line): string {
                [$label, $value] = array_pad(explode(':', $line, 2), 2, null);

                if ($value !== null && trim($label) !== '') {
                    return '<div style="display:grid;grid-template-columns:minmax(150px,220px) 1fr;gap:10px;padding:8px 0;border-top:1px solid #f1f5f9">'
                        .'<span style="font-size:13px;font-weight:700;color:#475569">'.e(trim($label)).'</span>'
                        .'<span style="font-size:13px;color:#0f172a;font-weight:600">'.e(trim($value)).'</span>'
                        .'</div>';
                }

                return '<div style="padding:8px 0;border-top:1px solid #f1f5f9;color:#334155;font-size:13px;font-weight:600">'.e($line).'</div>';
            })
            ->join('');

        return '<div style="display:grid;gap:0">'.$lines.'</div>';
    }

    private static function timelineTone(RecordChangeLog $log): array
    {
        $changes = is_array($log->changes) ? $log->changes : [];
        $status = self::publicStatus((string) data_get($changes, 'status.new', ''));

        if (array_key_exists('converted_at', $changes)) {
            return ['label' => 'Application', 'color' => '#047857', 'bg' => '#ecfdf5', 'soft' => '#d1fae5', 'border' => '#a7f3d0'];
        }

        if (in_array($status, ['Từ chối', 'Khách hàng bị trùng'], true) || $log->action === 'deleted') {
            return ['label' => 'Đóng', 'color' => '#b91c1c', 'bg' => '#fef2f2', 'soft' => '#fee2e2', 'border' => '#fecaca'];
        }

        if ($status === 'Khách hàng thoả mãn điều kiện') {
            return ['label' => 'Pass', 'color' => '#047857', 'bg' => '#ecfdf5', 'soft' => '#d1fae5', 'border' => '#a7f3d0'];
        }

        if ($log->action === 'created') {
            return ['label' => 'Tạo mới', 'color' => '#1d4ed8', 'bg' => '#eff6ff', 'soft' => '#dbeafe', 'border' => '#bfdbfe'];
        }

        return ['label' => 'Cập nhật', 'color' => '#475569', 'bg' => '#f8fafc', 'soft' => '#e2e8f0', 'border' => '#cbd5e1'];
    }

    private static function publicStatus(?string $status): string
    {
        return match ($status) {
            'Đã chuyển Application' => 'Khách hàng thoả mãn điều kiện',
            null, '' => '-',
            default => $status,
        };
    }

    private static function formatLogTitle(?string $action, RecordChangeLog $log): string
    {
        $changes = is_array($log->changes) ? $log->changes : [];

        if ($action === 'created') {
            return 'Tạo mới hồ sơ';
        }

        if ($action === 'deleted') {
            return 'Đóng hồ sơ';
        }

        if (array_key_exists('converted_at', $changes)) {
            return 'Chuyển đổi Lead';
        }

        if (array_key_exists('status', $changes)) {
            return 'Kiểm tra sơ bộ';
        }

        return match ($action) {
            'restored' => 'Khôi phục hồ sơ',
            'updated' => 'Cập nhật hồ sơ',
            default => $action ?: '-',
        };
    }

    private static function formatLogBody(mixed $changes, RecordChangeLog $log, ?Lead $leadRecord = null): string
    {
        $changes = is_array($changes) ? $changes : [];

        if ($log->action === 'created') {
            return 'Tạo mới hồ sơ';
        }

        if (array_key_exists('status', $changes)) {
            $status = self::publicStatus((string) ($changes['status']['new'] ?? ''));

            if (in_array($status, ['Từ chối', 'Khách hàng bị trùng'], true)) {
                return 'Kiểm tra sơ bộ: Trạng thái: '.$status."\n".'Đóng hồ sơ';
            }

            if ($status === 'Khách hàng thoả mãn điều kiện') {
                return 'Kiểm tra sơ bộ: Trạng thái: Khách hàng thoả mãn điều kiện';
            }
        }

        if (array_key_exists('converted_at', $changes)) {
            $lead = $leadRecord?->loadMissing(['salesProject', 'application'])
                ?? ($log->record instanceof Lead ? $log->record->loadMissing(['salesProject', 'application']) : null);
            $leadCode = $lead?->lead_code ?: 'Lead ID';
            $applicationCode = $lead?->application?->application_code ?: data_get($lead?->payload, 'review.application_code', 'Mã hồ sơ');
            $projectName = $lead?->salesProject?->name ?: 'Dự án';

            return 'Chuyển đổi Lead "'.$leadCode.'" => Mã hồ sơ "'.$applicationCode.'"'."\n".'Đã chuyển sang "'.$projectName.'", vui lòng truy cập "'.$projectName.'" để tiếp tục xử lý.';
        }

        if ($log->action === 'deleted') {
            return 'Đóng hồ sơ';
        }

        return 'Cập nhật thông tin hồ sơ.';
    }
}
