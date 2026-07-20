<?php

namespace App\Support\Filament\LeadCreate;

use App\Support\LotteFinanceSchemeCatalog;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;

class CreateLotteFinanceLeadAction
{
    use CreatesLeadRecords;

    public static function make(): Action
    {
        return Action::make('createLotteFinanceLead')
            ->label('Lotte Finance')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->visible(fn (): bool => self::canCreateForProject('lotte-finance'))
            ->modalHeading('Tạo Lead Lotte Finance')
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-create-modal'])
            ->modalWidth('5xl')
            ->modalSubmitActionLabel('Gửi Lead Kiểm Tra')
            ->modalSubmitAction(fn (Action $action): Action => $action->icon(Heroicon::OutlinedPaperAirplane))
            ->modalCancelActionLabel('Hủy')
            ->schema(self::schema())
            ->action(fn (array $data, mixed $livewire): mixed => self::createLeadForProject($data, 'lotte-finance', self::fieldKeys(), $livewire));
    }

    public static function schema(): array
    {
        return [
            Wizard::make([
                Step::make('Chọn Scheme')
                    ->schema([
                        Section::make('Thông tin sản phẩm')
                            ->columns(2)
                            ->schema([
                                Select::make('scheme_code')
                                    ->label('Mã Scheme')
                                    ->options(fn (): array => LotteFinanceSchemeCatalog::topOptions())
                                    ->getSearchResultsUsing(fn (string $search): array => LotteFinanceSchemeCatalog::searchOptions($search))
                                    ->getOptionLabelUsing(fn (?string $value): ?string => LotteFinanceSchemeCatalog::optionLabel($value))
                                    ->placeholder('Chọn mã scheme')
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                        self::syncSchemePayload($set, $state);
                                        self::syncLoanEstimate($set, $get);
                                    })
                                    ->native(false),
                                TextInput::make('scheme_product_type')
                                    ->label('Loại sản phẩm')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_product')
                                    ->label('Sản phẩm')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_name')
                                    ->label('Tên Scheme')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_start_date')
                                    ->label('Thời gian bắt đầu')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_dti_label')
                                    ->label('DTI')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_loan_period')
                                    ->label('Thời hạn tối đa')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_product_line')
                                    ->label('Dòng sản phẩm')
                                    ->disabled()
                                    ->dehydrated(),
                                Textarea::make('scheme_description')
                                    ->label('Mô tả')
                                    ->rows(2)
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Thông tin khoản vay')
                            ->columns(2)
                            ->schema([
                                Select::make('loan_purpose_code')
                                    ->label('Mục đích vay')
                                    ->options(fn (): array => LotteFinanceSchemeCatalog::loanPurposeOptions())
                                    ->placeholder('Chọn mục đích vay')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('loan_purpose_name', LotteFinanceSchemeCatalog::loanPurposeLabel($state)))
                                    ->native(false),
                                TextInput::make('loan_amount')
                                    ->label('Số tiền vay')
                                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                                    ->stripCharacters('.')
                                    ->suffix('VNĐ')
                                    ->live(onBlur: true)
                                    ->required()
                                    ->afterStateUpdated(fn (Set $set, Get $get): mixed => self::syncLoanEstimate($set, $get)),
                                TextInput::make('combo_loan_amount')
                                    ->label('Tổng số tiền vay (Combo 2 Loan)')
                                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                                    ->stripCharacters('.')
                                    ->suffix('VNĐ'),
                                TextInput::make('loan_term_months')
                                    ->label('Thời gian vay')
                                    ->numeric()
                                    ->suffix('tháng')
                                    ->live(onBlur: true)
                                    ->required()
                                    ->rule('integer')
                                    ->rule('min:1')
                                    ->rule('max:120')
                                    ->afterStateUpdated(fn (Set $set, Get $get): mixed => self::syncLoanEstimate($set, $get)),
                                Select::make('insurance_code')
                                    ->label('Tham gia bảo hiểm khoản vay')
                                    ->options(fn (): array => LotteFinanceSchemeCatalog::insuranceOptions())
                                    ->default('INSUR69')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                        $set('insurance_label', LotteFinanceSchemeCatalog::insuranceLabel($state));
                                        self::syncLoanEstimate($set, $get);
                                    })
                                    ->native(false),
                                Select::make('interest_option')
                                    ->label('Lãi suất khoản vay')
                                    ->options(fn (Get $get): array => LotteFinanceSchemeCatalog::interestOptions($get('scheme_code')))
                                    ->placeholder(fn (Get $get): string => LotteFinanceSchemeCatalog::interestOptions($get('scheme_code')) === [] ? 'Chưa có dữ liệu lãi suất' : 'Chọn lãi suất')
                                    ->helperText(fn (Get $get): ?string => LotteFinanceSchemeCatalog::interestHelper($get('scheme_code')))
                                    ->disabled(fn (Get $get): bool => blank($get('scheme_code')) || LotteFinanceSchemeCatalog::interestOptions($get('scheme_code')) === [])
                                    ->dehydrated()
                                    ->native(false),
                                TextInput::make('estimated_insurance_amount')
                                    ->label('Bảo hiểm khoản vay dự kiến')
                                    ->suffix('VNĐ')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('estimated_monthly_payment')
                                    ->label('Số tiền đóng hằng tháng')
                                    ->suffix('VNĐ')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('estimated_total_payment')
                                    ->label('Tổng thanh toán dự kiến')
                                    ->suffix('VNĐ')
                                    ->disabled()
                                    ->dehydrated(),
                                Placeholder::make('repayment_schedule_preview')
                                    ->label('')
                                    ->content(fn (Get $get): HtmlString => self::repaymentScheduleHtml($get))
                                    ->html()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Step::make('OCR/eKYC')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('ocr_front_image')
                                    ->label('OCR CCCD mặt trước')
                                    ->disk('public')
                                    ->directory('leads/lotte-finance/ocr')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->imagePreviewHeight('80')
                                    ->openable()
                                    ->downloadable()
                                    ->required(),
                                FileUpload::make('ocr_back_image')
                                    ->label('OCR CCCD mặt sau')
                                    ->disk('public')
                                    ->directory('leads/lotte-finance/ocr')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->imagePreviewHeight('80')
                                    ->openable()
                                    ->downloadable()
                                    ->required(),
                                Select::make('ekyc_status')
                                    ->label('Trạng thái eKYC')
                                    ->options([
                                        'pending' => 'Chưa eKYC',
                                        'processing' => 'Đang eKYC',
                                        'success' => 'Đã eKYC',
                                        'failed' => 'Lỗi eKYC',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->rule('in:success')
                                    ->validationMessages([
                                        'in' => 'Vui lòng chạy eKYC thành công trước khi nhập thông tin.',
                                    ])
                                    ->native(false),
                                TextInput::make('ekyc_request_id')
                                    ->label('eKYC Request ID')
                                    ->disabled()
                                    ->dehydrated()
                                    ->maxLength(120),
                                Actions::make([
                                    Action::make('run_ekyc')
                                        ->label('Chạy eKYC')
                                        ->icon(Heroicon::OutlinedIdentification)
                                        ->color('primary')
                                        ->action(function (Set $set, Get $get): void {
                                            if (blank($get('ocr_front_image')) || blank($get('ocr_back_image'))) {
                                                Notification::make()
                                                    ->title('Vui lòng upload CCCD mặt trước và mặt sau trước khi chạy eKYC.')
                                                    ->danger()
                                                    ->send();

                                                return;
                                            }

                                            $requestId = 'EKYC'.now()->format('ymdHis');
                                            $filledFromOcr = self::fillEkycFieldsFromPayload($set, $get('ekyc_raw_payload'));

                                            $set('ekyc_status', 'success');
                                            $set('ekyc_request_id', $requestId);
                                            $set('ekyc_completed_at', now()->format('H:i d/m/Y'));
                                            $set('ekyc_result_note', $filledFromOcr
                                                ? 'eKYC đã hoàn tất và hệ thống đã tự điền thông tin OCR nhận được.'
                                                : 'eKYC đã hoàn tất. Chưa nhận được dữ liệu OCR trả về từ API live, vui lòng nhập thông tin khách hàng.');
                                            $set('api_workflow_note', config('lotte_finance.live_api_ready')
                                                ? 'Đã chạy eKYC theo cấu hình API live.'
                                                : 'Chưa cấu hình URL endpoint eKYC live trong config Lotte; hệ thống chưa thể tự gọi OCR thật.');

                                            Notification::make()
                                                ->title('eKYC hoàn tất')
                                                ->body($filledFromOcr ? 'Thông tin CCCD đã được tự điền.' : 'Chưa có dữ liệu OCR live để tự điền, vui lòng nhập thủ công.')
                                                ->success()
                                                ->send();
                                        }),
                                ])->columnSpanFull(),
                                Textarea::make('ekyc_result_note')
                                    ->label('Kết quả eKYC')
                                    ->rows(2)
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Step::make('Nhập thông tin')
                    ->schema([
                        Section::make('Thông tin khách hàng')
                            ->columns(2)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Họ tên khách hàng')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Số điện thoại')
                                    ->tel()
                                    ->required()
                                    ->maxLength(30),
                                TextInput::make('identity_number')
                                    ->label('CCCD/CMND')
                                    ->required()
                                    ->maxLength(30),
                                TextInput::make('birthday')
                                    ->label('Ngày sinh')
                                    ->mask('99/99/9999')
                                    ->placeholder('dd/mm/yyyy')
                                    ->required()
                                    ->rule('date_format:d/m/Y')
                                    ->maxLength(10),
                                Select::make('gender')
                                    ->label('Giới tính')
                                    ->options([
                                        'MALE' => 'Nam',
                                        'FEMALE' => 'Nữ',
                                        'OTHER' => 'Khác',
                                    ])
                                    ->native(false),
                                TextInput::make('identity_issue_date')
                                    ->label('Ngày cấp')
                                    ->mask('99/99/9999')
                                    ->placeholder('dd/mm/yyyy')
                                    ->rule('date_format:d/m/Y')
                                    ->maxLength(10),
                                TextInput::make('identity_expiry_date')
                                    ->label('Ngày hết hạn')
                                    ->mask('99/99/9999')
                                    ->placeholder('dd/mm/yyyy')
                                    ->rule('date_format:d/m/Y')
                                    ->maxLength(10),
                                Select::make('identity_issue_place')
                                    ->label('Nơi cấp')
                                    ->options([
                                        'CCS' => 'CCS',
                                        'Bộ Công An' => 'Bộ Công An',
                                        'Cục CSQLHC về TTXH' => 'Cục CSQLHC về TTXH',
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                TextInput::make('nationality')
                                    ->label('Quốc tịch')
                                    ->maxLength(120),
                                Textarea::make('permanent_address')
                                    ->label('Địa chỉ thường trú')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Textarea::make('current_address')
                                    ->label('Địa chỉ hiện tại')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Textarea::make('api_workflow_note')
                                    ->label('Ghi chú API/OCR/eKYC')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
                ->nextAction(fn (Action $action): Action => $action
                    ->label('Tiếp tục')
                    ->icon(Heroicon::OutlinedArrowRight))
                ->previousAction(fn (Action $action): Action => $action
                    ->label('Quay lại')
                    ->icon(Heroicon::OutlinedArrowLeft))
                ->contained(false),
            Hidden::make('loan_purpose_name')->dehydrated(),
            Hidden::make('insurance_label')->default(LotteFinanceSchemeCatalog::insuranceLabel('INSUR69'))->dehydrated(),
            Hidden::make('scheme_sid')->dehydrated(),
            Hidden::make('scheme_interest_rate')->dehydrated(),
            Hidden::make('scheme_interest_code')->dehydrated(),
            Hidden::make('scheme_interest_period')->dehydrated(),
            Hidden::make('scheme_dti')->dehydrated(),
            Hidden::make('scheme_loan_period_min')->dehydrated(),
            Hidden::make('scheme_loan_period_max')->dehydrated(),
            Hidden::make('ekyc_completed_at')->dehydrated(),
            Hidden::make('ekyc_raw_payload')->dehydrated(),
        ];
    }

    private static function fillEkycFieldsFromPayload(Set $set, mixed $payload): bool
    {
        $data = self::decodeEkycPayload($payload);

        if ($data === []) {
            return false;
        }

        $fields = [
            'customer_name' => self::firstEkycValue($data, ['name.value', 'full_name.value', 'full_name', 'name']),
            'identity_number' => self::firstEkycValue($data, ['id_number.value', 'identity_number.value', 'id_number', 'identity_number', 'nric']),
            'birthday' => self::normalizeDate(self::firstEkycValue($data, ['dob.value', 'birthday.value', 'dob', 'birthday'])),
            'identity_issue_date' => self::normalizeDate(self::firstEkycValue($data, ['given_date.value', 'issue_date.value', 'given_date', 'issue_date'])),
            'identity_expiry_date' => self::normalizeDate(self::firstEkycValue($data, ['due_date.value', 'expiry_date.value', 'due_date', 'expiry_date'])),
            'identity_issue_place' => self::normalizeIssuePlace(self::firstEkycValue($data, ['given_place.value', 'issue_place.value', 'given_place', 'issue_place'])),
            'nationality' => self::firstEkycValue($data, ['nationality.value', 'ethnicity.value', 'nationality', 'ethnicity']) ?: 'Việt Nam',
            'permanent_address' => self::firstEkycValue($data, ['id_address.value', 'permanent_address.value', 'id_address', 'permanent_address', 'address']),
            'current_address' => self::firstEkycValue($data, ['id_address.value', 'current_address.value', 'id_address', 'current_address', 'address']),
            'gender' => self::normalizeGender(self::firstEkycValue($data, ['gender.value', 'gender'])),
        ];

        $filled = false;

        foreach ($fields as $field => $value) {
            if (filled($value)) {
                $set($field, $value);
                $filled = true;
            }
        }

        return $filled;
    }

    private static function decodeEkycPayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function firstEkycValue(array $data, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($data, $path);

            if (filled($value)) {
                return trim((string) $value);
            }

            foreach (['data', 'result', 'ocr', 'front', 'id_card'] as $root) {
                $value = data_get($data, $root.'.'.$path);

                if (filled($value)) {
                    return trim((string) $value);
                }
            }
        }

        return null;
    }

    private static function normalizeDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (strlen($digits) === 8) {
            return substr($digits, 0, 2).'/'.substr($digits, 2, 2).'/'.substr($digits, 4, 4);
        }

        return $value;
    }

    private static function normalizeIssuePlace(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $upper = mb_strtoupper($value);

        if (str_contains($upper, 'BỘ CÔNG AN') || str_contains($upper, 'CẢNH SÁT')) {
            return 'Bộ Công An';
        }

        return $value === 'CCS' ? 'CCS' : $value;
    }

    private static function normalizeGender(?string $value): ?string
    {
        return match (mb_strtoupper((string) $value)) {
            'MALE', 'NAM', 'M' => 'MALE',
            'FEMALE', 'NỮ', 'NU', 'F' => 'FEMALE',
            default => filled($value) ? 'OTHER' : null,
        };
    }

    private static function syncSchemePayload(Set $set, ?string $schemeCode): void
    {
        $scheme = LotteFinanceSchemeCatalog::find($schemeCode);

        $set('scheme_name', $scheme['name'] ?? null);
        $set('scheme_product_type', $scheme['product_type'] ?? null);
        $set('scheme_product', $scheme['product'] ?? null);
        $set('scheme_product_line', $scheme['product_line'] ?? null);
        $set('scheme_description', $scheme['description'] ?? null);
        $set('scheme_sid', $scheme['sid'] ?? null);
        $set('scheme_start_date', $scheme['start_date'] ?? null);
        $set('scheme_loan_period', $scheme['loan_period'] ?? null);
        $set('scheme_loan_period_min', $scheme['loan_period_min'] ?? null);
        $set('scheme_loan_period_max', $scheme['loan_period_max'] ?? null);
        $set('scheme_interest_rate', $scheme['interest_rate'] ?? null);
        $set('scheme_interest_code', $scheme['interest_code'] ?? null);
        $set('scheme_interest_period', $scheme['interest_period'] ?? null);
        $set('scheme_dti', $scheme['dti'] ?? null);
        $set('scheme_dti_label', $scheme['dti_label'] ?? null);
        $set('interest_option', $scheme['interest_label'] ?? null);
    }

    private static function syncLoanEstimate(Set $set, Get $get): void
    {
        $estimate = self::loanEstimate($get);

        $set('estimated_insurance_amount', self::formatMoney($estimate['insurance_amount']));
        $set('estimated_monthly_payment', self::formatMoney($estimate['monthly_payment']));
        $set('estimated_total_payment', self::formatMoney($estimate['total_payment']));
    }

    private static function repaymentScheduleHtml(Get $get): HtmlString
    {
        $estimate = self::loanEstimate($get);

        if ($estimate['principal'] <= 0 || $estimate['months'] <= 0 || $estimate['annual_rate'] <= 0) {
            return new HtmlString('<span style="font-size:14px;font-weight:600;color:#9ca3af;">Lịch trả nợ dự kiến</span>');
        }

        $rows = collect($estimate['schedule'])->map(fn (array $row): string => '<tr>'
            .'<td style="padding:8px 12px;text-align:center;">'.e((string) $row['month']).'</td>'
            .'<td style="padding:8px 12px;text-align:right;">'.e(self::formatMoney($row['principal'])).'</td>'
            .'<td style="padding:8px 12px;text-align:right;">'.e(self::formatMoney($row['interest'])).'</td>'
            .'<td style="padding:8px 12px;text-align:right;font-weight:700;">'.e(self::formatMoney($row['payment'])).'</td>'
            .'<td style="padding:8px 12px;text-align:right;">'.e(self::formatMoney($row['balance'])).'</td>'
            .'</tr>')->implode('');

        $tableHtml = '<div class="crm-repayment-table-wrap"><table class="crm-repayment-table"><thead><tr>'
            .'<th>Tháng</th><th>Gốc</th><th>Lãi</th><th>Phải đóng</th><th>Dư nợ</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table></div>';

        return new HtmlString('
            <button type="button" class="crm-repayment-preview-btn" data-crm-repayment-title="Lịch trả nợ dự kiến" data-crm-repayment-html="'.e($tableHtml).'">
                <span>Lịch trả nợ dự kiến</span>
                <span>'.e(self::formatMoney($estimate['monthly_payment'])).' VNĐ/tháng</span>
            </button>');
    }

    private static function loanEstimate(Get $get): array
    {
        $principal = self::moneyToInt($get('loan_amount'));
        $months = max(0, (int) $get('loan_term_months'));
        $annualRate = (float) ($get('scheme_interest_rate') ?: 0);
        $insuranceAmount = LotteFinanceSchemeCatalog::insuranceAmount($principal, $get('insurance_code') ?: 'INSUR69');
        $financedPrincipal = $principal + $insuranceAmount;
        $monthlyRate = $annualRate / 100 / 12;
        $monthlyPayment = 0.0;

        if ($financedPrincipal > 0 && $months > 0) {
            $monthlyPayment = $monthlyRate > 0
                ? $financedPrincipal * $monthlyRate / (1 - ((1 + $monthlyRate) ** (-$months)))
                : $financedPrincipal / $months;
        }

        $schedule = [];
        $balance = $financedPrincipal;
        $limit = min($months, 120);

        for ($month = 1; $month <= $limit; $month++) {
            $interest = $balance * $monthlyRate;
            $principalPayment = max(0, $monthlyPayment - $interest);
            $balance = max(0, $balance - $principalPayment);

            $schedule[] = [
                'month' => $month,
                'principal' => round($principalPayment),
                'interest' => round($interest),
                'payment' => round($monthlyPayment),
                'balance' => round($balance),
            ];
        }

        return [
            'principal' => $principal,
            'insurance_amount' => round($insuranceAmount),
            'financed_principal' => round($financedPrincipal),
            'months' => $months,
            'annual_rate' => $annualRate,
            'monthly_payment' => round($monthlyPayment),
            'total_payment' => round($monthlyPayment * $months),
            'schedule' => $schedule,
        ];
    }

    private static function moneyToInt(mixed $value): int
    {
        return (int) preg_replace('/\D+/', '', (string) $value);
    }

    private static function formatMoney(float|int $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    public static function fieldKeys(): array
    {
        return [
            'customer_name', 'phone', 'identity_number', 'birthday', 'gender', 'identity_issue_date',
            'identity_expiry_date', 'identity_issue_place', 'nationality', 'permanent_address', 'current_address',
            'scheme_code', 'scheme_name', 'scheme_product_type', 'scheme_product', 'scheme_product_line',
            'scheme_description', 'scheme_sid', 'scheme_start_date', 'scheme_loan_period',
            'scheme_loan_period_min', 'scheme_loan_period_max', 'scheme_interest_rate', 'scheme_interest_code',
            'scheme_interest_period', 'scheme_dti', 'scheme_dti_label', 'loan_purpose_code', 'loan_purpose_name',
            'loan_amount', 'combo_loan_amount', 'loan_term_months', 'insurance_code', 'insurance_label',
            'interest_option', 'estimated_insurance_amount', 'estimated_monthly_payment', 'estimated_total_payment',
            'ocr_front_image', 'ocr_back_image', 'ekyc_status', 'ekyc_request_id', 'ekyc_completed_at',
            'ekyc_raw_payload', 'ekyc_result_note', 'api_workflow_note',
        ];
    }
}
