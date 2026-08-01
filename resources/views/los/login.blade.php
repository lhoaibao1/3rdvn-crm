<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập LOS · 3RDVN</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
</head>
<body>
    <x-auth.glass-shell title="Đăng nhập LOS" subtitle="Truy vấn hồ sơ">
        @if ($errors->any())
            <div class="crm-auth-error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="crm-auth-status" role="status">{{ session('status') }}</div>
        @endif

        <form class="crm-auth-form" method="POST" action="{{ route('los.login.store') }}">
            @csrf

            <label class="crm-auth-field">
                <span>User/UID</span>
                <input
                    type="text"
                    name="identifier"
                    value="{{ old('identifier') }}"
                    autocomplete="username"
                    placeholder="Nhập User/UID"
                    required
                    autofocus
                >
            </label>

            <label class="crm-auth-field">
                <span>Mật khẩu</span>
                <input
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    placeholder="Nhập mật khẩu"
                    required
                >
            </label>

            <div class="crm-auth-row">
                <label class="crm-auth-check">
                    <input type="hidden" name="remember" value="0">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ghi nhớ</span>
                </label>
            </div>

            <button class="crm-auth-button" type="submit">Đăng nhập</button>
        </form>
    </x-auth.glass-shell>
</body>
</html>
