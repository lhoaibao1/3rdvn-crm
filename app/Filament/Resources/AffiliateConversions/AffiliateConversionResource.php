<?php

namespace App\Filament\Resources\AffiliateConversions;

use App\Filament\Resources\AffiliateConversions\Pages\ListAffiliateConversions;
use App\Models\AffiliateConversion;
use App\Models\Lead;
use App\Models\User;
use App\Support\AffiliateConversionStatus;
use App\Support\Permissions\RecordVisibility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                    ->weight('bold')
                    ->description(fn (AffiliateConversion $record): ?string => $record->transaction_id && $record->transaction_id !== '-' ? "Mã GD: {$record->transaction_id}" : null),

                TextColumn::make('campaign_name')
                    ->label('Chiến dịch')
                    ->badge()
                    ->getStateUsing(function (AffiliateConversion $record): string {
                        $meta = strtolower((string)($record->campaign_name . $record->partner . $record->offer_id . $record->landing_page . $record->conversion_id));
                        return match (true) {
                            str_contains($meta, 'vpbank') => 'VPBank UPL',
                            str_contains($meta, 'shb') || strtolower((string)$record->partner) === 'hyperlead' => 'SHB Finance',
                            str_contains($meta, 'tinvay') || str_contains($meta, 'vietcredit') => 'Tin Vay',
                            default => $record->campaign_name ?: 'SHB Finance',
                        };
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'SHB Finance' => 'warning',
                        'VPBank UPL' => 'success',
                        'Tin Vay' => 'info',
                        default => 'primary',
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('campaign_name', 'ilike', "%{$search}%")
                            ->orWhere('offer_id', 'ilike', "%{$search}%")
                            ->orWhere('partner', 'ilike', "%{$search}%");
                    }),

                TextColumn::make('customer_name')
                    ->label('Khách hàng')
                    ->getStateUsing(function (AffiliateConversion $record): string {
                        $raw = (array) ($record->raw_payload ?? []);
                        if (!empty($raw['customer_name'])) {
                            return $raw['customer_name'];
                        }
                        if (is_numeric($record->aff_sub2)) {
                            $lead = Lead::find((int) $record->aff_sub2);
                            if ($lead && $lead->lead_name) {
                                return $lead->lead_name;
                            }
                        }
                        return 'Khách hàng';
                    })
                    ->description(function (AffiliateConversion $record): ?string {
                        $raw = (array) ($record->raw_payload ?? []);
                        $phone = $raw['customer_phone'] ?? null;
                        $cccd = $raw['customer_identity_number'] ?? null;
                        if (!$phone && is_numeric($record->aff_sub2)) {
                            $lead = Lead::find((int) $record->aff_sub2);
                            $phone = $lead?->phone;
                            $cccd = is_array($lead?->payload) ? ($lead->payload['identity_number'] ?? null) : null;
                        }
                        $parts = [];
                        if ($phone) {
                            $parts[] = '📞 ' . $phone;
                        }
                        if ($cccd) {
                            $parts[] = 'CCCD: ' . $cccd;
                        }
                        return !empty($parts) ? implode(' · ', $parts) : null;
                    })
                    ->weight('semibold'),

                TextColumn::make('createdBy.name')
                    ->label('Nhân sự phụ trách')
                    ->getStateUsing(function (AffiliateConversion $record): string {
                        if ($record->createdBy) {
                            return $record->createdBy->name;
                        }
                        if ($record->aff_sub1) {
                            $u = User::where('employee_code', $record->aff_sub1)->first();
                            if ($u) return $u->name;
                            return $record->aff_sub1;
                        }
                        return 'Hệ thống';
                    })
                    ->description(fn (AffiliateConversion $record): ?string => $record->aff_sub1 ?: ($record->createdBy?->employee_code))
                    ->searchable(),

                TextColumn::make('sale_amount')
                    ->label('Doanh số duyệt')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '.')
                    ->suffix(' đ')
                    ->sortable()
                    ->placeholder('0 đ'),

                TextColumn::make('conversion_status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state, AffiliateConversion $record): string => AffiliateConversionStatus::tone($state, $record->sale_amount, $record->campaign_name))
                    ->formatStateUsing(fn (?string $state, AffiliateConversion $record): string => AffiliateConversionStatus::label($state, $record->sale_amount, $record->campaign_name)),

                TextColumn::make('conversion_time')
                    ->label('Thời gian ghi nhận')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('campaign_name')
                    ->label('Chiến dịch')
                    ->options([
                        'SHB Finance' => 'SHB Finance',
                        'VPBank UPL' => 'VPBank UPL',
                        'Tin Vay' => 'Tin Vay',
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
