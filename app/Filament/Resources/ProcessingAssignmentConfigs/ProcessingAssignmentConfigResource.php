<?php

namespace App\Filament\Resources\ProcessingAssignmentConfigs;

use App\Filament\Resources\ProcessingAssignmentConfigs\Pages\CreateProcessingAssignmentConfig;
use App\Filament\Resources\ProcessingAssignmentConfigs\Pages\EditProcessingAssignmentConfig;
use App\Filament\Resources\ProcessingAssignmentConfigs\Pages\ListProcessingAssignmentConfigs;
use App\Models\ProcessingAssignmentConfig;
use App\Models\SalesProject;
use App\Support\Permissions\HotLeadAccess;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProcessingAssignmentConfigResource extends Resource
{
    protected static ?string $model = ProcessingAssignmentConfig::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getModelLabel(): string
    {
        return 'Cấu hình phân bổ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Phân bổ xử lý';
    }

    public static function getNavigationLabel(): string
    {
        return 'Phân bổ xử lý';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Config Modules';
    }

    public static function getNavigationSort(): ?int
    {
        return 84;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Phân bổ người xử lý')
                ->columns(2)
                ->schema([
                    Select::make('sales_project_id')
                        ->label('Dự án / Module')
                        ->relationship('salesProject', 'name', fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name'))
                        ->unique(ignoreRecord: true)
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->required()
                        ->columnSpanFull(),
                    Toggle::make('is_enabled')
                        ->label('Bật cấu hình phân bổ')
                        ->helperText('Tắt để ngừng áp dụng danh sách phân bổ này.'),
                    Toggle::make('auto_assign')
                        ->label('Tự động phân bổ ngẫu nhiên')
                        ->helperText('Khi bật, hệ thống random một người trong danh sách đã chọn.'),
                    CheckboxList::make('user_ids')
                        ->label('Nhân viên được phép nhận xử lý')
                        ->options(fn (): array => ProcessingAssignmentConfig::selectableUserOptions())
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Trạng thái Hot Lead')
                ->description('Thêm các trạng thái nghiệp vụ cần dùng tại module Hot Lead.')
                ->visible(fn (Get $get): bool => SalesProject::query()->whereKey((int) $get('sales_project_id'))->value('slug') === HotLeadAccess::PROJECT_SLUG)
                ->schema([
                    Repeater::make('statuses')
                        ->label('Danh sách trạng thái')
                        ->simple(
                            TextInput::make('status')
                                ->label('Tên trạng thái')
                                ->required()
                                ->maxLength(120),
                        )
                        ->addActionLabel('Thêm trạng thái')
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sales_project_id')
            ->columns([
                TextColumn::make('salesProject.name')->label('Dự án / Module')->searchable()->sortable(),
                IconColumn::make('is_enabled')->label('Đang bật')->boolean(),
                IconColumn::make('auto_assign')->label('Tự động random')->boolean(),
                TextColumn::make('user_ids')->label('Số người nhận')->formatStateUsing(fn (mixed $state): int => count(is_array($state) ? $state : [])),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->label('Sửa'),
                    DeleteAction::make()->label('Xóa'),
                ])
                    ->iconButton()
                    ->label('Hành động')
                    ->tooltip('Hành động')
                    ->icon(Heroicon::EllipsisVertical),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProcessingAssignmentConfigs::route('/'),
            'create' => CreateProcessingAssignmentConfig::route('/create'),
            'edit' => EditProcessingAssignmentConfig::route('/{record}/edit'),
        ];
    }
}
