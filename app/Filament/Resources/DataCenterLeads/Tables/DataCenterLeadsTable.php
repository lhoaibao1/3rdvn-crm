<?php

namespace App\Filament\Resources\DataCenterLeads\Tables;

use App\Filament\Resources\DataCenterLeads\DataCenterLeadResource;
use App\Forms\Components\SearchableSelect as Select;
use App\Models\DataCenterLead;
use App\Models\User;
use App\Support\DataCenter\DataCenterCsvImporter;
use App\Support\DataCenter\DataCenterLeadService;
use App\Support\DataCenter\DataCenterStatus;
use App\Support\Permissions\DataCenterAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DataCenterLeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'class' => 'crm-users-table crm-leads-table crm-data-center-table',
                'data-crm-column-table' => 'data-center',
            ], merge: true)
            ->recordUrl(fn (DataCenterLead $record): string => DataCenterLeadResource::getUrl('view', ['record' => $record]))
            ->poll('5s')
            ->searchable(false)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('referral_code')->label('Mã Lead Referral')->badge()->color('info')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Họ tên khách hàng')->weight('bold')->searchable()->sortable(),
                TextColumn::make('phone')->label('Số điện thoại')->searchable(),
                TextColumn::make('identity_number')->label('CCCD/CMND')->searchable()->toggleable(),
                TextColumn::make('source')->label('Nguồn')->placeholder('-')->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => DataCenterStatus::label($state))
                    ->color(fn (?string $state): string => DataCenterStatus::color($state))
                    ->sortable(),
                TextColumn::make('assignedUser.name')->label('Người xử lý')->placeholder('Chưa phân')->sortable(),
                TextColumn::make('teamLeader.name')->label('Team Leader')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('am.name')->label('AM')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zd.name')->label('ZD')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('conversions_count')
                    ->label('Đã chuyển')
                    ->state(fn (DataCenterLead $record): string => $record->conversions->count().'/2 dự án')
                    ->badge()
                    ->color(fn (DataCenterLead $record): string => $record->conversions->isEmpty() ? 'gray' : 'success'),
                TextColumn::make('contacted_at')->label('Lần gọi gần nhất')->dateTime('H:i d/m/Y')->placeholder('-')->sortable(),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->sortable(),
            ])
            ->filters([
                Filter::make('quick_lookup')
                    ->label('Tìm kiếm')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('Mã Lead Referral / Họ tên / SĐT / CCCD')
                            ->placeholder('Nhập thông tin cần tìm'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['keyword'] ?? null, fn (Builder $query, string $keyword): Builder => $query
                            ->where(function (Builder $query) use ($keyword): void {
                                $query->where('referral_code', 'ilike', "%{$keyword}%")
                                    ->orWhere('customer_name', 'ilike', "%{$keyword}%")
                                    ->orWhere('phone', 'ilike', "%{$keyword}%")
                                    ->orWhere('identity_number', 'ilike', "%{$keyword}%");
                            }))),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(DataCenterStatus::options())
                    ->native(false),
                SelectFilter::make('source')
                    ->label('Nguồn')
                    ->options(fn (): array => DataCenterLead::query()
                        ->whereNotNull('source')
                        ->where('source', '<>', '')
                        ->distinct()
                        ->orderBy('source')
                        ->pluck('source', 'source')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('assigned_user_id')
                    ->label('Người xử lý')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('team_leader_id')
                    ->label('Team Leader')
                    ->relationship('teamLeader', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('am_id')
                    ->label('AM')
                    ->relationship('am', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('zd_id')
                    ->label('ZD')
                    ->relationship('zd', 'name')
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
            ->filtersTriggerAction(fn (Action $action): Action => $action
                ->label('Bộ lọc')
                ->icon(Heroicon::OutlinedFunnel)
                ->button()
                ->color('gray'))
            ->filtersApplyAction(fn (Action $action): Action => $action
                ->label('Tìm kiếm')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->color('primary'))
            ->filtersRemoveAllAction(fn (Action $action): Action => $action
                ->label('Reset')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray'))
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->deferColumnManager()
            ->columnManagerTriggerAction(fn (Action $action): Action => $action
                ->label('Cột hiển thị')
                ->icon(Heroicon::OutlinedViewColumns)
                ->button()
                ->color('gray'))
            ->columnManagerApplyAction(fn (Action $action): Action => $action
                ->label('Áp dụng')
                ->color('primary'))
            ->columnManagerColumns(2)
            ->columnManagerMaxHeight('65vh')
            ->columnManagerWidth('4xl')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem')
                        ->url(fn (DataCenterLead $record): string => DataCenterLeadResource::getUrl('view', ['record' => $record])),
                    self::assignmentAction(),
                    self::resultAction(),
                    self::convertAction(),
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
                self::assignSelectedAction(),
                self::downloadTemplateAction(),
                self::importAction(),
            ]);
    }

    private static function assignmentAction(): Action
    {
        return Action::make('assignLeadReferral')
            ->label('Gán người xử lý')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->visible(fn (DataCenterLead $record): bool => DataCenterAccess::canDistribute(auth()->user())
                && DataCenterAccess::canView(auth()->user(), $record))
            ->modalHeading(fn (DataCenterLead $record): string => 'Gán xử lý '.$record->referral_code)
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Lưu phân công')
            ->modalCancelActionLabel('Hủy')
            ->schema(fn (DataCenterLead $record): array => [
                Radio::make('assignee_id')
                    ->label('Chọn nhân viên xử lý')
                    ->options(self::assignmentOptions())
                    ->default($record->assigned_user_id ?? 0)
                    ->columns(1)
                    ->required(),
            ])
            ->action(function (DataCenterLead $record, array $data): void {
                $assignee = self::resolveAssignee($data);

                if ($assignee instanceof User) {
                    DataCenterLeadService::assign($record, auth()->user(), $assignee);
                    $message = 'Đã gán Lead cho '.$assignee->name;
                } else {
                    DataCenterLeadService::unassign($record, auth()->user());
                    $message = 'Đã hủy phân công Lead';
                }

                Notification::make()->title($message)->success()->send();
            });
    }

    private static function assignSelectedAction(): BulkAction
    {
        return BulkAction::make('assignSelectedLeadReferrals')
            ->label('Gán Lead đã chọn')
            ->icon(Heroicon::OutlinedUserGroup)
            ->color('primary')
            ->visible(fn (): bool => DataCenterAccess::canDistribute(auth()->user()))
            ->modalHeading('Gán các Lead Referral đã chọn')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Lưu phân công')
            ->modalCancelActionLabel('Hủy')
            ->schema([
                Radio::make('assignee_id')
                    ->label('Chọn nhân viên xử lý')
                    ->options(self::assignmentOptions())
                    ->columns(1)
                    ->required(),
            ])
            ->action(function (Collection $records, array $data): void {
                $assignee = self::resolveAssignee($data);
                $count = DataCenterLeadService::assignMany($records, auth()->user(), $assignee);

                Notification::make()
                    ->title($assignee instanceof User
                        ? 'Đã gán '.$count.' Lead cho '.$assignee->name
                        : 'Đã hủy phân công '.$count.' Lead')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function assignmentOptions(): array
    {
        return [0 => 'None - Không phân công'] + DataCenterAccess::assignableUsers(auth()->user())
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->getKey() => DataCenterAccess::userLabel($user)])
            ->all();
    }

    private static function resolveAssignee(array $data): ?User
    {
        $assigneeId = (int) ($data['assignee_id'] ?? 0);

        if ($assigneeId === 0) {
            return null;
        }

        $assignee = User::query()->find($assigneeId);

        if (! $assignee instanceof User) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Vui lòng chọn nhân viên xử lý hợp lệ.',
            ]);
        }

        return $assignee;
    }

    private static function resultAction(): Action
    {
        return Action::make('updateCallResult')
            ->label('Cập nhật kết quả gọi')
            ->icon(Heroicon::OutlinedPhone)
            ->visible(fn (DataCenterLead $record): bool => DataCenterAccess::canUpdateResult(auth()->user(), $record)
                && ! in_array($record->status, [DataCenterStatus::CONVERTED], true))
            ->fillForm(fn (DataCenterLead $record): array => [
                'status' => in_array($record->status, array_keys(DataCenterStatus::resultOptions()), true)
                    ? $record->status
                    : DataCenterStatus::CONTACTED,
                'call_note' => $record->call_note,
            ])
            ->schema([
                Select::make('status')
                    ->label('Kết quả cuộc gọi')
                    ->options(DataCenterStatus::resultOptions())
                    ->native(false)
                    ->required(),
                Textarea::make('call_note')
                    ->label('Ghi chú xử lý')
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->action(function (DataCenterLead $record, array $data): void {
                DataCenterLeadService::updateResult($record, auth()->user(), $data);

                Notification::make()->title('Đã cập nhật kết quả gọi')->success()->send();
            });
    }

    private static function convertAction(): Action
    {
        return Action::make('convertProjects')
            ->label('Chuyển sang dự án')
            ->icon(Heroicon::OutlinedArrowRightCircle)
            ->color('success')
            ->visible(fn (DataCenterLead $record): bool => DataCenterAccess::canConvert(auth()->user(), $record))
            ->schema([
                Select::make('sales_project_ids')
                    ->label('Dự án chuyển đổi')
                    ->options(fn (DataCenterLead $record): array => DataCenterLeadService::projectOptions(auth()->user(), $record))
                    ->multiple()
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Có thể chọn một hoặc hai dự án. Mỗi dự án tạo một Lead riêng.'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Chuyển Lead Referral sang dự án')
            ->action(function (DataCenterLead $record, array $data): void {
                $created = DataCenterLeadService::convert($record, auth()->user(), $data['sales_project_ids'] ?? []);

                Notification::make()
                    ->title('Đã tạo '.count($created).' Lead dự án')
                    ->success()
                    ->send();
            });
    }

    private static function importAction(): Action
    {
        return Action::make('importLeadReferral')
            ->label('Nhập file')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('primary')
            ->visible(fn (): bool => DataCenterAccess::canDistribute(auth()->user()))
            ->schema([
                FileUpload::make('file')
                    ->label('File dữ liệu')
                    ->disk('local')
                    ->directory('imports/data-center')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->helperText('Chỉ nhận file Excel .xlsx theo đúng file mẫu. UID người xử lý bắt buộc trên từng dòng.')
                    ->required(),
            ])
            ->modalHeading('Nhập và phân bổ Lead Referral')
            ->action(function (array $data): void {
                $storedPath = is_array($data['file']) ? reset($data['file']) : $data['file'];
                $absolutePath = Storage::disk('local')->path((string) $storedPath);

                try {
                    $result = DataCenterCsvImporter::import(
                        $absolutePath,
                        auth()->user(),
                    );
                } finally {
                    Storage::disk('local')->delete((string) $storedPath);
                }

                Notification::make()
                    ->title('Đã nhập '.$result['created'].' dòng')
                    ->body($result['skipped'] > 0
                        ? 'Bỏ qua '.$result['skipped'].' dòng: '.implode(' | ', array_slice($result['errors'], 0, 5))
                        : 'Tất cả dữ liệu đã được gán theo UID trong file.')
                    ->status($result['skipped'] > 0 ? 'warning' : 'success')
                    ->send();
            });
    }

    private static function downloadTemplateAction(): Action
    {
        return Action::make('downloadLeadReferralTemplate')
            ->label('Tải file mẫu')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (): bool => DataCenterAccess::canDistribute(auth()->user()))
            ->url(fn (): string => route('lead-referral.import-template'));
    }
}
