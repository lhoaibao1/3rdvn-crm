<?php

namespace App\Filament\Resources\SaleProfiles\Tables;

use App\Filament\Resources\SaleProfiles\SaleProfileResource;
use App\Models\SaleProfile;
use App\Support\Filament\RecordAssignAction;
use App\Support\Filament\SaleProfileProcessAction;
use App\Support\Filament\TableColumnPreferences;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'crm-users-table crm-leads-table crm-sale-profiles-table', 'data-crm-column-table' => 'sale-profiles'], merge: true)
            ->recordAction(null)
            ->recordUrl(fn (SaleProfile $record): string => SaleProfileResource::getUrl('view', ['record' => $record]))
            ->poll(fn (mixed $livewire): ?string => empty($livewire->mountedActions ?? []) ? '5s' : null)
            ->searchable(false)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns(TableColumnPreferences::apply('sale-profiles', [
                TextColumn::make('id')->label('Mã hồ sơ')->formatStateUsing(fn ($state): string => 'HS #'.$state)->badge()->color('info')->sortable(),
                TextColumn::make('customer_name')->label('Khách hàng')->searchable()->weight('bold')->color('gray'),
                TextColumn::make('phone')->label('SĐT')->searchable()->toggleable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('identity_number')->label('CCCD/CMND')->searchable()->toggleable(),
                TextColumn::make('product_interest')->label('Sản phẩm')->searchable()->toggleable(),
                TextColumn::make('saleOwner.name')->label('Nhân viên bán hàng')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('processingOwner.name')->label('Người xử lý')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('team.name')->label('Team')->badge()->color('info')->placeholder('-')->toggleable(),
                TextColumn::make('sourceLead.lead_code')->label('Mã Lead')->placeholder('-')->badge()->color('gray')->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'rejected' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('processing_status')
                    ->label('Xử lý')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::processingLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'rejected' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('approval_status')
                    ->label('Phê duyệt')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::approvalLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->toggleable(),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')->label('Đã xóa')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Filter::make('quick_lookup')
                    ->label('Tìm kiếm')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('Mã hồ sơ / Lead / CCCD / SĐT')
                            ->placeholder('Nhập mã cần tìm'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['keyword'] ?? null, fn (Builder $query, string $keyword): Builder => $query->where(function (Builder $query) use ($keyword): void {
                            $query
                                ->whereRaw('CAST(id AS TEXT) ILIKE ?', ["%{$keyword}%"])
                                ->orWhere('customer_name', 'ilike', "%{$keyword}%")
                                ->orWhere('phone', 'ilike', "%{$keyword}%")
                                ->orWhere('email', 'ilike', "%{$keyword}%")
                                ->orWhere('identity_number', 'ilike', "%{$keyword}%")
                                ->orWhereHas('sourceLead', fn (Builder $query): Builder => $query->where('lead_code', 'ilike', "%{$keyword}%"));
                        }))),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'new' => 'Mới',
                        'processing' => 'Đang xử lý',
                        'completed' => 'Hoàn tất',
                        'rejected' => 'Từ chối',
                    ])
                    ->native(false),
                SelectFilter::make('processing_status')
                    ->label('Tình trạng xử lý')
                    ->options([
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'completed' => 'Hoàn tất',
                        'rejected' => 'Từ chối',
                    ])
                    ->native(false),
                SelectFilter::make('team_id')
                    ->label('Team')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('processing_owner_id')
                    ->label('Người xử lý')
                    ->relationship('processingOwner', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                Filter::make('created_from')
                    ->label('Từ ngày')
                    ->schema([
                        DatePicker::make('date')->label('Từ ngày')->displayFormat('d/m/Y')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))),
                Filter::make('created_until')
                    ->label('Đến ngày')
                    ->schema([
                        DatePicker::make('date')->label('Đến ngày')->displayFormat('d/m/Y')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
                TrashedFilter::make()->label('Đã xóa'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->filtersFormWidth('7xl')
            ->filtersResetActionPosition(FiltersResetActionPosition::Footer)
            ->deferFilters()
            ->filtersTriggerAction(fn (Action $action): Action => $action->label('Bộ lọc')->icon(Heroicon::OutlinedFunnel)->button()->color('gray'))
            ->filtersApplyAction(fn (Action $action): Action => $action->label('Tìm kiếm')->icon(Heroicon::OutlinedMagnifyingGlass)->color('primary'))
            ->filtersRemoveAllAction(fn (Action $action): Action => $action->label('Reset')->icon(Heroicon::OutlinedArrowPath)->color('gray'))
            ->columnManagerTriggerAction(fn (Action $action): Action => $action->label('Cột')->icon(Heroicon::OutlinedViewColumns)->button())
            ->columnManagerColumns(1)
            ->columnManagerMaxHeight('28rem')
            ->columnManagerWidth('18rem')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem')
                        ->url(fn (SaleProfile $record): string => SaleProfileResource::getUrl('view', ['record' => $record])),
                    RecordAssignAction::make('assignSaleProfileProcessor'),
                    SaleProfileProcessAction::make(),
                    EditAction::make()->label('Sửa')->icon(Heroicon::OutlinedPencilSquare),
                    DeleteAction::make()->label('Xóa')->icon(Heroicon::OutlinedTrash)->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ])
                    ->iconButton()
                    ->label('Hành động')
                    ->tooltip('Hành động')
                    ->color('gray')
                    ->size('sm')
                    ->dropdownPlacement('bottom-end')
                    ->icon(Heroicon::EllipsisVertical),
            ])
            ->toolbarActions([
                Action::make('exportSaleProfiles')
                    ->label('Xuất báo cáo')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn () => response()->streamDownload(function (): void {
                        $out = fopen('php://output', 'w');
                        fwrite($out, "\xEF\xBB\xBF");
                        fputcsv($out, ['Mã hồ sơ', 'Khách hàng', 'SĐT', 'Email', 'CCCD/CMND', 'Sản phẩm', 'NVKD', 'Người xử lý', 'Team', 'Mã Lead', 'Trạng thái', 'Xử lý', 'Phê duyệt', 'Ngày tạo']);

                        SaleProfileResource::getEloquentQuery()
                            ->with(['saleOwner', 'processingOwner', 'team', 'sourceLead'])
                            ->orderByDesc('created_at')
                            ->chunk(500, function ($profiles) use ($out): void {
                                foreach ($profiles as $profile) {
                                    fputcsv($out, [
                                        'HS #'.$profile->getKey(),
                                        $profile->customer_name,
                                        $profile->phone,
                                        $profile->email,
                                        $profile->identity_number,
                                        $profile->product_interest,
                                        $profile->saleOwner?->name,
                                        $profile->processingOwner?->name,
                                        $profile->team?->name,
                                        $profile->sourceLead?->lead_code,
                                        self::statusLabel($profile->status),
                                        self::processingLabel($profile->processing_status),
                                        self::approvalLabel($profile->approval_status),
                                        $profile->created_at?->format('d/m/Y H:i'),
                                    ]);
                                }
                            });
                        fclose($out);
                    }, 'ho-so-'.now()->format('Ymd-His').'.csv')),
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Xóa đã chọn'),
                    ForceDeleteBulkAction::make()->label('Xóa vĩnh viễn'),
                    RestoreBulkAction::make()->label('Khôi phục'),
                ]),
            ]);
    }

    private static function statusLabel(?string $state): string
    {
        return match ($state) {
            'new' => 'Mới',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn tất',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }

    private static function processingLabel(?string $state): string
    {
        return match ($state) {
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn tất',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }

    private static function approvalLabel(?string $state): string
    {
        return match ($state) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }
}
