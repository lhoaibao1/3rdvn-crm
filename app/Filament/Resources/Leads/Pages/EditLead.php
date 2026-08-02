<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return $this->record->lead_name ?: $this->record->lead_code;
    }

    public function getBreadcrumb(): string
    {
        return 'Sửa';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LeadForm::normalizeDataForSave($this->record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('saveLead')
                    ->icon(Heroicon::OutlinedCheck)
                    ->label('Lưu thay đổi')
                    ->action(fn () => $this->save()),
                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye)
                    ->label('Xem Lead'),
                DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash)
                    ->label('Xóa Lead'),
                RestoreAction::make()
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->label('Khôi phục Lead'),
                ForceDeleteAction::make()
                    ->icon(Heroicon::OutlinedArchiveBoxXMark)
                    ->label('Xóa vĩnh viễn'),
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

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
