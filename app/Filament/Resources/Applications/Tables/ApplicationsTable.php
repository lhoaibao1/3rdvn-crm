<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Enums\FeDeeplinkStatus;
use App\Enums\FeolSyncState;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Forms\Components\SearchableSelectFilter as SelectFilter;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\ApplicationFinancialData;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Applications\RequestFeolApplicationSync;
use App\Support\Filament\AclMixDecisionAction;
use App\Support\Filament\AclMixOtpAction;
use App\Support\Filament\LotteFinanceDecisionAction;
use App\Support\Filament\RecordAssignAction;
use App\Support\Filament\TableColumnPreferences;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApplicationsTable
{
    public static function configure(Table $table, string $projectSlug = 'acl-mix', string $columnTable = 'applications.acl-mix', string $exportPrefix = 'acl-mix', string $resourceClass = ApplicationResource::class): Table
    {
        return $table
            ->extraAttributes(['class' => 'crm-users-table crm-applications-table', 'data-crm-column-table' => $columnTable], merge: true)
            ->recordAction(null)
            ->recordUrl(fn (Application $record): string => $resourceClass::getUrl('view', ['record' => $record]))
            ->searchable(false)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns(TableColumnPreferences::apply($columnTable, [
                TextColumn::make('application_code')
                    ->label('Mã hồ sơ')
                    ->badge()
                    ->color('info')
                    ->placeholder('Chờ cập nhật')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applicant_name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->weight('bold')
                    ->color('gray'),
                TextColumn::make('phone')
                    ->label('SĐT')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state): string => match ($projectSlug) {
                        'acl-mix' => AclMixWorkflow::statusColor($state),
                        'lotte-finance' => LotteFinanceWorkflow::statusColor($state),
                        'fe-deeplink' => FeDeeplinkStatus::colorFor($state),
                        default => match ($state) {
                            'processing' => 'info',
                            'pending_approval' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        },
                    })
                    ->formatStateUsing(fn (?string $state): string => $projectSlug === 'fe-deeplink'
                        ? FeDeeplinkStatus::labelFor($state)
                        : self::statusLabel($state))
                    ->sortable(),
                TextColumn::make('disbursed_at')
                    ->label('Ngày giải ngân')
                    ->state(fn (Application $record): mixed => ApplicationFinancialData::disbursedAt($record))
                    ->dateTime('d/m/Y')
                    ->placeholder('-'),
                ...match ($projectSlug) {
                    'acl-mix' => self::aclMixSummaryColumns(),
                    'lotte-finance' => self::lotteFinanceDataColumns(),
                    'fe-deeplink' => self::feDeeplinkColumns(),
                    default => [],
                },
                TextColumn::make('assignedSale.name')
                    ->label('Người xử lý')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('createdBy.name')
                    ->label('Người tạo')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('team.name')
                    ->label('Team')
                    ->badge()
                    ->color('info')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('H:i d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Filter::make('quick_lookup')
                    ->label('Tìm kiếm')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('Mã hồ sơ / CCCD / SĐT')
                            ->placeholder('Nhập mã cần tìm'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['keyword'] ?? null, fn (Builder $query, string $keyword): Builder => $query->where(function (Builder $query) use ($keyword): void {
                            $query
                                ->where('application_code', 'ilike', "%{$keyword}%")
                                ->orWhere('applicant_name', 'ilike', "%{$keyword}%")
                                ->orWhere('phone', 'ilike', "%{$keyword}%")
                                ->orWhere('identity_number', 'ilike', "%{$keyword}%");
                        }))),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(match ($projectSlug) {
                        'acl-mix' => AclMixWorkflow::statusOptions(),
                        'lotte-finance' => LotteFinanceWorkflow::statusOptions(),
                        'fe-deeplink' => FeDeeplinkStatus::options(),
                        default => [
                            'processing' => 'Đang xử lý',
                            'pending_approval' => 'Chờ duyệt',
                            'approved' => 'Đã duyệt',
                            'rejected' => 'Từ chối',
                        ],
                    })
                    ->native(false),
                SelectFilter::make('assigned_sale_id')
                    ->label('Sale')
                    ->relationship('assignedSale', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('team_id')
                    ->label('Team')
                    ->relationship('team', 'name')
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
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(3)
            ->filtersFormWidth('4xl')
            ->filtersResetActionPosition(FiltersResetActionPosition::Footer)
            ->deferFilters()
            ->filtersTriggerAction(fn (Action $action): Action => $action->label('Bộ lọc')->icon(Heroicon::OutlinedFunnel)->button()->color('gray'))
            ->filtersApplyAction(fn (Action $action): Action => $action->label('Tìm kiếm')->icon(Heroicon::OutlinedMagnifyingGlass)->color('primary'))
            ->filtersRemoveAllAction(fn (Action $action): Action => $action->label('Reset')->icon(Heroicon::OutlinedArrowPath)->color('gray'))
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->deferColumnManager()
            ->columnManagerTriggerAction(fn (Action $action): Action => $action->label('Cột hiển thị')->icon(Heroicon::OutlinedViewColumns)->button()->color('gray'))
            ->columnManagerApplyAction(fn (Action $action): Action => $action->label('Áp dụng')->color('primary'))
            ->columnManagerColumns(2)
            ->columnManagerMaxHeight('65vh')
            ->columnManagerWidth('4xl')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem')
                        ->url(fn (Application $record): string => $resourceClass::getUrl('view', ['record' => $record])),
                    AclMixOtpAction::make(),
                    AclMixDecisionAction::make(),
                    LotteFinanceDecisionAction::make(),
                    Action::make('requestFeolSync')
                        ->label('Kiểm tra đối tác ngay')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->visible(fn (Application $record): bool => $projectSlug === 'fe-deeplink'
                            && (auth()->user()?->can('update', $record) ?? false))
                        ->action(function (Application $record): void {
                            app(RequestFeolApplicationSync::class)->handle($record);
                            Notification::make()->title('Đã đưa hồ sơ vào hàng đợi kiểm tra')->success()->send();
                        }),
                    RecordAssignAction::make('assignApplicationProcessor'),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->icon(Heroicon::OutlinedTrash)
                        ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                    EditAction::make()
                        ->label('Cập nhật thông tin')
                        ->visible(fn (Application $record): bool => match ($projectSlug) {
                            'acl-mix' => AclMixWorkflow::canEditData(auth()->user(), $record),
                            'lotte-finance' => LotteFinanceWorkflow::canEditData(auth()->user(), $record),
                            default => true,
                        })
                        ->url(fn (Application $record): string => $resourceClass::getUrl('edit', ['record' => $record])),
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
                Action::make('createApplication')
                    ->label('Tạo hồ sơ')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('primary')
                    ->url(fn (): string => $resourceClass::getUrl('create'))
                    ->visible(fn (): bool => $resourceClass::canCreate()),
                Action::make('exportApplications')
                    ->label('Xuất báo cáo')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn () => response()->streamDownload(function () use ($resourceClass): void {
                        $out = fopen('php://output', 'w');
                        fwrite($out, 'ï»¿');
                        fputcsv($out, ['App ID', 'Khách hàng', 'SĐT', 'Trạng thái', 'Ngày giải ngân', 'Sản phẩm', 'Số tiền duyệt', 'Tạo bởi', 'Team', 'Team Leader', 'AM', 'ZD', 'Ngày tạo']);

                        $resourceClass::getEloquentQuery()
                            ->with(['assignedSale', 'team', 'teamLeader', 'am', 'zd'])
                            ->orderByDesc('created_at')
                            ->chunk(500, function ($applications) use ($out): void {
                                foreach ($applications as $application) {
                                    fputcsv($out, [
                                        $application->application_code,
                                        $application->applicant_name,
                                        $application->phone,
                                        self::statusLabel($application->status),
                                        ApplicationFinancialData::disbursedAt($application)?->format('d/m/Y'),
                                        ApplicationFinancialData::product($application),
                                        ApplicationFinancialData::approvedAmount($application),
                                        $application->createdBy?->name,
                                        $application->team?->name,
                                        $application->teamLeader?->name,
                                        $application->am?->name,
                                        $application->zd?->name,
                                        $application->created_at?->format('d/m/Y H:i'),
                                    ]);
                                }
                            });
                        fclose($out);
                    }, $exportPrefix.'-'.now()->format('Ymd-His').'.csv')),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
                ])
                    ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
            ]);
    }

    /** @return array<int, TextColumn> */
    private static function feDeeplinkColumns(): array
    {
        return [
            TextColumn::make('feolIntegration.partner_lead_id')
                ->label('Lead ID đối tác')
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('feolIntegration.partner_app_id')
                ->label('App ID')
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('fe_product')
                ->label('Sản phẩm')
                ->state(fn (Application $record): mixed => ApplicationFinancialData::product($record))
                ->badge()
                ->placeholder('-'),
            TextColumn::make('fe_approved_amount')
                ->label('Số tiền duyệt')
                ->state(fn (Application $record): mixed => ApplicationFinancialData::approvedAmount($record))
                ->money('VND', locale: 'vi')
                ->placeholder('-'),
            TextColumn::make('feolIntegration.b1_url')
                ->label('Landing Page B1')
                ->formatStateUsing(fn (mixed $state): string => filled($state) ? 'Sao chép B1' : '-')
                ->copyable()
                ->copyMessage('Đã sao chép Landing Page B1')
                ->icon(Heroicon::OutlinedClipboardDocument)
                ->color('info')
                ->placeholder('-'),
            TextColumn::make('feolIntegration.deeplink_url')
                ->label('Deeplink')
                ->formatStateUsing(fn (mixed $state): string => filled($state) ? 'Sao chép Deeplink' : '-')
                ->copyable()
                ->copyMessage('Đã sao chép Deeplink')
                ->icon(Heroicon::OutlinedLink)
                ->color('success')
                ->placeholder('Chờ Eligible'),
            TextColumn::make('feolIntegration.sync_state')
                ->label('Đồng bộ')
                ->badge()
                ->formatStateUsing(fn (mixed $state): string => $state instanceof FeolSyncState ? $state->label() : FeolSyncState::tryFrom((string) $state)?->label() ?? '-')
                ->color(fn (mixed $state): string => match ($state instanceof FeolSyncState ? $state : FeolSyncState::tryFrom((string) $state)) {
                    FeolSyncState::SYNCED => 'success',
                    FeolSyncState::FAILED => 'danger',
                    FeolSyncState::POLLING => 'info',
                    default => 'warning',
                })
                ->placeholder('-'),
        ];
    }

    /** @return array<int, TextColumn> */
    private static function aclMixSummaryColumns(): array
    {
        return [
            TextColumn::make('payload.review.product')
                ->label('Sản phẩm')
                ->badge()
                ->placeholder('-'),
            TextColumn::make('payload.review.pre_approved_amount')
                ->label('Số tiền phê duyệt sơ bộ')
                ->formatStateUsing(fn (mixed $state): string => filled($state)
                    ? number_format((int) preg_replace('/\D+/', '', (string) $state), 0, ',', '.').' VNĐ'
                    : '-')
                ->placeholder('-'),
            TextColumn::make('payload.review.pre_approved_months')
                ->label('Thời hạn phê duyệt')
                ->formatStateUsing(fn (mixed $state): string => filled($state) ? $state.' tháng' : '-')
                ->placeholder('-'),
            TextColumn::make('payload.review.pre_approved_interest_rate')
                ->label('Lãi suất phê duyệt')
                ->formatStateUsing(fn (mixed $state): string => filled($state) ? $state.'%' : '-')
                ->placeholder('-'),
        ];
    }

    /** @return array<int, TextColumn> */
    private static function lotteFinanceDataColumns(): array
    {
        return [
            TextColumn::make('payload.fields.scheme_code')
                ->label('Scheme')
                ->badge()
                ->placeholder('-'),
            TextColumn::make('payload.fields.scheme_product')
                ->label('Sản phẩm')
                ->badge()
                ->placeholder('-'),
            TextColumn::make('payload.fields.loan_amount')
                ->label('Số tiền vay')
                ->money('VND', locale: 'vi')
                ->placeholder('-'),
            TextColumn::make('payload.review.maximum_limit')
                ->label('Hạn mức tối đa')
                ->money('VND', locale: 'vi')
                ->placeholder('-'),
            TextColumn::make('payload.review.approved_amount')
                ->label('Số tiền được phê duyệt')
                ->money('VND', locale: 'vi')
                ->placeholder('-'),
            TextColumn::make('lotte_interest_rate')
                ->label('Lãi suất')
                ->state(fn (Application $record): mixed => data_get($record->payload, 'review.estimated_interest_rate')
                    ?: data_get($record->payload, 'fields.scheme_interest_rate'))
                ->formatStateUsing(fn (mixed $state): string => filled($state) ? rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.').'%' : '-')
                ->placeholder('-'),
        ];
    }

    private static function statusLabel(?string $state): string
    {
        if (array_key_exists((string) $state, AclMixWorkflow::statusOptions())) {
            return AclMixWorkflow::statusLabel($state);
        }

        if (array_key_exists((string) $state, LotteFinanceWorkflow::statusOptions())) {
            return LotteFinanceWorkflow::statusLabel($state);
        }

        if (FeDeeplinkStatus::tryFrom((string) $state)) {
            return FeDeeplinkStatus::labelFor($state);
        }

        return match ($state) {
            'processing' => 'Đang xử lý',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }
}
