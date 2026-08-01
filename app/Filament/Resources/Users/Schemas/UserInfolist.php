<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\Filament\ProcessTimeline;
use App\Support\Filament\RecordViewChrome;
use App\Support\UserSpecOptions;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_record_view_header')
                    ->hiddenLabel()
                    ->state(fn (User $record): HtmlString => RecordViewChrome::userProfile($record))
                    ->html()
                    ->columnSpanFull(),
                Tabs::make('User detail')
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
                                                TextEntry::make('name')->label('Họ tên'),
                                                TextEntry::make('username')->label('Username')->placeholder('-'),
                                                TextEntry::make('uid')->label('UID')->placeholder('-'),
                                                TextEntry::make('employee_code')->label('Employee Code')->placeholder('-'),
                                                TextEntry::make('email')->label('Email'),
                                                TextEntry::make('phone')->label('SĐT')->placeholder('-'),
                                                TextEntry::make('document_type')
                                                    ->label('Loại giấy tờ')
                                                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('document_type', $state))
                                                    ->placeholder('-'),
                                                TextEntry::make('identity_number')->label('CCCD/CMND/Hộ chiếu')->placeholder('-'),
                                                TextEntry::make('date_of_birth')->label('Ngày sinh')->date('d/m/Y')->placeholder('-'),
                                                TextEntry::make('gender')->label('Giới tính')->formatStateUsing(fn (?string $state): string => match ($state) {
                                                    'male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', default => '-',
                                                }),
                                                TextEntry::make('identity_issued_date')->label('Ngày cấp')->date('d/m/Y')->placeholder('-'),
                                                TextEntry::make('identity_issued_place')
                                                    ->label('Nơi cấp')
                                                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('issued_place', $state))
                                                    ->placeholder('-'),
                                            ]),
                                        Section::make('Công việc')
                                            ->columnSpan(4)
                                            ->schema([
                                                TextEntry::make('department')
                                                    ->label('Phòng ban')
                                                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('department', $state))
                                                    ->placeholder('-'),
                                                TextEntry::make('team.name')->label('Team')->placeholder('-'),
                                                TextEntry::make('employment_status')
                                                    ->label('Trạng thái')
                                                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('employment_status', $state))
                                                    ->badge()
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'deactive', 'inactive', 'resigned' => 'danger',
                                                        default => 'gray',
                                                    })
                                                    ->placeholder('-'),
                                                TextEntry::make('hire_date')->label('Ngày vào làm')->date('d/m/Y')->placeholder('-'),
                                                TextEntry::make('office')
                                                    ->label('Office')
                                                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('office', $state))
                                                    ->placeholder('-'),
                                                TextEntry::make('contract_type')
                                                    ->label('Loại hợp đồng')
                                                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('contract_type', $state))
                                                    ->placeholder('-'),
                                            ]),
                                    ]),

                                Tab::make('Dự án & kênh')
                                    ->icon(Heroicon::BuildingOffice2)
                                    ->columns(12)
                                    ->schema([
                                        Section::make('Dự án bán hàng')
                                            ->columnSpan(6)
                                            ->schema([
                                                TextEntry::make('sales_projects')
                                                    ->label('Dự án bán hàng')
                                                    ->state(fn ($record): string => collect($record->sales_projects ?? [])
                                                        ->filter()
                                                        ->map(fn (string $project): string => UserSpecOptions::salesProjectLabel($project))
                                                        ->join(', '))
                                                    ->placeholder('-'),
                                                TextEntry::make('sales_codes')
                                                    ->label('Mã bán hàng')
                                                    ->state(fn ($record): string => collect($record->sales_codes ?? [])
                                                        ->filter()
                                                        ->map(fn (?string $code, mixed $project): string => is_string($project)
                                                            ? UserSpecOptions::salesProjectLabel($project).': '.$code
                                                            : $code)
                                                        ->join(', '))
                                                    ->placeholder('-'),
                                            ]),
                                        Section::make('Kênh')
                                            ->columnSpan(6)
                                            ->columns(2)
                                            ->schema([
                                                TextEntry::make('company_name')->label('Tên công ty')->placeholder('-'),
                                                TextEntry::make('branch_name')->label('Chi nhánh')->placeholder('-'),
                                                TextEntry::make('branch_code')->label('Mã chi nhánh')->placeholder('-'),
                                                TextEntry::make('sales_channel')->label('Kênh')->placeholder('-'),
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
                                                TextEntry::make('teamLeader.name')->label('Team Leader')->placeholder('-'),
                                                TextEntry::make('courierManager.name')->label('Courier Manager')->placeholder('-'),
                                                TextEntry::make('am.name')->label('AM')->placeholder('-'),
                                                TextEntry::make('zd.name')->label('ZD')->placeholder('-'),
                                            ]),
                                    ]),

                                Tab::make('Địa chỉ')
                                    ->icon(Heroicon::MapPin)
                                    ->columns(12)
                                    ->schema([
                                Section::make('Địa chỉ hiện tại')
                                    ->columnSpan(8)
                                    ->schema([
                                        TextEntry::make('full_address')
                                            ->label('Địa chỉ đầy đủ')
                                            ->state(fn ($record): string => collect([
                                                $record->address_line,
                                                $record->ward_name,
                                                $record->district_name,
                                                $record->province_name,
                                            ])->filter()->join(', '))
                                            ->placeholder('-'),
                                        TextEntry::make('address_line')->label('Địa chỉ chi tiết')->placeholder('-'),
                                        TextEntry::make('province_name')->label('Tỉnh/Thành phố')->placeholder('-'),
                                        TextEntry::make('district_name')->label('Quận/Huyện')->placeholder('-'),
                                        TextEntry::make('ward_name')->label('Phường/Xã')->placeholder('-'),
                                    ]),
                                Section::make('Liên hệ khẩn cấp')
                                    ->columnSpan(4)
                                    ->schema([
                                        TextEntry::make('emergency_contact_name')->label('Người liên hệ')->placeholder('-'),
                                        TextEntry::make('emergency_contact_phone')->label('SĐT khẩn cấp')->placeholder('-'),
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
                                        TextEntry::make('bank_name')->label('Ngân hàng')->placeholder('-')->columnSpanFull(),
                                        TextEntry::make('bank_code')->label('Mã ngân hàng')->placeholder('-'),
                                        TextEntry::make('bank_account_number')->label('Số tài khoản')->placeholder('-'),
                                        TextEntry::make('bank_account_name')->label('Chủ tài khoản')->placeholder('-'),
                                        TextEntry::make('bank_branch')->label('Chi nhánh')->placeholder('-'),
                                    ]),
                                Section::make('Thuế & bảo hiểm')
                                    ->columnSpan(4)
                                    ->schema([
                                        TextEntry::make('tax_code')->label('Mã số thuế')->placeholder('-'),
                                        TextEntry::make('social_insurance_number')->label('Mã BHXH')->placeholder('-'),
                                    ]),
                                    ]),

                                Tab::make('Email doanh nghiệp')
                                    ->icon(Heroicon::Envelope)
                                    ->visible(fn (User $record): bool => auth()->user()?->hasRole('Admin')
                                        || auth()->id() === $record->getKey())
                                    ->columns(12)
                                    ->schema([
                                        Section::make('Hộp thư 3RDVN')
                                            ->columnSpanFull()
                                            ->columns(2)
                                            ->schema([
                                                TextEntry::make('mail_address')
                                                    ->label('Địa chỉ email')
                                                    ->copyable()
                                                    ->placeholder('Chưa cấp'),
                                                TextEntry::make('mail_status')
                                                    ->label('Trạng thái')
                                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                        User::MAIL_STATUS_ACTIVE => 'Đang hoạt động',
                                                        User::MAIL_STATUS_SUSPENDED => 'Đã khóa',
                                                        default => 'Chưa cấp',
                                                    })
                                                    ->badge()
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        User::MAIL_STATUS_ACTIVE => 'success',
                                                        User::MAIL_STATUS_SUSPENDED => 'danger',
                                                        default => 'gray',
                                                    }),
                                                TextEntry::make('mail_quota_mb')
                                                    ->label('Dung lượng')
                                                    ->formatStateUsing(fn ($state, User $record): string => $record->mail_account_id
                                                        ? number_format((int) $state, 0, ',', '.').' MB'
                                                        : '-'),
                                                TextEntry::make('mail_provisioned_at')
                                                    ->label('Ngày cấp')
                                                    ->dateTime('H:i d/m/Y')
                                                    ->placeholder('-'),
                                                TextEntry::make('mail_webmail')
                                                    ->label('Webmail')
                                                    ->state('Mở 3RDVN Mail')
                                                    ->icon(Heroicon::OutlinedEnvelopeOpen)
                                                    ->color('primary')
                                                    ->url(fn (): string => (string) config('services.stalwart.webmail_url'))
                                                    ->openUrlInNewTab()
                                                    ->visible(fn (User $record): bool => filled($record->mail_account_id))
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Tab::make('Phân quyền')
                                    ->icon(Heroicon::LockClosed)
                                    ->columns(12)
                                    ->schema([
                                Section::make('Vai trò')
                                    ->columnSpan(6)
                                    ->schema([
                                        TextEntry::make('roles.name')->label('Vai trò')->badge()->separator(', ')->placeholder('-'),
                                    ]),
                                Section::make('Hệ thống')
                                    ->columnSpan(6)
                                    ->schema([
                                        TextEntry::make('email_verified_at')->label('Xác thực email')->dateTime('H:i d/m/Y')->placeholder('-'),
                                        TextEntry::make('creator.name')->label('Tạo bởi')->placeholder('-'),
                                        TextEntry::make('created_at')->label('Tạo lúc')->dateTime('H:i d/m/Y')->placeholder('-'),
                                        TextEntry::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    ]),
                                    ]),

                                Tab::make('Lịch sử thay đổi')
                                    ->icon(Heroicon::Clock)
                                    ->schema([
                                        Section::make('Nhật ký chỉnh sửa')
                                            ->columnSpanFull()
                                            ->schema([
                                                TextEntry::make('change_history')
                                                    ->hiddenLabel()
                                                    ->state(fn (User $record): HtmlString => self::renderChangeHistory($record))
                                                    ->html()
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
            ]);
    }

    private static function renderChangeHistory(User $record): HtmlString
    {
        $logs = $record->changeLogs()
            ->with('actor:id,name,uid,employee_code,email')
            ->latest()
            ->limit(50)
            ->get();

        return ProcessTimeline::render(
            $logs,
            fn ($log): string => self::auditActionLabel($log->action),
            fn ($log): string => self::auditShortBody($log),
            fn ($log): array => self::auditTone($log),
            'Chưa có lịch sử thay đổi.',
        );
    }

    private static function auditShortBody(object $log): string
    {
        $changes = collect($log->changes ?? []);

        $statusChange = $changes->first(fn (array $change): bool => in_array($change['field'] ?? null, ['status', 'employment_status'], true));
        if ($statusChange) {
            $field = $statusChange['field'] ?? null;

            return ($field === 'employment_status' ? 'Trạng thái làm việc' : 'Trạng thái')
                .': '.self::auditValue($field, $statusChange['old'] ?? null)
                .' → '.self::auditValue($field, $statusChange['new'] ?? null);
        }

        $noteChange = $changes->first(fn (array $change): bool => in_array($change['field'] ?? null, ['note', 'description'], true));
        if ($noteChange) {
            return 'Ghi chú: '.self::auditValue($noteChange['field'] ?? null, $noteChange['new'] ?? null);
        }

        return match ($log->action) {
            'created' => 'Tạo người dùng.',
            'deleted_access' => 'Xóa quyền truy cập.',
            'reissued_access' => 'Tái cấp UID / Employee Code.',
            'role_updated' => 'Cập nhật vai trò/phân quyền.',
            default => 'Cập nhật thông tin người dùng.',
        };
    }

    private static function auditTone(object $log): array
    {
        return match ($log->action) {
            'created', 'reissued_access' => ['label' => 'Tạo mới', 'color' => '#2563eb', 'soft' => '#dbeafe', 'border' => '#bfdbfe'],
            'deleted_access' => ['label' => 'Đóng', 'color' => '#dc2626', 'soft' => '#fee2e2', 'border' => '#fecaca'],
            'role_updated' => ['label' => 'Phân quyền', 'color' => '#7c3aed', 'soft' => '#ede9fe', 'border' => '#c4b5fd'],
            default => ['label' => 'Cập nhật', 'color' => '#be185d', 'soft' => '#fce7f3', 'border' => '#f9a8d4'],
        };
    }

    private static function auditActionLabel(?string $action): string
    {
        return match ($action) {
            'created' => 'Tạo người dùng',
            'deleted_access' => 'Xóa quyền truy cập',
            'reissued_access' => 'Tái cấp UID / Employee Code',
            'role_updated' => 'Cập nhật vai trò',
            default => 'Cập nhật thông tin',
        };
    }

    private static function auditValue(?string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        if ($field === 'employment_status') {
            return UserSpecOptions::labelFor('employment_status', (string) $value);
        }

        if ($field === 'document_type') {
            return UserSpecOptions::labelFor('document_type', (string) $value);
        }

        if ($field === 'office') {
            return UserSpecOptions::labelFor('office', (string) $value);
        }

        if ($field === 'contract_type') {
            return UserSpecOptions::labelFor('contract_type', (string) $value);
        }

        if (in_array($field, ['team_leader_id', 'courier_manager_id', 'am_id', 'zd_id', 'created_by_id'], true)) {
            return User::query()->whereKey($value)->value('name') ?: (string) $value;
        }

        if ($field === 'sales_projects' && is_array($value)) {
            return collect($value)
                ->map(fn (mixed $project): string => UserSpecOptions::salesProjectLabel((string) $project))
                ->join(', ');
        }

        if ($field === 'sales_codes' && is_array($value)) {
            return collect($value)
                ->map(fn (mixed $code, mixed $project): string => is_string($project)
                    ? UserSpecOptions::salesProjectLabel($project).': '.self::auditValue(null, $code)
                    : self::auditValue(null, $code))
                ->join(', ');
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item, mixed $key): string => is_string($key)
                    ? $key.': '.self::auditValue(null, $item)
                    : self::auditValue(null, $item))
                ->join(', ');
        }

        return (string) $value;
    }
}
