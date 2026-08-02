<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Models\Application;
use App\Models\RecordChangeLog;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
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
                ->extraAttributes(['class' => 'crm-record-view-frame'])
                ->columnSpanFull()
                ->persistTabInQueryString('application_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::RectangleStack)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin Application')
                                ->columnSpanFull()
                                ->extraAttributes(['class' => 'crm-application-summary'])
                                ->columns(6)
                                ->schema([
                                    TextEntry::make('application_code')->label('Mã hồ sơ')->placeholder('-'),
                                    TextEntry::make('status')->label('Trạng thái')->badge()
                                        ->color(fn (?string $state, Application $record): string => match ($record->salesProject?->slug) {
                                            'acl-mix' => AclMixWorkflow::statusColor($state),
                                            'lotte-finance' => LotteFinanceWorkflow::statusColor($state),
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->placeholder('-'),
                                    TextEntry::make('salesProject.name')->label('Dự án')->placeholder('-'),
                                    TextEntry::make('applicant_name')->label('Khách hàng')->placeholder('-'),
                                    TextEntry::make('payload.review.product')
                                        ->label('Sản phẩm')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'acl-mix'),
                                    TextEntry::make('payload.review.pre_approved_amount')
                                        ->label('Số tiền phê duyệt sơ bộ')
                                        ->formatStateUsing(fn (mixed $state): string => filled($state)
                                            ? number_format((int) preg_replace('/\D+/', '', (string) $state), 0, ',', '.').' VNĐ'
                                            : '-')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'acl-mix'),
                                    TextEntry::make('payload.review.pre_approved_months')
                                        ->label('Số tháng phê duyệt')
                                        ->formatStateUsing(fn (mixed $state): string => filled($state) ? $state.' tháng' : '-')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'acl-mix'),
                                    TextEntry::make('payload.review.pre_approved_interest_rate')
                                        ->label('Lãi suất phê duyệt')
                                        ->formatStateUsing(fn (mixed $state): string => filled($state)
                                            ? rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.').'%'
                                            : '-')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'acl-mix'),
                                    TextEntry::make('payload.review.review_note')
                                        ->label('Ghi chú phê duyệt')
                                        ->placeholder('-')
                                        ->columnSpanFull()
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'acl-mix'),
                                    TextEntry::make('payload.fields.scheme_code')
                                        ->label('Scheme')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('lotte_product')
                                        ->label('Sản phẩm')
                                        ->state(fn (Application $record): mixed => data_get($record->payload, 'fields.scheme_product')
                                            ?: data_get($record->payload, 'fields.scheme_name'))
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('payload.fields.loan_amount')
                                        ->label('Số tiền vay')
                                        ->money('VND', locale: 'vi')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('payload.review.maximum_limit')
                                        ->label('Hạn mức tối đa')
                                        ->money('VND', locale: 'vi')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('payload.review.approved_amount')
                                        ->label('Số tiền được phê duyệt')
                                        ->money('VND', locale: 'vi')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('payload.review.approved_at')
                                        ->label('Thời gian Approval')
                                        ->dateTime('H:i d/m/Y')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('lotte_interest_rate')
                                        ->label('Lãi suất')
                                        ->state(fn (Application $record): mixed => data_get($record->payload, 'review.estimated_interest_rate')
                                            ?: data_get($record->payload, 'fields.scheme_interest_rate'))
                                        ->formatStateUsing(fn (mixed $state): string => filled($state) ? rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.').'%' : '-')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('payload.review.decision')
                                        ->label('Pre-Check')
                                        ->badge()
                                        ->color(fn (mixed $state): string => match ($state) {
                                            'Pass' => 'success',
                                            'Không Pass' => 'danger',
                                            default => 'gray',
                                        })
                                        ->placeholder('Chờ xử lý')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    TextEntry::make('payload.review.reviewed_at')
                                        ->label('Thời gian Pre-Check')
                                        ->dateTime('H:i d/m/Y')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'),
                                    ...self::lottePreCheckEntries(),
                                    TextEntry::make('sales_creator_compact')
                                        ->label('NVKD')
                                        ->state(fn (Application $record): string => collect([
                                            $record->createdBy?->name,
                                            $record->createdBy?->uid,
                                            $record->createdBy?->employee_code,
                                        ])->filter()->implode(' · '))
                                        ->placeholder('-'),
                                    TextEntry::make('team.name')->label('Team')->placeholder('-'),
                                    TextEntry::make('teamLeader.name')->label('Team Leader')->placeholder('-'),
                                    TextEntry::make('assignedSale.name')->label('Người xử lý')->placeholder('-'),
                                    TextEntry::make('created_at')
                                        ->label('Ngày tạo')
                                        ->dateTime('H:i d/m/Y')
                                        ->placeholder('-'),
                                    TextEntry::make('updated_at')
                                        ->label('Cập nhật')
                                        ->dateTime('H:i d/m/Y')
                                        ->placeholder('-'),
                                    TextEntry::make('payload.review.review_note')
                                        ->label('Ghi chú Pre-Check')
                                        ->placeholder('-')
                                        ->columnSpan(2)
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance' && filled(data_get($record->payload, 'review.review_note'))),
                                    TextEntry::make('payload.review.approval_note')
                                        ->label('Ghi chú Approval')
                                        ->placeholder('-')
                                        ->columnSpan(2)
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance' && filled(data_get($record->payload, 'review.approval_note'))),
                                    TextEntry::make('lotte_file_note')
                                        ->label('Ghi chú hồ sơ')
                                        ->state(fn (Application $record): mixed => $record->note
                                            ?: data_get($record->payload, 'fields.note'))
                                        ->placeholder('-')
                                        ->columnSpanFull()
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance' && filled($record->note ?: data_get($record->payload, 'fields.note'))),
                                    TextEntry::make('note')
                                        ->label(fn (Application $record): string => $record->salesProject?->slug === 'acl-mix' ? 'Ghi chú xử lý' : 'Ghi chú')
                                        ->placeholder('-')
                                        ->columnSpanFull()
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug !== 'lotte-finance'
                                            && ($record->salesProject?->slug !== 'acl-mix'
                                                || (filled($record->note)
                                                    && $record->note !== data_get($record->payload, 'review.review_note')))),
                                ]),
                            Section::make('Thông tin hồ sơ')
                                ->columnSpanFull()
                                ->schema([
                                    Section::make('Thông tin khách hàng')
                                        ->columns(3)
                                        ->schema(fn (Application $record): array => match ($record->salesProject?->slug) {
                                            'acl-mix' => AclMixFields::entriesFor([
                                                'customer_name',
                                                'cccd',
                                                'cmnd',
                                                'date_of_birth',
                                                'identity_issued_date',
                                                'identity_issued_place',
                                                'identity_expiry_date',
                                                'phone',
                                                'education',
                                                'marital_status',
                                            ]),
                                            'lotte-finance' => LotteFinanceFields::personalEntries(),
                                            default => LeadFormFieldFactory::entriesForProject($record->sales_project_id, 'lead', 'payload.fields'),
                                        }),
                                    Section::make('Địa chỉ cư trú hiện tại')
                                        ->visible(fn (Application $record): bool => in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
                                        ->columns(3)
                                        ->schema(fn (Application $record): array => $record->salesProject?->slug === 'lotte-finance'
                                            ? LotteFinanceFields::currentAddressEntries()
                                            : AclMixFields::entriesFor([
                                                'current_province_code',
                                                'current_district_code',
                                                'current_ward_code',
                                                'current_address_line',
                                            ])),
                                    Section::make('Địa chỉ thường trú')
                                        ->visible(fn (Application $record): bool => in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
                                        ->columns(3)
                                        ->schema(fn (Application $record): array => $record->salesProject?->slug === 'lotte-finance'
                                            ? LotteFinanceFields::permanentAddressEntries()
                                            : AclMixFields::entriesFor([
                                                'permanent_same_as_current',
                                                'permanent_province_code',
                                                'permanent_district_code',
                                                'permanent_ward_code',
                                                'permanent_address_line',
                                            ])),
                                    Section::make('Thông tin công việc')
                                        ->visible(fn (Application $record): bool => in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
                                        ->columns(3)
                                        ->schema(fn (Application $record): array => $record->salesProject?->slug === 'lotte-finance'
                                            ? LotteFinanceFields::workEntries()
                                            : AclMixFields::entriesFor([
                                                'employer_name',
                                                'employer_tax_code',
                                                'employer_phone',
                                                'contract_type',
                                                'working_years',
                                                'working_months',
                                                'monthly_income',
                                                'experience_years',
                                                'experience_months',
                                            ])),
                                    Section::make('Địa chỉ nơi làm việc')
                                        ->visible(fn (Application $record): bool => in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
                                        ->columns(3)
                                        ->schema(fn (Application $record): array => $record->salesProject?->slug === 'lotte-finance'
                                            ? LotteFinanceFields::workAddressEntries()
                                            : AclMixFields::entriesFor([
                                                'work_province_code',
                                                'work_district_code',
                                                'work_ward_code',
                                                'work_address_line',
                                            ])),
                                    Section::make('Thông tin tham chiếu')
                                        ->visible(fn (Application $record): bool => in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
                                        ->columns(3)
                                        ->schema(fn (Application $record): array => $record->salesProject?->slug === 'lotte-finance'
                                            ? LotteFinanceFields::contactEntries()
                                            : AclMixFields::entriesFor([
                                                'spouse_name',
                                                'spouse_identity_number',
                                                'spouse_phone',
                                                'reference_1_name',
                                                'reference_1_relationship',
                                                'reference_1_phone',
                                                'reference_2_name',
                                                'reference_2_relationship',
                                                'reference_2_phone',
                                            ])),
                                    Section::make('Chi tiết sản phẩm')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance')
                                        ->columns(3)
                                        ->schema([
                                            TextEntry::make('payload.fields.scheme_product_type')->label('Loại sản phẩm')->placeholder('-'),
                                            TextEntry::make('payload.fields.scheme_name')->label('Tên Scheme')->placeholder('-')->columnSpan(2),
                                            TextEntry::make('payload.fields.scheme_product_line')->label('Dòng sản phẩm')->placeholder('-'),
                                            TextEntry::make('payload.fields.scheme_loan_period')->label('Thời hạn tối đa')->placeholder('-'),
                                            TextEntry::make('payload.fields.scheme_description')->label('Mô tả Scheme')->placeholder('-')->columnSpanFull(),
                                        ]),
                                    Section::make('Thông tin khoản vay')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance')
                                        ->columns(3)
                                        ->schema([
                                            TextEntry::make('payload.fields.loan_purpose_name')->label('Mục đích vay')->placeholder('-'),
                                            TextEntry::make('payload.fields.combo_loan_amount')->label('Tổng số tiền vay')->money('VND', locale: 'vi')->placeholder('-'),
                                            TextEntry::make('payload.fields.loan_term_months')->label('Thời gian vay')->suffix(' tháng')->placeholder('-'),
                                            TextEntry::make('payload.fields.insurance_label')->label('Bảo hiểm khoản vay')->placeholder('-'),
                                            TextEntry::make('payload.fields.estimated_insurance_amount')->label('Phí bảo hiểm dự kiến')->money('VND', locale: 'vi')->placeholder('-'),
                                            TextEntry::make('payload.fields.estimated_monthly_payment')->label('Số tiền đóng hằng tháng')->money('VND', locale: 'vi')->placeholder('-'),
                                            TextEntry::make('payload.fields.estimated_total_payment')->label('Tổng thanh toán dự kiến')->money('VND', locale: 'vi')->placeholder('-'),
                                        ]),
                                    Section::make('Thông tin giải ngân')
                                        ->visible(fn (Application $record): bool => in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
                                        ->columns(3)
                                        ->schema(fn (Application $record): array => $record->salesProject?->slug === 'lotte-finance'
                                            ? LotteFinanceFields::disbursementEntries()
                                            : AclMixFields::entriesFor([
                                                'disbursement_method',
                                                'bank_name',
                                                'bank_account_number',
                                                'bank_account_name',
                                                'note',
                                            ])),
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
                        ->visible(fn (Application $record): bool => ! in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
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
                        ->visible(fn (Application $record): bool => ! in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
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

    private static function lottePreCheckEntries(): array
    {
        return collect([
            'blacklist_check' => 'Blacklist',
            'b11t_check' => 'B11T',
            'aml_check' => 'AML',
            'pcb_check' => 'PCB',
            'lf_grade' => 'LF Grade',
            'ml_grade' => 'ML Grade',
        ])->map(fn (string $label, string $key): TextEntry => TextEntry::make('payload.review.'.$key)
            ->label($label)
            ->badge()
            ->color(fn (mixed $state): string => match ($state) {
                'Pass' => 'success',
                'Không Pass' => 'danger',
                default => 'gray',
            })
            ->placeholder('-')
            ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'lotte-finance'))
            ->values()
            ->all();
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
        if (array_key_exists((string) $state, LotteFinanceWorkflow::statusOptions())) {
            return LotteFinanceWorkflow::statusLabel($state);
        }

        return AclMixWorkflow::statusOptions()[$state] ?? match ($state) {
            'processing' => 'Đang xử lý',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }
}
