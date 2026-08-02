<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])
            ->columns(12)
            ->components([
                Section::make('Thông tin vai trò')
                    ->columnSpan(4)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên vai trò')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('guard_name')
                            ->label('Guard')
                            ->required()
                            ->default('web'),
                    ]),

                Section::make('Ma trận quyền theo module')
                    ->columnSpan(8)
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Quyền')
                            ->relationship(
                                name: 'permissions',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('name'),
                            )
                            ->options(self::permissionOptions())
                            ->bulkToggleable()
                            ->columns(2)
                            ->searchable(),
                    ]),
            ]);
    }

    public static function permissionOptions(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'Xem dashboard',
            ],
            'Lead' => [
                'lead.view' => 'Xem Lead',
                'lead.create' => 'Tạo Lead',
                'lead.update' => 'Sửa Lead',
                'lead.delete' => 'Xóa Lead',
                'lead.convert' => 'Chuyển Lead thành hồ sơ',
            ],
            'Hồ sơ' => [
                'profile.view' => 'Xem hồ sơ',
                'profile.create' => 'Tạo hồ sơ',
                'profile.update' => 'Sửa hồ sơ',
                'profile.delete' => 'Xóa hồ sơ',
                'profile.submit' => 'Gửi phê duyệt',
                'profile.approve' => 'Duyệt hồ sơ',
                'profile.reject' => 'Từ chối hồ sơ',
                'profile.process' => 'Xử lý hồ sơ',
                'profile.complete' => 'Hoàn tất hồ sơ',
            ],
            'Phê duyệt' => [
                'approval.view' => 'Xem phê duyệt',
                'approval.update' => 'Cập nhật phê duyệt',
                'approval.approve' => 'Duyệt',
                'approval.export' => 'Xuất phê duyệt',
            ],
            'API Mapping' => [
                'api_mapping.view' => 'Xem API Mapping',
                'api_mapping.create' => 'Tạo API Mapping',
                'api_mapping.update' => 'Sửa API Mapping',
                'api_mapping.delete' => 'Xóa API Mapping',
                'api_mapping.test' => 'Test API Mapping',
            ],
            'Modules' => [
                'module.view' => 'Xem Modules',
                'module.create' => 'Tạo Module',
                'module.update' => 'Sửa Module',
                'module.delete' => 'Xóa Module',
            ],
            'Danh mục user' => [
                'lookup.view' => 'Xem danh mục user',
                'lookup.create' => 'Tạo danh mục user',
                'lookup.update' => 'Sửa danh mục user',
                'lookup.delete' => 'Xóa danh mục user',
            ],
            'Kênh bán hàng' => [
                'sales_channel.view' => 'Xem kênh bán hàng',
                'sales_channel.create' => 'Tạo kênh bán hàng',
                'sales_channel.update' => 'Sửa kênh bán hàng',
                'sales_channel.delete' => 'Xóa kênh bán hàng',
            ],
            'Người dùng' => [
                'user.view' => 'Xem người dùng',
                'user.create' => 'Tạo người dùng',
                'user.update' => 'Sửa người dùng',
                'user.delete' => 'Xóa người dùng',
                'user.manage_team' => 'Quản lý user trong team',
                'user.assign_hierarchy' => 'Map Team Leader / AM / ZD',
            ],
            'Vai trò' => [
                'role.view' => 'Xem vai trò',
                'role.create' => 'Tạo vai trò',
                'role.update' => 'Sửa vai trò',
                'role.delete' => 'Xóa vai trò',
            ],
            'Cài đặt' => [
                'settings.view' => 'Xem cài đặt',
                'settings.update' => 'Sửa cài đặt',
            ],
            'Báo cáo' => [
                'report.view' => 'Xem báo cáo',
                'report.export' => 'Xuất báo cáo',
            ],
        ];
    }
}
