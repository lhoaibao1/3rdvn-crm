<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Support\AdminWorkflowOverride;
use App\Support\VietnamAddressCatalog;
use App\Support\VietnamBankCatalog;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;

class LotteFinanceFields
{
    private const ROOT = 'payload.fields';

    /** @var array<string, string> */
    private const LEGACY_TO_FIELDS = [
        'customer_name' => 'customer_name',
        'phone' => 'phone',
        'cccd' => 'identity_number',
        'date_of_birth' => 'birthday',
        'identity_issued_date' => 'identity_issue_date',
        'identity_issued_place' => 'identity_issue_place',
        'identity_expiry_date' => 'identity_expiry_date',
        'education' => 'education',
        'marital_status' => 'marital_status',
        'residence_type' => 'residence_type',
        'residence_duration_years' => 'residence_duration_years',
        'residence_duration_months' => 'residence_duration_months',
        'current_province_code' => 'province_code',
        'current_district_code' => 'district_code',
        'current_ward_code' => 'ward_code',
        'current_address_line' => 'current_address',
        'permanent_province_code' => 'permanent_province_code',
        'permanent_district_code' => 'permanent_district_code',
        'permanent_ward_code' => 'permanent_ward_code',
        'permanent_address_line' => 'permanent_address',
        'employer_name' => 'employer_name',
        'employer_tax_code' => 'employer_tax_code',
        'employer_phone' => 'employer_phone',
        'work_province_code' => 'employer_province_code',
        'work_district_code' => 'employer_district_code',
        'work_ward_code' => 'employer_ward_code',
        'work_address_line' => 'employer_address',
        'contract_type' => 'contract_type',
        'working_years' => 'working_years',
        'working_months' => 'working_months',
        'monthly_income' => 'monthly_income',
        'experience_years' => 'experience_years',
        'experience_months' => 'experience_months',
        'reference_1_name' => 'reference_1_name',
        'reference_1_relationship' => 'reference_1_relationship',
        'reference_1_phone' => 'reference_1_phone',
        'reference_2_name' => 'reference_2_name',
        'reference_2_relationship' => 'reference_2_relationship',
        'reference_2_phone' => 'reference_2_phone',
        'disbursement_method' => 'disbursement_method',
        'bank_name' => 'bank_name',
        'bank_account_number' => 'bank_account_number',
        'bank_account_name' => 'bank_account_name',
        'note' => 'note',
    ];

    private const RELATIONSHIPS = [
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
    ];

    public static function components(bool|\Closure $disabled = false): array
    {
        return [
            Section::make('Thông tin cá nhân')
                ->disabled($disabled)
                ->columns(3)
                ->schema([
                    self::text('customer_name', 'Họ tên khách hàng')->required(AdminWorkflowOverride::required())->columnSpan(2),
                    self::text('phone', 'Số điện thoại')->tel()->required(AdminWorkflowOverride::required()),
                    self::text('identity_number', 'CCCD/CMND')->required(AdminWorkflowOverride::required()),
                    self::date('birthday', 'Ngày sinh'),
                    self::select('gender', 'Giới tính', ['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'OTHER' => 'Khác']),
                    self::select('education', 'Học vấn', [
                        'Trung học cơ sở' => 'Trung học cơ sở',
                        'Trung học phổ thông' => 'Trung học phổ thông',
                        'Trung cấp/Cao đẳng' => 'Trung cấp/Cao đẳng',
                        'Đại học' => 'Đại học',
                        'Sau đại học' => 'Sau đại học',
                    ]),
                    self::select('marital_status', 'Tình trạng hôn nhân', [
                        'Độc thân' => 'Độc thân',
                        'Đã kết hôn' => 'Đã kết hôn',
                        'Ly hôn' => 'Ly hôn',
                        'Góa' => 'Góa',
                    ]),
                    self::text('nationality', 'Quốc tịch'),
                    self::date('identity_issue_date', 'Ngày cấp giấy tờ'),
                    self::select('identity_issue_place', 'Nơi cấp', ['CCS' => 'CCS', 'Bộ Công An' => 'Bộ Công An']),
                    self::date('identity_expiry_date', 'Ngày hết hạn giấy tờ'),
                ]),
            self::addressSection('Địa chỉ cư trú', '', 'current_address', $disabled),
            Section::make('Thông tin cư trú')
                ->disabled($disabled)
                ->columns(3)
                ->schema([
                    self::text('residence_type', 'Hình thức cư trú'),
                    self::number('residence_duration_years', 'Thời gian cư trú - Năm'),
                    self::number('residence_duration_months', 'Thời gian cư trú - Tháng')->minValue(0)->maxValue(11),
                ]),
            self::addressSection('Địa chỉ thường trú', 'permanent_', 'permanent_address', $disabled),
            Section::make('Thông tin công việc')
                ->disabled($disabled)
                ->columns(3)
                ->schema([
                    self::text('employer_name', 'Tên đơn vị/Công việc')->columnSpan(2),
                    self::text('employer_tax_code', 'Mã số thuế'),
                    self::text('employer_phone', 'SĐT nơi làm việc')->tel(),
                    self::select('employment_type', 'Hình thức công việc', [
                        'FULL_TIME' => 'Toàn thời gian',
                        'PART_TIME' => 'Bán thời gian',
                        'SELF_EMPLOYED' => 'Tự do',
                    ]),
                    self::select('contract_type', 'Loại hợp đồng', [
                        'Dưới 12 tháng' => 'Dưới 12 tháng',
                        'Trên 12 tháng' => 'Trên 12 tháng',
                        'Không xác định thời hạn' => 'Không xác định thời hạn',
                    ]),
                    self::number('working_years', 'Thời gian làm việc - Năm'),
                    self::number('working_months', 'Thời gian làm việc - Tháng')->minValue(0)->maxValue(11),
                    self::money('monthly_income', 'Thu nhập/tháng'),
                    self::number('experience_years', 'Kinh nghiệm - Năm'),
                    self::number('experience_months', 'Kinh nghiệm - Tháng')->minValue(0)->maxValue(11),
                ]),
            self::addressSection('Địa chỉ nơi làm việc', 'employer_', 'employer_address', $disabled),
            Section::make('Thông tin tham chiếu')
                ->disabled($disabled)
                ->columns(3)
                ->schema([
                    self::text('reference_1_name', 'Người tham chiếu 1'),
                    self::select('reference_1_relationship', 'Quan hệ tham chiếu 1', self::RELATIONSHIPS),
                    self::text('reference_1_phone', 'SĐT tham chiếu 1')->tel(),
                    self::text('reference_2_name', 'Người tham chiếu 2'),
                    self::select('reference_2_relationship', 'Quan hệ tham chiếu 2', self::RELATIONSHIPS),
                    self::text('reference_2_phone', 'SĐT tham chiếu 2')->tel(),
                ]),
            Section::make('Thông tin giải ngân')
                ->disabled($disabled)
                ->columns(3)
                ->schema([
                    self::select('disbursement_method', 'Hình thức giải ngân', [
                        'agent' => 'Giải ngân tại đại lý chi hộ',
                        'bank' => 'Giải ngân qua tài khoản ngân hàng',
                    ]),
                    self::select('bank_name', 'Ngân hàng', fn (): array => VietnamBankCatalog::options()),
                    self::text('bank_account_number', 'Số tài khoản'),
                    self::text('bank_account_name', 'Chủ tài khoản'),
                    Textarea::make(self::path('note'))->label('Ghi chú')->rows(2)->columnSpanFull(),
                ]),
        ];
    }

    public static function personalEntries(): array
    {
        return [
            self::entry('customer_name', 'Họ tên khách hàng'),
            self::entry('phone', 'Số điện thoại'),
            self::entry('identity_number', 'CCCD/CMND'),
            self::entry('birthday', 'Ngày sinh'),
            self::optionEntry('gender', 'Giới tính', [
                'MALE' => 'Nam',
                'FEMALE' => 'Nữ',
                'OTHER' => 'Khác',
            ]),
            self::entry('education', 'Học vấn'),
            self::entry('marital_status', 'Tình trạng hôn nhân'),
            self::entry('nationality', 'Quốc tịch'),
            self::entry('identity_issue_date', 'Ngày cấp giấy tờ'),
            self::entry('identity_issue_place', 'Nơi cấp'),
            self::entry('identity_expiry_date', 'Ngày hết hạn giấy tờ'),
        ];
    }

    public static function currentAddressEntries(): array
    {
        return [
            self::addressEntry('', 'province', 'Tỉnh/Thành phố'),
            self::addressEntry('', 'district', 'Quận/Huyện'),
            self::addressEntry('', 'ward', 'Phường/Xã'),
            self::entry('current_address', 'Địa chỉ hiện tại'),
            self::entry('residence_type', 'Hình thức cư trú'),
            self::entry('residence_duration_years', 'Thời gian cư trú - Năm'),
            self::entry('residence_duration_months', 'Thời gian cư trú - Tháng'),
        ];
    }

    public static function permanentAddressEntries(): array
    {
        return [
            self::addressEntry('permanent_', 'province', 'Tỉnh/Thành phố'),
            self::addressEntry('permanent_', 'district', 'Quận/Huyện'),
            self::addressEntry('permanent_', 'ward', 'Phường/Xã'),
            self::entry('permanent_address', 'Địa chỉ thường trú'),
        ];
    }

    public static function workEntries(): array
    {
        return [
            self::entry('employer_name', 'Tên đơn vị/Công việc'),
            self::entry('employer_tax_code', 'Mã số thuế'),
            self::entry('employer_phone', 'SĐT nơi làm việc'),
            self::optionEntry('employment_type', 'Hình thức công việc', [
                'FULL_TIME' => 'Toàn thời gian',
                'PART_TIME' => 'Bán thời gian',
                'SELF_EMPLOYED' => 'Tự do',
            ]),
            self::entry('contract_type', 'Loại hợp đồng'),
            self::entry('working_years', 'Thời gian làm việc - Năm'),
            self::entry('working_months', 'Thời gian làm việc - Tháng'),
            self::moneyEntry('monthly_income', 'Thu nhập/tháng'),
            self::entry('experience_years', 'Kinh nghiệm - Năm'),
            self::entry('experience_months', 'Kinh nghiệm - Tháng'),
        ];
    }

    public static function workAddressEntries(): array
    {
        return [
            self::addressEntry('employer_', 'province', 'Tỉnh/Thành phố'),
            self::addressEntry('employer_', 'district', 'Quận/Huyện'),
            self::addressEntry('employer_', 'ward', 'Phường/Xã'),
            self::entry('employer_address', 'Địa chỉ công ty'),
        ];
    }

    public static function contactEntries(): array
    {
        return [
            self::entry('reference_1_name', 'Người tham chiếu 1'),
            self::entry('reference_1_relationship', 'Quan hệ tham chiếu 1'),
            self::entry('reference_1_phone', 'SĐT tham chiếu 1'),
            self::entry('reference_2_name', 'Người tham chiếu 2'),
            self::entry('reference_2_relationship', 'Quan hệ tham chiếu 2'),
            self::entry('reference_2_phone', 'SĐT tham chiếu 2'),
        ];
    }

    public static function disbursementEntries(): array
    {
        return [
            self::optionEntry('disbursement_method', 'Hình thức giải ngân', [
                'agent' => 'Giải ngân tại đại lý chi hộ',
                'bank' => 'Giải ngân qua tài khoản ngân hàng',
            ]),
            self::bankEntry(),
            self::entry('bank_account_number', 'Số tài khoản'),
            self::entry('bank_account_name', 'Chủ tài khoản'),
        ];
    }

    public static function prepareDataForFill(array $data): array
    {
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $payload['fields'] = self::mergeLegacyIntoFields($payload);
        $data['payload'] = $payload;

        return $data;
    }

    public static function synchronizeLegacyFields(array $payload): array
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $legacy = is_array($payload['module_fields'] ?? null) ? $payload['module_fields'] : [];

        foreach (self::LEGACY_TO_FIELDS as $legacyKey => $fieldKey) {
            if (array_key_exists($fieldKey, $fields)) {
                $legacy[$legacyKey] = $fields[$fieldKey];
            }
        }

        $payload['module_fields'] = $legacy;

        return $payload;
    }

    private static function mergeLegacyIntoFields(array $payload): array
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $legacy = is_array($payload['module_fields'] ?? null) ? $payload['module_fields'] : [];

        foreach (self::LEGACY_TO_FIELDS as $legacyKey => $fieldKey) {
            if (blank($fields[$fieldKey] ?? null) && filled($legacy[$legacyKey] ?? null)) {
                $fields[$fieldKey] = $legacy[$legacyKey];
            }
        }

        if (blank($fields['identity_number'] ?? null) && filled($legacy['cmnd'] ?? null)) {
            $fields['identity_number'] = $legacy['cmnd'];
        }

        return $fields;
    }

    private static function displayValue(Application $record, string $key): mixed
    {
        $value = data_get(self::mergeLegacyIntoFields(is_array($record->payload) ? $record->payload : []), $key);

        if (filled($value)) {
            return $value;
        }

        return match ($key) {
            'customer_name' => $record->applicant_name,
            'phone' => $record->phone,
            'identity_number' => $record->identity_number,
            default => $value,
        };
    }

    private static function entry(string $key, string $label): TextEntry
    {
        return TextEntry::make(self::path($key))
            ->label($label)
            ->state(fn (Application $record): mixed => self::displayValue($record, $key))
            ->placeholder('-');
    }

    private static function optionEntry(string $key, string $label, array $options): TextEntry
    {
        return self::entry($key, $label)
            ->formatStateUsing(fn (mixed $state): string => filled($state)
                ? ($options[(string) $state] ?? (string) $state)
                : '-');
    }

    private static function bankEntry(): TextEntry
    {
        return self::entry('bank_name', 'Ngân hàng')
            ->formatStateUsing(fn (mixed $state): string => filled($state)
                ? (VietnamBankCatalog::labelFor((string) $state) ?? (string) $state)
                : '-');
    }

    private static function moneyEntry(string $key, string $label): TextEntry
    {
        return self::entry($key, $label)
            ->formatStateUsing(fn (mixed $state): string => filled($state)
                ? number_format((int) preg_replace('/\D+/', '', (string) $state), 0, ',', '.').' VNĐ'
                : '-');
    }

    private static function addressEntry(string $prefix, string $level, string $label): TextEntry
    {
        $key = $prefix.$level.'_code';

        return self::entry($key, $label)
            ->formatStateUsing(function (mixed $state, Application $record) use ($prefix, $level): string {
                if (blank($state)) {
                    return '-';
                }

                $name = match ($level) {
                    'province' => VietnamAddressCatalog::provinceName((string) $state),
                    'district' => VietnamAddressCatalog::districtName(
                        self::displayValue($record, $prefix.'province_code'),
                        (string) $state,
                    ),
                    'ward' => VietnamAddressCatalog::wardName(
                        self::displayValue($record, $prefix.'district_code'),
                        (string) $state,
                    ),
                    default => null,
                };

                return filled($name) ? (string) $name : (string) $state;
            });
    }

    private static function addressSection(string $title, string $prefix, string $addressKey, bool|\Closure $disabled): Section
    {
        $provinceKey = $prefix.'province_code';
        $districtKey = $prefix.'district_code';
        $wardKey = $prefix.'ward_code';

        return Section::make($title)
            ->disabled($disabled)
            ->columns(3)
            ->schema([
                self::select($provinceKey, 'Tỉnh/Thành phố', fn (): array => VietnamAddressCatalog::provinceOptions())
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) use ($prefix, $districtKey, $wardKey): void {
                        $set(self::path($prefix.'province_name'), VietnamAddressCatalog::provinceName($state));
                        $set(self::path($districtKey), null);
                        $set(self::path($prefix.'district_name'), null);
                        $set(self::path($wardKey), null);
                        $set(self::path($prefix.'ward_name'), null);
                    }),
                self::select($districtKey, 'Quận/Huyện', fn (Get $get): array => VietnamAddressCatalog::districtOptions($get(self::path($provinceKey))))
                    ->disabled(fn (Get $get): bool => blank($get(self::path($provinceKey))))
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($prefix, $provinceKey, $wardKey): void {
                        $set(self::path($prefix.'district_name'), VietnamAddressCatalog::districtName($get(self::path($provinceKey)), $state));
                        $set(self::path($wardKey), null);
                        $set(self::path($prefix.'ward_name'), null);
                    }),
                self::select($wardKey, 'Phường/Xã', fn (Get $get): array => VietnamAddressCatalog::wardOptions($get(self::path($districtKey))))
                    ->disabled(fn (Get $get): bool => blank($get(self::path($districtKey))))
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set(
                        self::path($prefix.'ward_name'),
                        VietnamAddressCatalog::wardName($get(self::path($districtKey)), $state),
                    )),
                Textarea::make(self::path($addressKey))->label('Địa chỉ chi tiết')->rows(2)->columnSpanFull(),
                Hidden::make(self::path($prefix.'province_name'))->dehydrated(),
                Hidden::make(self::path($prefix.'district_name'))->dehydrated(),
                Hidden::make(self::path($prefix.'ward_name'))->dehydrated(),
            ]);
    }

    private static function text(string $key, string $label): TextInput
    {
        return TextInput::make(self::path($key))->label($label)->maxLength(255);
    }

    private static function number(string $key, string $label): TextInput
    {
        return self::text($key, $label)->numeric();
    }

    private static function date(string $key, string $label): TextInput
    {
        return self::text($key, $label)
            ->mask('99/99/9999')
            ->placeholder('dd/mm/yyyy')
            ->rule('date_format:d/m/Y');
    }

    private static function money(string $key, string $label): TextInput
    {
        return self::text($key, $label)
            ->mask(RawJs::make('$money($input, ",", ".", 0)'))
            ->stripCharacters('.')
            ->suffix('VNĐ');
    }

    private static function select(string $key, string $label, array|\Closure $options): Select
    {
        return Select::make(self::path($key))
            ->label($label)
            ->options($options)
            ->searchable()
            ->preload()
            ->native(false);
    }

    private static function path(string $key): string
    {
        return self::ROOT.'.'.$key;
    }
}
