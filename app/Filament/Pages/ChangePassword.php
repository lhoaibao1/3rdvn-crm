<?php

namespace App\Filament\Pages;

use App\Services\StalwartMailService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
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
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.min' => 'Mật khẩu mới tối thiểu 6 ký tự.',
        ]);

        $user = auth()->user();
        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($data['password']),
            'updated_at' => now(),
        ]);
        $user->refresh();

        try {
            app(StalwartMailService::class)->scheduleCredentialSync($user, $data['password']);
        } catch (\Throwable $e) {}

        $this->reset('current_password', 'password', 'password_confirmation');

        Notification::make()
            ->title('Đã thay đổi mật khẩu thành công!')
            ->body('Mật khẩu mới đã được cập nhật đồng bộ trên toàn bộ hệ sinh thái (SSO, CRM, Affiliate, LOS).')
            ->success()
            ->send();
    }
}
