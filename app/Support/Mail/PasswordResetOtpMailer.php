<?php

namespace App\Support\Mail;

use App\Models\UiSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordResetOtpMailer
{
    public static function send(User $user, string $otp): void
    {
        $settings = UiSetting::current();
        $subject = self::render($settings->password_reset_mail_subject ?: self::defaultSubject(), $user, $otp);
        $body = self::render($settings->password_reset_mail_body ?: self::defaultBody(), $user, $otp);

        if ((bool) $settings->smtp_enabled) {
            self::configureSmtp($settings);

            try {
                Mail::mailer('crm_otp_smtp')->raw($body, function ($message) use ($user, $subject, $settings): void {
                    $message->to($user->email, $user->name)
                        ->from($settings->mail_from_address, $settings->mail_from_name ?: config('app.name'))
                        ->subject($subject);
                });
            } catch (Throwable $exception) {
                report($exception);

                throw ValidationException::withMessages([
                    'identifier' => 'Không gửi được OTP qua SMTP. Vui lòng kiểm tra Host, Port, bảo mật và tài khoản SMTP trong UAT.',
                ]);
            }

            return;
        }

        if (config('mail.default') === 'log') {
            throw ValidationException::withMessages([
                'identifier' => 'Chưa cấu hình SMTP gửi mail thật. Vui lòng vào UAT > Cài đặt giao diện > Mail/OTP để cấu hình.',
            ]);
        }

        Mail::raw($body, function ($message) use ($user, $subject, $settings): void {
            $message->to($user->email, $user->name)
                ->subject($subject);

            if (filled($settings->mail_from_address)) {
                $message->from($settings->mail_from_address, $settings->mail_from_name ?: config('app.name'));
            }
        });
    }

    public static function defaultSubject(): string
    {
        return 'OTP đặt lại mật khẩu 3RDVN CRM';
    }

    public static function defaultBody(): string
    {
        return implode("
", [
            'Xin chào {{name}},',
            '',
            'Mã OTP đặt lại mật khẩu {{app_name}} của bạn là: {{otp}}',
            'OTP có hiệu lực trong {{ttl}} phút.',
            '',
            'Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.',
        ]);
    }

    private static function configureSmtp(UiSetting $settings): void
    {
        $host = (string) config('services.stalwart.smtp_host');
        $port = (int) config('services.stalwart.smtp_port');
        $username = (string) config('services.stalwart.admin_email');
        $password = (string) config('services.stalwart.admin_password');

        if (
            $host === ''
            || $port < 1
            || $username === ''
            || $password === ''
            || blank($settings->mail_from_address)
        ) {
            throw ValidationException::withMessages([
                'identifier' => 'Máy chủ mail nội bộ chưa đủ cấu hình SMTP submission.',
            ]);
        }

        config([
            'mail.mailers.crm_otp_smtp' => [
                'transport' => 'smtp',
                'scheme' => 'smtp',
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'timeout' => 20,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ],
        ]);
    }

    private static function render(string $template, User $user, string $otp): string
    {
        $settings = UiSetting::current();
        $replacements = [
            '{{app_name}}' => $settings->app_name ?: config('app.name'),
            '{{name}}' => $user->name ?: 'bạn',
            '{{email}}' => $user->email ?: '',
            '{{uid}}' => $user->uid ?: '',
            '{{employee_code}}' => $user->employee_code ?: '',
            '{{otp}}' => $otp,
            '{{ttl}}' => '10',
        ];

        return strtr($template, $replacements);
    }
}
