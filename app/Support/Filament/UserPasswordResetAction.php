<?php

namespace App\Support\Filament;

use App\Actions\Users\ResetUserPassword;
use App\Models\User;
use App\Services\StalwartMailService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserPasswordResetAction
{
    public static function make(): Action
    {
        return Action::make('resetLoginPassword')
            ->label('Đổi / Reset mật khẩu')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->visible(fn (): bool => auth()->user()?->hasRole(['Admin', 'Super Admin', 'Director', 'General Manager']) ?? false)
            ->modalHeading(fn (User $record): string => "Đổi mật khẩu cho {$record->name}")
            ->modalDescription('Nhập mật khẩu mới bên dưới (hoặc để trống để đặt về mật khẩu mặc định).')
            ->modalSubmitActionLabel('Lưu mật khẩu')
            ->modalCancelActionLabel('Hủy')
            ->form([
                TextInput::make('new_password')
                    ->label('Mật khẩu mới')
                    ->password()
                    ->revealable()
                    ->placeholder('Để trống nếu muốn đặt về mặc định (' . ResetUserPassword::defaultPassword() . ')')
                    ->helperText('Tối thiểu 6 ký tự. Nếu để trống sẽ dùng: ' . ResetUserPassword::defaultPassword())
                    ->minLength(6),
            ])
            ->action(function (User $record, array $data): void {
                $customPassword = trim((string) ($data['new_password'] ?? ''));
                $finalPassword = $customPassword !== '' ? $customPassword : ResetUserPassword::defaultPassword();

                DB::table('users')->where('id', $record->id)->update([
                    'password' => Hash::make($finalPassword),
                    'updated_at' => now(),
                ]);

                try {
                    app(StalwartMailService::class)->scheduleCredentialSync($record, $finalPassword);
                } catch (\Throwable $e) {}

                Notification::make()
                    ->title("Đã cập nhật mật khẩu cho {$record->name}")
                    ->body($customPassword !== '' ? 'Đã đổi mật khẩu tùy chỉnh thành công.' : 'Mật khẩu đã được đặt về mặc định: ' . ResetUserPassword::defaultPassword())
                    ->success()
                    ->send();
            });
    }
}
