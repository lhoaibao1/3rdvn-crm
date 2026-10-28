@php
    $brandName = '3RDVN Financial Holdings';
    $systemName = 'Hệ Thống SAPP LOS';
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Đăng Nhập | SAPP LOS - {{ $brandName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-base: #030712;
            --card-bg: rgba(15, 23, 42, 0.85);
            --border-glass: rgba(255, 255, 255, 0.12);
            --brand-primary: #059669;
            --brand-accent: #10b981;
            --brand-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --text-title: #ffffff;
            --text-body: #e2e8f0;
            --text-muted: #94a3b8;
            --radius-lg: 16px;
            --radius-md: 12px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 20px;
        }

        .ambient-orbs { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .orb { position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.45; }
        .orb-1 { top: -10%; left: 10%; width: 480px; height: 480px; background: radial-gradient(circle, rgba(16, 185, 129, 0.45) 0%, transparent 70%); }
        .orb-2 { bottom: -10%; right: 10%; width: 520px; height: 520px; background: radial-gradient(circle, rgba(59, 130, 246, 0.4) 0%, transparent 70%); }

        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 980px;
            min-height: 560px;
            background: var(--card-bg);
            border: 1px solid var(--border-glass);
            border-radius: 28px;
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7), 0 0 40px rgba(16, 185, 129, 0.15);
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            overflow: hidden;
        }

        .hero-section {
            background: linear-gradient(145deg, rgba(6, 95, 70, 0.4) 0%, rgba(15, 23, 42, 0.8) 100%);
            border-right: 1px solid var(--border-glass);
            padding: 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hero-top { display: flex; align-items: center; gap: 12px; }
        .brand-logo-img { width: 44px; height: 44px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); }
        .brand-title { font-family: 'Outfit', sans-serif; font-size: 19px; font-weight: 800; color: #fff; }
        .brand-subtitle { font-size: 11.5px; color: #34d399; font-weight: 600; }

        .hero-center { margin: 36px 0; }
        .hero-headline { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: #fff; line-height: 1.25; margin-bottom: 12px; }
        .gradient-accent { background: linear-gradient(135deg, #34d399 0%, #60a5fa 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-description { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; }

        .form-section {
            padding: 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #34d399;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .form-heading { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .form-subtext { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }

        .error-alert {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: var(--radius-md);
            font-size: 12.5px;
            margin-bottom: 16px;
        }

        .auth-form { display: flex; flex-direction: column; gap: 16px; }
        .input-group { display: flex; flex-direction: column; gap: 6px; }
        .input-label { font-size: 12px; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.4px; }

        .input-field-wrap { position: relative; display: flex; align-items: center; }
        .input-field-icon { position: absolute; left: 14px; color: #64748b; }

        .auth-input {
            width: 100%;
            height: 48px;
            background: rgba(10, 16, 32, 0.85);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 0 16px 0 44px;
            font-size: 14px;
            font-family: inherit;
            color: #fff;
            outline: none;
            transition: all 0.2s ease;
        }
        .auth-input:focus {
            border-color: #34d399;
            box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.2), 0 0 20px rgba(52, 211, 153, 0.25);
        }

        .btn-toggle-pw {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
        }

        .btn-submit-login {
            height: 48px;
            background: var(--brand-gradient);
            border: none;
            border-radius: var(--radius-md);
            color: #ffffff;
            font-size: 14.5px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
            transition: all 0.2s ease;
            margin-top: 4px;
        }
        .btn-submit-login:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.55); }

        .btn-sso-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            height: 46px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: var(--radius-md);
            color: #6ee7b7;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .btn-sso-link:hover { background: rgba(16, 185, 129, 0.22); border-color: rgba(16, 185, 129, 0.6); }

        @media (max-width: 860px) {
            .login-container { grid-template-columns: 1fr; margin: 12px; }
            .hero-section { display: none; }
            .form-section { padding: 32px 20px; }
        }
    </style>
</head>
<body>

    <div class="ambient-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="login-container">

        <section class="hero-section">
            <div class="hero-top">
                <img src="/assets/images/logo.png" onerror="this.src='https://sso.3rdvn.io.vn/static/logo.jpg'" alt="3RDVN" class="brand-logo-img">
                <div>
                    <div class="brand-title">3RD-VN GROUP</div>
                    <div class="brand-subtitle">Loan Origination System (LOS)</div>
                </div>
            </div>

            <div class="hero-center">
                <h1 class="hero-headline">
                    Cổng Hồ Sơ SAPP LOS<br>
                    <span class="gradient-accent">Thẩm Định &amp; Đối Soát</span>
                </h1>
                <p class="hero-description">
                    Hệ thống theo dõi tiến độ hồ sơ ngân hàng, phân luồng thẩm định hồ sơ vay và đối soát kết quả giải ngân theo thời gian thực.
                </p>
            </div>

            <div style="font-size: 11.5px; color: #64748b;">
                &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
            </div>
        </section>

        <section class="form-section">
            <div class="form-badge-tag">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Bảo Mật Zero-Trust</span>
            </div>

            <h2 class="form-heading">Đăng Nhập LOS</h2>
            <p class="form-subtext">Sử dụng tài khoản nội bộ hoặc đăng nhập nhanh bằng SSO.</p>

            @if ($errors->any())
                <div class="error-alert">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form class="auth-form" method="POST" action="{{ route('los.login.store') }}">
                @csrf

                <div class="input-group">
                    <label class="input-label" for="identifier">Tài Khoản / Email / Mã NV</label>
                    <div class="input-field-wrap">
                        <span class="input-field-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input class="auth-input" id="identifier" name="identifier" type="text" placeholder="Email, RD26xxxx, Username..." value="{{ old('identifier') }}" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="password">Mật Khẩu</label>
                    <div class="input-field-wrap">
                        <span class="input-field-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input class="auth-input" id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password" style="padding-right: 44px;">
                        <button type="button" class="btn-toggle-pw" onclick="togglePasswordVisibility()">
                            <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit-login">
                    <span>Đăng Nhập SAPP LOS</span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>

                <div style="margin: 10px 0 6px; display: flex; align-items: center; gap: 10px;">
                    <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.12);"></div>
                    <span style="font-size: 11px; color: rgba(255,255,255,0.4); font-weight: 700; text-transform: uppercase;">HOẶC</span>
                    <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.12);"></div>
                </div>

                <a href="https://sso.3rdvn.io.vn" class="btn-sso-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="11" r="3"/></svg>
                    <span>Đăng Nhập Bằng 3RD-VN SSO</span>
                </a>
            </form>
        </section>

    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                input.type = 'password';
                eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
</body>
</html>
