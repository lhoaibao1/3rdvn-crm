<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Enums\FeDeeplinkStatus;
use App\Enums\FeolSyncState;
use App\Models\Application;
use App\Models\RecordChangeLog;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\ApplicationFinancialData;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Filament\ApplicationAuditLog;
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
                                            'fe-deeplink' => FeDeeplinkStatus::colorFor($state),
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (?string $state, Application $record): string => self::statusLabel($state, $record))->placeholder('-'),
                                    TextEntry::make('salesProject.name')->label('Dự án')->placeholder('-'),
                                    TextEntry::make('applicant_name')->label('Khách hàng')->placeholder('-'),
                                    TextEntry::make('application_phone')
                                        ->label('Số điện thoại')
                                        ->state(fn (Application $record): mixed => $record->phone
                                            ?: data_get($record->payload, 'fields.phone')
                                            ?: data_get($record->payload, 'module_fields.phone'))
                                        ->placeholder('-'),
                                    TextEntry::make('payload.review.otp')
                                        ->label('OTP')
                                        ->badge()
                                        ->color('warning')
                                        ->copyable()
                                        ->placeholder('Chưa nhập')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'acl-mix'
                                            && $record->status === AclMixWorkflow::OTP_REQUIRED),
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
                                    TextEntry::make('disbursed_at')
                                        ->label('Ngày giải ngân')
                                        ->state(fn (Application $record): mixed => ApplicationFinancialData::disbursedAt($record))
                                        ->dateTime('d/m/Y')
                                        ->placeholder('-'),
                                    TextEntry::make('fe_product')
                                        ->label('Sản phẩm')
                                        ->state(fn (Application $record): mixed => ApplicationFinancialData::product($record))
                                        ->badge()
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('fe_approved_amount')
                                        ->label('Số tiền duyệt')
                                        ->state(fn (Application $record): mixed => ApplicationFinancialData::approvedAmount($record))
                                        ->money('VND', locale: 'vi')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.partner_lead_id')
                                        ->label('Lead ID đối tác')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.partner_app_id')
                                        ->label('App ID')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.main_status')
                                        ->label('Trạng thái chính FEOL')
                                        ->badge()
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.sync_state')
                                        ->label('Trạng thái đồng bộ')
                                        ->badge()
                                        ->formatStateUsing(fn (mixed $state): string => match ((string) $state) {
                                            'idle' => 'Chờ kích hoạt',
                                            'pending' => 'Chờ đồng bộ',
                                            'processing' => 'Đang đồng bộ',
                                            'synced' => 'Đã đồng bộ',
                                            'failed' => 'Đồng bộ lỗi',
                                            'terminal' => 'Hoàn tất',
                                            default => filled($state) ? (string)$state : '-',
                                        })
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.submit_state')
                                        ->label('Trạng thái gửi FEOL')
                                        ->badge()
                                        ->formatStateUsing(fn (mixed $state): string => match ((string) $state) {
                                            'awaiting_customer' => 'Chờ khách hàng nhập',
                                            'queued' => 'Chờ gửi đối tác',
                                            'processing' => 'Đang gửi đối tác',
                                            'submitted' => 'Đã gửi đối tác',
                                            'failed' => 'Gửi đối tác lỗi',
                                            default => filled($state) ? (string)$state : '-',
                                        })
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.deeplink_url')
                                        ->label('Deeplink')
                                        ->copyable()
                                        ->copyMessage('Đã sao chép Deeplink')
                                        ->placeholder('Chỉ có sau khi Eligible')
                                        ->columnSpan(3)
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.last_synced_at')
                                        ->label('Đồng bộ gần nhất')
                                        ->dateTime('H:i:s d/m/Y')
                                        ->placeholder('-')
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'),
                                    TextEntry::make('feolIntegration.last_error')
                                        ->label('Lỗi đồng bộ')
                                        ->color('danger')
                                        ->placeholder('-')
                                        ->columnSpanFull()
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'
                                            && filled($record->feolIntegration?->last_error)),
                                    TextEntry::make('feolIntegration.submit_last_error')
                                        ->label('Lỗi gửi hồ sơ FEOL')
                                        ->color('danger')
                                        ->placeholder('-')
                                        ->columnSpanFull()
                                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'
                                            && filled($record->feolIntegration?->submit_last_error)),
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
                                ->visible(fn (Application $record): bool => $record->salesProject?->slug !== 'fe-deeplink')
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
                                            TextEntry::make('payload.fields.scheme_dti_label')->label('DTI')->placeholder('-'),
                                            TextEntry::make('payload.fields.scheme_ltv_label')->label('LTV')->placeholder('-'),
                                            TextEntry::make('payload.fields.scheme_loan_amount_range')->label('Khoản vay áp dụng')->placeholder('-'),
                                            TextEntry::make('payload.fields.scheme_age_range')->label('Độ tuổi áp dụng')->placeholder('-'),
                                            TextEntry::make('payload.fields.scheme_insurance_fee')->label('Phí bảo hiểm Scheme')->placeholder('-'),
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
                        ->visible(fn (Application $record): bool => in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance'], true))
                        ->schema([
                            Section::make('Thư mục chứng từ')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('project_documents')
                                        ->hiddenLabel()
                                        ->state(fn (Application $record): HtmlString => DocumentPreview::projectDocuments($record->payload ?? [], $record->salesProject?->slug))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Xử lý dự án')
                        ->icon(Heroicon::Briefcase)
                        ->visible(fn (Application $record): bool => ! in_array($record->salesProject?->slug, ['acl-mix', 'lotte-finance', 'fe-deeplink'], true))
                        ->columns(12)
                        ->schema([
                            Section::make('Dữ liệu dự án')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema(fn (Application $record): array => LeadFormFieldFactory::entriesForProject($record->sales_project_id, 'module', 'payload.module_fields')),
                        ]),
                    Tab::make('Lịch sử Node-RED')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'
                            && (auth()->user()?->hasRole('Admin') ?? false))
                        ->schema([
                            Section::make('Quá trình đồng bộ CRM đối tác')
                                ->description('Hiển thị từng lần Node-RED cập nhật trạng thái hoặc trả lỗi về CRM.')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('feol_bridge_history')
                                        ->hiddenLabel()
                                        ->state(fn (Application $record): HtmlString => self::renderFeolBridgeTimeline($record))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Lịch sử xử lý')
                        ->icon(Heroicon::Clock)
                        ->schema([
                            Section::make('Nội dung xử lý hồ sơ')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('application_history_timeline')
                                        ->hiddenLabel()
                                        ->state(fn (Application $record): HtmlString => self::renderHistoryTimeline($record))
                                        ->html()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Audit Log')
                        ->icon(Heroicon::OutlinedListBullet)
                        ->schema([
                            Section::make('Nhật ký thay đổi dữ liệu')
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('application_audit_log')
                                        ->hiddenLabel()
                                        ->state(fn (Application $record): HtmlString => self::renderAuditLog($record))
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
            fn (RecordChangeLog $log): string => self::historyBody($log, $record),
            fn (RecordChangeLog $log): array => self::historyTone($log),
        );
    }

    private static function renderFeolBridgeTimeline(Application $record): HtmlString
    {
        $logs = $record->changeLogs()
            ->whereIn('action', ['feol_synced', 'feol_sync_failed'])
            ->latest()
            ->limit(100)
            ->get();

        return ProcessTimeline::render(
            $logs,
            fn (RecordChangeLog $log): string => $log->action === 'feol_sync_failed'
                ? 'Node-RED báo lỗi đồng bộ'
                : 'Node-RED cập nhật CRM đối tác',
            fn (RecordChangeLog $log): string => self::feolBridgeHistoryBody($log),
            fn (RecordChangeLog $log): array => $log->action === 'feol_sync_failed'
                ? ['label' => 'Lỗi', 'color' => '#b91c1c', 'bg' => '#fef2f2', 'soft' => '#fee2e2', 'border' => '#fecaca']
                : ['label' => 'Đã đồng bộ', 'color' => '#047857', 'bg' => '#ecfdf5', 'soft' => '#d1fae5', 'border' => '#a7f3d0'],
            'Chưa có lần đồng bộ Node-RED nào.',
        );
    }

    private static function feolBridgeHistoryBody(RecordChangeLog $log): string
    {
        $changes = is_array($log->changes) ? $log->changes : [];

        return collect([
            filled(data_get($changes, 'partner_lead_id.new')) ? 'Lead ID: '.data_get($changes, 'partner_lead_id.new') : null,
            filled(data_get($changes, 'main_status.new')) ? 'Trạng thái chính: '.data_get($changes, 'main_status.new') : null,
            filled(data_get($changes, 'sub_status.new')) ? 'Trạng thái phụ: '.FeDeeplinkStatus::labelFor((string) data_get($changes, 'sub_status.new')) : null,
            filled(data_get($changes, 'last_error.new')) ? 'Lỗi: '.data_get($changes, 'last_error.new') : null,
            filled(data_get($changes, 'last_synced_at.new')) ? 'Thời điểm quét: '.data_get($changes, 'last_synced_at.new') : null,
        ])->filter()->implode("\n") ?: 'Node-RED đã kiểm tra hồ sơ, không có dữ liệu thay đổi.';
    }

    private static function renderAuditLog(Application $record): HtmlString
    {
        $logs = $record->changeLogs()
            ->with('actor:id,name,uid,employee_code,email')
            ->latest()
            ->limit(100)
            ->get();

        return ApplicationAuditLog::render(
            $logs,
            fn (?string $status): string => self::statusLabel($status, $record),
        );
    }

    private static function historyTitle(RecordChangeLog $log): string
    {
        $changes = is_array($log->changes) ? $log->changes : [];

        if ($log->action === 'created') {
            return 'Tạo hồ sơ Application';
        }

        if (array_key_exists('status', $changes)) {
            $oldStatus = (string) data_get($changes, 'status.old', '');
            $newStatus = (string) data_get($changes, 'status.new', '');

            if ($newStatus === AclMixWorkflow::RETURNED_TO_SALE || $newStatus === LotteFinanceWorkflow::RETURNED_TO_SALE) {
                return 'Trả về Sale';
            }

            if ($oldStatus === AclMixWorkflow::RETURNED_TO_SALE || $oldStatus === LotteFinanceWorkflow::RETURNED_TO_SALE) {
                return 'Quay về bước trước khi trả';
            }

            return 'Chuyển bước xử lý';
        }

        return match ($log->action) {
            'deleted' => 'Đóng hồ sơ',
            'restored' => 'Khôi phục hồ sơ',
            'updated' => 'Cập nhật hồ sơ',
            default => $log->action ?: '-',
        };
    }

    private static function historyBody(RecordChangeLog $log, Application $record): string
    {
        return ApplicationAuditLog::businessSummary(
            $log,
            fn (?string $status): string => self::statusLabel($status, $record),
        );
    }

    private static function historyTone(RecordChangeLog $log): array
    {
        $changes = is_array($log->changes) ? $log->changes : [];
        $status = (string) data_get($changes, 'status.new', '');

        if ($status === AclMixWorkflow::RETURNED_TO_SALE || $status === LotteFinanceWorkflow::RETURNED_TO_SALE) {
            return ['label' => 'Trả Sale', 'color' => '#c2410c', 'bg' => '#fff7ed', 'soft' => '#ffedd5', 'border' => '#fed7aa'];
        }

        $oldStatus = (string) data_get($changes, 'status.old', '');

        if ($oldStatus === AclMixWorkflow::RETURNED_TO_SALE || $oldStatus === LotteFinanceWorkflow::RETURNED_TO_SALE) {
            return ['label' => 'Quay lại', 'color' => '#047857', 'bg' => '#ecfdf5', 'soft' => '#d1fae5', 'border' => '#a7f3d0'];
        }

        if ($status === 'approved' || $status === FeDeeplinkStatus::PL_DISBURSED->value) {
            return ['label' => 'Duyệt', 'color' => '#047857', 'bg' => '#ecfdf5', 'soft' => '#d1fae5', 'border' => '#a7f3d0'];
        }

        if (in_array($status, ['rejected', FeDeeplinkStatus::HARD_REJECT->value, FeDeeplinkStatus::INELIGIBLE->value], true) || $log->action === 'deleted') {
            return ['label' => 'Đóng', 'color' => '#b91c1c', 'bg' => '#fef2f2', 'soft' => '#fee2e2', 'border' => '#fecaca'];
        }

        if ($log->action === 'created') {
            return ['label' => 'Tạo mới', 'color' => '#1d4ed8', 'bg' => '#eff6ff', 'soft' => '#dbeafe', 'border' => '#bfdbfe'];
        }

        return ['label' => 'Xử lý', 'color' => '#475569', 'bg' => '#f8fafc', 'soft' => '#e2e8f0', 'border' => '#cbd5e1'];
    }

    private static function statusLabel(?string $state, ?Application $record = null): string
    {
        if ($record?->salesProject?->slug === 'lotte-finance') {
            return LotteFinanceWorkflow::statusLabel($state);
        }

        if ($record?->salesProject?->slug === 'acl-mix') {
            return AclMixWorkflow::statusLabel($state);
        }

        if ($record?->salesProject?->slug === 'fe-deeplink') {
            return FeDeeplinkStatus::labelFor($state);
        }

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
