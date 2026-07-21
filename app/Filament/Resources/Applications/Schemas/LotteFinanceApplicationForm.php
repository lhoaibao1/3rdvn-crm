<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Models\User;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Assignments\RecordAssignment;
use App\Support\Filament\LeadCreate\CreateLotteFinanceLeadAction;
use App\Support\LotteFinanceDocuments;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LotteFinanceApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::components());
    }

    public static function components(): array
    {
        $createComponents = array_map(
            fn ($component) => $component->visible(fn (string $operation): bool => $operation === 'create'),
            CreateLotteFinanceLeadAction::schema(),
        );
        $visibleOnEdit = fn (string $operation): bool => $operation === 'edit';
        $locked = fn (?Application $record): bool => $record instanceof Application
            && ! LotteFinanceWorkflow::canEditData(auth()->user(), $record);

        return [
            ...$createComponents,
            Section::make('Quản trị hồ sơ')
                ->visible(fn (?Application $record, string $operation): bool => $operation === 'edit'
                    && $record instanceof Application
                    && (bool) auth()->user()?->hasRole('Admin'))
                ->columns(3)
                ->schema([
                    TextInput::make('application_code')->label('Mã hồ sơ')->maxLength(120),
                    Select::make('assigned_sale_id')
                        ->label('Người xử lý')
                        ->options(fn (?Application $record): array => $record ? RecordAssignment::assigneeOptions($record) : [])
                        ->searchable()->preload()->placeholder('Chưa phân công'),
                    Select::make('created_by_id')
                        ->label('Người tạo')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->required(),
                    DateTimePicker::make('created_at')->label('Ngày tạo')->seconds(false)->required(),
                    TextInput::make('status')
                        ->label('Trạng thái')
                        ->formatStateUsing(fn (?string $state): string => LotteFinanceWorkflow::statusLabel($state))
                        ->disabled()
                        ->dehydrated(false),
                ]),
            Section::make('Thông tin sản phẩm và khoản vay')
                ->visible($visibleOnEdit)
                ->columns(3)
                ->schema([
                    self::readOnly('payload.fields.scheme_code', 'Mã Scheme'),
                    self::readOnly('payload.fields.scheme_product_type', 'Loại sản phẩm'),
                    self::readOnly('payload.fields.scheme_product', 'Sản phẩm'),
                    self::readOnly('payload.fields.scheme_name', 'Tên Scheme')->columnSpan(2),
                    self::readOnly('payload.fields.scheme_loan_period', 'Thời hạn tối đa'),
                    self::readOnly('payload.fields.loan_purpose_name', 'Mục đích vay'),
                    self::readOnly('payload.fields.loan_amount', 'Số tiền vay')->suffix('VNĐ'),
                    self::readOnly('payload.fields.loan_term_months', 'Thời gian vay')->suffix('tháng'),
                    self::readOnly('payload.fields.insurance_label', 'Bảo hiểm khoản vay')->columnSpan(2),
                    self::readOnly('payload.fields.scheme_interest_rate', 'Lãi suất')->suffix('%'),
                    self::readOnly('payload.fields.estimated_insurance_amount', 'Phí bảo hiểm dự kiến')->suffix('VNĐ'),
                    self::readOnly('payload.fields.estimated_monthly_payment', 'Số tiền đóng hằng tháng')->suffix('VNĐ'),
                    self::readOnly('payload.fields.estimated_total_payment', 'Tổng thanh toán dự kiến')->suffix('VNĐ'),
                    Textarea::make('payload.fields.scheme_description')
                        ->label('Mô tả Scheme')
                        ->rows(2)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
            Section::make('Thông tin OCR/eKYC')
                ->visible($visibleOnEdit)
                ->columns(3)
                ->schema([
                    self::readOnly('payload.fields.ekyc_status', 'Trạng thái eKYC'),
                    self::readOnly('payload.fields.ekyc_request_id', 'eKYC Request ID'),
                    self::readOnly('payload.fields.ekyc_completed_at', 'Hoàn tất lúc'),
                    Textarea::make('payload.fields.ekyc_result_note')
                        ->label('Kết quả eKYC')
                        ->rows(2)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
            ...array_map(
                fn ($component) => $component->visible($visibleOnEdit),
                AclMixFields::components($locked),
            ),
            ...array_map(
                fn ($component) => $component
                    ->visible(fn (?Application $record, string $operation): bool => $operation === 'edit'
                        && $record instanceof Application
                        && ! in_array($record->status, [LotteFinanceWorkflow::PRE_CHECK, LotteFinanceWorkflow::REJECTED], true)),
                LotteFinanceDocuments::components($locked),
            ),
        ];
    }

    public static function normalizeDataForSave(Application $record, array $data): array
    {
        $existingPayload = is_array($record->payload) ? $record->payload : [];
        $incomingPayload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $canEditData = LotteFinanceWorkflow::canEditData(auth()->user(), $record);
        $data['payload'] = array_replace_recursive($existingPayload, $incomingPayload);

        if ($canEditData) {
            $data['payload'] = AclMixFields::normalize($data['payload']);
        } else {
            $data['payload']['module_fields'] = $existingPayload['module_fields'] ?? [];
            $data['payload']['documents'] = $existingPayload['documents'] ?? [];
        }

        if (! auth()->user()?->hasRole('Admin')) {
            foreach (['application_code', 'assigned_sale_id', 'created_by_id', 'created_at', 'status'] as $field) {
                $data[$field] = $record->{$field};
            }
        }

        $moduleFields = data_get($data, 'payload.module_fields', []);
        $leadFields = data_get($data, 'payload.fields', []);
        $data['applicant_name'] = $moduleFields['customer_name'] ?? $leadFields['customer_name'] ?? $record->applicant_name;
        $data['phone'] = $moduleFields['phone'] ?? $leadFields['phone'] ?? $record->phone;
        $data['identity_number'] = $moduleFields['cccd'] ?? $moduleFields['cmnd'] ?? $leadFields['identity_number'] ?? $record->identity_number;
        $data['sales_project_id'] = $record->sales_project_id;
        $data['lead_id'] = null;

        return $data;
    }

    private static function readOnly(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->disabled()
            ->dehydrated(false);
    }
}
