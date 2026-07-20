<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Models\Application;
use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Filament\LeadFormFieldFactory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ApplicationForm
{
    private static function adminCanEdit(): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    private static function disabledForNonAdmin(): bool
    {
        return ! self::adminCanEdit();
    }

    private static function dehydratedForAdmin(): bool
    {
        return self::adminCanEdit();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            Section::make('Quản trị hệ thống')
                ->visible(fn (): bool => self::adminCanEdit())
                ->columns(3)
                ->schema([
                    Select::make('sales_project_id')
                        ->label('Dự án')
                        ->options(fn (): array => SalesProject::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->native(false),
                    Select::make('lead_id')
                        ->label('Lead nguồn')
                        ->options(fn (): array => Lead::query()->latest()->limit(500)->pluck('lead_code', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('created_by_id')
                        ->label('Người tạo')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('assigned_sale_id')
                        ->label('Người xử lý')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->native(false),
                    DateTimePicker::make('created_at')
                        ->label('Ngày tạo')
                        ->seconds(false)
                        ->required(),
                    DateTimePicker::make('updated_at')
                        ->label('Ngày cập nhật')
                        ->seconds(false)
                        ->required(),
                ]),
            Tabs::make('Application detail')
                ->columnSpanFull()
                ->persistTabInQueryString('application_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::RectangleStack)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin chính')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextInput::make('application_code')->label('Mã hồ sơ')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->required()->maxLength(255),
                                    TextInput::make('applicant_name')->label('Khách hàng')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->maxLength(255),
                                    TextInput::make('phone')->label('SĐT')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->maxLength(50),
                                    TextInput::make('identity_number')->label('CCCD/CMND')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->maxLength(50),
                                ]),
                            Section::make('Xử lý')
                                ->columnSpan(4)
                                ->schema([
                                    Select::make('status')
                                        ->label('Trạng thái xử lý')
                                        ->options([
                                            'processing' => 'Đang xử lý',
                                            'pending_approval' => 'Chờ duyệt',
                                            'approved' => 'Đã duyệt',
                                            'rejected' => 'Từ chối',
                                        ])
                                        ->disabled(fn (?Application $record): bool => ! self::adminCanEdit() && ($record?->projectReport()->exists() ?? false))
                                        ->helperText(fn (?Application $record): ?string => (! self::adminCanEdit() && ($record?->projectReport()->exists() ?? false)) ? 'Trạng thái đồng bộ theo Báo cáo và chỉ Admin quyết định tại module Báo cáo.' : null)
                                        ->required()
                                        ->native(false),
                                    Textarea::make('note')->label('Ghi chú xử lý')->rows(3),
                                ]),
                            Section::make('Dữ liệu Lead')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema(fn (Get $get): array => LeadFormFieldFactory::componentsForProject($get('sales_project_id'), 'lead', 'payload.fields', self::disabledForNonAdmin())),
                        ]),
                    Tab::make('Thông tin phê duyệt')
                        ->icon(Heroicon::ClipboardDocumentCheck)
                        ->columns(12)
                        ->schema([
                            Section::make('Quyết định sơ bộ')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('payload.review.product')->label('Sản phẩm')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->maxLength(255),
                                    TextInput::make('payload.review.pre_approved_amount')->label('Số tiền phê duyệt sơ bộ')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->maxLength(255),
                                    TextInput::make('payload.review.pre_approved_months')->label('Số tháng phê duyệt')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->maxLength(255),
                                    TextInput::make('payload.review.pre_approved_interest_rate')->label('Lãi suất phê duyệt')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->maxLength(255),
                                    Textarea::make('payload.review.review_note')->label('Ghi chú kiểm tra')->disabled(fn (): bool => self::disabledForNonAdmin())->dehydrated(fn (): bool => self::dehydratedForAdmin())->rows(3)->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Xử lý dự án')
                        ->icon(Heroicon::Briefcase)
                        ->columns(12)
                        ->schema([
                            Section::make('Dữ liệu dự án')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema(fn (Get $get): array => LeadFormFieldFactory::componentsForProject($get('sales_project_id'), 'module', 'payload.module_fields')),
                        ]),
                ]),
        ];
    }

    public static function normalizeDataForSave(Application $record, array $data): array
    {
        $existingPayload = is_array($record->payload) ? $record->payload : [];
        $incomingPayload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $isAdmin = self::adminCanEdit();

        $data['payload'] = array_replace_recursive($existingPayload, $incomingPayload);

        if ($isAdmin) {
            foreach (['sales_project_id', 'lead_id', 'created_by_id', 'assigned_sale_id', 'created_at', 'updated_at', 'application_code', 'applicant_name', 'phone', 'identity_number'] as $field) {
                if (! array_key_exists($field, $data)) {
                    $data[$field] = $record->{$field};
                }
            }

            if (filled($data['application_code'] ?? null)) {
                $codeExists = Application::query()
                    ->where('application_code', $data['application_code'])
                    ->whereKeyNot($record->getKey())
                    ->exists();

                if ($codeExists) {
                    throw ValidationException::withMessages([
                        'application_code' => 'Mã hồ sơ đã tồn tại.',
                    ]);
                }
            }
        } else {
            $data['sales_project_id'] = $record->sales_project_id;
            $data['lead_id'] = $record->lead_id;
            $data['created_by_id'] = $record->created_by_id;
            $data['assigned_sale_id'] = $record->assigned_sale_id;
            $data['created_at'] = $record->created_at;
            $data['updated_at'] = $record->updated_at;
            $data['payload']['fields'] = $existingPayload['fields'] ?? ($data['payload']['fields'] ?? []);
            $data['payload']['review'] = $existingPayload['review'] ?? ($data['payload']['review'] ?? []);
            $data['application_code'] = $record->application_code;
            $data['applicant_name'] = $record->applicant_name;
            $data['phone'] = $record->phone;
            $data['identity_number'] = $record->identity_number;
        }

        if (! $isAdmin && $record->projectReport()->exists()) {
            $data['status'] = $record->status;
        }

        return $data;
    }
}
