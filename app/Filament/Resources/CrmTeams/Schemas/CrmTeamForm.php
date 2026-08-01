<?php

namespace App\Filament\Resources\CrmTeams\Schemas;

use App\Models\CrmTeam;
use App\Models\User;
use App\Support\RoleHierarchy;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CrmTeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Thông tin Team')
                    ->columnSpan(5)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên Team')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('code')
                            ->label('Mã Team')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->dehydrateStateUsing(fn (?string $state): string => Str::upper(trim((string) $state))),
                        Toggle::make('is_active')
                            ->label('Đang hoạt động')
                            ->default(true),
                        Select::make('manager_id')
                            ->label('Trưởng Team')
                            ->options(fn (): array => self::teamLeaderOptions())
                            ->getOptionLabelUsing(fn (mixed $value): ?string => self::userLabel(User::query()->find($value)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->columnSpanFull(),
                    ]),
                Section::make('Thành viên')
                    ->description('Chọn nhân viên thuộc Team. Khi lưu, Team Leader, AM và ZD của thành viên sẽ được đồng bộ theo Trưởng Team.')
                    ->columnSpan(7)
                    ->schema([
                        Select::make('member_ids')
                            ->label('Nhân viên trong Team')
                            ->options(fn (?CrmTeam $record): array => self::memberOptions($record))
                            ->afterStateHydrated(fn (Select $component, ?CrmTeam $record) => $component->state(
                                $record?->members()
                                    ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', RoleHierarchy::SALES_ROLES))
                                    ->pluck('id')
                                    ->all() ?? []
                            ))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->dehydrated(false)
                            ->helperText('Chỉ hiển thị Direct Sale, Telesale và CTV chưa thuộc Team khác.'),
                    ]),
            ]);
    }

    private static function teamLeaderOptions(): array
    {
        return User::role('Team Leader')
            ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->getKey() => self::userLabel($user)])
            ->all();
    }

    private static function memberOptions(?CrmTeam $record): array
    {
        return User::role(RoleHierarchy::SALES_ROLES)
            ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
            ->where(function (Builder $query) use ($record): void {
                $query->whereNull('team_id');

                if ($record instanceof CrmTeam) {
                    $query->orWhere('team_id', $record->getKey());
                }
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->getKey() => self::userLabel($user)])
            ->all();
    }

    private static function userLabel(?User $user): ?string
    {
        if (! $user instanceof User) {
            return null;
        }

        return collect([$user->name, $user->uid, $user->employee_code])->filter()->join(' · ');
    }
}
