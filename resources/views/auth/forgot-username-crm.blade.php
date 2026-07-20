@php
    $settings = \App\Models\UiSetting::current();
@endphp

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quên tên đăng nhập - {{ $settings->app_name ?: '3RDVN CRM' }}</title>
    @if ($settings->favicon_path)
        <link rel="icon" href="{{ asset('storage/'.$settings->favicon_path) }}">
    @endif
</head>
<body>
    <x-auth.glass-shell
        title="Quên tên đăng nhập"
        subtitle="Tra cứu User/UID bằng thông tin đã đăng ký"
    >
        @if (session('status'))
            <div class="crm-auth-status" role="status">{{ session('status') }}</div>
        @endif

        <form class="crm-auth-form" method="POST" action="{{ route('crm.username.lookup') }}">
            @csrf
            <label class="crm-auth-field">
                <span>CCCD / SĐT / Email</span>
                <input
                    name="identifier"
                    value="{{ old('identifier') }}"
                    placeholder="Nhập thông tin tra cứu"
                    autocomplete="username"
                    autofocus
                    required
                >
                @error('identifier')
                    <div class="crm-auth-inline-error">{{ $message }}</div>
                @enderror
            </label>

            <button class="crm-auth-button" type="submit">Kiểm tra</button>

            <div class="crm-auth-row">
                <a class="crm-auth-link" href="{{ url('/authen/login') }}">Quay lại</a>
                <a class="crm-auth-link" href="{{ route('crm.password.request') }}">Quên mật khẩu?</a>
            </div>
        </form>
    </x-auth.glass-shell>
</body>
</html>
