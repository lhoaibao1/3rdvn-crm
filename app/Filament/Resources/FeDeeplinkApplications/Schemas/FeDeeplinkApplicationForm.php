<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Schemas;

use App\Enums\FeDeeplinkStatus;
use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Models\User;
use App\Support\SalesLineSnapshot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
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
                        TextInput::make('application_code')
                            ->label('App ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('applicant_name')
                            ->label('Họ tên')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('SĐT')
                            ->tel()
                            ->required()
                            ->maxLength(50),
                        Select::make('created_by_id')
                            ->label('Tên nhân viên / Tạo bởi')
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
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
                        DatePicker::make('payload.fields.disbursed_at')
                            ->label('Ngày giải ngân')
                            ->required(),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options(FeDeeplinkStatus::options())
                            ->default(FeDeeplinkStatus::END->value)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('payload.fields.product')
                            ->label('Sản phẩm')
                            ->options([
                                'NTB' => 'NTB',
                                'Xsell' => 'Xsell',
                                'Topup' => 'Topup',
                            ])
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('payload.fields.approved_amount')
                            ->label('Số tiền duyệt')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₫')
                            ->required(),
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
