<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Enums\FeDeeplinkStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Forms\Components\SearchableSelectFilter as SelectFilter;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\ApplicationFinancialData;
use App\Support\Applications\FeolSalesIdentity;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Applications\RequestFeolApplicationSync;
use App\Support\Filament\AclMixDecisionAction;
use App\Support\Filament\ApplicationDateInput;
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
        $publicRegistrationUrl = null;

        if ($projectSlug === 'fe-deeplink' && auth()->user()) {
            try {
                $publicRegistrationUrl = app(FeolSalesIdentity::class)->publicRegistrationUrl(auth()->user());
            } catch (\Throwable) {
                $publicRegistrationUrl = null;
            }
        }

        return $table
            ->extraAttributes([
                'class' => 'crm-users-table crm-applications-table'.($projectSlug === 'fe-deeplink' ? ' crm-feol-partner-table' : ''),
                'data-crm-column-table' => $columnTable,
            ], merge: true)
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
                    ->sortable()
                    ->visible($projectSlug !== 'fe-deeplink'),
                TextColumn::make('applicant_name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->weight('bold')
                    ->color('gray')
                    ->visible($projectSlug !== 'fe-deeplink'),
                TextColumn::make('phone')
                    ->label('SĐT')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable()
                    ->visible($projectSlug !== 'fe-deeplink'),
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
                    ->sortable()
                    ->visible($projectSlug !== 'fe-deeplink'),
                TextColumn::make('disbursed_at')
                    ->label('Ngày giải ngân')
                    ->state(fn (Application $record): mixed => ApplicationFinancialData::disbursedAt($record))
                    ->dateTime('d/m/Y')
                    ->placeholder('-')
                    ->visible($projectSlug !== 'fe-deeplink'),
                ...match ($projectSlug) {
                    'acl-mix' => self::aclMixSummaryColumns(),
                    'lotte-finance' => self::lotteFinanceDataColumns(),
                    'fe-deeplink' => self::feDeeplinkColumns(),
                    default => [],
                },
                TextColumn::make('assignedSale.name')
                    ->label('Người xử lý')
                    ->placeholder('-')
                    ->toggleable()
                    ->visible($projectSlug !== 'fe-deeplink'),
                TextColumn::make('createdBy.name')
                    ->label('Người tạo')
                    ->placeholder('-')
                    ->toggleable()
                    ->visible($projectSlug !== 'fe-deeplink'),
                TextColumn::make('team.name')
                    ->label('Team')
                    ->badge()
                    ->color('info')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable()
                    ->visible($projectSlug !== 'fe-deeplink'),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('H:i d/m/Y')
                    ->sortable()
                    ->visible($projectSlug !== 'fe-deeplink'),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('H:i d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($projectSlug !== 'fe-deeplink'),
            ]))
            ->filters($projectSlug === 'fe-deeplink' ? self::feDeeplinkFilters() : [
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
                        ApplicationDateInput::make('date', 'Từ ngày'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))),
                Filter::make('created_until')
                    ->label('Đến ngày')
                    ->schema([
                        ApplicationDateInput::make('date', 'Đến ngày'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ], layout: $projectSlug === 'fe-deeplink' ? FiltersLayout::AboveContent : FiltersLayout::Modal)
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
                    Action::make('copyFeDeeplink')
                        ->label('Copy Deeplink')
                        ->icon(Heroicon::OutlinedClipboardDocument)
                        ->visible(fn (Application $record): bool => $projectSlug === 'fe-deeplink'
                            && filled($record->feolIntegration?->deeplink_url))
                        ->actionJs(fn (Application $record): string => 'navigator.clipboard.writeText('.json_encode((string) $record->feolIntegration?->deeplink_url).').then(() => new FilamentNotification().title(\'Đã sao chép Deeplink\').success().send())'),
                    AclMixOtpAction::make(),
                    AclMixDecisionAction::make(),
                    LotteFinanceDecisionAction::make(),
                    Action::make('requestFeolSync')
                        ->label('Kiểm tra đối tác ngay')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->visible(fn (Application $record): bool => $projectSlug === 'fe-deeplink'
                            && (auth()->user()?->hasRole('Admin') ?? false))
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
                            default => auth()->user()?->hasRole('Admin') ?? false,
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
            ->recordActionsColumnLabel($projectSlug === 'fe-deeplink'
                ? new \Illuminate\Support\HtmlString('&nbsp;')
                : 'Hành động')
            ->toolbarActions([
                Action::make('createApplication')
                    ->label($projectSlug === 'fe-deeplink' ? 'Tạo khách hàng' : 'Tạo hồ sơ')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('primary')
                    ->url(fn (): string => $resourceClass::getUrl('create'))
                    ->visible(fn (): bool => $resourceClass::canCreate()),
                Action::make('copyFeRegistrationLink')
                    ->label('Copy link')
                    ->icon(Heroicon::OutlinedClipboardDocument)
                    ->color('gray')
                    ->outlined()
                    ->actionJs($publicRegistrationUrl
                        ? "navigator.clipboard.writeText('{$publicRegistrationUrl}').then(() => new FilamentNotification().title('Đã sao chép link đăng ký').success().send())"
                        : 'new FilamentNotification().title(\'Tài khoản chưa có mã bán hàng FE Deeplink hợp lệ\').warning().send()')
                    ->visible($projectSlug === 'fe-deeplink'),
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
                ->label('ID')
                ->placeholder('-')
                ->searchable()
                ->toggleable(),
            TextColumn::make('fe_campaign')
                ->label('Chiến dịch')
                ->state(fn (): string => (string) config('services.feol_bridge.partner_campaign_code', 'CTV_FEC_DL'))
                ->badge()
                ->toggleable(),
            TextColumn::make('fe_customer_name')
                ->label('Tên khách hàng')
                ->state(fn (Application $record): string => $record->applicant_name)
                ->weight('bold'),
            TextColumn::make('fe_customer_phone')
                ->label('Số điện thoại')
                ->state(fn (Application $record): ?string => $record->phone)
                ->placeholder('-'),
            TextColumn::make('fe_employee')
                ->label('Nhân viên')
                ->state(fn (Application $record): ?string => $record->createdBy?->name)
                ->placeholder('-'),
            TextColumn::make('fe_manager')
                ->label('Quản lý')
                ->state(fn (Application $record): ?string => $record->teamLeader?->name ?: $record->am?->name ?: $record->zd?->name)
                ->description(fn (Application $record): ?string => $record->teamLeader?->employee_code ?: $record->am?->employee_code ?: $record->zd?->employee_code)
                ->placeholder('-'),
            TextColumn::make('payload.fields.referral_code')
                ->label('Mã giới thiệu')
                ->placeholder('-'),
            TextColumn::make('feolIntegration.main_status')
                ->label('Trạng thái chính')
                ->badge()
                ->placeholder('-'),
            TextColumn::make('feolIntegration.sub_status')
                ->label('Trạng thái phụ')
                ->badge()
                ->formatStateUsing(fn (mixed $state): string => FeDeeplinkStatus::labelFor($state instanceof FeDeeplinkStatus ? $state->value : (string) $state))
                ->color(fn (mixed $state): string => FeDeeplinkStatus::colorFor($state instanceof FeDeeplinkStatus ? $state->value : (string) $state))
                ->placeholder('-'),
            TextColumn::make('feolIntegration.partner_app_id')
                ->label('App id')
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('fe_product')
                ->label('App type')
                ->state(fn (Application $record): mixed => ApplicationFinancialData::product($record))
                ->badge()
                ->placeholder('-'),
            TextColumn::make('fe_approved_amount')
                ->label('Offer Amt')
                ->state(fn (Application $record): mixed => ApplicationFinancialData::approvedAmount($record))
                ->money('VND', locale: 'vi')
                ->placeholder('-'),
            TextColumn::make('payload.fields.disbursed_amount')
                ->label('Disbursed Amt')
                ->money('VND', locale: 'vi')
                ->placeholder('-'),
            TextColumn::make('fe_disbursed_at')
                ->label('Disbursed Date')
                ->state(fn (Application $record): mixed => ApplicationFinancialData::disbursedAt($record))
                ->date('d/m/Y')
                ->placeholder('-'),
            TextColumn::make('feolIntegration.last_synced_at')
                ->label('Thời gian cập nhật')
                ->dateTime('d/m/Y H:i:s')
                ->placeholder('-'),
        ];
    }

    /** @return array<int, Filter|SelectFilter> */
    private static function feDeeplinkFilters(): array
    {
        return [
            Filter::make('quick_lookup')
                ->label('Tìm nhanh')
                ->schema([
                    TextInput::make('keyword')
                        ->label('Tìm nhanh')
                        ->placeholder('LeadID, AppID, Họ tên KH, SĐT'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['keyword'] ?? null, fn (Builder $query, string $keyword): Builder => $query
                        ->where(function (Builder $query) use ($keyword): void {
                            $query
                                ->where('applicant_name', 'ilike', "%{$keyword}%")
                                ->orWhere('phone', 'ilike', "%{$keyword}%")
                                ->orWhereHas('feolIntegration', fn (Builder $query): Builder => $query
                                    ->where('partner_lead_id', 'ilike', "%{$keyword}%")
                                    ->orWhere('partner_app_id', 'ilike', "%{$keyword}%"));
                        }))),
            Filter::make('campaign')
                ->label('Chiến dịch')
                ->schema([
                    TextInput::make('value')
                        ->label('Chiến dịch')
                        ->default((string) config('services.feol_bridge.partner_campaign_name', 'FE - Cash Loan - Deeplink'))
                        ->disabled(),
                ]),
            Filter::make('partner')
                ->label('Đối tác')
                ->schema([
                    TextInput::make('value')
                        ->label('Đối tác')
                        ->default('3RDVN Fintech')
                        ->disabled(),
                ]),
            Filter::make('updated_period')
                ->label('Thời gian cập nhật')
                ->schema([
                    ApplicationDateInput::make('from', 'Từ ngày'),
                    ApplicationDateInput::make('until', 'Đến ngày'),
                ])
                ->columns(2)
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('updated_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('updated_at', '<=', $date))),
            SelectFilter::make('team_id')
                ->label('Nhóm')
                ->relationship('team', 'name')
                ->searchable()
                ->preload()
                ->native(false),
            SelectFilter::make('created_by_id')
                ->label('Nhân viên')
                ->relationship('createdBy', 'name')
                ->searchable()
                ->preload()
                ->native(false),
            SelectFilter::make('main_status')
                ->label('Trạng thái chính')
                ->options(fn (): array => \App\Models\FeolApplicationIntegration::query()
                    ->whereNotNull('main_status')
                    ->distinct()
                    ->orderBy('main_status')
                    ->pluck('main_status', 'main_status')
                    ->all())
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['value'] ?? null, fn (Builder $query, string $status): Builder => $query
                        ->whereHas('feolIntegration', fn (Builder $query): Builder => $query->where('main_status', $status))))
                ->searchable()
                ->native(false),
            SelectFilter::make('status')
                ->label('Trạng thái phụ')
                ->options(FeDeeplinkStatus::options())
                ->native(false),
            Filter::make('created_period')
                ->label('Thời gian tạo')
                ->schema([
                    ApplicationDateInput::make('from', 'Thời gian tạo từ'),
                    ApplicationDateInput::make('until', 'Thời gian tạo đến'),
                ])
                ->columns(2)
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
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
