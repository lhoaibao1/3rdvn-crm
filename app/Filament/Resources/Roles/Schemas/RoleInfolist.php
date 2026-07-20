<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin vai trò')->schema([
                    TextEntry::make('name')->label('Tên vai trò'),
                    TextEntry::make('guard_name')->label('Guard'),
                    TextEntry::make('created_at')->label('Tạo lúc')->dateTime()->placeholder('-'),
                    TextEntry::make('updated_at')->label('Cập nhật')->dateTime()->placeholder('-'),
                ])->columns(2),
                Section::make('Ma trận quyền theo module')->schema([
                    TextEntry::make('permission_matrix')
                        ->hiddenLabel()
                        ->state(fn ($record): HtmlString => self::permissionMatrix($record))
                        ->html(),
                ]),
            ]);
    }

    public static function permissionMatrix($record): HtmlString
    {
        $permissions = $record->permissions->pluck('name')->all();
        $groups = RoleForm::permissionOptions();
        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">';

        foreach ($groups as $module => $items) {
            $html .= '<div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff"><div style="font-weight:750;color:#0f172a;margin-bottom:8px">'.e($module).'</div><div style="display:flex;flex-wrap:wrap;gap:6px">';

            foreach ($items as $permission => $label) {
                $enabled = in_array($permission, $permissions, true);
                $html .= '<span style="font-size:12px;border-radius:999px;padding:4px 8px;background:'.($enabled ? '#dcfce7' : '#f1f5f9').';color:'.($enabled ? '#166534' : '#94a3b8').'">'.e($label).'</span>';
            }

            $html .= '</div></div>';
        }

        return new HtmlString($html.'</div>');
    }
}
