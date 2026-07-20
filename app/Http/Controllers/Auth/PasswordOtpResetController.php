<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StalwartMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Support\Mail\PasswordResetOtpMailer;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordOtpResetController extends Controller
{
    private const SESSION_USER_ID = 'password_reset_user_id';
    private const SESSION_IDENTIFIER = 'password_reset_identifier';
    private const SESSION_MASKED_EMAIL = 'password_reset_masked_email';
    private const OTP_TTL_MINUTES = 10;

    public function create(Request $request): View
    {
        return view('auth.forgot-password-crm', [
            'otpSent' => $request->session()->has(self::SESSION_USER_ID),
            'maskedEmail' => $request->session()->get(self::SESSION_MASKED_EMAIL),
            'identifier' => old('identifier', $request->session()->get(self::SESSION_IDENTIFIER, '')),
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'Vui lòng nhập UID, Employee Code, CCCD, số điện thoại hoặc email.',
        ]);

        $identifier = trim($data['identifier']);
        $limiterKey = 'password-reset-otp:'.Str::lower($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return back()
                ->withInput()
                ->withErrors(['identifier' => 'Bạn gửi OTP quá nhiều lần. Vui lòng thử lại sau '.RateLimiter::availableIn($limiterKey).' giây.']);
        }

        RateLimiter::hit($limiterKey, 300);

        $user = $this->findUser($identifier);

        if (! $user instanceof User || blank($user->email)) {
            $request->session()->forget([self::SESSION_USER_ID, self::SESSION_IDENTIFIER, self::SESSION_MASKED_EMAIL]);

            return back()
                ->withInput()
                ->with('status', 'Nếu thông tin hợp lệ, hệ thống sẽ gửi OTP về email đã đăng ký.');
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put($this->cacheKey($user), Hash::make($otp), now()->addMinutes(self::OTP_TTL_MINUTES));

        try {
            PasswordResetOtpMailer::send($user, $otp);
        } catch (Throwable $exception) {
            report($exception);

            $message = $exception instanceof ValidationException
                ? ($exception->errors()['identifier'][0] ?? 'Không gửi được OTP.')
                : 'Không gửi được OTP. Vui lòng kiểm tra cấu hình email hoặc thử lại sau.';

            return back()
                ->withInput()
                ->withErrors(['identifier' => $message]);
        }

        $request->session()->put([
            self::SESSION_USER_ID => $user->getKey(),
            self::SESSION_IDENTIFIER => $identifier,
            self::SESSION_MASKED_EMAIL => $this->maskEmail($user->email),
        ]);

        return back()
            ->withInput(['identifier' => $identifier])
            ->with('status', 'Đã gửi OTP về email '.$this->maskEmail($user->email).'. OTP có hiệu lực '.self::OTP_TTL_MINUTES.' phút.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'otp.required' => 'Vui lòng nhập OTP.',
            'otp.digits' => 'OTP gồm 6 số.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.min' => 'Mật khẩu mới tối thiểu 8 ký tự.',
            'password.mixed' => 'Mật khẩu mới cần có chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu mới cần có số.',
        ]);

        $userId = $request->session()->get(self::SESSION_USER_ID);
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user instanceof User) {
            return redirect('/authen/forgot-password')
                ->withErrors(['identifier' => 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng gửi OTP lại.']);
        }

        $otpHash = Cache::get($this->cacheKey($user));

        if (! is_string($otpHash) || ! Hash::check($data['otp'], $otpHash)) {
            return back()->withErrors(['otp' => 'OTP không đúng hoặc đã hết hạn.']);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();
        app(StalwartMailService::class)->scheduleCredentialSync($user, $data['password']);

        Cache::forget($this->cacheKey($user));
        $request->session()->forget([self::SESSION_USER_ID, self::SESSION_IDENTIFIER, self::SESSION_MASKED_EMAIL]);

        return redirect('/authen/login')->with('status', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập bằng mật khẩu mới.');
    }

    private function findUser(string $identifier): ?User
    {
        $normalizedPhone = preg_replace('/\D+/', '', $identifier) ?: $identifier;

        return User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('uid', $identifier)
            ->orWhere('employee_code', $identifier)
            ->orWhere('identity_number', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('phone', $normalizedPhone)
            ->first();
    }

    private function cacheKey(User $user): string
    {
        return 'password-reset-otp:user:'.$user->getKey();
    }

    private function mailBody(User $user, string $otp): string
    {
        return implode("
", [
            'Xin chào '.$user->name.',',
            '',
            'Mã OTP đặt lại mật khẩu 3RDVN CRM của bạn là: '.$otp,
            'OTP có hiệu lực trong '.self::OTP_TTL_MINUTES.' phút.',
            '',
            'Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.',
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return $email;
        }

        $visible = Str::substr($name, 0, min(2, max(1, Str::length($name))));

        return $visible.str_repeat('*', max(3, Str::length($name) - Str::length($visible))).'@'.$domain;
    }
}
