<?php

namespace App\Filament\Resources\AffiliateConversions;

use App\Filament\Resources\AffiliateConversions\Pages\ListAffiliateConversions;
use App\Models\AffiliateConversion;
use App\Support\Permissions\RecordVisibility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffiliateConversionResource extends Resource
{
    protected static ?string $model = AffiliateConversion::class;

    protected static ?string $slug = 'applications/affiliate';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getNavigationGroup(): ?string
    {
        return 'Quản lý hồ sơ';
    }

    public static function getNavigationLabel(): string
    {
        return 'Leads & Đơn Tiếp Thị';
    }

    public static function getModelLabel(): string
    {
        return 'Lead Tiếp Thị';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Danh sách Leads & Đơn Tiếp Thị';
    }

    public static function getNavigationSort(): ?int
    {
        return 25;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return auth()->user()?->can('application.view') ?? true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin', 'Sales Admin']) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = auth()->user();
                if (! $user) {
                    return $query->whereRaw('1 = 0');
                }
                if ($user->hasAnyRole(['Admin', 'Super Admin', 'Sales Admin'])) {
                    return $query;
                }

                return RecordVisibility::applyUserScope($query, $user, 'created_by_id', 'createdBy');
            })
            ->columns([
                TextColumn::make('conversion_id')
                    ->label('Mã chuyển đổi')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('transaction_id')
                    ->label('Mã giao dịch')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('campaign_name')
                    ->label('Chiến dịch')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('sale_amount')
                    ->label('Doanh số vay')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.')
                    ->suffix(' đ')
                    ->sortable(),
                TextColumn::make('conversion_status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower((string)$state)) {
                        'success', 'approved', 'disbursed', 'completed', 'paid' => 'success',
                        'rejected', 'cancelled', 'failed', 'declined', 'trash' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?string $state): string => match (strtolower((string)$state)) {
                        'success', 'approved', 'disbursed', 'completed', 'paid' => 'Đã duyệt / Giải ngân',
                        'rejected', 'cancelled', 'failed', 'declined', 'trash' => 'Từ chối / Hủy',
                        default => 'Đang thẩm định / Chờ',
                    }),
                TextColumn::make('aff_sub1')
                    ->label('Mã NVKD (Sub 1)')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('aff_sub2')
                    ->label('Mã Lead (Sub 2)')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('conversion_time')
                    ->label('Thời gian ghi nhận')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('campaign_name')
                    ->label('Chiến dịch')
                    ->options([
                        'VPBank UPL' => 'VPBank UPL',
                        'Tin Vay' => 'Tin Vay',
                        'SHB Finance' => 'SHB Finance',
                    ]),
                SelectFilter::make('conversion_status')
                    ->label('Trạng thái')
                    ->options([
                        'approved' => 'Đã duyệt / Giải ngân',
                        'pending' => 'Đang thẩm định',
                        'rejected' => 'Bị từ chối / Hủy',
                    ]),
            ])
            ->defaultSort('conversion_time', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliateConversions::route('/'),
        ];
    }
}
