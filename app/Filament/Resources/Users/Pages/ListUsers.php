<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return '';
    }


    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Hiện hoạt')
                ->query(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                    $query
                        ->where('employment_status', User::STATUS_ACTIVE)
                        ->orWhereNull('employment_status');
                })),
            'inactive' => Tab::make('Không hoạt động')
                ->query(fn (Builder $query): Builder => $query->whereIn('employment_status', [
                    User::STATUS_DEACTIVE,
                    'inactive',
                    'resigned',
                ])),
            'deleted' => Tab::make('Đã xoá')
                ->query(fn (Builder $query): Builder => $query->where('employment_status', User::STATUS_DELETED)),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'active';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
