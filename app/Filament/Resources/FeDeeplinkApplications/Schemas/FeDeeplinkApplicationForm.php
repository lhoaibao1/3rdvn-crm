<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Schemas;

use App\Enums\FeDeeplinkStatus;
use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Models\User;
use App\Support\SalesLineSnapshot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeDeeplinkApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])
            ->components([
                Section::make('Thông tin hồ sơ FE Deeplink')
                    ->columns(2)
                    ->schema([
                        TextInput::make('applicant_name')
                            ->label('Họ tên')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('SĐT')
                            ->tel()
                            ->required()
                            ->maxLength(50),
                        TextInput::make('identity_number')
                            ->label('Số CCCD')
                            ->required()
                            ->maxLength(20),
                        DatePicker::make('payload.fields.date_of_birth')
                            ->label('Ngày tháng năm sinh')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        TextInput::make('payload.fields.email')
                            ->label('Địa chỉ Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('payload.fields.loan_amount')
                            ->label('Số tiền vay')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₫'),
                        TextInput::make('payload.fields.loan_term_months')
                            ->label('Thời hạn vay')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->suffix('tháng'),
                        Select::make('created_by_id')
                            ->label('Tên nhân viên / Tạo bởi')
                            ->options(fn (): array => auth()->user()?->hasRole('Admin')
                                ? User::query()->orderBy('name')->pluck('name', 'id')->all()
                                : User::query()->whereKey(auth()->id())->pluck('name', 'id')->all())
                            ->default(fn (): ?int => auth()->id())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        DateTimePicker::make('created_at')
                            ->label('Ngày tạo')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Toggle::make('payload.fields.customer_consent')
                            ->label('Khách hàng đã đồng ý cung cấp thông tin')
                            ->accepted()
                            ->required()
                            ->inline(false)
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
