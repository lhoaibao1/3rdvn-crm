<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\StalwartMailService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Support\Enums\Width;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login-glass';

    public ?array $data = [
        'identifier' => '',
        'password' => '',
        'remember' => false,
    ];

    public function getMaxWidth(): Width|string|null
    {
        return '100%';
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $identifier = trim((string) ($this->data['identifier'] ?? ''));
        $password = (string) ($this->data['password'] ?? '');

        if ($identifier === '') {
            $this->addError('data.identifier', 'Vui lòng nhập User/UID.');

            return null;
        }

        if ($password === '') {
            $this->addError('data.password', 'Vui lòng nhập mật khẩu.');

            return null;
        }

        $user = $this->findUserByIdentifier($identifier);

        if (! $user) {
            throw ValidationException::withMessages([
                'data.identifier' => 'Thông tin đăng nhập không đúng.',
            ]);
        }

        if (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            throw ValidationException::withMessages([
                'data.identifier' => 'Tài khoản này hiện chưa được phép truy cập CRM.',
            ]);
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
                'data.password' => 'Thông tin đăng nhập không đúng.',
            ]);
        }

        session()->regenerate();
        app(StalwartMailService::class)->scheduleCredentialSync($user, $password);

        return app(LoginResponse::class);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Đăng nhập';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
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
