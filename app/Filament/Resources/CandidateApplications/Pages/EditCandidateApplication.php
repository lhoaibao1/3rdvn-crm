<?php

namespace App\Filament\Resources\CandidateApplications\Pages;

use App\Filament\Resources\CandidateApplications\CandidateApplicationResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCandidateApplication extends EditRecord
{
    protected static string $resource = CandidateApplicationResource::class;

    public function getTitle(): string
    {
        return $this->record->full_name ?: $this->record->application_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('save')
                    ->label('Lưu thay đổi')
                    ->icon(Heroicon::OutlinedCheck)
                    ->action(fn () => $this->save()),
                ViewAction::make()->label('Xem hồ sơ'),
                Action::make('cancel')
                    ->label('Hủy')
                    ->color('gray')
                    ->icon(Heroicon::OutlinedXMark)
                    ->url(CandidateApplicationResource::getUrl('index')),
            ])->button()->label('Hành động')->icon(Heroicon::EllipsisHorizontal),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
