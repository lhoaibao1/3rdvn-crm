<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Schemas;

use App\Enums\FeDeeplinkStatus;
use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Support\Applications\FeolConsent;
use App\Support\SalesLineSnapshot;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class FeDeeplinkApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])
            ->components([
                View::make('filament.feol.create-header')
                    ->columnSpanFull()
                    ->visibleOn('create'),
                Section::make('Thông tin đăng ký')
                    ->description('Nhập đầy đủ thông tin theo biểu mẫu FE CREDIT. Hồ sơ được lưu CRM trước khi gửi đối tác.')
                    ->extraAttributes(['class' => 'feol-partner-form-card'])
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('applicant_name')
                            ->label('Họ và tên')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->length(10)
                            ->rules(['regex:/^0[0-9]{9}$/'])
                            ->required()
                            ->placeholder('Nhập số điện thoại (bắt buộc), phải đủ 10 số'),
                        TextInput::make('identity_number')
                            ->label('Số CCCD')
                            ->length(12)
                            ->rules(['regex:/^[0-9]{12}$/'])
                            ->required()
                            ->placeholder('Nhập số CCCD (bắt buộc), phải đủ 12 số'),
                        TextInput::make('payload.fields.date_of_birth')
                            ->label('Ngày tháng năm sinh')
                            ->mask('99/99/9999')
                            ->placeholder('dd/mm/yyyy')
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->maxLength(10)
                            ->rules(['date_format:d/m/Y', 'before_or_equal:today'])
                            ->formatStateUsing(fn (?string $state): ?string => filled($state)
                                ? CarbonImmutable::parse($state)->format('d/m/Y')
                                : null)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? CarbonImmutable::createFromFormat('d/m/Y', $state)->format('Y-m-d')
                                : null)
                            ->required(),
                        TextInput::make('payload.fields.email')
                            ->label('Địa chỉ Email')
                            ->email()
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('payload.fields.loan_amount')
                            ->label('Số tiền vay')
                            ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                            ->stripCharacters('.')
                            ->minValue(1000000)
                            ->prefix('₫')
                            ->rules(['integer', 'max:1000000000'])
                            ->required(),
                        TextInput::make('payload.fields.loan_term_months')
                            ->label('Thời hạn vay (tháng)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->suffix('tháng')
                            ->required(),
                        TextInput::make('payload.fields.referral_code')
                            ->label('Mã giới thiệu')
                            ->default(fn (): ?string => auth()->user()
                                ? data_get(auth()->user()->sales_codes, 'fe-deeplink')
                                : null)
                            ->length(5)
                            ->required()
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Tự động lấy từ mã bán hàng FE Deeplink của tài khoản đang đăng nhập.'),
                        TextInput::make('payload.fields.salesman_code')
                            ->label('Mã nhân viên')
                            ->default(fn (): ?string => config('services.feol_bridge.landing_sale_code'))
                            ->readOnly()
                            ->dehydrated(),
                        Hidden::make('created_by_id')
                            ->default(fn (): ?int => auth()->id())
                            ->required(),
                        Hidden::make('created_at')
                            ->default(now())
                            ->required(),
                        Checkbox::make('payload.fields.customer_consent')
                            ->label(FeolConsent::TEXT)
                            ->accepted()
                            ->required()
                            ->columnSpanFull(),
                        Hidden::make('status')
                            ->default(FeDeeplinkStatus::PENDING_SUBMISSION->value)
                            ->visibleOn('create'),
                        Select::make('status')
                            ->label('Trạng thái FEOL')
                            ->options(FeDeeplinkStatus::options())
                            ->default(FeDeeplinkStatus::PENDING_SUBMISSION->value)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->hiddenOn('create'),
                    ]),
                Section::make('Kết quả đối tác / nhập thủ công UAT')
                    ->relationship('feolIntegration')
                    ->columns(2)
                    ->hiddenOn('create')
                    ->schema([
                        TextInput::make('partner_lead_id')->label('Lead ID đối tác')->maxLength(100),
                        TextInput::make('partner_app_id')->label('App ID')->maxLength(100),
                        TextInput::make('main_status')->label('Trạng thái chính')->maxLength(100),
                        TextInput::make('b1_url')->label('Landing Page B1')->url()->maxLength(4000)->columnSpanFull(),
                        TextInput::make('deeplink_url')->label('Deeplink')->url()->maxLength(4000)->columnSpanFull(),
                    ]),
                Section::make('Kết quả tài chính FEOL')
                    ->columns(2)
                    ->hiddenOn('create')
                    ->schema([
                        DatePicker::make('payload.fields.disbursed_at')
                            ->label('Ngày giải ngân')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Select::make('payload.fields.product')
                            ->label('Sản phẩm')
                            ->options([
                                'NTB' => 'NTB',
                                'Xsell' => 'Xsell',
                                'Topup' => 'Topup',
                            ])
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('payload.fields.approved_amount')
                            ->label('Số tiền duyệt')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₫'),
                    ]),
            ]);
    }

    public static function normalizePayload(array $payload): array
    {
        $disbursedAt = data_get($payload, 'fields.disbursed_at');

        if (filled($disbursedAt)) {
            data_set($payload, 'fields.completed_at', $disbursedAt);
        }

        return $payload;
    }

    public static function normalizeDataForSave(Application $record, array $data): array
    {
        $payload = array_replace_recursive($record->payload ?? [], $data['payload'] ?? []);
        $data['payload'] = self::normalizePayload($payload);
        $data['sales_project_id'] = $record->sales_project_id;
        $data['status'] = FeDeeplinkStatus::from((string) ($data['status'] ?? $record->status))->value;
        $data['assigned_sale_id'] = $data['created_by_id'] ?? $record->assigned_sale_id;
        $data = array_replace($data, SalesLineSnapshot::hierarchyForUserId($data['created_by_id'] ?? $record->created_by_id));

        return $data;
    }
}
