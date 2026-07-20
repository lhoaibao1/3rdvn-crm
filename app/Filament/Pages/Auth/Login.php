<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\StalwartMailService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login-glass';

    public ?array $data = [
        'identifier' => '',
        'password' => '',
        'remember' => false,
    ];

    public ?int $identifiedUserId = null;

    public function getMaxWidth(): Width | string | null
    {
        return '100%';
    }

    public function identify(): void
    {
        try {
            $this->rateLimit(8);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $identifier = trim((string) ($this->data['identifier'] ?? ''));

        if ($identifier === '') {
            $this->addError('data.identifier', 'Vui lòng nhập Username, UID, CCCD, SĐT, Employee Code hoặc email.');

            return;
        }

        $user = $this->findUserByIdentifier($identifier);

        if (! $user) {
            $this->addError('data.identifier', 'Không tìm thấy tài khoản phù hợp.');

            return;
        }

        if (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            $this->addError('data.identifier', 'Tài khoản này hiện chưa được phép truy cập CRM.');

            return;
        }

        $this->resetErrorBag();
        $this->identifiedUserId = $user->getKey();
        $this->data['password'] = '';
    }

    public function changeIdentifier(): void
    {
        $this->identifiedUserId = null;
        $this->data['password'] = '';
        $this->resetErrorBag();
    }

    public function authenticate(): ?LoginResponse
    {
        $user = $this->identifiedUser();

        if (! $user) {
            $this->identify();

            return null;
        }

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $password = (string) ($this->data['password'] ?? '');

        if ($password === '') {
            $this->addError('data.password', 'Vui lòng nhập mật khẩu.');

            return null;
        }

        $credentials = [
            'email' => $user->email,
            'password' => $password,
        ];

        $remember = (bool) ($this->data['remember'] ?? false);

        if (! Filament::auth()->attemptWhen($credentials, function (Authenticatable $user): bool {
            if (! ($user instanceof FilamentUser)) {
                return true;
            }

            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
        }, $remember)) {
            event(app(Failed::class, [
                'guard' => 'web',
                'user' => $user,
                'credentials' => $credentials,
            ]));

            throw ValidationException::withMessages([
                'data.password' => 'Mật khẩu không đúng.',
            ]);
        }

        session()->regenerate();
        app(StalwartMailService::class)->scheduleCredentialSync($user, $password);

        return app(LoginResponse::class);
    }

    public function identifiedUser(): ?User
    {
        if (! $this->identifiedUserId) {
            return null;
        }

        return User::query()->find($this->identifiedUserId);
    }

    public function identifiedUserAvatarUrl(): ?string
    {
        $user = $this->identifiedUser();

        if (! $user) {
            return null;
        }

        if ($user->avatar_path) {
            return Storage::url($user->avatar_path);
        }

        return filament()->getUserAvatarUrl($user);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Đăng nhập';
    }

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null;
    }

    private function findUserByIdentifier(string $identifier): ?User
    {
        $normalizedPhone = preg_replace('/\D+/', '', $identifier) ?: $identifier;

        return User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('name', 'ilike', "%{$identifier}%")
            ->orWhere('uid', $identifier)
            ->orWhere('employee_code', $identifier)
            ->orWhere('identity_number', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('phone', $normalizedPhone)
            ->first();
    }
}
