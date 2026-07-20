<?php

namespace App\Filament\Pages;

use App\Services\StalwartMailService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePassword extends Page
{
    protected static ?string $slug = 'change-password';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.change-password';

    public ?string $current_password = null;

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public function getTitle(): string
    {
        return 'Thay đổi mật khẩu';
    }

    public function save(): void
    {
        $data = $this->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.min' => 'Mật khẩu mới tối thiểu 8 ký tự.',
            'password.mixed' => 'Mật khẩu mới cần có chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu mới cần có số.',
        ]);

        $user = auth()->user();
        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();
        app(StalwartMailService::class)->scheduleCredentialSync($user, $data['password']);

        $this->reset('current_password', 'password', 'password_confirmation');

        Notification::make()
            ->title('Đã thay đổi mật khẩu')
            ->success()
            ->send();
    }
}
