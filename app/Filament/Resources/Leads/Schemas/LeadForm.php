<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\LeadPayload;
use App\Support\Filament\LeadFormFieldFactory;
use App\Support\HotLeads\HotLeadStatus;
use App\Support\Permissions\HotLeadAccess;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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

class LeadForm
{
    private static function adminCanEdit(): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            Hidden::make('lead_name'),
            Hidden::make('phone'),
            Hidden::make('email'),
            Section::make('Quản trị hệ thống')
                ->visible(fn (): bool => self::adminCanEdit())
                ->columns(3)
                ->schema([
                    TextInput::make('lead_code')->label('Lead ID')->required()->maxLength(255),
                    Select::make('sales_project_id')
                        ->label('Dự án')
                        ->options(fn (): array => SalesProject::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->required()->searchable()->preload()->live()->native(false),
                    Select::make('created_by_id')
                        ->label('Người tạo')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()->searchable()->preload()->native(false),
                    Select::make('assigned_sale_id')
                        ->label('Người xử lý')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false),
                    TextInput::make('source')->label('Nguồn')->maxLength(255),
                    DateTimePicker::make('created_at')->label('Ngày tạo')->seconds(false)->required(),
                    DateTimePicker::make('updated_at')->label('Ngày cập nhật')->seconds(false)->required(),
                ]),
            Tabs::make('Lead detail')
                ->columnSpanFull()
                ->persistTabInQueryString('lead_tab')
                ->tabs([
                    Tab::make('Thông tin Lead')
                        ->icon(Heroicon::DocumentText)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin khách hàng')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema(fn (Get $get): array => LeadFormFieldFactory::componentsForProject(
                                    $get('sales_project_id'),
                                    'lead',
                                    'payload.fields',
                                    ! self::adminCanEdit(),
                                )),
                            Section::make('Hệ thống')
                                ->columnSpan(4)
                                ->schema([
                                    Placeholder::make('lead_code_display')
                                        ->label('Lead ID')
                                        ->content(fn (?Lead $record): string => $record?->lead_code ?? '-'),
                                    Placeholder::make('sales_project_display')
                                        ->label('Dự án')
                                        ->content(fn (?Lead $record): string => $record?->salesProject?->name ?? '-'),
                                    Placeholder::make('lead_status_display')
                                        ->label('Trạng thái hồ sơ')
                                        ->content(fn (?Lead $record): string => self::publicStatus($record?->status)),
                                    Placeholder::make('assigned_sale_display')
                                        ->label('Sale phụ trách')
                                        ->content(fn (?Lead $record): string => $record?->assignedSale?->name ?? '-'),
                                    Placeholder::make('created_at_display')
                                        ->label('Ngày tạo')
                                        ->content(fn (?Lead $record): string => $record?->created_at?->format('H:i d/m/Y') ?? '-'),
                                ]),
                        ]),

                    Tab::make('Kết quả kiểm tra')
                        ->icon(Heroicon::ClipboardDocumentCheck)
                        ->columns(12)
                        ->schema([
                            Section::make('Kết quả xử lý')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Select::make('status')
                                        ->label('Trạng thái hồ sơ')
                                        ->options(fn (?Lead $record): array => self::statusOptions($record))
                                        ->disabled(fn (): bool => ! self::adminCanEdit())
                                        ->dehydrated(fn (): bool => self::adminCanEdit())
                                        ->native(false),
                                    TextInput::make('payload.review.product')
                                        ->label('Sản phẩm')
                                        ->disabled(fn (): bool => ! self::adminCanEdit())
                                        ->dehydrated(fn (): bool => self::adminCanEdit())
                                        ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
                                    TextInput::make('payload.review.pre_approved_amount')
                                        ->label('Số tiền phê duyệt sơ bộ')
                                        ->disabled(fn (): bool => ! self::adminCanEdit())
                                        ->dehydrated(fn (): bool => self::adminCanEdit())
                                        ->suffix('VNĐ')
                                        ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
                                    TextInput::make('payload.review.pre_approved_months')
                                        ->label('Số tháng phê duyệt')
                                        ->disabled(fn (): bool => ! self::adminCanEdit())
                                        ->dehydrated(fn (): bool => self::adminCanEdit())
                                        ->suffix('tháng')
                                        ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
                                    TextInput::make('payload.review.pre_approved_interest_rate')
                                        ->label('Lãi suất phê duyệt')
                                        ->disabled(fn (): bool => ! self::adminCanEdit())
                                        ->dehydrated(fn (): bool => self::adminCanEdit())
                                        ->suffix('%')
                                        ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
                                    Textarea::make('payload.review.review_note')
                                        ->label('Ghi chú kiểm tra')
                                        ->rows(2)
                                        ->columnSpanFull()
                                        ->disabled(fn (): bool => ! self::adminCanEdit())
                                        ->dehydrated(fn (): bool => self::adminCanEdit())
                                        ->visible(fn (Get $get): bool => in_array($get('status'), ['Từ chối', 'Khách hàng bị trùng', 'Khách hàng thoả mãn điều kiện'], true)),
                                ]),
                        ]),
                ]),
        ];
    }

    public static function normalizeDataForSave(Lead $record, array $data): array
    {
        $existingPayload = is_array($record->payload) ? $record->payload : [];
        $incomingPayload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        $data['payload'] = array_replace_recursive($existingPayload, $incomingPayload);
        $isAdmin = self::adminCanEdit();

        if ($isAdmin) {
            foreach (['lead_code', 'sales_project_id', 'created_by_id', 'assigned_sale_id', 'source', 'status', 'created_at', 'updated_at'] as $field) {
                if (! array_key_exists($field, $data)) {
                    $data[$field] = $record->{$field};
                }
            }

            if (filled($data['lead_code'] ?? null) && Lead::query()->where('lead_code', $data['lead_code'])->whereKeyNot($record->getKey())->exists()) {
                throw ValidationException::withMessages(['lead_code' => 'Lead ID đã tồn tại.']);
            }
        } else {
            foreach (['lead_code', 'sales_project_id', 'created_by_id', 'assigned_sale_id', 'source', 'status', 'created_at', 'updated_at'] as $field) {
                $data[$field] = $record->{$field};
            }
            $data['payload'] = $existingPayload;
        }

        if (! $isAdmin && ($data['status'] ?? null) === 'Đã chuyển Application') {
            throw ValidationException::withMessages([
                'status' => 'Trạng thái chuyển Application là nội bộ, không được sửa trực tiếp.',
            ]);
        }

        return self::syncPayloadToLeadColumns($data);
    }

    private static function statusOptions(?Lead $record): array
    {
        $status = self::publicStatus($record?->status);
        $options = $record instanceof Lead && HotLeadAccess::isPendingHotLead($record)
            ? HotLeadStatus::options()
            : [
                'Chờ kiểm tra' => 'Chờ kiểm tra',
                'Từ chối' => 'Từ chối',
                'Khách hàng bị trùng' => 'Khách hàng bị trùng',
                'Khách hàng thoả mãn điều kiện' => 'Khách hàng thoả mãn điều kiện',
            ];

        if ($status !== '-' && ! array_key_exists($status, $options)) {
            $options[$status] = $status;
        }

        return $options;
    }

    private static function publicStatus(?string $status): string
    {
        return match ($status) {
            'Đã chuyển Application' => 'Khách hàng thoả mãn điều kiện',
            null, '' => '-',
            default => $status,
        };
    }

    private static function syncPayloadToLeadColumns(array $data): array
    {
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $project = filled($data['sales_project_id'] ?? null) ? SalesProject::query()->find((int) $data['sales_project_id']) : null;

        $data['lead_name'] = LeadPayload::primaryName($payload, $data['lead_name'] ?? null)
            ?: LeadPayload::firstFilledValue($payload)
            ?: 'Lead '.now()->format('d/m/Y H:i');
        $data['phone'] = LeadPayload::phone($payload, $data['phone'] ?? null);
        $data['email'] = LeadPayload::email($payload, $data['email'] ?? null);
        $data['source'] = filled($data['source'] ?? null) ? $data['source'] : ($project?->name ?: null);

        return $data;
    }
}
