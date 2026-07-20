@php($settings = $settings ?? \App\Models\UiSetting::current())
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->app_name }}</title>
    @php($faviconPath = $settings->favicon_path ? public_path('storage/'.$settings->favicon_path) : null)
    @php($faviconUrl = $settings->favicon_path ? asset('storage/'.$settings->favicon_path).(is_file($faviconPath) ? '?v='.filemtime($faviconPath) : '') : ($settings->favicon_url ?: asset('favicon.ico').'?v='.filemtime(public_path('favicon.ico'))))
    <link rel="icon" href="{{ $faviconUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-vars', ['settings' => $settings])
</head>
<body class="login-body">
    <section class="login-shell">
        <aside class="login-panel">
            <div class="brand">
                <div class="brand-mark">3</div>
                <div>
                    <strong>{{ $settings->logo_text ?: $settings->app_name }}</strong>
                    <span>Internal CRM</span>
                </div>
            </div>
            <div>
                <h1>{{ $settings->login_title ?: 'Đăng nhập 3RDVN CRM' }}</h1>
                <p>{{ $settings->login_subtitle ?: 'Quản lý lead, hồ sơ, phê duyệt và API mapping trong một hệ thống nội bộ.' }}</p>
            </div>
            <p>Secure session auth · RBAC · PostgreSQL</p>
        </aside>
        <main class="login-card">
            <h2>Đăng nhập</h2>
            <p>Dùng tài khoản được cấp để vào CRM.</p>
            @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
            <form class="form-stack" method="POST" action="{{ route('login.post') }}">
                @csrf
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                </label>
                <label class="field">
                    <span>Mật khẩu</span>
                    <input type="password" name="password" required>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
                <button class="primary-btn" type="submit">Đăng nhập</button>
            </form>
        </main>
    </section>
</body>
</html>
