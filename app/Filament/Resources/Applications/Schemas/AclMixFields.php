<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Support\AdminWorkflowOverride;
use App\Support\VietnamAddressCatalog;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Illuminate\Support\Str;

class AclMixFields
{
    private const ROOT = 'payload.module_fields';

    private const RELATIONSHIPS = [
        'Ba/Mẹ ruột' => 'Ba/Mẹ ruột',
        'Ba/Mẹ Vợ/Chồng' => 'Ba/Mẹ Vợ/Chồng',
        'Họ hàng' => 'Họ hàng',
        'Bạn bè' => 'Bạn bè',
        'Đồng nghiệp' => 'Đồng nghiệp',
        'Anh/Chị/Em ruột' => 'Anh/Chị/Em ruột',
        'Anh/Chị/Em họ' => 'Anh/Chị/Em họ',
        'Khác' => 'Khác',
    ];

    public static function components(bool|\Closure $disabled = false): array
    {
        return [
            Section::make('Thông tin khách hàng')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    self::text('customer_name', 'Họ tên')
                        ->required(AdminWorkflowOverride::required())
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set(
                            self::path('bank_account_name'),
                            filled($state) ? Str::upper(Str::ascii($state)) : null,
                        )),
                    self::text('cccd', 'CCCD'),
                    self::text('cmnd', 'CMND'),
                    self::date('date_of_birth', 'Ngày sinh'),
                    self::date('identity_issued_date', 'Ngày cấp'),
                    self::select('identity_issued_place', 'Nơi cấp', ['CCS' => 'CCS', 'Bộ Công An' => 'Bộ Công An']),
                    self::date('identity_expiry_date', 'Ngày hết hạn'),
                    self::text('phone', 'Số điện thoại')->tel(),
                    self::select('education', 'Học vấn', [
                        'Trung học cơ sở' => 'Trung học cơ sở',
                        'Trung học phổ thông' => 'Trung học phổ thông',
                        'Trung cấp/Cao đẳng' => 'Trung cấp/Cao đẳng',
                        'Đại học' => 'Đại học',
                        'Sau đại học' => 'Sau đại học',
                    ]),
                    self::select('marital_status', 'Hôn nhân', [
                        'Độc thân' => 'Độc thân',
                        'Đã kết hôn' => 'Đã kết hôn',
                        'Ly hôn' => 'Ly hôn',
                        'Góa' => 'Góa',
                    ]),
                ]),
            self::addressSection('Thông tin cư trú hiện tại', 'current', $disabled),
            Section::make('Thông tin thường trú')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    Toggle::make(self::path('permanent_same_as_current'))
                        ->label('Thường trú giống cư trú hiện tại')
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, bool $state): void {
                            if ($state) {
                                self::copyAddress($get, $set, 'current', 'permanent');
                            }
                        })
                        ->columnSpanFull(),
                    ...self::addressFields('permanent', true),
                ]),
            Section::make('Thông tin công việc')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    self::text('employer_name', 'Tên đơn vị/Công việc'),
                    self::text('employer_tax_code', 'Mã số thuế'),
                    self::text('employer_phone', 'Số điện thoại nơi làm việc')->tel(),
                    self::select('contract_type', 'Loại hợp đồng', [
                        'Dưới 12 tháng' => 'Dưới 12 tháng',
                        'Trên 12 tháng' => 'Trên 12 tháng',
                        'Không xác định thời hạn' => 'Không xác định thời hạn',
                    ]),
                    self::number('working_years', 'Thời gian làm việc - Năm'),
                    self::number('working_months', 'Thời gian làm việc - Tháng')->minValue(0)->maxValue(11),
                    self::text(self::path('monthly_income'), 'Thu nhập')
                        ->numeric()
                        ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                        ->stripCharacters('.')
                        ->suffix('VNĐ')
                        ->minValue(0),
                    self::number('experience_years', 'Kinh nghiệm làm việc - Năm'),
                    self::number('experience_months', 'Kinh nghiệm làm việc - Tháng')->minValue(0)->maxValue(11),
                ]),
            self::addressSection('Địa chỉ nơi làm việc', 'work', $disabled),
            Section::make('Thông tin hôn phối')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    self::text('spouse_name', 'Họ tên hôn phối'),
                    self::text('spouse_identity_number', 'CCCD/CMND hôn phối'),
                    self::text('spouse_phone', 'Số điện thoại hôn phối')->tel(),
                ]),
            Section::make('Thông tin tham chiếu 1')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    self::text('reference_1_name', 'Họ tên tham chiếu 1'),
                    self::select('reference_1_relationship', 'Mối quan hệ tham chiếu 1', self::RELATIONSHIPS),
                    self::text('reference_1_phone', 'Số điện thoại tham chiếu 1')->tel(),
                ]),
            Section::make('Thông tin tham chiếu 2')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    self::text('reference_2_name', 'Họ tên tham chiếu 2'),
                    self::select('reference_2_relationship', 'Mối quan hệ tham chiếu 2', self::RELATIONSHIPS),
                    self::text('reference_2_phone', 'Số điện thoại tham chiếu 2')->tel(),
                ]),
            Section::make('Thông tin giải ngân')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    self::select('disbursement_method', 'Hình thức giải ngân', [
                        'agent' => 'Giải ngân tại đại lý chi hộ',
                        'bank' => 'Giải ngân qua Tài khoản ngân hàng',
                    ])->live(),
                    self::select('bank_name', 'Ngân hàng', self::bankOptions())
                        ->visible(fn (Get $get): bool => $get(self::path('disbursement_method')) === 'bank'),
                    self::text('bank_account_number', 'Số tài khoản')
                        ->visible(fn (Get $get): bool => $get(self::path('disbursement_method')) === 'bank'),
                    self::text('bank_account_name', 'Chủ tài khoản')
                        ->disabled()
                        ->dehydrated()
                        ->visible(fn (Get $get): bool => $get(self::path('disbursement_method')) === 'bank'),
                    Textarea::make(self::path('note'))
                        ->label('Ghi chú')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function entries(): array
    {
        return self::entriesFor(array_column(self::definitions(), 'field_key'));
    }

    /**
     * @param  array<int, string>  $fieldKeys
     * @return array<int, TextEntry>
     */
    public static function entriesFor(array $fieldKeys): array
    {
        $definitions = collect(self::definitions())->keyBy('field_key');

        return collect($fieldKeys)
            ->map(function (string $key) use ($definitions): ?TextEntry {
                $field = $definitions->get($key);

                if (! is_array($field)) {
                    return null;
                }

                $displayKey = Str::endsWith($key, ['_province_code', '_district_code', '_ward_code'])
                    ? Str::replaceEnd('_code', '_name', $key)
                    : $key;
                $entry = TextEntry::make(self::path($displayKey))
                    ->label($field['label'])
                    ->placeholder('-');

                if ($key === 'monthly_income') {
                    $entry->money('VND', locale: 'vi');
                }

                return $entry;
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function definitions(): array
    {
        return [
            ['field_key' => 'customer_name', 'label' => 'Họ tên', 'type' => 'text'],
            ['field_key' => 'cccd', 'label' => 'CCCD', 'type' => 'text'],
            ['field_key' => 'cmnd', 'label' => 'CMND', 'type' => 'text'],
            ['field_key' => 'date_of_birth', 'label' => 'Ngày sinh', 'type' => 'date'],
            ['field_key' => 'identity_issued_date', 'label' => 'Ngày cấp', 'type' => 'date'],
            ['field_key' => 'identity_issued_place', 'label' => 'Nơi cấp', 'type' => 'select'],
            ['field_key' => 'identity_expiry_date', 'label' => 'Ngày hết hạn', 'type' => 'date'],
            ['field_key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'phone'],
            ['field_key' => 'education', 'label' => 'Học vấn', 'type' => 'select'],
            ['field_key' => 'marital_status', 'label' => 'Hôn nhân', 'type' => 'select'],
            ...self::addressDefinitions('current', 'Cư trú'),
            ...self::addressDefinitions('permanent', 'Thường trú'),
            ['field_key' => 'employer_name', 'label' => 'Tên đơn vị/Công việc', 'type' => 'text'],
            ['field_key' => 'employer_tax_code', 'label' => 'Mã số thuế', 'type' => 'text'],
            ['field_key' => 'employer_phone', 'label' => 'SĐT nơi làm việc', 'type' => 'phone'],
            ['field_key' => 'contract_type', 'label' => 'Loại hợp đồng', 'type' => 'select'],
            ['field_key' => 'working_years', 'label' => 'Thời gian làm việc - Năm', 'type' => 'number'],
            ['field_key' => 'working_months', 'label' => 'Thời gian làm việc - Tháng', 'type' => 'number'],
            ['field_key' => 'monthly_income', 'label' => 'Thu nhập', 'type' => 'number'],
            ['field_key' => 'experience_years', 'label' => 'Kinh nghiệm - Năm', 'type' => 'number'],
            ['field_key' => 'experience_months', 'label' => 'Kinh nghiệm - Tháng', 'type' => 'number'],
            ...self::addressDefinitions('work', 'Nơi làm việc'),
            ['field_key' => 'spouse_name', 'label' => 'Họ tên hôn phối', 'type' => 'text'],
            ['field_key' => 'spouse_identity_number', 'label' => 'CCCD/CMND hôn phối', 'type' => 'text'],
            ['field_key' => 'spouse_phone', 'label' => 'SĐT hôn phối', 'type' => 'phone'],
            ['field_key' => 'reference_1_name', 'label' => 'Họ tên tham chiếu 1', 'type' => 'text'],
            ['field_key' => 'reference_1_relationship', 'label' => 'Mối quan hệ tham chiếu 1', 'type' => 'select'],
            ['field_key' => 'reference_1_phone', 'label' => 'SĐT tham chiếu 1', 'type' => 'phone'],
            ['field_key' => 'reference_2_name', 'label' => 'Họ tên tham chiếu 2', 'type' => 'text'],
            ['field_key' => 'reference_2_relationship', 'label' => 'Mối quan hệ tham chiếu 2', 'type' => 'select'],
            ['field_key' => 'reference_2_phone', 'label' => 'SĐT tham chiếu 2', 'type' => 'phone'],
            ['field_key' => 'disbursement_method', 'label' => 'Hình thức giải ngân', 'type' => 'select'],
            ['field_key' => 'bank_name', 'label' => 'Ngân hàng', 'type' => 'select'],
            ['field_key' => 'bank_account_number', 'label' => 'Số tài khoản', 'type' => 'text'],
            ['field_key' => 'bank_account_name', 'label' => 'Chủ tài khoản', 'type' => 'text'],
            ['field_key' => 'note', 'label' => 'Ghi chú', 'type' => 'textarea'],
        ];
    }

    public static function normalize(array $payload): array
    {
        $fields = is_array($payload['module_fields'] ?? null) ? $payload['module_fields'] : [];

        if ($fields['permanent_same_as_current'] ?? false) {
            foreach (['province_code', 'province_name', 'district_code', 'district_name', 'ward_code', 'ward_name', 'address_line'] as $key) {
                $fields['permanent_'.$key] = $fields['current_'.$key] ?? null;
            }
        }

        $name = $fields['customer_name'] ?? data_get($payload, 'fields.customer_name') ?? data_get($payload, 'fields.lead_name');

        if (filled($name)) {
            $fields['bank_account_name'] = Str::upper(Str::ascii((string) $name));
        }

        $payload['module_fields'] = $fields;

        return $payload;
    }

    private static function addressSection(string $title, string $prefix, bool|\Closure $disabled = false): Section
    {
        return Section::make($title)
            ->disabled($disabled)
            ->columns(2)
            ->schema(self::addressFields($prefix));
    }

    private static function addressFields(string $prefix, bool $locked = false): array
    {
        $disabled = fn (Get $get): bool => $locked && (bool) $get(self::path('permanent_same_as_current'));

        return [
            self::select($prefix.'_province_code', 'Tỉnh/Thành phố', fn (): array => VietnamAddressCatalog::provinceOptions())
                ->disabled($disabled)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($prefix): void {
                    $set(self::path($prefix.'_province_name'), VietnamAddressCatalog::provinceName($state));
                    $set(self::path($prefix.'_district_code'), null);
                    $set(self::path($prefix.'_district_name'), null);
                    $set(self::path($prefix.'_ward_code'), null);
                    $set(self::path($prefix.'_ward_name'), null);
                    self::syncPermanentWhenLocked($get, $set, $prefix);
                }),
            self::select($prefix.'_district_code', 'Quận/Huyện', fn (Get $get): array => VietnamAddressCatalog::districtOptions($get(self::path($prefix.'_province_code'))))
                ->disabled(fn (Get $get): bool => $disabled($get) || blank($get(self::path($prefix.'_province_code'))))
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($prefix): void {
                    $set(self::path($prefix.'_district_name'), VietnamAddressCatalog::districtName($get(self::path($prefix.'_province_code')), $state));
                    $set(self::path($prefix.'_ward_code'), null);
                    $set(self::path($prefix.'_ward_name'), null);
                    self::syncPermanentWhenLocked($get, $set, $prefix);
                }),
            self::select($prefix.'_ward_code', 'Phường/Xã', fn (Get $get): array => VietnamAddressCatalog::wardOptions($get(self::path($prefix.'_district_code'))))
                ->disabled(fn (Get $get): bool => $disabled($get) || blank($get(self::path($prefix.'_district_code'))))
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($prefix): void {
                    $set(self::path($prefix.'_ward_name'), VietnamAddressCatalog::wardName($get(self::path($prefix.'_district_code')), $state));
                    self::syncPermanentWhenLocked($get, $set, $prefix);
                }),
            self::text($prefix.'_address_line', 'Địa chỉ chi tiết')
                ->disabled($disabled)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set): mixed => self::syncPermanentWhenLocked($get, $set, $prefix)),
            Hidden::make(self::path($prefix.'_province_name'))->dehydrated(),
            Hidden::make(self::path($prefix.'_district_name'))->dehydrated(),
            Hidden::make(self::path($prefix.'_ward_name'))->dehydrated(),
        ];
    }

    private static function syncPermanentWhenLocked(Get $get, Set $set, string $prefix): void
    {
        if ($prefix === 'current' && $get(self::path('permanent_same_as_current'))) {
            self::copyAddress($get, $set, 'current', 'permanent');
        }
    }

    private static function copyAddress(Get $get, Set $set, string $source, string $target): void
    {
        foreach (['province_code', 'province_name', 'district_code', 'district_name', 'ward_code', 'ward_name', 'address_line'] as $key) {
            $set(self::path($target.'_'.$key), $get(self::path($source.'_'.$key)));
        }
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

    private static function addressDefinitions(string $prefix, string $label): array
    {
        return [
            ['field_key' => $prefix.'_province_code', 'label' => $label.' - Tỉnh/Thành phố', 'type' => 'select'],
            ['field_key' => $prefix.'_district_code', 'label' => $label.' - Quận/Huyện', 'type' => 'select'],
            ['field_key' => $prefix.'_ward_code', 'label' => $label.' - Phường/Xã', 'type' => 'select'],
            ['field_key' => $prefix.'_address_line', 'label' => $label.' - Địa chỉ chi tiết', 'type' => 'text'],
        ];
    }

    private static function bankOptions(): array
    {
        return [
            'Vietcombank' => 'Vietcombank',
            'VietinBank' => 'VietinBank',
            'BIDV' => 'BIDV',
            'Agribank' => 'Agribank',
            'MB Bank' => 'MB Bank',
            'Techcombank' => 'Techcombank',
            'ACB' => 'ACB',
            'VPBank' => 'VPBank',
            'Sacombank' => 'Sacombank',
            'TPBank' => 'TPBank',
            'VIB' => 'VIB',
            'SHB' => 'SHB',
            'OCB' => 'OCB',
            'HDBank' => 'HDBank',
            'MSB' => 'MSB',
            'SeABank' => 'SeABank',
            'Eximbank' => 'Eximbank',
        ];
    }
}
