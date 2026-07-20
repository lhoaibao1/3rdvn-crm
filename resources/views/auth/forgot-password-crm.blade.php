@php
    $settings = \App\Models\UiSetting::current();
    $otpSent = (bool) ($otpSent ?? false);
    $maskedEmail = $maskedEmail ?? null;
    $identifier = $identifier ?? old('identifier');
    $pageTitle = $otpSent ? 'Xác nhận OTP' : 'Quên mật khẩu';
    $pageSubtitle = $otpSent
        ? 'Nhập mã OTP và tạo mật khẩu mới'
        : 'Nhập User/UID để nhận mã xác nhận';
@endphp

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} - {{ $settings->app_name ?: '3RDVN CRM' }}</title>
    @if ($settings->favicon_path)
        <link rel="icon" href="{{ asset('storage/'.$settings->favicon_path) }}">
    @endif
</head>
<body>
    <x-auth.glass-shell :title="$pageTitle" :subtitle="$pageSubtitle">
        @if (session('status'))
            <div class="crm-auth-status" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="crm-auth-error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if (! $otpSent)
            <form class="crm-auth-form" method="POST" action="{{ route('crm.password.lookup') }}">
                @csrf
                <label class="crm-auth-field">
                    <span>User/UID</span>
                    <input
                        name="identifier"
                        value="{{ $identifier }}"
                        placeholder="Nhập User/UID"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </label>

                <button class="crm-auth-button" type="submit">Gửi OTP</button>

                <div class="crm-auth-row">
                    <a class="crm-auth-link" href="{{ url('/authen/login') }}">Quay lại</a>
                    <a class="crm-auth-link" href="{{ route('crm.username.request') }}">Quên tên đăng nhập?</a>
                </div>
            </form>
        @else
            <div class="crm-auth-hint" role="status">
                OTP đã gửi tới {{ $maskedEmail ?: 'email đã đăng ký' }}.
            </div>

            <form class="crm-auth-form" method="POST" action="{{ route('crm.password.otp.reset') }}">
                @csrf
                <label class="crm-auth-field">
                    <span>Mã OTP</span>
                    <input
                        name="otp"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        placeholder="Nhập 6 chữ số"
                        autocomplete="one-time-code"
                        autofocus
                        required
                    >
                </label>

                <label class="crm-auth-field">
                    <span>Mật khẩu mới</span>
                    <input
                        type="password"
                        name="password"
                        placeholder="Nhập mật khẩu mới"
                        autocomplete="new-password"
                        required
                    >
                </label>

                <label class="crm-auth-field">
                    <span>Xác nhận mật khẩu</span>
                    <input
                        type="password"
                        name="password_confirmation"
                        placeholder="Nhập lại mật khẩu mới"
                        autocomplete="new-password"
                        required
                    >
                </label>

                <button class="crm-auth-button" type="submit">Đổi mật khẩu</button>
            </form>

            <div class="crm-auth-row" style="margin-top: 16px">
                <a class="crm-auth-link" href="{{ url('/authen/login') }}">Quay lại</a>
                <form method="POST" action="{{ route('crm.password.lookup') }}">
                    @csrf
                    <input type="hidden" name="identifier" value="{{ $identifier }}">
                    <button class="crm-auth-link-button" type="submit">Gửi lại OTP</button>
                </form>
            </div>
        @endif
    </x-auth.glass-shell>
</body>
</html>
