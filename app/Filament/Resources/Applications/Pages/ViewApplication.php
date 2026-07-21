<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Filament\AclMixDecisionAction;
use App\Support\Filament\LotteFinanceDecisionAction;
use App\Support\Filament\RecordAssignAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getTitle(): string
    {
        return $this->record->applicant_name ?: $this->record->application_code;
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }

    protected function getHeaderActions(): array
    {
        return [
            AclMixDecisionAction::make(),
            LotteFinanceDecisionAction::make(),
            RecordAssignAction::make('assignApplicationProcessor'),
            EditAction::make()
                ->label('Cập nhật thông tin')
                ->visible(fn (Application $record): bool => match ($record->salesProject?->slug) {
                    'acl-mix' => AclMixWorkflow::canEditData(auth()->user(), $record),
                    'lotte-finance' => LotteFinanceWorkflow::canEditData(auth()->user(), $record),
                    default => true,
                }),
            DeleteAction::make()
                ->label('Xóa')
                ->icon(Heroicon::OutlinedTrash)
                ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
        ];
    }
}
