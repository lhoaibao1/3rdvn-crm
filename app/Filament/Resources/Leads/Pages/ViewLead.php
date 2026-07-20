<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Support\Filament\LeadDecisionAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return $this->record->lead_name ?: $this->record->lead_code;
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                LeadDecisionAction::make(fn (): Lead => $this->record),
                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->label('Cập nhật Lead')
                    ->visible(fn (): bool => auth()->user()?->can('update', $this->record) ?? false),
                DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash)
                    ->label('Xóa Lead')
                    ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
                Action::make('cancel')
                    ->icon(Heroicon::OutlinedXMark)
                    ->label('Hủy')
                    ->color('gray')
                    ->url(LeadResource::getUrl('index')),
            ])
                ->button()
                ->label('Hành động')
                ->icon(Heroicon::EllipsisHorizontal),
        ];
    }
}
