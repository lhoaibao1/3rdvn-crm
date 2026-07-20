<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\SalesChannel;
use App\Models\User;
use App\Models\UserChangeLog;
use App\Models\SalesProject;
use App\Support\VietnamAddressCatalog;
use App\Support\VietnamBankCatalog;
use App\Support\UserSpecOptions;
use App\Support\RoleHierarchy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('User profile')
                            ->columnSpanFull()
                            ->persistTabInQueryString('user_tab')
                            ->tabs([
                                Tab::make('Hồ sơ')
                                    ->icon(Heroicon::UserCircle)
                                    ->columns(12)
                                    ->schema([
                                        Section::make('Thông tin chính')
                                            ->columnSpan(8)
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
                                                TextInput::make('username')
                                                    ->label('Username')
                                                    ->required()
                                                    ->maxLength(63)
                                                    ->unique(ignoreRecord: true)
                                                    ->rules(['regex:/^[a-z0-9](?:[a-z0-9._-]{0,61}[a-z0-9])?$/'])
                                                    ->validationMessages([
                                                        'regex' => 'Username chỉ gồm chữ thường, số, dấu chấm, gạch ngang hoặc gạch dưới.',
                                                    ])
                                                    ->dehydrateStateUsing(fn (?string $state): string => User::normalizeUsername($state))
                                                    ->disabled(fn (string $operation): bool => $operation === 'edit' && ! auth()->user()?->hasRole('Admin')),
                                                TextInput::make('uid')
                                                    ->label('UID')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                                TextInput::make('employee_code')
                                                    ->label('Employee Code')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                                TextInput::make('email')->label('Email đăng nhập')->email()->required()->unique(ignoreRecord: true),
                                                TextInput::make('phone')
                                                    ->label('Số điện thoại')
                                                    ->tel()
                                                    ->maxLength(10)
                                                    ->unique(ignoreRecord: true)
                                                    ->rules(['nullable', 'regex:/^0\d{9}$/'])
                                                    ->validationMessages([
                                                        'regex' => 'Số điện thoại phải có 10 số và bắt đầu bằng 0.',
                                                    ]),
                                                Select::make('document_type')
                                                    ->label('Loại giấy tờ')
                                                    ->options(fn (): array => UserSpecOptions::documentTypes())
                                                    ->native(false),
                                                TextInput::make('identity_number')
                                                    ->label('CCCD/CMND/Hộ chiếu')
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->rules(['nullable', 'regex:/^0?\d{9,12}$|^[A-Z0-9]{6,20}$/i'])
                                                    ->validationMessages([
                                                        'regex' => 'Số giấy tờ chưa đúng định dạng.',
                                                    ]),
                                                DatePicker::make('date_of_birth')->label('Ngày sinh')->displayFormat('d/m/Y')->native(false),
                                                Select::make('gender')
                                                    ->label('Giới tính')
                                                    ->options(['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'])
                                                    ->native(false),
                                                DatePicker::make('identity_issued_date')->label('Ngày cấp')->displayFormat('d/m/Y')->native(false),
                                                Select::make('identity_issued_place')
                                                    ->label('Nơi cấp')
                                                    ->options(fn (): array => UserSpecOptions::issuedPlaces())
                                                    ->native(false),
                                            ]),
                                        Section::make('Công việc')
                                            ->columnSpan(4)
                                            ->schema([
                                                Select::make('department')
                                                    ->label('Phòng ban')
                                                    ->options(fn (): array => UserSpecOptions::departments())
                                                    ->native(false),
                                                Select::make('employment_status')
                                                    ->label('Trạng thái')
                                                    ->options(fn (): array => UserSpecOptions::employmentStatuses())
                                                    ->default('active')
                                                    ->disabled(fn (string $operation): bool => self::isLockedForManagerEdit($operation))
                                                    ->native(false),
                                                DatePicker::make('hire_date')
                                                    ->label('Ngày vào làm')
                                                    ->displayFormat('d/m/Y')
                                                    ->disabled(fn (string $operation): bool => self::isLockedForManagerEdit($operation))
                                                    ->native(false),
                                                Select::make('office')
                                                    ->label('Office')
                                                    ->options(fn (): array => UserSpecOptions::offices())
                                                    ->disabled(fn (string $operation): bool => self::isLockedForManagerEdit($operation))
                                                    ->native(false),
                                                Select::make('contract_type')
                                                    ->label('Loại hợp đồng')
                                                    ->options(fn (): array => UserSpecOptions::contractTypes())
                                                    ->disabled(fn (string $operation): bool => self::isLockedForManagerEdit($operation))
                                                    ->native(false),
                                            ]),
                                    ]),

                                Tab::make('Dự án & kênh')
                                    ->icon(Heroicon::BuildingOffice2)
                                    ->columns(12)
                                    ->schema([
                                        Section::make('Dự án bán hàng')
                                            ->columnSpan(6)
                                            ->schema([
                                                Select::make('sales_projects')
                                                    ->label('Dự án bán hàng')
                                                    ->options(fn (): array => UserSpecOptions::salesProjects())
                                                    ->disabled(fn (string $operation): bool => self::isLockedForManagerEdit($operation))
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->live()
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?array $state): void {
                                                        $selected = collect($state ?? [])->filter()->values()->all();
                                                        $codes = collect($get('sales_codes') ?? [])->only($selected)->all();

                                                        $set('sales_codes', $codes);
                                                    })
                                                    ->native(false),
                                                Grid::make(2)
                                                    ->columns(2)
                                                    ->schema(fn (): array => SalesProject::query()
                                                        ->where('is_active', true)
                                                        ->orderBy('sort_order')
                                                        ->orderBy('name')
                                                        ->get()
                                                        ->map(fn (SalesProject $project): TextInput => TextInput::make('sales_codes.'.$project->slug)
                                                            ->label('Mã bán hàng '.$project->name)
                                                            ->maxLength(120)
                                                            ->disabled(fn (string $operation): bool => self::isLockedForManagerEdit($operation))
                                                            ->visible(fn (Get $get): bool => in_array($project->slug, $get('sales_projects') ?? [], true))
                                                            ->dehydrated(fn (Get $get): bool => in_array($project->slug, $get('sales_projects') ?? [], true)))
                                                        ->all()),
                                            ]),
                                        Section::make('Kênh')
                                            ->columnSpan(6)
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('company_name')
                                                    ->label('Tên công ty')
                                                    ->maxLength(255)
                                                    ->default('3RDVN')
                                                    ->disabled(fn (Get $get, string $operation): bool => self::isLockedForManagerEdit($operation) || filled($get('sales_channel')))
                                                    ->dehydrated(),
                                                TextInput::make('branch_name')
                                                    ->label('Chi nhánh')
                                                    ->maxLength(255)
                                                    ->default('3RDVN - HCMC')
                                                    ->disabled(fn (Get $get, string $operation): bool => self::isLockedForManagerEdit($operation) || filled($get('sales_channel')))
                                                    ->dehydrated(),
                                                TextInput::make('branch_code')
                                                    ->label('Mã chi nhánh')
                                                    ->maxLength(50)
                                                    ->default('RDVN')
                                                    ->disabled(fn (Get $get, string $operation): bool => self::isLockedForManagerEdit($operation) || filled($get('sales_channel')))
                                                    ->dehydrated(),
                                                Select::make('sales_channel')
                                                    ->label('Kênh')
                                                    ->options(fn (): array => SalesChannel::query()
                                                        ->where('is_active', true)
                                                        ->orderBy('company_name')
                                                        ->orderBy('branch_name')
                                                        ->orderBy('channel_name')
                                                        ->get()
                                                        ->mapWithKeys(fn (SalesChannel $channel): array => [
                                                            $channel->channel_name => $channel->channel_name.' - '.$channel->branch_name,
                                                        ])
                                                        ->all())
                                                    ->default('F1')
                                                    ->disabled(fn (string $operation): bool => self::isLockedForManagerEdit($operation))
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                        $channel = SalesChannel::query()
                                                            ->where('is_active', true)
                                                            ->where('channel_name', $state)
                                                            ->first();

                                                        if (! $channel) {
                                                            return;
                                                        }

                                                        $set('company_name', $channel->company_name);
                                                        $set('branch_name', $channel->branch_name);
                                                        $set('branch_code', $channel->branch_code);
                                                    })
                                                    ->native(false),
                                            ]),
                                    ]),

                                Tab::make('Thông tin người quản lý')
                                    ->icon(Heroicon::UserGroup)
                                    ->columns(12)
                                    ->schema([
                                        Section::make('Quản lý trực tiếp')
                                            ->columnSpanFull()
                                            ->columns(3)
                                            ->schema([
                                                Select::make('team_leader_id')
                                                    ->label('Team Leader')
                                                    ->options(fn (Get $get): array => UserSpecOptions::roleUsers('Team Leader', $get('zd_id'), $get('am_id')))
                                                    ->default(fn (): ?int => auth()->user()?->hasRole('Team Leader') ? auth()->id() : null)
                                                    ->visible(fn (Get $get): bool => in_array($get('roles'), RoleHierarchy::SALES_ROLES, true))
                                                    ->disabled(fn ($record): bool => self::isEditingSelf($record) || auth()->user()?->hasRole('Team Leader'))
                                                    ->required(fn (Get $get): bool => in_array($get('roles'), RoleHierarchy::SALES_ROLES, true))
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                                        foreach (UserSpecOptions::managerChainFor($state) as $field => $value) {
                                                            $set($field, $value);
                                                        }
                                                    })
                                                    ->dehydrated()
                                                    ->searchable()
                                                    ->preload()
                                                    ->native(false),
                                                Select::make('am_id')
                                                    ->label('AM')
                                                    ->options(fn (Get $get): array => UserSpecOptions::roleUsers('AM', $get('zd_id')))
                                                    ->default(fn (): ?int => auth()->user()?->hasRole('AM') ? auth()->id() : (auth()->user()?->hasRole('Team Leader') ? auth()->user()?->am_id : null))
                                                    ->visible(fn (Get $get): bool => in_array($get('roles'), ['Team Leader', 'Direct Sale', 'Telesale', 'CTV'], true))
                                                    ->disabled(fn ($record): bool => self::isEditingSelf($record) || auth()->user()?->hasAnyRole(['AM', 'Team Leader']))
                                                    ->required(fn (Get $get): bool => in_array($get('roles'), ['Team Leader', 'Direct Sale', 'Telesale', 'CTV'], true))
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                                        $chain = UserSpecOptions::managerChainFor($state);

                                                        $set('zd_id', $chain['zd_id']);
                                                        $set('team_leader_id', null);
                                                    })
                                                    ->dehydrated()
                                                    ->searchable()
                                                    ->preload()
                                                    ->native(false),
                                                Select::make('zd_id')
                                                    ->label('ZD')
                                                    ->options(fn (): array => UserSpecOptions::roleUsers('ZD'))
                                                    ->default(fn (): ?int => auth()->user()?->hasRole('ZD') ? auth()->id() : (auth()->user()?->hasAnyRole(['AM', 'Team Leader']) ? auth()->user()?->zd_id : null))
                                                    ->visible(fn (Get $get): bool => in_array($get('roles'), ['AM', 'Team Leader', 'Direct Sale', 'Telesale', 'CTV'], true))
                                                    ->disabled(fn ($record): bool => self::isEditingSelf($record) || ! auth()->user()?->hasRole('Admin'))
                                                    ->required(fn (Get $get): bool => in_array($get('roles'), ['AM', 'Team Leader', 'Direct Sale', 'Telesale', 'CTV'], true))
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set): void {
                                                        $set('am_id', null);
                                                        $set('team_leader_id', null);
                                                    })
                                                    ->dehydrated()
                                                    ->searchable()
                                                    ->preload()
                                                    ->native(false),
                                            ]),
                                    ]),

                                Tab::make('Địa chỉ')
                                    ->icon(Heroicon::MapPin)
                                    ->columns(12)
                                    ->schema([
                                Section::make('Địa chỉ hiện tại')
                                    ->columnSpan(8)
                                    ->columns(2)
                                    ->schema([
                                        Textarea::make('address_line')
                                            ->label('Địa chỉ chi tiết')
                                            ->placeholder('Số nhà, tên đường, tòa nhà, tầng/phòng...')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Select::make('province_code')
                                            ->label('Tỉnh/Thành phố')
                                            ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                                            ->searchable()
                                            ->preload()
                                            ->live()
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
                                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set('ward_name', VietnamAddressCatalog::wardName($get('district_code'), $state)))
                                            ->native(false),
                                        Hidden::make('province_name')->dehydrated(),
                                        Hidden::make('district_name')->dehydrated(),
                                        Hidden::make('ward_name')->dehydrated(),
                                    ]),
                                Section::make('Liên hệ khẩn cấp')
                                    ->columnSpan(4)
                                    ->schema([
                                        TextInput::make('emergency_contact_name')->label('Người liên hệ')->maxLength(255),
                                        TextInput::make('emergency_contact_phone')->label('SĐT khẩn cấp')->tel()->maxLength(30),
                                    ]),
                                    ]),

                                Tab::make('Ngân hàng')
                                    ->icon(Heroicon::CreditCard)
                                    ->columns(12)
                                    ->schema([
                                Section::make('Tài khoản nhận lương')
                                    ->columnSpan(8)
                                    ->columns(2)
                                    ->schema([
                                        Select::make('bank_code')
                                            ->label('Ngân hàng')
                                            ->options(fn (): array => VietnamBankCatalog::options())
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('bank_name', VietnamBankCatalog::nameFor($state)))
                                            ->native(false)
                                            ->columnSpanFull(),
                                        Hidden::make('bank_name')->dehydrated(),
                                        TextInput::make('bank_account_number')->label('Số tài khoản')->maxLength(80),
                                        TextInput::make('bank_account_name')->label('Tên chủ tài khoản')->maxLength(255),
                                        TextInput::make('bank_branch')->label('Chi nhánh')->columnSpanFull()->maxLength(255),
                                    ]),
                                Section::make('Thuế & bảo hiểm')
                                    ->columnSpan(4)
                                    ->schema([
                                        TextInput::make('tax_code')->label('Mã số thuế')->maxLength(80),
                                        TextInput::make('social_insurance_number')->label('Mã BHXH')->maxLength(80),
                                    ]),
                                    ]),

                                Tab::make('Email doanh nghiệp')
                                    ->icon(Heroicon::Envelope)
                                    ->visible(fn ($record): bool => $record instanceof User
                                        && (auth()->user()?->hasRole('Admin') || auth()->id() === $record->getKey()))
                                    ->columns(2)
                                    ->schema([
                                        Section::make('Hộp thư 3RDVN')
                                            ->columnSpanFull()
                                            ->columns(2)
                                            ->schema([
                                                Placeholder::make('mail_address_display')
                                                    ->label('Địa chỉ email')
                                                    ->content(fn (?User $record): string => $record?->mail_address ?: 'Chưa cấp'),
                                                Placeholder::make('mail_status_display')
                                                    ->label('Trạng thái')
                                                    ->content(fn (?User $record): string => match ($record?->mail_status) {
                                                        User::MAIL_STATUS_ACTIVE => 'Đang hoạt động',
                                                        User::MAIL_STATUS_SUSPENDED => 'Đã khóa',
                                                        default => 'Chưa cấp',
                                                    }),
                                                Placeholder::make('mail_quota_display')
                                                    ->label('Dung lượng')
                                                    ->content(fn (?User $record): string => $record?->mail_account_id
                                                        ? number_format((int) $record->mail_quota_mb, 0, ',', '.').' MB'
                                                        : '-'),
                                                Placeholder::make('mail_provisioned_display')
                                                    ->label('Ngày cấp')
                                                    ->content(fn (?User $record): string => $record?->mail_provisioned_at?->format('H:i d/m/Y') ?: '-'),
                                            ]),
                                    ]),


                                Tab::make('Phân quyền')
                                    ->icon(Heroicon::LockClosed)
                                    ->columns(12)
                                    ->schema([
                                Section::make('Đăng nhập')
                                    ->columnSpan(5)
                                    ->schema([
                                        TextInput::make('password')
                                            ->label('Mật khẩu')
                                            ->password()
                                            ->revealable()
                                            ->visible(fn (string $operation): bool => $operation === 'create' || auth()->user()?->hasRole('Admin'))
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->dehydrated(fn (?string $state): bool => filled($state))
                                            ->maxLength(255),
                                        DateTimePicker::make('email_verified_at')
                                            ->label('Xác thực email lúc')
                                            ->visible(fn (): bool => auth()->user()?->hasRole('Admin'))
                                            ->displayFormat('H:i d/m/Y'),
                                    ]),
                                Section::make('Vai trò')
                                    ->columnSpan(7)
                                    ->schema([
                                        Select::make('roles')
                                            ->label('Vai trò')
                                            ->options(function ($record): array {
                                                $options = RoleHierarchy::assignableRoleOptions();
                                                $currentRole = $record instanceof User ? RoleHierarchy::primaryRole($record) : null;

                                                if (filled($currentRole)) {
                                                    $options[$currentRole] = $currentRole;
                                                }

                                                return $options;
                                            })
                                            ->afterStateHydrated(fn (Select $component, $record): mixed => $component->state($record?->roles()->value('name')))
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                foreach (RoleHierarchy::managerFieldDefaults(auth()->user(), $state) as $field => $value) {
                                                    $set($field, $value);
                                                }
                                            })
                                            ->saveRelationshipsUsing(function ($record, ?string $state): mixed {
                                                $currentRole = $record instanceof User ? RoleHierarchy::primaryRole($record) : null;

                                                $allowed = blank($currentRole)
                                                    ? RoleHierarchy::canAssignRole(auth()->user(), $state)
                                                    : RoleHierarchy::canUseRoleOnEdit(auth()->user(), $record, $state);

                                                if (! $allowed) {
                                                    throw ValidationException::withMessages([
                                                        'roles' => 'Bạn không được phép gán vai trò này.',
                                                    ]);
                                                }

                                                if ($currentRole !== $state) {
                                                    UserChangeLog::query()->create([
                                                        'user_id' => $record->getKey(),
                                                        'actor_id' => auth()->id(),
                                                        'action' => 'role_updated',
                                                        'changes' => [[
                                                            'field' => 'roles',
                                                            'label' => 'Vai trò',
                                                            'old' => $currentRole,
                                                            'new' => $state,
                                                        ]],
                                                        'ip_address' => request()?->ip(),
                                                        'user_agent' => request()?->userAgent(),
                                                    ]);
                                                }

                                                return $record->syncRoles([$state]);
                                            })
                                            ->disabled(fn (string $operation): bool => $operation === 'edit' && ! auth()->user()?->hasRole('Admin'))
                                            ->dehydrated(false)
                                            ->required()
                                            ->preload()
                                            ->searchable(false)
                                            ->native(false),
                                    ]),
                                    ]),
                            ]),
            ]);
    }

    private static function isEditingSelf(mixed $record): bool
    {
        return $record instanceof User && auth()->id() === $record->getKey();
    }

    private static function isLockedForManagerEdit(string $operation): bool
    {
        return $operation === 'edit' && ! auth()->user()?->hasRole('Admin');
    }
}
