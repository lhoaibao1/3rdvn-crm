@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $favicon = $settings->favicon_path ? asset('storage/'.$settings->favicon_path) : asset('favicon.ico');
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập &middot; {{ $brandName }} SAPP LOS</title>
    <link rel="icon" href="{{ $favicon }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #3b82f6;
            --brand-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --navy-bg: #070a13;
            --navy-panel: #0d1322;
            --navy-card: #131b2e;
            --navy-border: rgba(255, 255, 255, 0.08);
            --navy-border-hover: rgba(59, 130, 246, 0.45);
            --text-title: #f8fafc;
            --text-body: #cbd5e1;
            --text-muted: #64748b;
            --radius-xl: 24px;
            --radius-lg: 14px;
            --radius-md: 10px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100vh; }
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: var(--navy-bg);
            color: var(--text-body);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* ─── Ambient Glow Background Orbs ─── */
        .ambient-orbs {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.35;
        }
        .orb-1 {
            top: -10%;
            left: 10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #3b82f6 0%, rgba(59, 130, 246, 0) 70%);
            animation: floatOrb 18s infinite alternate ease-in-out;
        }
        .orb-2 {
            bottom: -15%;
            right: 15%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
            animation: floatOrb 22s infinite alternate-reverse ease-in-out;
        }
        .orb-3 {
            top: 40%;
            left: 45%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #0ea5e9 0%, rgba(14, 165, 233, 0) 70%);
            opacity: 0.2;
        }

        /* ─── Split Screen Layout ─── */
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1200px;
            min-height: 660px;
            margin: 24px;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            background: rgba(13, 19, 34, 0.75);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--navy-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: 0 25px 70px -15px rgba(0, 0, 0, 0.8), 0 0 40px rgba(59, 130, 246, 0.1);
        }

        /* ─── Left Hero Section ─── */
        .hero-section {
            padding: 48px 52px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(160deg, rgba(15, 23, 42, 0.8) 0%, rgba(2, 6, 23, 0.95) 100%);
            border-right: 1px solid var(--navy-border);
            position: relative;
            overflow: hidden;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.4), transparent);
        }

        .hero-header { display: flex; align-items: center; gap: 14px; }
        .hero-logo-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--brand-gradient);
            display: grid;
            place-items: center;
            color: #ffffff;
            font-weight: 900;
            font-size: 18px;
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
        }
        .hero-brand-name { font-size: 17px; font-weight: 800; color: var(--text-title); letter-spacing: -0.02em; }
        .hero-brand-sub { font-size: 11.5px; color: var(--text-muted); }

        .hero-center { margin: 36px 0; }
        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 14px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            color: #60a5fa;
            letter-spacing: 0.04em;
            margin-bottom: 20px;
        }
        .hero-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 8px #38bdf8; }

        .hero-headline {
            font-size: 32px;
            font-weight: 900;
            line-height: 1.25;
            color: var(--text-title);
            margin-bottom: 14px;
            letter-spacing: -0.03em;
        }
        .gradient-accent {
            background: linear-gradient(135deg, #60a5fa 0%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-description {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 28px;
        }

        /* Feature Cards Grid */
        .feature-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .feature-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-lg);
            transition: all 0.2s ease;
        }
        .feature-card:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateX(3px);
        }
        .feature-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #60a5fa;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .feature-title { font-size: 13px; font-weight: 700; color: var(--text-title); margin-bottom: 2px; }
        .feature-desc { font-size: 11.5px; color: var(--text-muted); line-height: 1.4; }

        .hero-footer {
            font-size: 11px;
            color: #475569;
            font-weight: 500;
        }

        /* ─── Right Form Panel ─── */
        .form-section {
            padding: 48px 52px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .form-badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 800;
            color: #34d399;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .form-heading { font-size: 24px; font-weight: 800; color: var(--text-title); margin-bottom: 6px; letter-spacing: -0.02em; }
        .form-subtext { font-size: 13px; color: var(--text-muted); margin-bottom: 28px; line-height: 1.5; }

        .error-alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            font-size: 12.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            animation: fadeIn 0.2s ease both;
        }

        .auth-form { display: flex; flex-direction: column; gap: 18px; }
        
        .input-group { display: flex; flex-direction: column; gap: 6px; }
        .input-label { font-size: 12px; font-weight: 700; color: var(--text-body); }
        
        .input-field-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-field-icon {
            position: absolute;
            left: 14px;
            color: #64748b;
            display: flex;
            pointer-events: none;
            transition: color 0.15s ease;
        }
        .auth-input {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 44px;
            background: rgba(2, 6, 23, 0.6);
            border: 1.5px solid var(--navy-border);
            border-radius: var(--radius-lg);
            color: #ffffff;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }
        .auth-input::placeholder { color: #475569; }
        .auth-input:focus {
            border-color: #3b82f6;
            background: rgba(2, 6, 23, 0.9);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18), 0 0 16px rgba(59, 130, 246, 0.1);
        }
        .auth-input:focus + .input-field-icon,
        .input-field-wrap:focus-within .input-field-icon {
            color: #60a5fa;
        }

        .btn-toggle-pw {
            position: absolute;
            right: 12px;
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 6px;
            display: flex;
            border-radius: 6px;
            transition: color 0.15s ease;
        }
        .btn-toggle-pw:hover { color: #ffffff; }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .remember-label { display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
        .remember-checkbox {
            accent-color: #3b82f6;
            width: 15px;
            height: 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-submit-login {
            height: 50px;
            margin-top: 6px;
            background: var(--brand-gradient);
            border: none;
            border-radius: var(--radius-lg);
            color: #ffffff;
            font-size: 14.5px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
            transition: all 0.2s ease;
        }
        .btn-submit-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.55);
        }
        .btn-submit-login:active { transform: translateY(0); }

        .security-badge-footer {
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes floatOrb { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(40px, 30px) scale(1.1); } }

        @media (max-width: 900px) {
            .login-container { grid-template-columns: 1fr; margin: 16px; min-height: auto; }
            .hero-section { display: none; }
            .form-section { padding: 36px 24px; }
        }
    </style>
</head>
<body>

    <!-- Ambient Glowing Orbs -->
    <div class="ambient-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Main Container -->
    <div class="login-container">
        
        <!-- ─── Left Hero Section ─── -->
        <section class="hero-section">
            <div class="hero-header">
                <div class="hero-logo-box">3R</div>
                <div>
                    <div class="hero-brand-name">{{ $brandName }}</div>
                    <div class="hero-brand-sub">Loan Origination System &middot; SAPP LOS</div>
                </div>
            </div>

            <div class="hero-center">
                <div class="hero-pill">
                    <span class="hero-pill-dot"></span>
                    <span>HỆ THỐNG SAPP LOS</span>
                </div>

                <h1 class="hero-headline">
                    Hệ thống SAPP LOS<br>
                    <span class="gradient-accent">Tra cứu & Đối soát</span>
                </h1>

                <p class="hero-description">
                    Môi trường đối soát, tra cứu và theo dõi trạng thái hồ sơ theo thời gian thực với các đối tác tài chính.
                </p>

                <div class="feature-cards">
                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <div class="feature-title">Bảo mật đa tầng</div>
                            <div class="feature-desc">Mã hóa phiên truy cập và kiểm soát phân quyền chuyên viên nội bộ.</div>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        </div>
                        <div>
                            <div class="feature-title">Webhook Real-time</div>
                            <div class="feature-desc">Tự động bắt trạng thái & lý do từ chối từ SHB Finance, FE Credit, VietCredit.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-footer">
                &copy; {{ date('Y') }} {{ $brandName }} &mdash; Môi trường vận hành SAPP LOS
            </div>
        </section>

        <!-- ─── Right Form Panel ─── -->
        <section class="form-section">
            <div class="form-badge-tag">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Khu vực bảo mật nội bộ</span>
            </div>

            <h2 class="form-heading">Đăng nhập</h2>
            <p class="form-subtext">Sử dụng tài khoản CRM / UID được cấp để truy cập hệ thống SAPP LOS.</p>

            @if ($errors->any())
                <div class="error-alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form class="auth-form" method="POST" action="{{ route('los.login.store') }}">
                @csrf

                <div class="input-group">
                    <label class="input-label" for="identifier">User / UID / Mã nhân viên</label>
                    <div class="input-field-wrap">
                        <span class="input-field-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
                        </span>
                        <input 
                            class="auth-input" 
                            id="identifier" 
                            name="identifier" 
                            type="text" 
                            placeholder="Nhập User, UID hoặc Mã nhân viên" 
                            value="{{ old('identifier') }}" 
                            required 
                            autofocus 
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="password">Mật khẩu</label>
                    <div class="input-field-wrap">
                        <span class="input-field-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input 
                            class="auth-input" 
                            id="password" 
                            name="password" 
                            type="password" 
                            placeholder="Nhập mật khẩu" 
                            required 
                            autocomplete="current-password"
                            style="padding-right: 44px;"
                        >
                        <button type="button" class="btn-toggle-pw" onclick="togglePasswordVisibility()" title="Ẩn/Hiện mật khẩu">
                            <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <label class="remember-label">
                        <input type="checkbox" class="remember-checkbox" name="remember" value="1" checked>
                        <span>Ghi nhớ phiên đăng nhập</span>
                    </label>
                </div>

                <button type="submit" class="btn-submit-login">
                    <span>Đăng nhập hệ thống SAPP LOS</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <div class="security-badge-footer">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                <span>Phiên làm việc được bảo vệ và ghi nhận theo chính sách nội bộ</span>
            </div>
        </section>

    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/>';
            } else {
                input.type = 'password';
                eyeIcon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
</body>
</html>
