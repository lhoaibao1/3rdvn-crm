<?php

namespace App\Filament\Resources\HotLeads\Tables;

use App\Filament\Resources\HotLeads\HotLeadResource;
use App\Models\Lead;
use App\Models\User;
use App\Support\Assignments\RecordAssignment;
use App\Support\Filament\LeadCreate\LeadAddressFields;
use App\Support\Filament\RecordAssignAction;
use App\Support\Filament\TableColumnPreferences;
use App\Support\HotLeads\HotLeadConverter;
use App\Support\HotLeads\HotLeadStatus;
use App\Support\Permissions\HotLeadAccess;
use App\Support\SalesLineSnapshot;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class HotLeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'crm-users-table crm-leads-table crm-hot-leads-table', 'data-crm-column-table' => 'hot-leads'], merge: true)
            ->recordAction(null)
            ->recordUrl(fn (Lead $record): string => HotLeadResource::getUrl('view', ['record' => $record]))
            ->poll('3s')
            ->searchable(false)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns(TableColumnPreferences::apply('hot-leads', [
                TextColumn::make('lead_code')->label('Mã Lead')->badge()->color('info')->searchable()->sortable(),
                TextColumn::make('salesProject.name')->label('Dự án')->badge()->color('primary')->sortable()->toggleable(),
                TextColumn::make('lead_name')->label('Khách hàng')->searchable()->weight('bold')->color('gray'),
                TextColumn::make('phone')->label('SĐT')->searchable()->toggleable(),
                TextColumn::make('email')->label('Thư điện tử')->searchable()->toggleable(),
                TextColumn::make('payload.fields.identity_number')->label('CCCD/CMND')->toggleable(),
                TextColumn::make('payload.fields.date_of_birth')->label('Ngày sinh')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload.fields.product_interest')->label('Sản phẩm')->toggleable(),
                TextColumn::make('payload.fields.province_name')->label('Tỉnh/TP')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload.fields.district_name')->label('Quận/Huyện')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload.fields.ward_name')->label('Phường/Xã')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload.fields.address')->label('Địa chỉ chi tiết')->limit(36)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignedSale.name')->label('Người xử lý')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('createdBy.name')->label('Người tạo')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('team.name')->label('Nhóm')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('teamLeader.name')->label('Trưởng nhóm')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('am.name')->label('AM')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zd.name')->label('ZD')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state): string => HotLeadStatus::color($state))
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'Khách hàng thoả mãn điều kiện' => 'Thoả điều kiện',
                        'Từ chối' => 'Không thoả điều kiện',
                        default => $state ?: '-',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('converted_record')
                    ->label('Hồ sơ/Application')
                    ->state(fn (Lead $record): ?string => $record->application?->application_code ?: ($record->convertedSaleProfile ? 'HS #'.$record->convertedSaleProfile->getKey() : null))
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? (string) $state : '-')
                    ->badge()
                    ->color(fn (mixed $state): string => filled($state) ? 'success' : 'gray')
                    ->toggleable(),
                TextColumn::make('converted_at')->label('Đã chuyển')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Filter::make('quick_lookup')
                    ->label('Tìm kiếm')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('Lead / Hồ sơ / CCCD / SĐT')
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
                                ->orWhere('payload->fields->address', 'ilike', "%{$keyword}%")
                                ->orWhereHas('application', fn (Builder $query): Builder => $query->where('application_code', 'ilike', "%{$keyword}%"))
                                ->orWhereHas('convertedSaleProfile', fn (Builder $query): Builder => $query->where('id', (int) preg_replace('/\D+/', '', $keyword)));
                        }))),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(fn (): array => HotLeadStatus::options())
                    ->native(false),
                SelectFilter::make('assigned_sale_id')
                    ->label('Người xử lý')
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
                        ->url(fn (Lead $record): string => HotLeadResource::getUrl('view', ['record' => $record])),
                    RecordAssignAction::make('assignHotLeadProcessor'),
                    EditAction::make()
                        ->label('Sửa')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin'))
                        ->url(fn (Lead $record): string => HotLeadResource::getUrl('edit', ['record' => $record])),
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
                self::exportAction(),
                self::createAction(),
            ]);
    }

    private static function exportAction(): Action
    {
        return Action::make('exportHotLeads')
            ->label('Xuất báo cáo')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->action(fn () => response()->streamDownload(function (): void {
                $out = fopen('php://output', 'w');
                fwrite($out, 'ï»¿');
                fputcsv($out, ['Mã Lead', 'Dự án', 'Khách hàng', 'SĐT', 'Thư điện tử', 'CCCD/CMND', 'Ngày sinh', 'Sản phẩm', 'Địa chỉ chi tiết', 'Tỉnh/TP', 'Quận/Huyện', 'Phường/Xã', 'Người xử lý', 'Người tạo', 'Nhóm', 'Trưởng nhóm', 'AM', 'ZD', 'Trạng thái', 'Hồ sơ/Application', 'Ngày tạo']);

                HotLeadAccess::applyVisibleTo(
                    Lead::query()->with(['salesProject', 'assignedSale', 'createdBy', 'team', 'teamLeader', 'am', 'zd', 'application.salesProject', 'convertedSaleProfile']),
                    auth()->user()
                )
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
                                data_get($lead->payload, 'fields.date_of_birth'),
                                data_get($lead->payload, 'fields.product_interest'),
                                data_get($lead->payload, 'fields.address'),
                                data_get($lead->payload, 'fields.province_name'),
                                data_get($lead->payload, 'fields.district_name'),
                                data_get($lead->payload, 'fields.ward_name'),
                                $lead->assignedSale?->name,
                                $lead->createdBy?->name,
                                $lead->team?->name,
                                $lead->teamLeader?->name,
                                $lead->am?->name,
                                $lead->zd?->name,
                                $lead->status === 'Khách hàng thoả mãn điều kiện' ? 'Thoả điều kiện' : ($lead->status === 'Từ chối' ? 'Không thoả điều kiện' : $lead->status),
                                $lead->application?->application_code ?: ($lead->convertedSaleProfile ? 'HS #'.$lead->convertedSaleProfile->getKey() : null),
                                $lead->created_at?->format('d/m/Y H:i'),
                            ]);
                        }
                    });
                fclose($out);
            }, 'hot-leads-'.now()->format('Ymd-His').'.csv'));
    }

    private static function createAction(): Action
    {
        return Action::make('createHotLead')
            ->label('Tạo Lead nóng')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->color('primary')
            ->visible(fn (): bool => HotLeadAccess::canCreate(auth()->user()))
            ->url(fn (): string => HotLeadResource::getUrl('create'));
    }

    public static function createFormComponents(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('customer_name')->label('Họ tên')->required()->maxLength(255),
                TextInput::make('phone')->label('Số điện thoại')->tel()->required()->maxLength(30),
                TextInput::make('identity_number')->label('CCCD/CMND')->maxLength(30),
                TextInput::make('date_of_birth')
                    ->label('Ngày sinh')
                    ->mask('99/99/9999')
                    ->placeholder('dd/mm/yyyy')
                    ->rules(['nullable', 'date_format:d/m/Y'])
                    ->maxLength(10),
                TextInput::make('email')->label('Thư điện tử')->email()->maxLength(255),
                Select::make('product_interest')
                    ->label('Sản phẩm')
                    ->options([
                        'LĐTD' => 'LĐTD',
                        'Đi làm hưởng lương' => 'Đi làm hưởng lương',
                        'Khác' => 'Khác',
                    ])
                    ->searchable()
                    ->preload()
                    ->native(false),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(fn (): array => HotLeadStatus::options())
                    ->default(HotLeadStatus::PENDING_ASSIGNMENT)
                    ->native(false)
                    ->required(),
                ...LeadAddressFields::make(),
                Textarea::make('note')->label('Ghi chú')->rows(2)->columnSpanFull(),
            ]),
        ];
    }

    public static function createHotLead(array $data): Lead
    {
        $project = HotLeadAccess::project();

        if (! $project || ! HotLeadAccess::canCreate(auth()->user())) {
            throw ValidationException::withMessages([
                'customer_name' => 'Bạn chưa được phân quyền tạo Hot Lead.',
            ]);
        }

        $snapshot = SalesLineSnapshot::fromUser(auth()->user());
        $snapshot['assigned_sale_id'] = null;
        $assignee = RecordAssignment::autoAssigneeForProject($project, auth()->user());

        $payload = [
            'workflow' => [
                'stage' => HotLeadConverter::STAGE_HOT_LEAD,
            ],
            'fields' => [
                'customer_name' => $data['customer_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'identity_number' => $data['identity_number'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'email' => $data['email'] ?? null,
                'product_interest' => $data['product_interest'] ?? null,
                'address' => $data['address'] ?? null,
                'province_code' => $data['province_code'] ?? null,
                'province_name' => $data['province_name'] ?? null,
                'district_code' => $data['district_code'] ?? null,
                'district_name' => $data['district_name'] ?? null,
                'ward_code' => $data['ward_code'] ?? null,
                'ward_name' => $data['ward_name'] ?? null,
            ],
        ];

        $lead = Lead::query()->create(array_replace($snapshot, [
            'sales_project_id' => $project->getKey(),
            'lead_name' => $data['customer_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'source' => 'Lead nóng',
            'status' => $data['status'] ?? HotLeadStatus::PENDING_ASSIGNMENT,
            'note' => $data['note'] ?? null,
            'payload' => $payload,
        ]));

        if ($assignee instanceof User) {
            $lead = HotLeadConverter::promoteToLead($lead, auth()->user(), $assignee);
        }

        Notification::make()
            ->title($assignee ? 'Đã tạo và chuyển sang Lead' : 'Đã tạo Lead nóng')
            ->body($assignee
                ? 'Lead '.$lead->lead_code.' đã tự động phân cho '.$assignee->name.'.'
                : 'Lead nóng '.$lead->lead_code.' đang chờ phân bổ.')
            ->success()
            ->send();

        return $lead;
    }
}
