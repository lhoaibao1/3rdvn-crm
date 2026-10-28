<?php

namespace App\Filament\Resources\FeolBridgeLogs;

use App\Filament\Resources\FeolBridgeLogs\Pages\ListFeolBridgeLogs;
use App\Models\Application;
use App\Models\RecordChangeLog;
use App\Support\Filament\AdminOnlyResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeolBridgeLogResource extends Resource
{
    use AdminOnlyResource;

    protected static function currentUserIsAdmin(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    protected static ?string $model = RecordChangeLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $slug = 'admin/feol-bridge-history';

    public static function getModelLabel(): string
    {
        return 'Lịch sử Node-RED';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lịch sử Node-RED';
    }

    public static function getNavigationLabel(): string
    {
        return 'Lịch sử Node-RED';
    }

    public static function getNavigationGroup(): ?string
    {
        return \App\Support\Filament\AdminNavigation::GROUP;
    }

    public static function getNavigationSort(): ?int
    {
        return 990;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('record_type', Application::class)
            ->where('action', 'like', 'feol_%')
            ->with(['record.feolIntegration', 'actor']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Thời gian')->dateTime('H:i:s d/m/Y')->sortable(),
                TextColumn::make('record.application_code')->label('Mã hồ sơ')->searchable(),
                TextColumn::make('record.applicant_name')->label('Khách hàng')->searchable()->weight('bold'),
                TextColumn::make('record.feolIntegration.partner_lead_id')->label('Partner Lead ID')->placeholder('-'),
                TextColumn::make('action')
                    ->label('Tác vụ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'feol_synced' => 'API cập nhật thành công',
                        'feol_sync_failed' => 'API trả lỗi',
                        default => str($state)->replace('_', ' ')->headline()->toString(),
                    })
                    ->color(fn (string $state): string => $state === 'feol_sync_failed' ? 'danger' : 'success'),
                TextColumn::make('api_actor')
                    ->label('Người xử lý')
                    ->state(fn (RecordChangeLog $record): string => $record->actor?->name ?: 'Hệ thống FEOL'),
                TextColumn::make('changes')
                    ->label('Chuyển bước')
                    ->state(function (RecordChangeLog $record): string {
                        $changes = (array) $record->changes;
                        $old = data_get($changes, 'sub_status.old') ?: data_get($changes, 'main_status.old') ?: '-';
                        $new = data_get($changes, 'sub_status.new') ?: data_get($changes, 'main_status.new') ?: null;

                        if ($record->action === 'feol_sync_failed') {
                            return 'Đồng bộ lỗi';
                        }

                        return filled($new) && $old !== $new ? $old.' → '.$new : 'Đã kiểm tra, chưa đổi bước';
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'lỗi') ? 'danger' : 'info'),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([])
            ->paginated([20, 50, 100])
            ->defaultPaginationPageOption(20);
    }

    public static function getPages(): array
    {
        return ['index' => ListFeolBridgeLogs::route('/')];
    }
}
