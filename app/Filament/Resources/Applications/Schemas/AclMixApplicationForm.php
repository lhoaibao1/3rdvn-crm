<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Models\User;
use App\Support\AdminWorkflowOverride;
use App\Support\Filament\ApplicationDateInput;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Assignments\RecordAssignment;
use App\Support\CustomerName;
use App\Support\SalesLineSnapshot;
use App\Support\VietnamAddressCatalog;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class AclMixApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])
            ->columns(1)
            ->components(self::components());
    }

    public static function components(): array
    {
        $locked = fn (?Application $record): bool => $record instanceof Application
            && ! AclMixWorkflow::canEditData(auth()->user(), $record);

        return [
            Section::make('Thông tin kiểm tra ban đầu')
                ->visible(fn (?Application $record): bool => ! $record instanceof Application)
                ->columns(3)
                ->schema([
                    TextInput::make('applicant_name')->label('Họ tên khách hàng')->required(AdminWorkflowOverride::required())->maxLength(255)->extraInputAttributes(['class' => 'crm-uppercase-input'])->dehydrateStateUsing(fn (?string $state): ?string => CustomerName::normalize($state)),
                    TextInput::make('phone')->label('Số điện thoại')->tel()->required(AdminWorkflowOverride::required())->maxLength(30),
                    TextInput::make('identity_number')->label('CCCD/CMND')->required(AdminWorkflowOverride::required())->maxLength(30),
                    TextInput::make('birthday')->label('Ngày sinh')->mask('99/99/9999')->placeholder('dd/mm/yyyy')->required(AdminWorkflowOverride::required())->rule('date_format:d/m/Y')->maxLength(10),
                    Select::make('identity_issued_place')->label('Nơi cấp')->options(['CCS' => 'CCS', 'Bộ Công An' => 'Bộ Công An'])->searchable()->preload()->required(AdminWorkflowOverride::required()),
                    TextInput::make('identity_issued_date')->label('Ngày cấp')->mask('99/99/9999')->placeholder('dd/mm/yyyy')->required(AdminWorkflowOverride::required())->rule('date_format:d/m/Y')->maxLength(10),
                    Textarea::make('address')->label('Địa chỉ chi tiết')->rows(2),
                    Select::make('province_code')
                        ->label('Tỉnh/Thành phố')->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                        ->searchable()->preload()->live()->required(AdminWorkflowOverride::required())
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $set('province_name', VietnamAddressCatalog::provinceName($state));
                            $set('district_code', null);
                            $set('district_name', null);
                            $set('ward_code', null);
                            $set('ward_name', null);
                        }),
                    Select::make('district_code')
                        ->label('Quận/Huyện')->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions($get('province_code')))
                        ->disabled(fn (Get $get): bool => blank($get('province_code')))
                        ->searchable()->preload()->live()->required(AdminWorkflowOverride::required())
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            $set('district_name', VietnamAddressCatalog::districtName($get('province_code'), $state));
                            $set('ward_code', null);
                            $set('ward_name', null);
                        }),
                    Select::make('ward_code')
                        ->label('Phường/Xã')->options(fn (Get $get): array => VietnamAddressCatalog::wardOptions($get('district_code')))
                        ->disabled(fn (Get $get): bool => blank($get('district_code')))
                        ->searchable()->preload()->live()->required(AdminWorkflowOverride::required())
                        ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set('ward_name', VietnamAddressCatalog::wardName($get('district_code'), $state))),
                    FileUpload::make('consent_6088')
                        ->label('Chứng từ Consent gửi đến 6088')
                        ->disk('public')
                        ->directory('applications/acl-mix/consent-6088')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                        ->maxSize(10240)
                        ->maxFiles(1)
                        ->panelLayout('compact')
                        ->previewable()
                        ->openable()
                        ->downloadable()
                        ->deletable()
                        ->required(AdminWorkflowOverride::required())
                        ->columnSpan(1),
                    Hidden::make('province_name')->dehydrated(),
                    Hidden::make('district_name')->dehydrated(),
                    Hidden::make('ward_name')->dehydrated(),
                ]),
            Section::make('Quản trị hồ sơ')
                ->visible(fn (?Application $record): bool => $record instanceof Application && (bool) auth()->user()?->hasAnyRole(['Admin', 'Sales Admin']))
                ->columns(3)
                ->schema([
                    TextInput::make('application_code')->label('Mã hồ sơ')->required(AdminWorkflowOverride::required())->maxLength(120),
                    Select::make('assigned_sale_id')
                        ->label('Người xử lý')
                        ->options(fn (?Application $record): array => $record ? RecordAssignment::assigneeOptions($record) : [])
                        ->searchable()->preload()->placeholder('Chưa phân công'),
                    Select::make('created_by_id')
                        ->label('Người tạo')->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->required(AdminWorkflowOverride::required()),
                    DateTimePicker::make('created_at')->label('Ngày tạo')->seconds(false)->required(AdminWorkflowOverride::required()),
                    ApplicationDateInput::make('payload.fields.disbursed_at'),
                    TextInput::make('status')->label('Trạng thái')->formatStateUsing(fn (?string $state): string => AclMixWorkflow::statusLabel($state))->disabled()->dehydrated(false),
                ]),
            self::approvalSection(),
            ...array_map(
                fn ($component) => $component->visible(fn (?Application $record): bool => $record instanceof Application),
                AclMixFields::components($locked),
            ),
        ];
    }

    private static function approvalSection(): Section
    {
        $adminEditable = fn (): bool => auth()->user()?->hasAnyRole(['Admin', 'Sales Admin']) ?? false;

        return Section::make('Thông tin phê duyệt sơ bộ')
            ->visible(fn (?Application $record): bool => $record instanceof Application)
            ->disabled(fn (): bool => ! $adminEditable())
            ->columns(4)
            ->schema([
                Select::make('payload.review.product')
                    ->label('Sản phẩm')
                    ->options(['ACL01' => 'ACL01', 'ACL02' => 'ACL02', 'ACL03' => 'ACL03', 'ACL04' => 'ACL04'])
                    ->searchable()
                    ->preload()
                    ->native(false),
                TextInput::make('payload.review.pre_approved_amount')
                    ->label('Số tiền phê duyệt')
                    ->suffix('VNĐ')
                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                    ->stripCharacters('.')
                    ->extraInputAttributes(['class' => 'crm-money-input', 'inputmode' => 'numeric']),
                TextInput::make('payload.review.pre_approved_months')
                    ->label('Số tháng phê duyệt')
                    ->numeric()
                    ->suffix('tháng'),
                TextInput::make('payload.review.pre_approved_interest_rate')
                    ->label('Lãi suất phê duyệt')
                    ->numeric()
                    ->suffix('%'),
                Textarea::make('payload.review.review_note')
                    ->label('Ghi chú phê duyệt')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function normalizeDataForSave(Application $record, array $data): array
    {
        $existingPayload = is_array($record->payload) ? $record->payload : [];
        $incomingPayload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        $data['payload'] = AclMixWorkflow::canEditData(auth()->user(), $record)
            ? AclMixFields::normalize(array_replace_recursive($existingPayload, $incomingPayload))
            : $existingPayload;

        if ((auth()->user()?->hasRole('Admin') ?? false) && data_has($incomingPayload, 'fields.disbursed_at')) {
            data_set($data, 'payload.fields.disbursed_at', data_get($incomingPayload, 'fields.disbursed_at'));
        }

        if (! (auth()->user()?->hasAnyRole(['Admin', 'Sales Admin']) ?? false)) {
            foreach (['application_code', 'assigned_sale_id', 'created_by_id', 'created_at', 'status'] as $field) {
                $data[$field] = $record->{$field};
            }
        } else {
            foreach (['application_code', 'created_by_id', 'created_at', 'status'] as $field) {
                if (blank($data[$field] ?? null) && filled($record->{$field})) {
                    $data[$field] = $record->{$field};
                }
            }

        }

        if ((auth()->user()?->hasAnyRole(['Admin', 'Sales Admin']) ?? false) && filled($data['created_by_id'] ?? null)) {
            $data = array_replace($data, SalesLineSnapshot::hierarchyForUserId($data['created_by_id']));
        }

        $fields = data_get($data, 'payload.module_fields', []);
        $data['applicant_name'] = $fields['customer_name'] ?? $record->applicant_name;
        $data['phone'] = $fields['phone'] ?? $record->phone;
        $data['identity_number'] = $fields['cccd'] ?? $fields['cmnd'] ?? $record->identity_number;
        $data['sales_project_id'] = $record->sales_project_id;
        $data['lead_id'] = null;

        return $data;
    }
}
