<?php

namespace App\Filament\Resources\CrmModules\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class CrmModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Module')
                    ->columnSpan(5)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')->label('Tên kỹ thuật')->required()->maxLength(100),
                            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true)->maxLength(100),
                        ]),
                        TextInput::make('label')->label('Tên hiển thị trên menu')->required()->maxLength(120),
                        Textarea::make('description')->label('Mô tả')->rows(3),
                        Grid::make(2)->schema([
                            TextInput::make('icon')->label('Icon key')->maxLength(80),
                            TextInput::make('route_name')->label('Route name')->required()->maxLength(120),
                            TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(100),
                            Toggle::make('is_active')->label('Bật module')->default(true),
                        ]),
                    ]),

                Section::make('Map role và quyền')
                    ->columnSpan(7)
                    ->schema([
                        Select::make('required_roles')
                            ->label('Role được thấy module')
                            ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'name')->all())
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        CheckboxList::make('required_permissions')
                            ->label('Quyền được thấy module')
                            ->options(self::permissionOptions())
                            ->columns(2)
                            ->bulkToggleable()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function permissionOptions(): array
    {
        return [
            'Dashboard' => ['dashboard.view' => 'Xem dashboard'],
            'Lead' => ['lead.view' => 'Xem Lead', 'lead.create' => 'Tạo Lead', 'lead.update' => 'Sửa Lead', 'lead.delete' => 'Xóa Lead', 'lead.convert' => 'Chuyển Lead'],
            'Application' => ['application.view' => 'Xem Application', 'application.update' => 'Xử lý Application'],
            'Hồ sơ' => ['profile.view' => 'Xem hồ sơ', 'profile.create' => 'Tạo hồ sơ', 'profile.update' => 'Sửa hồ sơ', 'profile.delete' => 'Xóa hồ sơ', 'profile.submit' => 'Gửi duyệt', 'profile.approve' => 'Duyệt', 'profile.reject' => 'Từ chối', 'profile.process' => 'Xử lý', 'profile.complete' => 'Hoàn tất'],
            'API Mapping' => ['api_mapping.view' => 'Xem API Mapping', 'api_mapping.create' => 'Tạo API Mapping', 'api_mapping.update' => 'Sửa API Mapping', 'api_mapping.delete' => 'Xóa API Mapping', 'api_mapping.test' => 'Test API'],
            'Modules' => ['module.view' => 'Xem Modules', 'module.create' => 'Tạo Module', 'module.update' => 'Sửa Module', 'module.delete' => 'Xóa Module'],
            'Danh mục user' => ['lookup.view' => 'Xem danh mục user', 'lookup.create' => 'Tạo danh mục user', 'lookup.update' => 'Sửa danh mục user', 'lookup.delete' => 'Xóa danh mục user'],
            'Kênh bán hàng' => ['sales_channel.view' => 'Xem kênh bán hàng', 'sales_channel.create' => 'Tạo kênh bán hàng', 'sales_channel.update' => 'Sửa kênh bán hàng', 'sales_channel.delete' => 'Xóa kênh bán hàng'],
            'Dự án bán hàng' => ['sales_project.view' => 'Xem dự án bán hàng', 'sales_project.create' => 'Tạo dự án bán hàng', 'sales_project.update' => 'Sửa dự án bán hàng', 'sales_project.delete' => 'Xóa dự án bán hàng'],
            'Người dùng' => ['user.view' => 'Xem người dùng', 'user.create' => 'Tạo người dùng', 'user.update' => 'Sửa người dùng', 'user.delete' => 'Xóa người dùng'],
            'Vai trò' => ['role.view' => 'Xem vai trò', 'role.create' => 'Tạo vai trò', 'role.update' => 'Sửa vai trò', 'role.delete' => 'Xóa vai trò'],
            'Cài đặt' => ['settings.view' => 'Xem cài đặt', 'settings.update' => 'Sửa cài đặt'],
        ];
    }
}
