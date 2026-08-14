<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Models\User;
use App\Support\AdminWorkflowOverride;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Assignments\RecordAssignment;
use App\Support\Filament\LeadCreate\CreateLotteFinanceLeadAction;
use App\Support\LotteFinanceDocuments;
use App\Support\SalesLineSnapshot;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class LotteFinanceApplicationForm
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
                        ->searchable()->preload()->required(AdminWorkflowOverride::required()),
                    DateTimePicker::make('created_at')->label('Ngày tạo')->seconds(false)->required(AdminWorkflowOverride::required()),
                    DatePicker::make('payload.fields.disbursed_at')
                        ->label('Ngày giải ngân')
                        ->native(false),
                    TextInput::make('status')
                        ->label('Trạng thái')
                        ->formatStateUsing(fn (?string $state): string => LotteFinanceWorkflow::statusLabel($state))
                        ->disabled()
                        ->dehydrated(false),
                ]),
            self::reviewSection($visibleOnEdit),
            Section::make('Thông tin sản phẩm và khoản vay')
                ->visible($visibleOnEdit)
                ->columns(3)
                ->schema([
                    self::readOnly('payload.fields.scheme_code', 'Mã Scheme'),
                    self::readOnly('payload.fields.scheme_product_type', 'Loại sản phẩm'),
                    self::readOnly('payload.fields.scheme_product', 'Sản phẩm'),
                    self::readOnly('payload.fields.scheme_name', 'Tên Scheme')->columnSpan(2),
                    self::readOnly('payload.fields.scheme_loan_period', 'Thời hạn tối đa'),
                    self::readOnly('payload.fields.scheme_dti_label', 'DTI'),
                    self::readOnly('payload.fields.scheme_ltv_label', 'LTV'),
                    self::readOnly('payload.fields.scheme_loan_amount_range', 'Khoản vay áp dụng'),
                    self::readOnly('payload.fields.scheme_age_range', 'Độ tuổi áp dụng'),
                    self::readOnly('payload.fields.scheme_insurance_fee', 'Phí bảo hiểm Scheme'),
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
            ...array_map(
                fn ($component) => $component->visible($visibleOnEdit),
                LotteFinanceFields::components($locked),
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
        $user = auth()->user();
        $canEditData = LotteFinanceWorkflow::canEditData($user, $record);
        $canEditReview = AdminWorkflowOverride::active($user);
        $data['payload'] = array_replace_recursive($existingPayload, $incomingPayload);

        if ($canEditData && array_key_exists('documents', $incomingPayload)) {
            $data['payload']['documents'] = $incomingPayload['documents'];

            if (empty($incomingPayload['documents']['doc100'])) {
                data_set($data, 'payload.fields.ocr_front_image', null);
                data_set($data, 'payload.fields.ocr_back_image', null);
            }
        }

        if ($canEditData) {
            $data['payload'] = LotteFinanceFields::synchronizeLegacyFields($data['payload']);
        } else {
            $data['payload']['fields'] = $existingPayload['fields'] ?? [];
            $data['payload']['module_fields'] = $existingPayload['module_fields'] ?? [];
            $data['payload']['documents'] = $existingPayload['documents'] ?? [];
        }

        if ($canEditReview) {
            $data['payload'] = self::normalizeReviewData($data['payload']);
        } else {
            $data['payload']['review'] = $existingPayload['review'] ?? [];
        }

        if ((auth()->user()?->hasRole('Admin') ?? false) && data_has($incomingPayload, 'fields.disbursed_at')) {
            data_set($data, 'payload.fields.disbursed_at', data_get($incomingPayload, 'fields.disbursed_at'));
        }

        if (! auth()->user()?->hasRole('Admin')) {
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

        if (auth()->user()?->hasRole('Admin') && filled($data['created_by_id'] ?? null)) {
            $data = array_replace($data, SalesLineSnapshot::hierarchyForUserId($data['created_by_id']));
        }

        $leadFields = data_get($data, 'payload.fields', []);
        $data['applicant_name'] = $leadFields['customer_name'] ?? $record->applicant_name;
        $data['phone'] = $leadFields['phone'] ?? $record->phone;
        $data['identity_number'] = $leadFields['identity_number'] ?? $record->identity_number;
        $data['sales_project_id'] = $record->sales_project_id;
        $data['lead_id'] = null;

        return $data;
    }

    public static function prepareDataForFill(array $data): array
    {
        return LotteFinanceFields::prepareDataForFill($data);
    }

    private static function reviewSection(\Closure $visibleOnEdit): Section
    {
        $reviewOptions = [
            'Pass' => 'Pass',
            'Không Pass' => 'Không Pass',
        ];

        return Section::make('Thông tin phê duyệt / Pre-Check')
            ->visible(fn (?Application $record, string $operation): bool => $visibleOnEdit($operation)
                && $record instanceof Application
                && AdminWorkflowOverride::active())
            ->columns(3)
            ->schema([
                Select::make('payload.review.decision')
                    ->label('Kết quả Pre-Check')
                    ->options($reviewOptions)
                    ->searchable()
                    ->preload(),
                Select::make('payload.review.blacklist_check')
                    ->label('Blacklist')
                    ->options($reviewOptions)
                    ->searchable()
                    ->preload(),
                Select::make('payload.review.b11t_check')
                    ->label('B11T')
                    ->options($reviewOptions)
                    ->searchable()
                    ->preload(),
                Select::make('payload.review.aml_check')
                    ->label('AML')
                    ->options($reviewOptions)
                    ->searchable()
                    ->preload(),
                Select::make('payload.review.pcb_check')
                    ->label('PCB')
                    ->options($reviewOptions)
                    ->searchable()
                    ->preload(),
                TextInput::make('payload.review.lf_grade')
                    ->label('LF Grade')
                    ->maxLength(50),
                TextInput::make('payload.review.ml_grade')
                    ->label('ML Grade')
                    ->maxLength(50),
                TextInput::make('payload.review.maximum_limit')
                    ->label('Hạn mức tối đa')
                    ->suffix('VNĐ')
                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                    ->stripCharacters('.')
                    ->extraInputAttributes(['class' => 'crm-money-input', 'inputmode' => 'numeric']),
                TextInput::make('payload.review.estimated_interest_rate')
                    ->label('Lãi suất dự kiến')
                    ->numeric()
                    ->suffix('%'),
                TextInput::make('payload.review.approved_amount')
                    ->label('Số tiền được phê duyệt')
                    ->suffix('VNĐ')
                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                    ->stripCharacters('.')
                    ->extraInputAttributes(['class' => 'crm-money-input', 'inputmode' => 'numeric']),
                DateTimePicker::make('payload.review.reviewed_at')
                    ->label('Thời gian Pre-Check')
                    ->seconds(false),
                DateTimePicker::make('payload.review.approved_at')
                    ->label('Thời gian Approval')
                    ->seconds(false),
                Textarea::make('payload.review.review_note')
                    ->label('Ghi chú Pre-Check')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('payload.review.approval_note')
                    ->label('Ghi chú Approval')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    private static function normalizeReviewData(array $payload): array
    {
        $review = is_array($payload['review'] ?? null) ? $payload['review'] : [];

        foreach (['maximum_limit', 'approved_amount'] as $key) {
            if (array_key_exists($key, $review)) {
                $review[$key] = self::digits($review[$key]);
            }
        }

        $payload['review'] = $review;

        return $payload;
    }

    private static function digits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return filled($digits) ? $digits : null;
    }

    private static function readOnly(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->disabled()
            ->dehydrated(false);
    }
}
