<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\CrmTeam;
use App\Models\SalesChannel;
use App\Models\User;
use App\Support\Filament\TableColumnPreferences;
use App\Support\RoleHierarchy;
use App\Support\UserSpecOptions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'crm-users-table', 'data-crm-column-table' => 'users'], merge: true)
            ->poll('3s')
            ->searchable(false)
            ->striped()
            ->defaultSort('id', 'asc')
            ->columns(TableColumnPreferences::apply('users', [
                TextColumn::make('name')->label('Họ tên')->sortable()->weight('bold')->color('gray'),
                TextColumn::make('username')->label('Username')->sortable()->toggleable(),
                TextColumn::make('uid')->label('UID')->badge()->color('info')->sortable(),
                TextColumn::make('employee_code')->label('Employee Code')->badge()->color('primary')->sortable(),
                TextColumn::make('identity_number')->label('CCCD/CMND')->toggleable(),
                TextColumn::make('phone')->label('SĐT')->toggleable(),
                TextColumn::make('email')->label('Email')->toggleable(),
                TextColumn::make('roles.name')->label('Vai trò')->badge()->color('warning')->separator(','),
                TextColumn::make('department')
                    ->label('Phòng ban')
                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('department', $state))
                    ->toggleable(),
                TextColumn::make('office')
                    ->label('Office')
                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('office', $state))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('sales_channel')->label('Kênh')->badge()->color('success')->toggleable(),
                TextColumn::make('team_display')->label('Team')->state(fn (User $record): ?string => $record->team?->name ?: $record->managedTeam?->name)->badge()->color('info')->placeholder('-')->toggleable(),
                TextColumn::make('teamLeader.name')->label('Team Leader')->toggleable(),
                TextColumn::make('courierManager.name')->label('Courier Manager')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('am.name')->label('AM')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zd.name')->label('ZD')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_code')->label('Ngân hàng')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_of_birth')->label('Ngày sinh')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hire_date')->label('Ngày vào làm')->date('d/m/Y')->sortable()->toggleable(),
                TextColumn::make('employment_status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn (?string $state): string => UserSpecOptions::labelFor('employment_status', $state))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        User::STATUS_ACTIVE => 'success',
                        User::STATUS_DEACTIVE, 'inactive', 'resigned' => 'warning',
                        User::STATUS_DELETED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')->label('Tạo lúc')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Filter::make('quick_lookup')
                    ->label('Tìm kiếm')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('Username / UID / Employee / CCCD')
                            ->placeholder('Nhập mã cần tìm'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['keyword'] ?? null, fn (Builder $query, string $keyword): Builder => $query->where(function (Builder $query) use ($keyword): void {
                            $query
                                ->where('username', 'ilike', "%{$keyword}%")
                                ->orWhere('uid', 'ilike', "%{$keyword}%")
                                ->orWhere('employee_code', 'ilike', "%{$keyword}%")
                                ->orWhere('identity_number', 'ilike', "%{$keyword}%");
                        }))),
                SelectFilter::make('team_id')
                    ->label('Team')
                    ->options(fn (): array => CrmTeam::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('team_leader_id')
                    ->label('Team Leader')
                    ->options(fn (): array => UserSpecOptions::roleUsers('Team Leader'))
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('sales_channel')
                    ->label('Kênh')
                    ->options(fn (): array => SalesChannel::query()
                        ->where('is_active', true)
                        ->orderBy('channel_name')
                        ->pluck('channel_name', 'channel_name')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('office')
                    ->label('Office')
                    ->options(fn (): array => UserSpecOptions::offices())
                    ->searchable()
                    ->native(false),
                SelectFilter::make('department')
                    ->label('Phòng ban')
                    ->options(fn (): array => UserSpecOptions::departments())
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('employment_status')
                    ->label('Trạng thái')
                    ->options(fn (): array => UserSpecOptions::employmentStatuses())
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('role')
                    ->label('Vai trò')
                    ->relationship('roles', 'name')
                    ->options(fn (): array => Role::query()
                        ->whereIn('name', UserSpecOptions::primaryRoleNames())
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->all())
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
            ->columnManagerTriggerAction(fn (Action $action): Action => $action
                ->label('Cột')
                ->icon(Heroicon::OutlinedViewColumns)
                ->button())
            ->columnManagerColumns(1)
            ->columnManagerMaxHeight('28rem')
            ->columnManagerWidth('18rem')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                    EditAction::make()->label('Sửa'),
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
                Action::make('exportUsers')
                    ->label('Xuất báo cáo')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn () => response()->streamDownload(function (): void {
                        $out = fopen('php://output', 'w');
                        fwrite($out, "\xEF\xBB\xBF");

                        fputcsv($out, [
                            'UID',
                            'Employee Code',
                            'Username',
                            'Họ tên',
                            'Email',
                            'SĐT',
                            'CCCD/CMND/Hộ chiếu',
                            'Vai trò',
                            'Phòng ban',
                            'Office',
                            'Kênh',
                            'Team',
                            'Team Leader',
                            'AM',
                            'ZD',
                            'Trạng thái',
                            'Ngày sinh',
                            'Ngày vào làm',
                            'Ngày tạo',
                        ]);

                        RoleHierarchy::applyVisibilityScope(User::query()->with(['roles', 'team', 'teamLeader', 'am', 'zd']))
                            ->orderBy('id')
                            ->chunk(500, function ($users) use ($out): void {
                                foreach ($users as $user) {
                                    fputcsv($out, [
                                        $user->uid,
                                        $user->employee_code,
                                        $user->username,
                                        $user->name,
                                        $user->email,
                                        $user->phone,
                                        $user->identity_number,
                                        $user->roles->pluck('name')->join(', '),
                                        UserSpecOptions::labelFor('department', $user->department),
                                        UserSpecOptions::labelFor('office', $user->office),
                                        $user->sales_channel,
                                        $user->team?->name,
                                        $user->teamLeader?->name,
                                        $user->am?->name,
                                        $user->zd?->name,
                                        UserSpecOptions::labelFor('employment_status', $user->employment_status),
                                        $user->date_of_birth?->format('d/m/Y'),
                                        $user->hire_date?->format('d/m/Y'),
                                        $user->created_at?->format('d/m/Y H:i'),
                                    ]);
                                }
                            });

                        fclose($out);
                    }, 'users-'.now()->format('Ymd-His').'.csv')),
                Action::make('createUser')
                    ->visible(fn (): bool => RoleHierarchy::canCreateUsers())
                    ->label('Tạo người dùng')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('primary')
                    ->url(UserResource::getUrl('create')),
                BulkActionGroup::make([
                    BulkAction::make('markUsersDeleted')
                        ->label('Xóa đã chọn')
                        ->visible(fn (): bool => auth()->user()?->can('deleteAny', User::class) ?? false)
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Xóa người dùng đã chọn')
                        ->modalDescription('Người dùng sẽ được chuyển sang trạng thái Đã xoá, UID và Employee Code sẽ bị thu hồi. Dữ liệu hồ sơ vẫn được lưu lại.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->action(function (Collection $records): void {
                            $actor = auth()->user();
                            $count = 0;

                            $records->each(function (User $user) use ($actor, &$count): void {
                                if (! $actor?->can('delete', $user)) {
                                    return;
                                }

                                if ($user->employment_status === User::STATUS_DELETED) {
                                    return;
                                }

                                $user->markAccessDeleted();
                                $count++;
                            });

                            $notification = Notification::make()
                                ->title($count > 0 ? 'Đã chuyển người dùng sang trạng thái Đã xoá' : 'Không có người dùng nào đủ quyền để xoá');

                            ($count > 0 ? $notification->success() : $notification->warning())->send();
                        }),
                ]),
            ]);
    }
}
