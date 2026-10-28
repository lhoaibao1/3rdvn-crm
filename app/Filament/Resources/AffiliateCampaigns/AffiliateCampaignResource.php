<?php

namespace App\Filament\Resources\AffiliateCampaigns;

use App\Filament\Resources\AffiliateCampaigns\Pages\CreateAffiliateCampaign;
use App\Filament\Resources\AffiliateCampaigns\Pages\EditAffiliateCampaign;
use App\Filament\Resources\AffiliateCampaigns\Pages\ListAffiliateCampaigns;
use App\Models\AffiliateCampaign;
use App\Support\Filament\AdminOnlyResource;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AffiliateCampaignResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = AffiliateCampaign::class;

    protected static ?string $slug = 'affiliate/campaigns';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function getNavigationGroup(): ?string
    {
        return 'Tiếp thị liên kết';
    }

    public static function getNavigationLabel(): string
    {
        return 'Cấu hình chiến dịch';
    }

    public static function getModelLabel(): string
    {
        return 'Chiến dịch tiếp thị';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cấu hình chiến dịch';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin chiến dịch')->columns(2)->schema([
            TextInput::make('name')
                ->label('Tên chiến dịch')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug đường dẫn')
                ->required()
                ->maxLength(255),
            TextInput::make('tracking_url')
                ->label('Link đối tác gốc')
                ->required()
                ->url()
                ->maxLength(1000),
            TextInput::make('logo_url')
                ->label('Logo URL')
                ->url()
                ->maxLength(500),
            TextInput::make('attribution_param')
                ->label('Tham số gắn mã NV')
                ->default('aff_sub1')
                ->maxLength(50),
            Textarea::make('summary')
                ->label('Tóm tắt')
                ->rows(2),
            Textarea::make('details')
                ->label('Chi tiết & Điều kiện')
                ->rows(3)
                ->columnSpanFull(),
            ]),
            Section::make('Đóng / mở chiến dịch')
                ->description('Trạng thái được áp dụng cả trên Pub, nút tạo link và mọi link đã phát hành.')
                ->columns(2)
                ->schema([
            Toggle::make('is_active')
                ->label('Cho phép tiếp nhận hồ sơ')
                ->helperText('Tắt để đóng ngay chiến dịch và chặn toàn bộ link cũ.')
                ->default(true),
            DateTimePicker::make('opens_at')
                ->label('Tự mở lúc')
                ->seconds(false)
                ->native(false),
            DateTimePicker::make('closes_at')
                ->label('Tự đóng lúc')
                ->seconds(false)
                ->native(false)
                ->after('opens_at'),
            Textarea::make('closure_message')
                ->label('Thông báo khi chiến dịch đóng')
                ->placeholder('Chiến dịch đang tạm đóng và chưa tiếp nhận hồ sơ mới.')
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->label('Tên chiến dịch')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                ToggleColumn::make('is_active')->label('Cho phép mở link'),
                IconColumn::make('availability')
                    ->label('Trạng thái thực tế')
                    ->state(fn (AffiliateCampaign $record): bool => $record->isOpen())
                    ->boolean(),
                TextColumn::make('opens_at')->label('Tự mở')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('closes_at')->label('Tự đóng')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                EditAction::make()->label('Cấu hình'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliateCampaigns::route('/'),
            'create' => CreateAffiliateCampaign::route('/create'),
            'edit' => EditAffiliateCampaign::route('/{record}/edit'),
        ];
    }
}
