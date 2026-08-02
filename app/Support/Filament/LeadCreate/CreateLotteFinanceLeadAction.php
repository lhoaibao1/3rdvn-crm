<?php

namespace App\Support\Filament\LeadCreate;

use App\Forms\Components\SearchableSelect as Select;
use App\Support\AdminWorkflowOverride;
use App\Support\CustomerName;
use App\Support\LotteFinanceSchemeCatalog;
use App\Support\VietnamAddressCatalog;
use App\Support\VietnamBankCatalog;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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
            ->modalSubmitAction(false)
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
                            ->columns(['default' => 1, 'md' => 3])
                            ->schema([
                                Select::make('scheme_code')
                                    ->label('Mã Scheme')
                                    ->options(fn (): array => LotteFinanceSchemeCatalog::topOptions())
                                    ->getSearchResultsUsing(fn (string $search): array => LotteFinanceSchemeCatalog::searchOptions($search))
                                    ->getOptionLabelUsing(fn (?string $value): ?string => LotteFinanceSchemeCatalog::optionLabel($value))
                                    ->placeholder('Chọn mã scheme')
                                    ->searchable()
                                    ->optionsLimit(1000)
                                    ->searchDebounce(250)
                                    ->live()
                                    ->required(AdminWorkflowOverride::required())
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
                                TextInput::make('scheme_ltv_label')
                                    ->label('LTV')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_loan_amount_range')
                                    ->label('Khoản vay áp dụng')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_age_range')
                                    ->label('Độ tuổi áp dụng')
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('scheme_insurance_fee')
                                    ->label('Phí bảo hiểm Scheme')
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
                            ->columns(['default' => 1, 'md' => 3])
                            ->schema([
                                Select::make('loan_purpose_code')
                                    ->label('Mục đích vay')
                                    ->options(fn (): array => LotteFinanceSchemeCatalog::loanPurposeOptions())
                                    ->placeholder('Chọn mục đích vay')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required(AdminWorkflowOverride::required())
                                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('loan_purpose_name', LotteFinanceSchemeCatalog::loanPurposeLabel($state)))
                                    ->native(false),
                                TextInput::make('loan_amount')
                                    ->label('Số tiền vay')
                                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                                    ->stripCharacters('.')
                                    ->extraInputAttributes(['class' => 'crm-money-input', 'inputmode' => 'numeric'])
                                    ->suffix('VNĐ')
                                    ->live(onBlur: true)
                                    ->required(AdminWorkflowOverride::required())
                                    ->afterStateUpdated(fn (Set $set, Get $get): mixed => self::syncLoanEstimate($set, $get)),
                                TextInput::make('combo_loan_amount')
                                    ->label('Tổng số tiền vay (Combo 2 Loan)')
                                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                                    ->stripCharacters('.')
                                    ->extraInputAttributes(['class' => 'crm-money-input', 'inputmode' => 'numeric'])
                                    ->suffix('VNĐ'),
                                TextInput::make('loan_term_months')
                                    ->label('Thời gian vay')
                                    ->numeric()
                                    ->suffix('tháng')
                                    ->live(onBlur: true)
                                    ->required(AdminWorkflowOverride::required())
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
                Step::make('Tải CCCD')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('ocr_front_image')
                                    ->label('CCCD mặt trước')
                                    ->disk('public')
                                    ->directory('leads/lotte-finance/ocr')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->imagePreviewHeight('120')
                                    ->previewable()
                                    ->openable()
                                    ->downloadable()
                                    ->deletable()
                                    ->required(AdminWorkflowOverride::required()),
                                FileUpload::make('ocr_back_image')
                                    ->label('CCCD mặt sau')
                                    ->disk('public')
                                    ->directory('leads/lotte-finance/ocr')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->imagePreviewHeight('120')
                                    ->previewable()
                                    ->openable()
                                    ->downloadable()
                                    ->deletable()
                                    ->required(AdminWorkflowOverride::required()),
                            ]),
                    ]),
                Step::make('Nhập thông tin')
                    ->schema([
                        Hidden::make('disbursement_method')
                            ->default('bank')
                            ->dehydrated(),
                        Section::make('Thông tin cá nhân')
                            ->description('Thông tin cơ bản của khách hàng')
                            ->columns(3)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Họ tên khách hàng')
                                    ->required(AdminWorkflowOverride::required())
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->extraInputAttributes(['class' => 'crm-uppercase-input'])
                                    ->dehydrateStateUsing(fn (?string $state): ?string => CustomerName::normalize($state))
                                    ->columnSpan(2),
                                TextInput::make('phone')
                                    ->label('Số điện thoại')
                                    ->tel()
                                    ->required(AdminWorkflowOverride::required())
                                    ->maxLength(30),
                                TextInput::make('identity_number')
                                    ->label('CCCD/CMND')
                                    ->required(AdminWorkflowOverride::required())
                                    ->maxLength(30),
                                TextInput::make('birthday')
                                    ->label('Ngày sinh')
                                    ->mask('99/99/9999')
                                    ->placeholder('dd/mm/yyyy')
                                    ->required(AdminWorkflowOverride::required())
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
                                Select::make('education')
                                    ->label('Học vấn')
                                    ->options([
                                        'Trung học cơ sở' => 'Trung học cơ sở',
                                        'Trung học phổ thông' => 'Trung học phổ thông',
                                        'Trung cấp/Cao đẳng' => 'Trung cấp/Cao đẳng',
                                        'Đại học' => 'Đại học',
                                        'Sau đại học' => 'Sau đại học',
                                    ])
                                    ->native(false),
                                TextInput::make('nationality')
                                    ->label('Quốc tịch')
                                    ->maxLength(120),
                            ]),
                        Section::make('Địa chỉ cư trú')
                            ->description('Thông tin nơi ở hiện tại')
                            ->columns(3)
                            ->schema([
                                Select::make('province_code')
                                    ->label('Tỉnh/Thành phố')
                                    ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                                    ->placeholder('Chọn tỉnh/thành phố')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        $set('province_name', VietnamAddressCatalog::provinceName($state));
                                        $set('district_code', null);
                                        $set('district_name', null);
                                        $set('ward_code', null);
                                        $set('ward_name', null);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('district_code')
                                    ->label('Quận/Huyện')
                                    ->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions($get('province_code')))
                                    ->placeholder('Chọn quận/huyện')
                                    ->disabled(fn (Get $get): bool => blank($get('province_code')))
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                        $set('district_name', VietnamAddressCatalog::districtName($get('province_code'), $state));
                                        $set('ward_code', null);
                                        $set('ward_name', null);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('ward_code')
                                    ->label('Phường/Xã')
                                    ->options(fn (Get $get): array => VietnamAddressCatalog::wardOptions($get('district_code')))
                                    ->placeholder('Chọn phường/xã')
                                    ->disabled(fn (Get $get): bool => blank($get('district_code')))
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set('ward_name', VietnamAddressCatalog::wardName($get('district_code'), $state)))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Hidden::make('province_name')->dehydrated(),
                                Hidden::make('district_name')->dehydrated(),
                                Hidden::make('ward_name')->dehydrated(),
                                Textarea::make('current_address')
                                    ->label('Địa chỉ hiện tại')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Địa chỉ thường trú')
                            ->description('Thông tin nơi đăng ký thường trú')
                            ->columns(3)
                            ->schema([
                                Select::make('permanent_province_code')
                                    ->label('Tỉnh/Thành phố')
                                    ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                                    ->placeholder('Chọn tỉnh/thành phố')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('permanent_district_code')
                                    ->label('Quận/Huyện')
                                    ->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions($get('permanent_province_code')))
                                    ->placeholder('Chọn quận/huyện')
                                    ->disabled(fn (Get $get): bool => blank($get('permanent_province_code')))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('permanent_ward_code')
                                    ->label('Phường/Xã')
                                    ->options(fn (Get $get): array => VietnamAddressCatalog::wardOptions($get('permanent_district_code')))
                                    ->placeholder('Chọn phường/xã')
                                    ->disabled(fn (Get $get): bool => blank($get('permanent_district_code')))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Textarea::make('permanent_address')
                                    ->label('Địa chỉ thường trú')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Công việc')
                            ->description('Thông tin công việc')
                            ->columns(3)
                            ->schema([
                                TextInput::make('employer_name')
                                    ->label('Tên đơn vị/Công việc')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('employer_tax_code')
                                    ->label('Mã số thuế')
                                    ->maxLength(30),
                                Select::make('employer_province_code')
                                    ->label('Tỉnh/Thành phố')
                                    ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                                    ->placeholder('Chọn tỉnh/thành phố')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('employer_district_code')
                                    ->label('Quận/Huyện')
                                    ->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions($get('employer_province_code')))
                                    ->placeholder('Chọn quận/huyện')
                                    ->disabled(fn (Get $get): bool => blank($get('employer_province_code')))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('employer_ward_code')
                                    ->label('Phường/Xã')
                                    ->options(fn (Get $get): array => VietnamAddressCatalog::wardOptions($get('employer_district_code')))
                                    ->placeholder('Chọn phường/xã')
                                    ->disabled(fn (Get $get): bool => blank($get('employer_district_code')))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                TextInput::make('employer_phone')
                                    ->label('SĐT nơi làm việc')
                                    ->tel()
                                    ->maxLength(30),
                                Select::make('employment_type')
                                    ->label('Hình thức công việc')
                                    ->options([
                                        'FULL_TIME' => 'Toàn thời gian',
                                        'PART_TIME' => 'Bán thời gian',
                                        'SELF_EMPLOYED' => 'Tự do',
                                    ])
                                    ->native(false),
                                TextInput::make('working_years')
                                    ->label('Năm làm việc')
                                    ->numeric()
                                    ->maxLength(3),
                                TextInput::make('working_months')
                                    ->label('Tháng làm việc')
                                    ->numeric()
                                    ->maxLength(2),
                                TextInput::make('monthly_income')
                                    ->label('Thu nhập/tháng')
                                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                                    ->stripCharacters('.')
                                    ->suffix('VNĐ')
                                    ->maxLength(30),
                                Textarea::make('employer_address')
                                    ->label('Địa chỉ công ty')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Tham chiếu')
                            ->description('Thông tin người tham chiếu')
                            ->columns(3)
                            ->schema([
                                TextInput::make('reference_1_name')
                                    ->label('Tên')
                                    ->maxLength(255),
                                Select::make('reference_1_relationship')
                                    ->label('Quan hệ')
                                    ->options([
                                        'Cha' => 'Cha',
                                        'Mẹ' => 'Mẹ',
                                        'Anh' => 'Anh',
                                        'Chị' => 'Chị',
                                        'Em' => 'Em',
                                        'Ông' => 'Ông',
                                        'Bà' => 'Bà',
                                        'Bạn' => 'Bạn',
                                        'Công ty' => 'Công ty',
                                        'Khác' => 'Khác',
                                    ])
                                    ->placeholder('Chọn quan hệ')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                TextInput::make('reference_1_phone')
                                    ->label('SĐT')
                                    ->tel()
                                    ->maxLength(30),
                                TextInput::make('reference_2_name')
                                    ->label('Tên')
                                    ->maxLength(255),
                                Select::make('reference_2_relationship')
                                    ->label('Quan hệ')
                                    ->options([
                                        'Cha' => 'Cha',
                                        'Mẹ' => 'Mẹ',
                                        'Anh' => 'Anh',
                                        'Chị' => 'Chị',
                                        'Em' => 'Em',
                                        'Ông' => 'Ông',
                                        'Bà' => 'Bà',
                                        'Bạn' => 'Bạn',
                                        'Công ty' => 'Công ty',
                                        'Khác' => 'Khác',
                                    ])
                                    ->placeholder('Chọn quan hệ')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                TextInput::make('reference_2_phone')
                                    ->label('SĐT')
                                    ->tel()
                                    ->maxLength(30),
                            ]),
                        Section::make('Giải ngân')
                            ->description('Thông tin tài khoản nhận tiền')
                            ->columns(3)
                            ->schema([
                                Select::make('bank_name')
                                    ->label('Ngân hàng')
                                    ->options(fn (): array => VietnamBankCatalog::options())
                                    ->placeholder('Chọn ngân hàng')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->default(fn (): ?string => self::defaultBankFields(auth()->user())['bank_name'] ?? null),
                                TextInput::make('bank_account_number')
                                    ->label('Số tài khoản')
                                    ->maxLength(120)
                                    ->default(fn (): ?string => self::defaultBankFields(auth()->user())['bank_account_number'] ?? null),
                                TextInput::make('bank_account_name')
                                    ->label('Chủ tài khoản')
                                    ->maxLength(255)
                                    ->default(fn (): ?string => self::defaultBankFields(auth()->user())['bank_account_name'] ?? null),
                                Textarea::make('note')
                                    ->label('Ghi chú')
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
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary">Gửi kiểm tra</button>'))
                ->extraAttributes(['class' => 'crm-record-form-frame crm-lotte-create-form'])
                ->contained(false),
            Hidden::make('loan_purpose_name')->dehydrated(),
            Hidden::make('insurance_label')->default(LotteFinanceSchemeCatalog::insuranceLabel('INSUR69'))->dehydrated(),
            Hidden::make('scheme_sid')->dehydrated(),
            Hidden::make('scheme_interest_rate')->dehydrated(),
            Hidden::make('scheme_interest_code')->dehydrated(),
            Hidden::make('scheme_interest_period')->dehydrated(),
            Hidden::make('scheme_dti')->dehydrated(),
            Hidden::make('scheme_ltv')->dehydrated(),
            Hidden::make('scheme_loan_amount_min')->dehydrated(),
            Hidden::make('scheme_loan_amount_max')->dehydrated(),
            Hidden::make('scheme_age_min')->dehydrated(),
            Hidden::make('scheme_age_max')->dehydrated(),
            Hidden::make('scheme_loan_period_min')->dehydrated(),
            Hidden::make('scheme_loan_period_max')->dehydrated(),
        ];
    }

    public static function defaultBankFields(mixed $user): array
    {
        $userData = $user instanceof Model ? $user->toArray() : [];
        $bankName = Arr::get($userData, 'bank_name')
            ?? Arr::get($userData, 'bankName')
            ?? $user?->bank_name
            ?? $user?->bankName
            ?? null;
        $bankAccountNumber = Arr::get($userData, 'bank_account_number')
            ?? Arr::get($userData, 'bankAccountNumber')
            ?? $user?->bank_account_number
            ?? $user?->bankAccountNumber
            ?? null;
        $bankAccountName = Arr::get($userData, 'bank_account_name')
            ?? Arr::get($userData, 'bankAccountName')
            ?? $user?->bank_account_name
            ?? $user?->bankAccountName
            ?? null;

        return [
            'disbursement_method' => 'bank',
            'bank_name' => VietnamBankCatalog::codeFor($bankName),
            'bank_account_number' => $bankAccountNumber,
            'bank_account_name' => $bankAccountName,
        ];
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
        $set('scheme_ltv', $scheme['ltv'] ?? null);
        $set('scheme_ltv_label', filled($scheme['ltv'] ?? null) ? '<= '.$scheme['ltv'].'%' : null);
        $set('scheme_loan_amount_min', $scheme['loan_amount_min'] ?? null);
        $set('scheme_loan_amount_max', $scheme['loan_amount_max'] ?? null);
        $set('scheme_loan_amount_range', self::moneyRange($scheme['loan_amount_min'] ?? null, $scheme['loan_amount_max'] ?? null));
        $set('scheme_age_min', $scheme['age_min'] ?? null);
        $set('scheme_age_max', $scheme['age_max'] ?? null);
        $set('scheme_age_range', self::numberRange($scheme['age_min'] ?? null, $scheme['age_max'] ?? null, ' tuổi'));
        $set('scheme_insurance_fee', $scheme['insurance_fee'] ?? null);
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

    private static function moneyRange(mixed $min, mixed $max): ?string
    {
        if (blank($min) && blank($max)) {
            return null;
        }

        $from = filled($min) ? self::formatMoney((float) $min) : '0';
        $to = filled($max) ? self::formatMoney((float) $max) : 'không giới hạn';

        return $from.' - '.$to.' VNĐ';
    }

    private static function numberRange(mixed $min, mixed $max, string $suffix = ''): ?string
    {
        if (blank($min) && blank($max)) {
            return null;
        }

        if (filled($min) && filled($max)) {
            return $min.' - '.$max.$suffix;
        }

        return (filled($min) ? 'Từ '.$min : 'Đến '.$max).$suffix;
    }

    public static function fieldKeys(): array
    {
        return [
            'customer_name', 'phone', 'identity_number', 'birthday', 'gender', 'education', 'marital_status',
            'identity_issue_date', 'identity_expiry_date', 'identity_issue_place', 'nationality', 'province_code',
            'province_name', 'district_code', 'district_name', 'ward_code', 'ward_name', 'residence_type',
            'residence_duration_years', 'residence_duration_months', 'employer_name', 'employer_tax_code',
            'employer_province_code', 'employer_district_code', 'employer_ward_code', 'employer_address',
            'employer_phone', 'employment_type', 'working_years', 'working_months', 'monthly_income',
            'contract_type', 'experience_years', 'experience_months', 'reference_1_name',
            'reference_1_relationship', 'reference_1_phone', 'reference_2_name', 'reference_2_relationship',
            'reference_2_phone', 'disbursement_method', 'bank_name', 'bank_account_number', 'bank_account_name',
            'permanent_province_code', 'permanent_district_code', 'permanent_ward_code', 'permanent_address',
            'current_address', 'note', 'scheme_code', 'scheme_name',
            'scheme_product_type', 'scheme_product', 'scheme_product_line', 'scheme_description', 'scheme_sid',
            'scheme_start_date', 'scheme_loan_period', 'scheme_loan_period_min', 'scheme_loan_period_max',
            'scheme_interest_rate', 'scheme_interest_code', 'scheme_interest_period', 'scheme_dti', 'scheme_dti_label',
            'scheme_ltv', 'scheme_ltv_label', 'scheme_loan_amount_min', 'scheme_loan_amount_max',
            'scheme_loan_amount_range', 'scheme_age_min', 'scheme_age_max', 'scheme_age_range', 'scheme_insurance_fee',
            'loan_purpose_code', 'loan_purpose_name', 'loan_amount', 'combo_loan_amount', 'loan_term_months',
            'insurance_code', 'insurance_label', 'interest_option', 'estimated_insurance_amount',
            'estimated_monthly_payment', 'estimated_total_payment', 'ocr_front_image', 'ocr_back_image',
        ];
    }
}
