<?php

namespace App\Filament\Resources\UiSettings\Pages;

use App\Filament\Resources\UiSettings\UiSettingResource;
use App\Services\StalwartMailService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class EditUiSetting extends EditRecord
{
    protected static string $resource = UiSettingResource::class;

    private const SMTP_FIELDS = [
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
    ];

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged(self::SMTP_FIELDS)) {
            return;
        }

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
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->icon(Heroicon::OutlinedEye),
            DeleteAction::make()->icon(Heroicon::OutlinedTrash),
        ];
    }
}
