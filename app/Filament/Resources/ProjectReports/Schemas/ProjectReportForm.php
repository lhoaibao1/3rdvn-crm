<?php

namespace App\Filament\Resources\ProjectReports\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\ProjectReport;
use App\Models\User;
use App\Support\AdminWorkflowOverride;
use App\Support\Reports\ProjectReportAccess;
use App\Support\Reports\ProjectReportProductCatalog;
use App\Support\VietnamAddressCatalog;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class ProjectReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])
            ->components(self::components());
    }

    public static function components(bool $projectLocked = false): array
    {
        return [
            Section::make('Quản trị hệ thống')
                ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin'))
                ->columns(3)
                ->schema([
                    Select::make('created_by_id')
                        ->label('Người tạo')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required(AdminWorkflowOverride::required())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                            $user = filled($state) ? User::query()->find($state) : null;
                            $projectId = $get('sales_project_id');

                            if (filled($projectId) && ! array_key_exists((int) $projectId, ProjectReportAccess::creatableProjectOptions($user))) {
                                $set('sales_project_id', null);
                                $set('product_code', null);
                                $projectId = null;
                            }

                            $set('sales_code', ProjectReportAccess::salesCode($user, $projectId));
                        })
                        ->native(false),
                    Select::make('status')
                        ->label('Trạng thái')
                        ->options(ProjectReport::statusOptions())
                        ->required(AdminWorkflowOverride::required())
                        ->native(false),
                    DateTimePicker::make('created_at')->label('Ngày tạo')->seconds(false)->required(AdminWorkflowOverride::required()),
                    DateTimePicker::make('updated_at')->label('Ngày cập nhật')->seconds(false)->required(AdminWorkflowOverride::required()),
                ]),
            Section::make('Thông tin báo cáo')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('sales_project_id')
                            ->label('Dự án')
                            ->options(function (Get $get) use ($projectLocked): array {
                                $user = self::selectedUser($get('created_by_id'));

                                return $projectLocked
                                    ? ProjectReportAccess::projectOptions($user)
                                    : ProjectReportAccess::creatableProjectOptions($user);
                            })
                            ->required(AdminWorkflowOverride::required())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                                $set('sales_code', ProjectReportAccess::salesCode(
                                    self::selectedUser($get('created_by_id')),
                                    $state,
                                ));
                                $set('product_code', null);
                            })
                            ->disabled($projectLocked)
                            ->dehydrated()
                            ->native(false),
                        TextInput::make('sales_code')
                            ->label('Mã bán hàng')
                            ->readOnly()
                            ->required(AdminWorkflowOverride::required())
                            ->maxLength(120),
                        TextInput::make('customer_name')
                            ->label('Họ tên khách hàng')
                            ->required(AdminWorkflowOverride::required())
                            ->maxLength(255),
                        TextInput::make('identity_number')
                            ->label('CCCD/CMND')
                            ->required(AdminWorkflowOverride::required())
                            ->maxLength(30),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->required(AdminWorkflowOverride::required())
                            ->maxLength(30),
                        Select::make('product_code')
                            ->label('Sản phẩm/Scheme')
                            ->options(fn (Get $get): array => ProjectReportProductCatalog::initialOptions(
                                ProjectReportAccess::project($get('sales_project_id')),
                            ))
                            ->getSearchResultsUsing(fn (Get $get, string $search): array => ProjectReportProductCatalog::searchOptions(
                                ProjectReportAccess::project($get('sales_project_id')),
                                $search,
                            ))
                            ->getOptionLabelUsing(fn (Get $get, ?string $value): ?string => ProjectReportProductCatalog::label(
                                ProjectReportAccess::project($get('sales_project_id')),
                                $value,
                            ) ?? $value)
                            ->disabled(fn (Get $get): bool => blank($get('sales_project_id')))
                            ->required(AdminWorkflowOverride::required())
                            ->searchable()
                            ->live()
                            ->native(false),
                        TextInput::make('loan_amount')
                            ->label('Số tiền vay')
                            ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                            ->stripCharacters('.')
                            ->suffix('VNĐ')
                            ->required(AdminWorkflowOverride::required())
                            ->rules(['integer', 'min:1']),
                        Select::make('province_code')
                            ->label('Tỉnh/Thành phố')
                            ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                            ->required(AdminWorkflowOverride::required())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('province_name', VietnamAddressCatalog::provinceName($state));
                                $set('district_code', null);
                                $set('district_name', null);
                            })
                            ->native(false),
                        Select::make('district_code')
                            ->label('Quận/Huyện')
                            ->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions($get('province_code')))
                            ->disabled(fn (Get $get): bool => blank($get('province_code')))
                            ->required(AdminWorkflowOverride::required())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set(
                                'district_name',
                                VietnamAddressCatalog::districtName($get('province_code'), $state),
                            ))
                            ->native(false),
                        Hidden::make('province_name'),
                        Hidden::make('district_name'),
                    ]),
                ]),
        ];
    }

    private static function selectedUser(mixed $createdById): ?User
    {
        if ((bool) auth()->user()?->hasRole('Admin') && filled($createdById)) {
            return User::query()->find((int) $createdById);
        }

        return auth()->user();
    }
}
