@php
    $settings = \App\Models\UiSetting::current();
    $user = $this->identifiedUser();
    $avatar = $this->identifiedUserAvatarUrl();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN CRM');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
@endphp

<div class="crm-login-page">
    <style>
        html, body {
            overflow: hidden;
            background: #06122b !important;
        }

        .fi-simple-main,
        .fi-simple-main-ctn {
            width: 100% !important;
            max-width: none !important;
            min-height: 100dvh !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .crm-login-page {
            position: fixed;
            inset: 0;
            z-index: 999;
            width: 100vw;
            height: 100dvh;
            overflow: hidden;
            display: grid;
            place-items: center;
            padding: 22px;
            background:
                linear-gradient(120deg, rgba(51, 97, 255, .18) 0 1px, transparent 1px 320px),
                linear-gradient(150deg, #06122b 0%, #081b3d 45%, #122e7a 100%);
            color: #0f172a;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .crm-login-shell {
            width: min(980px, 100%);
            height: min(560px, calc(100dvh - 44px));
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, .86fr);
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, .34);
            border-radius: 22px;
            background: rgba(255, 255, 255, .18);
            box-shadow: 0 30px 90px rgba(0, 8, 30, .38);
            backdrop-filter: blur(22px);
        }

        .crm-login-form-side {
            order: 2;
            display: grid;
            place-items: center;
            padding: 34px;
            background: linear-gradient(135deg, rgba(255,255,255,.70), rgba(255,255,255,.38));
            border-left: 1px solid rgba(226, 232, 240, .50);
        }

        .crm-login-card {
            width: min(360px, 100%);
            min-height: 420px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 36px 34px;
            border: 1px solid rgba(226, 232, 240, .78);
            border-radius: 20px;
            background: rgba(255,255,255,.92);
            box-shadow: 0 20px 56px rgba(15, 23, 42, .16);
        }

        .crm-login-mark {
            width: 58px;
            height: 58px;
            margin: 0 auto 26px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: linear-gradient(145deg, #071a3d, #123a7c);
            color: #fff;
            font-size: 1.04rem;
            line-height: .86;
            font-weight: 900;
            letter-spacing: 0;
            box-shadow: 0 14px 28px rgba(37, 99, 235, .26);
            overflow: hidden;
        }

        .crm-login-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
            background: #fff;
        }

        .crm-login-card h2 {
            margin: 0;
            color: #0f172a;
            text-align: center;
            font-size: 1.55rem;
            line-height: 1.16;
            font-weight: 840;
            letter-spacing: 0;
        }

        .crm-login-form {
            margin-top: 28px;
            display: grid;
            gap: 15px;
        }

        .crm-login-field span {
            display: block;
            margin-bottom: 7px;
            color: #344256;
            font-size: .82rem;
            font-weight: 720;
        }

        .crm-input-wrap {
            position: relative;
        }

        .crm-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            width: 18px;
            height: 18px;
            color: #8a98ad;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .crm-login-field input {
            width: 100%;
            height: 48px;
            border: 1px solid #d7e0ec;
            border-radius: 12px;
            background: #fff;
            padding: 0 14px 0 44px;
            color: #0f172a;
            font-size: .94rem;
            font-weight: 560;
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }

        .crm-login-field input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
        }

        .crm-login-error {
            margin-top: 7px;
            color: #dc2626;
            font-size: .8rem;
            font-weight: 650;
        }

        .crm-login-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .crm-login-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-size: .82rem;
            font-weight: 620;
        }

        .crm-login-check input {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
        }

        .crm-login-submit {
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #1f6fff 0%, #1557f0 100%);
            color: #fff;
            font-size: .92rem;
            font-weight: 780;
            box-shadow: 0 14px 30px rgba(37, 99, 235, .28);
            cursor: pointer;
            transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
        }

        .crm-login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(37, 99, 235, .34);
        }

        .crm-login-submit svg {
            width: 17px;
            height: 17px;
        }

        .crm-login-submit[disabled] {
            opacity: .68;
            cursor: wait;
            transform: none;
        }

        .crm-login-link,
        .crm-login-back {
            justify-self: center;
            border: 0;
            background: transparent;
            color: #1557f0;
            font-size: .84rem;
            font-weight: 720;
            text-decoration: none;
            cursor: pointer;
            padding: 0;
        }

        .crm-login-link:hover,
        .crm-login-back:hover { color: #0f3fbf; }

        .crm-login-card-password {
            min-height: 0;
            padding-top: 32px;
            padding-bottom: 32px;
        }

        .crm-login-user {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-top: 18px;
            padding: 10px 12px;
            border: 1px solid #e5edf7;
            border-radius: 14px;
            background: #f8fafc;
        }

        .crm-login-avatar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 2px solid #fff;
            background: #dbeafe;
            object-fit: cover;
            box-shadow: 0 6px 14px rgba(15, 23, 42, .10);
            flex: 0 0 auto;
        }

        .crm-login-user strong {
            display: block;
            color: #0f172a;
            font-size: .92rem;
            font-weight: 780;
            line-height: 1.2;
        }

        .crm-login-user span {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: .76rem;
            font-weight: 620;
            overflow-wrap: anywhere;
        }

        .crm-login-visual {
            order: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 58px 52px;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(5, 16, 39, .88), rgba(5, 27, 62, .74)),
                radial-gradient(circle at 84% 18%, rgba(56, 189, 248, .20), transparent 34%);
            overflow: hidden;
        }

        .crm-login-visual::before,
        .crm-login-visual::after {
            content: '';
            position: absolute;
            border: 1px solid rgba(96, 165, 250, .22);
            transform: rotate(-26deg);
            pointer-events: none;
        }

        .crm-login-visual::before {
            width: 520px;
            height: 180px;
            right: -180px;
            top: -70px;
            border-radius: 42px;
        }

        .crm-login-visual::after {
            width: 620px;
            height: 220px;
            left: 20px;
            bottom: -150px;
            border-radius: 52px;
        }

        .crm-visual-brand,
        .crm-visual-board { position: relative; z-index: 1; }

        .crm-visual-brand {
            display: flex;
            align-items: baseline;
            gap: 14px;
            margin-bottom: 28px;
        }

        .crm-visual-brand strong {
            font-size: clamp(1.8rem, 3vw, 2.65rem);
            line-height: 1;
            letter-spacing: .01em;
            font-weight: 900;
        }

        .crm-visual-brand span {
            color: rgba(255,255,255,.82);
            font-size: clamp(1.35rem, 2.2vw, 2.05rem);
            font-weight: 360;
        }

        .crm-visual-line {
            width: 86px;
            height: 3px;
            margin-bottom: 30px;
            border-radius: 999px;
            background: linear-gradient(90deg, #38bdf8, rgba(56,189,248,0));
        }

        .crm-visual-board {
            width: 100%;
            max-width: 430px;
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 14px;
        }

        .crm-visual-rail,
        .crm-visual-panel,
        .crm-visual-wide {
            border: 1px solid rgba(148, 191, 255, .22);
            background: linear-gradient(145deg, rgba(24, 56, 112, .52), rgba(10, 29, 64, .36));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08), 0 20px 50px rgba(0,0,0,.16);
            backdrop-filter: blur(8px);
        }

        .crm-visual-rail {
            grid-row: span 3;
            height: 286px;
            border-radius: 18px;
            padding: 18px 0;
            display: grid;
            justify-items: center;
            align-content: start;
            gap: 18px;
        }

        .crm-visual-rail i {
            width: 26px;
            height: 26px;
            border-radius: 9px;
            background: rgba(148, 191, 255, .18);
        }

        .crm-visual-rail i:first-child { background: linear-gradient(135deg, #2b7cff, #1bd3ff); }

        .crm-visual-panel {
            height: 96px;
            border-radius: 18px;
            padding: 18px;
            display: grid;
            align-content: center;
            gap: 10px;
        }

        .crm-visual-wide {
            height: 132px;
            border-radius: 20px;
            padding: 18px;
            display: grid;
            gap: 12px;
        }

        .crm-row,
        .crm-row-short,
        .crm-row-line {
            height: 8px;
            border-radius: 999px;
            background: rgba(173, 199, 238, .40);
        }

        .crm-row { width: 74%; }
        .crm-row-short { width: 44%; }
        .crm-row-line { width: 100%; height: 7px; background: rgba(173, 199, 238, .26); }

        .crm-panel-grid {
            display: grid;
            grid-template-columns: 84px 1fr;
            gap: 18px;
            align-items: center;
        }

        .crm-ring {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: conic-gradient(#38bdf8 0 38%, rgba(45, 76, 126, .74) 38% 100%);
            box-shadow: inset 0 0 0 14px rgba(8, 24, 55, .85);
        }

        .crm-checks {
            display: grid;
            gap: 9px;
        }

        .crm-checks b {
            display: grid;
            grid-template-columns: 14px 1fr;
            gap: 8px;
            align-items: center;
        }

        .crm-checks b::before {
            content: '';
            width: 12px;
            height: 12px;
            border-radius: 4px;
            background: #2f80ff;
        }

        @media (max-width: 840px) {
            html, body { overflow: auto; }

            .crm-login-page {
                height: auto;
                min-height: 100dvh;
                overflow: visible;
                padding: 0;
            }

            .crm-login-shell {
                width: 100%;
                height: auto;
                min-height: 100dvh;
                grid-template-columns: 1fr;
                border-radius: 0;
                border: 0;
            }

            .crm-login-form-side {
                min-height: 100dvh;
                padding: 26px;
                border-left: 0;
            }

            .crm-login-visual { display: none; }
            .crm-login-card { min-height: 0; }
        }

        @media (max-height: 640px) and (min-width: 841px) {
            .crm-login-shell { height: calc(100dvh - 24px); }
            .crm-login-form-side { padding: 22px; }
            .crm-login-card { min-height: 390px; padding: 28px; }
            .crm-login-mark { margin-bottom: 18px; }
            .crm-login-form { margin-top: 20px; gap: 12px; }
            .crm-login-visual { padding: 34px; }
        }
    </style>

    <section class="crm-login-shell">
        <main class="crm-login-form-side">
            <div class="crm-login-card @if ($user) crm-login-card-password @endif">
                <div class="crm-login-mark">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="{{ $brandName }}">
                    @else
                        <span>3</span>
                    @endif
                </div>

                @if (! $user)
                    <h2>Đăng nhập</h2>

                    <form class="crm-login-form" wire:submit.prevent="identify">
                        <label class="crm-login-field">
                            <span>User/UID</span>
                            <div class="crm-input-wrap">
                                <svg class="crm-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                <input
                                    type="text"
                                    wire:model.defer="data.identifier"
                                    autocomplete="username"
                                    autofocus
                                >
                            </div>
                            @error('data.identifier')
                                <div class="crm-login-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <button class="crm-login-submit" type="submit" wire:loading.attr="disabled" wire:target="identify">
                            <span>Tiếp tục</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                        </button>

                        <a class="crm-login-link" href="{{ url('/authen/forgot-username') }}">Quên tên đăng nhập?</a>
                    </form>
                @else
                    <h2>Nhập mật khẩu</h2>

                    <div class="crm-login-user">
                        <img class="crm-login-avatar" src="{{ $avatar }}" alt="Avatar {{ $user->name }}">
                        <div>
                            <strong>Xin chào, {{ $user->name }}</strong>
                            <span>{{ $user->uid ?: $user->employee_code }}</span>
                        </div>
                    </div>

                    <form class="crm-login-form" wire:submit.prevent="authenticate">
                        <label class="crm-login-field">
                            <span>Mật khẩu</span>
                            <div class="crm-input-wrap">
                                <svg class="crm-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input
                                    type="password"
                                    wire:model.defer="data.password"
                                    autocomplete="current-password"
                                    autofocus
                                >
                            </div>
                            @error('data.password')
                                <div class="crm-login-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <div class="crm-login-actions">
                            <label class="crm-login-check">
                                <input type="checkbox" wire:model.defer="data.remember">
                                <span>Ghi nhớ</span>
                            </label>

                            <a class="crm-login-link" href="{{ url('/authen/forgot-password') }}">Quên mật khẩu?</a>
                        </div>

                        <button class="crm-login-submit" type="submit" wire:loading.attr="disabled" wire:target="authenticate">
                            <span>Đăng nhập</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                        </button>

                        <button class="crm-login-back" type="button" wire:click="changeIdentifier">
                            Quay lại
                        </button>
                    </form>
                @endif
            </div>
        </main>

        <aside class="crm-login-visual" aria-hidden="true">
            <div class="crm-visual-brand">
                <strong>{{ $brandName }}</strong>
                <span>Access</span>
            </div>
            <div class="crm-visual-line"></div>

            <div class="crm-visual-board">
                <div class="crm-visual-rail"><i></i><i></i><i></i><i></i><i></i></div>
                <div class="crm-visual-panel">
                    <div class="crm-row"></div>
                    <div class="crm-row-short"></div>
                </div>
                <div class="crm-visual-panel crm-panel-grid">
                    <div class="crm-ring"></div>
                    <div class="crm-checks"><b><span class="crm-row-line"></span></b><b><span class="crm-row-line"></span></b><b><span class="crm-row-line"></span></b></div>
                </div>
                <div class="crm-visual-wide">
                    <div class="crm-row-line"></div>
                    <div class="crm-row-line"></div>
                    <div class="crm-row-line"></div>
                    <div class="crm-row-line"></div>
                </div>
            </div>
        </aside>
    </section>
</div>
