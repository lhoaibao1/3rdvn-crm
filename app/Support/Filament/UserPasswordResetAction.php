<?php

namespace App\Support\Filament;

use App\Actions\Users\ResetUserPassword;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class UserPasswordResetAction
{
    public static function make(): Action
    {
        return Action::make('resetLoginPassword')
            ->label('Reset mật khẩu')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false)
            ->requiresConfirmation()
            ->modalHeading('Reset mật khẩu đăng nhập')
            ->modalDescription('Mật khẩu của người dùng sẽ được đặt về mặc định: '.ResetUserPassword::defaultPassword())
            ->modalSubmitActionLabel('Reset mật khẩu')
            ->modalCancelActionLabel('Hủy')
            ->action(function (User $record): void {
                app(ResetUserPassword::class)->execute($record);

                Notification::make()
                    ->title('Đã reset mật khẩu')
                    ->body('Mật khẩu mặc định: '.ResetUserPassword::defaultPassword())
                    ->success()
                    ->send();
            });
    }
}
