<?php

namespace App\Filament\Resources\UiSettings\Pages;

use App\Filament\Resources\UiSettings\UiSettingResource;
use App\Services\StalwartMailService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateUiSetting extends CreateRecord
{
    protected static string $resource = UiSettingResource::class;

    protected function afterCreate(): void
    {
        try {
            app(StalwartMailService::class)->configureOutboundRelay($this->record);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Không cập nhật được SMTP relay')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
