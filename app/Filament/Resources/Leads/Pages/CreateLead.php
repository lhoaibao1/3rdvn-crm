<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\SalesProject;
use App\Support\Applications\LeadPayload;
use App\Support\Assignments\RecordAssignment;
use App\Support\Filament\LeadCreate\CreateLotteFinanceLeadAction;
use App\Support\Permissions\LeadAccess;
use App\Support\SalesLineSnapshot;
use Filament\Actions\Action;
use App\Support\VietnamAddressCatalog;
use Filament\Forms\Components\Hidden;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class CreateLead extends CreateRecord
{

    protected static string $resource = LeadResource::class;

    protected static bool $canCreateAnother = false;

    public ?int $selectedProjectId = null;

    public function mount(): void
    {
        $this->selectedProjectId = request()->integer('project') ?: null;

        parent::mount();

        if (! LeadAccess::canUseProjectId(auth()->user(), $this->selectedProjectId)) {
            Notification::make()
                ->title('Vui lòng chọn dự án từ nút Tạo Lead.')
                ->warning()
                ->send();

            $this->redirect(LeadResource::getUrl('index'), navigate: true);

            return;
        }

        $this->form->fill([
            'sales_project_id' => $this->selectedProjectId,
        ]);
    }

    public function getTitle(): string
    {
        $projectName = SalesProject::query()->whereKey($this->selectedProjectId)->value('name');

        return $projectName ? 'Tạo Lead '.$projectName : 'Tạo Lead';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Hidden::make('sales_project_id')
                    ->default(fn (): ?int => $this->selectedProjectId)
                    ->dehydrated(),
                Hidden::make('lead_name'),
                Hidden::make('email'),
                Section::make('Thông tin Lead')
                    ->columns(fn (Get $get): int => LeadAccess::selectedProjectSlug($get('sales_project_id') ?: $this->selectedProjectId) === 'lotte-finance' ? 1 : 2)
                    ->schema(fn (Get $get): array => self::leadFieldsForProject($get('sales_project_id') ?: $this->selectedProjectId)),
            ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Gửi Lead Kiểm Tra')
            ->icon(Heroicon::OutlinedPaperAirplane);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sales_project_id'] = LeadAccess::normalizeProjectId(auth()->user(), $data['sales_project_id'] ?? null);

        if (! LeadAccess::canUseProjectId(auth()->user(), $data['sales_project_id'])) {
            throw ValidationException::withMessages([
                'sales_project_id' => 'Bạn chưa được phân quyền tạo Lead cho dự án này.',
            ]);
        }

        $data = $this->normalizeStaticLeadPayload($data);
        $data = $this->syncPayloadToLeadColumns($data);

        $data = array_replace($data, SalesLineSnapshot::fromUser(auth()->user()));
        $project = SalesProject::query()->find($data['sales_project_id']);
        $assignee = $project ? RecordAssignment::autoAssigneeForProject($project, auth()->user()) : null;

        if ($assignee) {
            $data = array_replace($data, RecordAssignment::leadLikeAssignmentAttributes($assignee));
        }

        $data['created_by_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if (LeadAccess::selectedProjectSlug($this->record->sales_project_id) === 'acl-mix') {
            $identityNumber = preg_replace('/\\D+/', '', (string) data_get($this->record->payload, 'fields.identity_number'));
            $suffix = strlen($identityNumber) >= 6 ? substr($identityNumber, -6) : $identityNumber;
            $suffix = $suffix !== '' ? $suffix : 'xxxxxx';

            $this->dispatch(
                'crm-nd13-consent',
                title: 'Thông báo đồng ý NĐ13',
                leadCode: $this->record->lead_code,
                suffix: $suffix,
                message: 'SF '.$suffix.' Toi da doc hieu ro va tu nguyen dong y Chinh sach Du lieu ca nhan hien hanh cua SHBFinance va dong y nhan cuoc goi, sms, email quang cao den 20h.',
            );
        }
    }



    private static function leadFieldsForProject(int|string|null $projectId): array
    {
        return match (LeadAccess::selectedProjectSlug($projectId)) {
            'cbp' => self::cbpLeadFields(),
            default => self::aclMixLeadFields(),
            'lotte-finance' => CreateLotteFinanceLeadAction::schema(),
        };
    }

    private static function cbpLeadFields(): array
    {
        return [
            TextInput::make('customer_name')
                ->label('Họ tên')
                ->required()
                ->maxLength(255),
            TextInput::make('identity_number')
                ->label('CCCD')
                ->required()
                ->maxLength(30),
            TextInput::make('phone')
                ->label('Số điện thoại')
                ->tel()
                ->required()
                ->maxLength(30),
        ];
    }

    private static function aclMixLeadFields(): array
    {
        return [
            TextInput::make('customer_name')
                ->label('Họ tên khách hàng')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Số điện thoại')
                ->tel()
                ->required()
                ->maxLength(30),
            TextInput::make('identity_number')
                ->label('CCCD/CMND')
                ->required()
                ->maxLength(30),
            TextInput::make('birthday')
                ->label('Ngày sinh')
                ->mask('99/99/9999')
                ->placeholder('dd/mm/yyyy')
                ->required()
                ->rule('date_format:d/m/Y')
                ->maxLength(10),
            Select::make('noi_cap')
                ->label('Nơi cấp')
                ->options([
                    'CCS' => 'CCS',
                    'Bộ Công An' => 'Bộ Công An',
                ])
                ->searchable()
                ->preload()
                ->required()
                ->native(false),
            TextInput::make('date_cap')
                ->label('Ngày cấp')
                ->mask('99/99/9999')
                ->placeholder('dd/mm/yyyy')
                ->required()
                ->rule('date_format:d/m/Y')
                ->maxLength(10),
            Textarea::make('address')
                ->label('Địa chỉ chi tiết')
                ->rows(2)
                ->columnSpan(1),
            Select::make('province_code')
                ->label('Tỉnh/Thành phố')
                ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    $set('province_name', VietnamAddressCatalog::provinceName($state));
                    $set('district_code', null);
                    $set('district_name', null);
                    $set('ward_code', null);
                    $set('ward_name', null);
                })
                ->native(false),
            Select::make('district_code')
                ->label('Quận/Huyện')
                ->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions($get('province_code')))
                ->disabled(fn (Get $get): bool => blank($get('province_code')))
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                    $set('district_name', VietnamAddressCatalog::districtName($get('province_code'), $state));
                    $set('ward_code', null);
                    $set('ward_name', null);
                })
                ->native(false),
            Select::make('ward_code')
                ->label('Phường/Xã')
                ->options(fn (Get $get): array => VietnamAddressCatalog::wardOptions($get('district_code')))
                ->disabled(fn (Get $get): bool => blank($get('district_code')))
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set('ward_name', VietnamAddressCatalog::wardName($get('district_code'), $state)))
                ->native(false),
            Hidden::make('province_name')->dehydrated(),
            Hidden::make('district_name')->dehydrated(),
            Hidden::make('ward_name')->dehydrated(),
        ];
    }

    private function normalizeStaticLeadPayload(array $data): array
    {
        $fieldKeys = LeadAccess::selectedProjectSlug($data['sales_project_id'] ?? null) === 'lotte-finance'
            ? CreateLotteFinanceLeadAction::fieldKeys()
            : [
                'customer_name', 'phone', 'identity_number', 'birthday', 'noi_cap', 'date_cap', 'address',
                'province_code', 'province_name', 'district_code', 'district_name', 'ward_code', 'ward_name',
            ];

        $fields = [];

        foreach ($fieldKeys as $key) {
            if (array_key_exists($key, $data)) {
                $fields[$key] = $data[$key];
                unset($data[$key]);
            }
        }

        if ($fields !== []) {
            $data['payload'] = array_replace_recursive(
                is_array($data['payload'] ?? null) ? $data['payload'] : [],
                ['fields' => $fields],
            );
        }

        return $data;
    }

    private function syncPayloadToLeadColumns(array $data): array
    {
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $project = filled($data['sales_project_id'] ?? null) ? SalesProject::query()->find((int) $data['sales_project_id']) : null;

        $data['lead_name'] = LeadPayload::primaryName($payload, $data['lead_name'] ?? null)
            ?: LeadPayload::firstFilledValue($payload)
            ?: 'Lead '.now()->format('d/m/Y H:i');
        $data['phone'] = LeadPayload::phone($payload, $data['phone'] ?? null);
        $data['email'] = LeadPayload::email($payload, $data['email'] ?? null);
        $data['source'] = $project?->name;
        $data['status'] = 'Chờ kiểm tra';

        return $data;
    }
}
