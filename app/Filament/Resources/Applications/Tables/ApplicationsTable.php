<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Schemas\AclMixFields;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Filament\AclMixDecisionAction;
use App\Support\Filament\ProjectSchemaColumns;
use App\Support\Filament\RecordAssignAction;
use App\Support\Filament\TableColumnPreferences;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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
                TextColumn::make('application_code')->label('Mã hồ sơ')->badge()->color('info')->searchable()->sortable(),
                TextColumn::make('applicant_name')->label('Khách hàng')->searchable()->weight('bold')->color('gray'),
                TextColumn::make('phone')->label('SĐT')->searchable()->toggleable(),
                TextColumn::make('identity_number')->label('CCCD/CMND')->searchable()->toggleable(),
                TextColumn::make('salesProject.name')->label('Dự án')->badge()->color('primary')->toggleable(),
                TextColumn::make('lead.lead_code')->label('Lead ID')->badge()->color('gray')->placeholder('-')->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state): string => $projectSlug === 'acl-mix'
                        ? AclMixWorkflow::statusColor($state)
                        : match ($state) {
                            'processing' => 'info',
                            'pending_approval' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        })
                    ->formatStateUsing(fn (?string $state): string => $projectSlug === 'acl-mix'
                        ? AclMixWorkflow::statusLabel($state)
                        : self::statusLabel($state))
                    ->sortable(),
                TextColumn::make('assignedSale.name')->label('Người xử lý')->placeholder('-')->toggleable(),
                TextColumn::make('createdBy.name')->label('Người tạo')->placeholder('-')->toggleable(),
                TextColumn::make('team.name')->label('Team')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('teamLeader.name')->label('Team Leader')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('am.name')->label('AM')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zd.name')->label('ZD')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload.review.product')->label('Sản phẩm')->badge()->placeholder('-')->toggleable(),
                TextColumn::make('payload.review.pre_approved_amount')
                    ->label('Số tiền sơ bộ')
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((int) preg_replace('/\D+/', '', (string) $state), 0, ',', '.').' VNĐ' : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload.review.pre_approved_months')->label('Số tháng phê duyệt')->suffix(' tháng')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload.review.pre_approved_interest_rate')->label('Lãi suất phê duyệt')->suffix('%')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                ...($projectSlug === 'acl-mix' ? self::aclMixDataColumns() : []),
                ...ProjectSchemaColumns::forApplication($projectSlug, [
                    'customer_name', 'applicant_name', 'phone', 'identity_number',
                    'cccd', 'product', 'pre_approved_amount', 'pre_approved_months',
                    'pre_approved_interest_rate', 'status',
                ]),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Filter::make('quick_lookup')
                    ->label('Tìm kiếm')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('App / Lead / CCCD / SĐT')
                            ->placeholder('Nhập mã cần tìm'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['keyword'] ?? null, fn (Builder $query, string $keyword): Builder => $query->where(function (Builder $query) use ($keyword): void {
                            $query
                                ->where('application_code', 'ilike', "%{$keyword}%")
                                ->orWhere('applicant_name', 'ilike', "%{$keyword}%")
                                ->orWhere('phone', 'ilike', "%{$keyword}%")
                                ->orWhere('identity_number', 'ilike', "%{$keyword}%")
                                ->orWhereHas('lead', fn (Builder $query): Builder => $query->where('lead_code', 'ilike', "%{$keyword}%"));
                        }))),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options($projectSlug === 'acl-mix' ? AclMixWorkflow::statusOptions() : [
                        'processing' => 'Đang xử lý',
                        'pending_approval' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                    ])
                    ->native(false),
                SelectFilter::make('assigned_sale_id')
                    ->label('Sale')
                    ->relationship('assignedSale', 'name')
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
                    AclMixDecisionAction::make(),
                    RecordAssignAction::make('assignApplicationProcessor'),
                    DeleteAction::make()
                        ->label('Xóa')
                        ->icon(Heroicon::OutlinedTrash)
                        ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                    EditAction::make()
                        ->label('Cập nhật thông tin')
                        ->visible(fn (Application $record): bool => $projectSlug !== 'acl-mix'
                            || AclMixWorkflow::canEditData(auth()->user(), $record))
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
                        fputcsv($out, ['Mã hồ sơ', 'Lead ID', 'Khách hàng', 'SĐT', 'CCCD/CMND', 'Trạng thái', 'NVKD', 'Team', 'Team Leader', 'AM', 'ZD', 'Ngày tạo']);

                        $resourceClass::getEloquentQuery()
                            ->with(['lead', 'assignedSale', 'team', 'teamLeader', 'am', 'zd'])
                            ->orderByDesc('created_at')
                            ->chunk(500, function ($applications) use ($out): void {
                                foreach ($applications as $application) {
                                    fputcsv($out, [
                                        $application->application_code,
                                        $application->lead?->lead_code,
                                        $application->applicant_name,
                                        $application->phone,
                                        $application->identity_number,
                                        self::statusLabel($application->status),
                                        $application->assignedSale?->name,
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
            ]);
    }

    /** @return array<int, TextColumn> */
    private static function aclMixDataColumns(): array
    {
        $duplicateKeys = ['customer_name', 'cccd', 'phone'];

        return collect(AclMixFields::definitions())
            ->reject(fn (array $field): bool => in_array($field['field_key'], $duplicateKeys, true))
            ->map(function (array $field): TextColumn {
                $key = $field['field_key'];
                $displayKey = str_ends_with($key, '_province_code')
                    || str_ends_with($key, '_district_code')
                    || str_ends_with($key, '_ward_code')
                        ? substr($key, 0, -4).'name'
                        : $key;

                return TextColumn::make('payload.module_fields.'.$displayKey)
                    ->label($field['label'])
                    ->placeholder('-')
                    ->formatStateUsing(fn (mixed $state): string => match ($key) {
                        'disbursement_method' => match ($state) {
                            'agent' => 'Đại lý chi hộ',
                            'bank' => 'Tài khoản ngân hàng',
                            default => filled($state) ? (string) $state : '-',
                        },
                        default => filled($state) ? (string) $state : '-',
                    })
                    ->toggleable(isToggledHiddenByDefault: true);
            })
            ->values()
            ->all();
    }

    private static function statusLabel(?string $state): string
    {
        return match ($state) {
            'processing' => 'Đang xử lý',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }
}
