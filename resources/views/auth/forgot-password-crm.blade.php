@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN CRM');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $otpSent = (bool) ($otpSent ?? false);
    $maskedEmail = $maskedEmail ?? null;
    $identifier = $identifier ?? old('identifier');
@endphp

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt lại mật khẩu - {{ $settings->app_name ?: '3RDVN CRM' }}</title>
    @if ($settings->favicon_path)
        <link rel="icon" href="{{ asset('storage/'.$settings->favicon_path) }}">
    @endif
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: clamp(16px, 3vw, 34px);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 18% 18%, rgba(37, 99, 235, .14), transparent 30%),
                radial-gradient(circle at 84% 18%, rgba(14, 165, 233, .14), transparent 28%),
                linear-gradient(135deg, #f8fbff 0%, #eef4ff 48%, #f7fbff 100%);
            color: #0f172a;
        }
        .card {
            width: min(560px, 100%);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 26px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 26px 78px rgba(15, 23, 42, .14);
            padding: clamp(24px, 5vw, 42px);
            backdrop-filter: blur(20px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 26px;
            color: #0f172a;
            font-weight: 860;
        }
        .mark {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            color: #fff;
            font-weight: 900;
            overflow: hidden;
        }
        .mark img { width: 100%; height: 100%; object-fit: contain; padding: 7px; background: #fff; }
        h1 { margin: 0; font-size: clamp(1.7rem, 4vw, 2.2rem); line-height: 1.08; font-weight: 900; letter-spacing: 0; }
        p { margin: 10px 0 0; color: #64748b; line-height: 1.55; font-weight: 520; }
        form { margin-top: 24px; display: grid; gap: 15px; }
        label span { display: block; margin-bottom: 7px; color: #334155; font-size: .86rem; font-weight: 760; }
        input {
            width: 100%; min-height: 52px; border: 1px solid #dbe3ef; border-radius: 15px;
            background: #fff; padding: 0 15px; color: #0f172a; font-size: .98rem; font-weight: 620; outline: none;
        }
        input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37, 99, 235, .12); }
        button {
            min-height: 52px; border: 0; border-radius: 15px; background: #2563eb; color: #fff;
            font-size: .96rem; font-weight: 850; cursor: pointer; box-shadow: 0 16px 32px rgba(37, 99, 235, .22);
        }
        .secondary { background: #eef4ff; color: #1d4ed8; box-shadow: none; }
        .actions { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .back { display: inline-flex; margin-top: 18px; color: #2563eb; text-decoration: none; font-weight: 760; }
        .status, .error {
            margin-top: 16px; padding: 12px 14px; border-radius: 14px; font-size: .9rem; font-weight: 680; line-height: 1.45;
        }
        .status { background: #ecfdf5; color: #047857; }
        .error { background: #fef2f2; color: #dc2626; }
        .hint {
            margin-top: 14px; padding: 12px 14px; border-radius: 14px; background: #eff6ff;
            color: #1e40af; font-size: .9rem; font-weight: 680; line-height: 1.45;
        }
        .split { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 560px) { .split { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            <div class="mark">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $brandName }}">
                @else
                    3
                @endif
            </div>
            {{ $brandName }}
        </div>

        <h1>Đặt lại mật khẩu</h1>
        <p>Nhập tài khoản để nhận OTP qua email, sau đó tạo mật khẩu mới.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('crm.password.lookup') }}">
            @csrf
            <label>
                <span>User / UID / Employee Code / CCCD / SĐT / Email</span>
                <input name="identifier" value="{{ $identifier }}" autofocus required autocomplete="username">
            </label>
            <button type="submit">Gửi OTP</button>
        </form>

        @if ($otpSent)
            <div class="hint">OTP đã được gửi về {{ $maskedEmail ?: 'email đã đăng ký' }}.</div>
            <form method="POST" action="{{ route('crm.password.otp.reset') }}">
                @csrf
                <label>
                    <span>OTP</span>
                    <input name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code">
                </label>
                <div class="split">
                    <label>
                        <span>Mật khẩu mới</span>
                        <input type="password" name="password" required autocomplete="new-password">
                    </label>
                    <label>
                        <span>Xác nhận mật khẩu mới</span>
                        <input type="password" name="password_confirmation" required autocomplete="new-password">
                    </label>
                </div>
                <button type="submit">Đổi mật khẩu</button>
            </form>
        @endif

        <a class="back" href="{{ url('/authen/login') }}">Quay lại đăng nhập</a>
    </main>
</body>
</html>
