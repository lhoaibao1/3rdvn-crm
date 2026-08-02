<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Forms\Components\SearchableSelectFilter as SelectFilter;
use App\Models\Lead;
use App\Support\Filament\ProjectSchemaColumns;
use App\Support\Filament\RecordAssignAction;
use App\Support\Filament\StableTablePolling;
use App\Support\Filament\TableColumnPreferences;
use App\Support\Permissions\LeadAccess;
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
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'crm-users-table crm-leads-table', 'data-crm-column-table' => 'leads'], merge: true)
            ->recordAction(null)
            ->recordUrl(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record]))
            ->poll(fn (mixed $livewire): ?string => StableTablePolling::interval($livewire))
            ->searchable(false)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns(TableColumnPreferences::apply('leads', [
                TextColumn::make('lead_code')->label('Lead ID')->badge()->color('info')->searchable()->sortable(),
                TextColumn::make('salesProject.name')->label('Dự án')->badge()->color('primary')->searchable()->sortable(),
                TextColumn::make('lead_name')->label('Khách hàng')->searchable()->weight('bold')->color('gray'),
                TextColumn::make('phone')->label('SĐT')->searchable()->toggleable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('payload.fields.identity_number')->label('CCCD/CMND')->toggleable(),
                TextColumn::make('source')->label('Nguồn')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignedSale.name')->label('Người xử lý')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('createdBy.name')->label('Người tạo')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('team.name')->label('Team')->badge()->color('info')->placeholder('-')->toggleable(),
                TextColumn::make('teamLeader.name')->label('Team Leader')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('am.name')->label('AM')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zd.name')->label('ZD')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Khách hàng thoả mãn điều kiện' => 'success',
                        'Từ chối' => 'danger',
                        'Khách hàng bị trùng' => 'danger',
                        'Chờ kiểm tra' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state === 'Đã chuyển Application' ? 'Khách hàng thoả mãn điều kiện' : ($state ?: '-'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('application.application_code')->label('Mã hồ sơ')->badge()->color('success')->placeholder('-')->toggleable(),
                TextColumn::make('converted_at')->label('Đã chuyển')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                ...ProjectSchemaColumns::forLeads([
                    'customer_name', 'lead_name', 'phone', 'email', 'identity_number',
                    'cccd', 'status', 'source',
                ]),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')->label('Đã xóa')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Filter::make('quick_lookup')
                    ->label('Tìm kiếm')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('Lead / App / CCCD / SĐT')
                            ->placeholder('Nhập mã cần tìm'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['keyword'] ?? null, fn (Builder $query, string $keyword): Builder => $query->where(function (Builder $query) use ($keyword): void {
                            $query
                                ->where('lead_code', 'ilike', "%{$keyword}%")
                                ->orWhere('lead_name', 'ilike', "%{$keyword}%")
                                ->orWhere('phone', 'ilike', "%{$keyword}%")
                                ->orWhere('email', 'ilike', "%{$keyword}%")
                                ->orWhere('payload->fields->identity_number', 'ilike', "%{$keyword}%")
                                ->orWhereHas('application', fn (Builder $query): Builder => $query->where('application_code', 'ilike', "%{$keyword}%"));
                        }))),
                SelectFilter::make('sales_project_id')
                    ->label('Dự án')
                    ->options(fn (): array => LeadAccess::projectOptions(auth()->user()))
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'Chờ kiểm tra' => 'Chờ kiểm tra',
                        'Khách hàng thoả mãn điều kiện' => 'Khách hàng thoả mãn điều kiện',
                        'Từ chối' => 'Từ chối',
                        'Khách hàng bị trùng' => 'Khách hàng bị trùng',
                    ])
                    ->native(false),
                SelectFilter::make('team_id')
                    ->label('Team')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload()
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
                TrashedFilter::make()->label('Đã xóa'),
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
                        ->url(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record])),
                    RecordAssignAction::make('assignLeadProcessor'),
                    EditAction::make()
                        ->label('Sửa')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin'))
                        ->url(fn (Lead $record): string => LeadResource::getUrl('edit', ['record' => $record])),
                    DeleteAction::make()
                        ->label('Xóa Lead')
                        ->icon(Heroicon::OutlinedTrash)
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
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
                Action::make('exportLeads')
                    ->label('Xuất báo cáo')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn () => response()->streamDownload(function (): void {
                        $out = fopen('php://output', 'w');
                        fwrite($out, 'ï»¿');
                        fputcsv($out, ['Lead ID', 'Dự án', 'Khách hàng', 'SĐT', 'Email', 'CCCD/CMND', 'NVKD', 'Team', 'Team Leader', 'AM', 'ZD', 'Trạng thái', 'Mã hồ sơ', 'Ngày tạo']);

                        LeadResource::getEloquentQuery()
                            ->with(['salesProject', 'application', 'assignedSale', 'team', 'teamLeader', 'am', 'zd'])
                            ->orderByDesc('created_at')
                            ->chunk(500, function ($leads) use ($out): void {
                                foreach ($leads as $lead) {
                                    fputcsv($out, [
                                        $lead->lead_code,
                                        $lead->salesProject?->name,
                                        $lead->lead_name,
                                        $lead->phone,
                                        $lead->email,
                                        data_get($lead->payload, 'fields.identity_number'),
                                        $lead->assignedSale?->name,
                                        $lead->team?->name,
                                        $lead->teamLeader?->name,
                                        $lead->am?->name,
                                        $lead->zd?->name,
                                        $lead->status === 'Đã chuyển Application' ? 'Khách hàng thoả mãn điều kiện' : $lead->status,
                                        $lead->application?->application_code,
                                        $lead->created_at?->format('d/m/Y H:i'),
                                    ]);
                                }
                            });
                        fclose($out);
                    }, 'leads-'.now()->format('Ymd-His').'.csv')),
                self::createLeadAction(),
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Xóa đã chọn')->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
                    ForceDeleteBulkAction::make()->label('Xóa vĩnh viễn')->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
                    RestoreBulkAction::make()->label('Khôi phục')->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
                ]),
            ]);
    }

    private static function createLeadAction(): ActionGroup
    {
        $projectActions = [];

        foreach (LeadAccess::projectOptions(auth()->user()) as $projectId => $projectName) {
            $projectActions[] = Action::make('createLeadProject'.$projectId)
                ->label($projectName)
                ->icon(Heroicon::OutlinedFolderOpen)
                ->url(fn (): string => LeadResource::getUrl('create', ['project' => $projectId]));
        }

        return ActionGroup::make($projectActions)
            ->label('Tạo Lead')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->button()
            ->color('primary')
            ->dropdownPlacement('bottom-end')
            ->visible($projectActions !== []);
    }

    public static function saveAsAdmin(Lead $record, array $data): Lead
    {
        $data = LeadForm::normalizeDataForSave($record, $data);
        $manualTimestamps = array_intersect_key($data, array_flip(['created_at', 'updated_at']));
        unset($data['created_at'], $data['updated_at']);

        $record->update($data);

        if (auth()->user()?->hasRole('Admin') && $manualTimestamps !== []) {
            $usesTimestamps = $record->timestamps;
            $record->timestamps = false;
            $record->forceFill($manualTimestamps)->save();
            $record->timestamps = $usesTimestamps;
        }

        return $record->refresh();
    }
}
