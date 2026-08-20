<?php

namespace App\Filament\Resources\AffiliateCampaigns;

use App\Filament\Resources\AffiliateCampaigns\Pages\CreateAffiliateCampaign;
use App\Filament\Resources\AffiliateCampaigns\Pages\EditAffiliateCampaign;
use App\Filament\Resources\AffiliateCampaigns\Pages\ListAffiliateCampaigns;
use App\Models\AffiliateCampaign;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AffiliateCampaignResource extends Resource
{
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

    /**
     * Hidden for general staff, only visible for Admin
     */
    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        $user = auth()->user();
        return $user?->hasAnyRole(['Admin', 'Super Admin', 'Sales Admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                ->rows(3),
            Toggle::make('is_active')
                ->label('Đang hoạt động')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->label('Tên chiến dịch')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                IconColumn::make('is_active')->label('Kích hoạt')->boolean(),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i'),
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
