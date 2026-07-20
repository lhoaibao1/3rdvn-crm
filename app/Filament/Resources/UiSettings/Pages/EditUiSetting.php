<?php

namespace App\Filament\Resources\UiSettings\Pages;

use App\Filament\Resources\UiSettings\UiSettingResource;
use App\Services\StalwartMailService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditUiSetting extends EditRecord
{
    protected static string $resource = UiSettingResource::class;

    protected function afterSave(): void
    {
        try {
            app(StalwartMailService::class)->configureOutboundRelay($this->record);

            if ($this->record->smtp_enabled) {
                Notification::make()
                    ->title('Đã cập nhật SMTP relay')
                    ->body('Module Mail và OTP đang dùng cấu hình SMTP vừa lưu.')
                    ->success()
                    ->send();
            }
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Không cập nhật được SMTP relay')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }

        $this->js('setTimeout(() => window.location.reload(), 900)');
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedEye),
            DeleteAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedTrash),
        ];
    }
}
