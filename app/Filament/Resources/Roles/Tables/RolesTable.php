<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->columns([
                TextColumn::make('name')
                    ->label('Vai trò')
                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->label('Số quyền')
                    ->counts('permissions')
                    ->sortable(),
                TextColumn::make('permission_summary')
                    ->label('Theo module')
                    ->state(fn ($record): HtmlString => self::permissionSummary($record))
                    ->html()
                    ->wrap(),
                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem')->url(fn ($record): string => RoleResource::getUrl('view', ['record' => $record])),
                    EditAction::make()->label('Sửa'),
                    DeleteAction::make()->label('Xóa')->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
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
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Xóa đã chọn'),
                ]),
            ]);
    }

    public static function permissionSummary($record): HtmlString
    {
        $permissions = $record->permissions->pluck('name')->all();
        $groups = RoleForm::permissionOptions();
        $html = '<div style="display:flex;flex-wrap:wrap;gap:6px">';

        foreach ($groups as $module => $items) {
            $enabled = collect(array_keys($items))
                ->filter(fn (string $permission): bool => in_array($permission, $permissions, true))
                ->count();

            if ($enabled === 0) {
                continue;
            }

            $html .= '<span style="font-size:12px;border-radius:999px;padding:4px 8px;background:#eff6ff;color:#1d4ed8;font-weight:650">'.e($module).': '.$enabled.'/'.count($items).'</span>';
        }

        return new HtmlString($html.'</div>');
    }
}
