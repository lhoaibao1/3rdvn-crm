<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Models\Application;
use App\Models\RecordChangeLog;
use App\Support\Applications\AclMixWorkflow;
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

class ApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            Tabs::make('Application detail')
                ->columnSpanFull()
                ->persistTabInQueryString('application_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::RectangleStack)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin chính')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('application_code')->label('Mã hồ sơ')->placeholder('-'),
                                    TextEntry::make('salesProject.name')->label('Dự án')->placeholder('-'),
                                    TextEntry::make('applicant_name')->label('Khách hàng')->placeholder('-'),
                                    TextEntry::make('phone')->label('SĐT')->placeholder('-'),
                                    TextEntry::make('identity_number')->label('CCCD/CMND')->placeholder('-'),
                                    TextEntry::make('assignedSale.name')->label(fn (Application $record): string => $record->salesProject?->slug === 'acl-mix' ? 'Người xử lý' : 'Sale phụ trách')->placeholder('-'),
                                    TextEntry::make('lead.lead_code')->label('Lead ID')->placeholder('-')->visible(fn (Application $record): bool => $record->salesProject?->slug !== 'acl-mix'),
                                    TextEntry::make('status')->label('Trạng thái')->badge()->color(fn (?string $state, Application $record): string => $record->salesProject?->slug === 'acl-mix' ? AclMixWorkflow::statusColor($state) : 'gray')->formatStateUsing(fn (?string $state, Application $record): string => $record->salesProject?->slug === 'acl-mix' ? AclMixWorkflow::statusLabel($state) : self::statusLabel($state))->placeholder('-'),
                                ]),
                            Section::make('Hệ thống')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('note')->label('Ghi chú')->placeholder('-'),
                                ]),
                            Section::make('Thông tin hồ sơ')
                                ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'acl-mix')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema(AclMixFields::entries()),
                            Section::make('Dữ liệu Lead')
                                ->visible(fn (Application $record): bool => $record->salesProject?->slug !== 'acl-mix')
                                ->columnSpanFull()
                                ->columns(3)
                                ->schema(fn (Application $record): array => LeadFormFieldFactory::entriesForProject($record->sales_project_id, 'lead', 'payload.fields')),
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
                                        ->state(fn (Application $record): HtmlString => DocumentPreview::lotteDocuments($record->payload ?? []))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Thông tin phê duyệt')
                        ->icon(Heroicon::ClipboardDocumentCheck)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin phê duyệt')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('payload.review.product')->label('Sản phẩm')->placeholder('-'),
                                    TextEntry::make('payload.review.pre_approved_amount')->label('Số tiền phê duyệt sơ bộ')->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((int) preg_replace('/\D+/', '', (string) $state), 0, ',', '.').' VNĐ' : '-')->placeholder('-'),
                                    TextEntry::make('payload.review.pre_approved_months')->label('Số tháng phê duyệt')->placeholder('-'),
                                    TextEntry::make('payload.review.pre_approved_interest_rate')->label('Lãi suất phê duyệt')->formatStateUsing(fn (mixed $state): string => filled($state) ? rtrim(rtrim((string) $state, '0'), '.').'%' : '-')->placeholder('-'),
                                    TextEntry::make('payload.review.review_note')->label('Ghi chú kiểm tra')->placeholder('-')->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Xử lý dự án')
                        ->icon(Heroicon::Briefcase)
                        ->visible(fn (Application $record): bool => $record->salesProject?->slug !== 'acl-mix')
                        ->columns(12)
                        ->schema([
                            Section::make('Dữ liệu dự án')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema(fn (Application $record): array => LeadFormFieldFactory::entriesForProject($record->sales_project_id, 'module', 'payload.module_fields')),
                        ]),
                    Tab::make('Lịch sử thao tác')
                        ->icon(Heroicon::Clock)
                        ->schema([
                            Section::make('Nhật ký hồ sơ')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('application_history_timeline')
                                        ->hiddenLabel()
                                        ->state(fn (Application $record): HtmlString => self::renderHistoryTimeline($record))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ];
    }

    private static function renderHistoryTimeline(Application $record): HtmlString
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
            return 'Tạo hồ sơ Application';
        }

        if (array_key_exists('status', $changes)) {
            return 'Chuyển bước xử lý';
        }

        return match ($log->action) {
            'deleted' => 'Đóng hồ sơ',
            'restored' => 'Khôi phục hồ sơ',
            'updated' => 'Cập nhật hồ sơ',
            default => $log->action ?: '-',
        };
    }

    private static function historyBody(RecordChangeLog $log): string
    {
        $changes = is_array($log->changes) ? $log->changes : [];

        if ($log->action === 'created') {
            return 'Tạo hồ sơ Application.';
        }

        if (array_key_exists('status', $changes)) {
            $old = self::statusLabel($changes['status']['old'] ?? null);
            $new = self::statusLabel($changes['status']['new'] ?? null);

            return 'Trạng thái: '.$old.' → '.$new;
        }

        foreach (['note', 'processing_note', 'review_note'] as $field) {
            if (array_key_exists($field, $changes)) {
                $note = self::historyValue($changes[$field]['new'] ?? null);

                return 'Ghi chú: '.$note;
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
        $changes = is_array($log->changes) ? $log->changes : [];
        $status = (string) data_get($changes, 'status.new', '');

        if ($status === 'approved') {
            return ['label' => 'Duyệt', 'color' => '#047857', 'bg' => '#ecfdf5', 'soft' => '#d1fae5', 'border' => '#a7f3d0'];
        }

        if ($status === 'rejected' || $log->action === 'deleted') {
            return ['label' => 'Đóng', 'color' => '#b91c1c', 'bg' => '#fef2f2', 'soft' => '#fee2e2', 'border' => '#fecaca'];
        }

        if ($log->action === 'created') {
            return ['label' => 'Tạo mới', 'color' => '#1d4ed8', 'bg' => '#eff6ff', 'soft' => '#dbeafe', 'border' => '#bfdbfe'];
        }

        return ['label' => 'Xử lý', 'color' => '#475569', 'bg' => '#f8fafc', 'soft' => '#e2e8f0', 'border' => '#cbd5e1'];
    }

    private static function fieldLabel(string $field): string
    {
        return match ($field) {
            'application_code' => 'Mã hồ sơ',
            'applicant_name' => 'Khách hàng',
            'phone' => 'Số điện thoại',
            'identity_number' => 'CCCD/CMND',
            'status' => 'Trạng thái',
            'assigned_sale_id' => 'Sale phụ trách',
            'payload' => 'Dữ liệu hồ sơ',
            'note' => 'Ghi chú',
            default => str($field)->replace('_', ' ')->headline()->toString(),
        };
    }

    private static function historyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_array($value)) {
            return 'Đã cập nhật';
        }

        return (string) $value;
    }

    private static function statusLabel(?string $state): string
    {
        return AclMixWorkflow::statusOptions()[$state] ?? match ($state) {
            'processing' => 'Đang xử lý',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }
}
