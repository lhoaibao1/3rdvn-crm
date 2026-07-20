@php
    $settings = \App\Models\UiSetting::current();
    $user = $this->identifiedUser();
    $avatar = $this->identifiedUserAvatarUrl();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN CRM');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $title = $settings->login_title ?: 'Đăng nhập 3RDVN CRM';
    $subtitle = $settings->login_subtitle ?: 'Hệ thống CRM nội bộ';
    $background = $settings->login_background_type === 'image' && $settings->login_background_image
        ? asset('storage/'.$settings->login_background_image)
        : asset('images/login-mountain.png');
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="crm-glass-login" style="--crm-login-background: url('{{ $background }}')">
    <style>
        :root { color-scheme: dark; }

        html,
        body,
        .fi-body {
            min-height: 100%;
            overflow: hidden !important;
            background: #080b11 !important;
        }

        .fi-simple-main,
        .fi-simple-main-ctn {
            width: 100% !important;
            max-width: none !important;
            min-height: 100dvh !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .crm-glass-login,
        .crm-glass-login * { box-sizing: border-box; }

        .crm-glass-login {
            position: fixed;
            inset: 0;
            z-index: 999;
            display: grid;
            place-items: center;
            width: 100vw;
            height: 100dvh;
            padding: 32px;
            overflow: hidden;
            color: #fff;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-image:
                linear-gradient(rgba(5, 7, 12, .76), rgba(5, 7, 12, .84)),
                var(--crm-login-background);
            background-position: center;
            background-size: cover;
        }

        .crm-glass-shell {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: min(1180px, calc(100vw - 64px));
            height: min(660px, calc(100dvh - 64px));
            min-height: 510px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 8px;
            background: rgba(7, 11, 18, .28);
            box-shadow: 0 34px 100px rgba(0, 0, 0, .48);
            isolation: isolate;
        }

        .crm-glass-photo {
            position: relative;
            min-width: 0;
            background-image:
                linear-gradient(180deg, rgba(7, 12, 21, .08), rgba(5, 8, 14, .44)),
                var(--crm-login-background);
            background-position: center;
            background-size: cover;
        }

        .crm-glass-photo::after {
            content: '';
            position: absolute;
            inset: auto 0 0;
            height: 36%;
            background: linear-gradient(180deg, transparent, rgba(4, 7, 12, .62));
            pointer-events: none;
        }

        .crm-glass-photo-caption {
            position: absolute;
            z-index: 1;
            left: 34px;
            bottom: 30px;
            max-width: 330px;
        }

        .crm-glass-photo-caption strong {
            display: block;
            font-size: 1rem;
            font-weight: 720;
            line-height: 1.35;
        }

        .crm-glass-photo-caption span {
            display: block;
            margin-top: 7px;
            color: rgba(255, 255, 255, .62);
            font-size: .75rem;
            line-height: 1.45;
        }

        .crm-glass-pane {
            position: relative;
            display: grid;
            place-items: center;
            min-width: 0;
            overflow: hidden;
            border-left: 1px solid rgba(255, 255, 255, .16);
            background: rgba(12, 16, 25, .42);
            backdrop-filter: blur(20px) saturate(112%);
            -webkit-backdrop-filter: blur(20px) saturate(112%);
        }

        .crm-glass-pane::before {
            content: '';
            position: absolute;
            inset: -28px;
            z-index: -2;
            background-image: var(--crm-login-background);
            background-position: center;
            background-size: cover;
            filter: blur(20px);
            opacity: .46;
            transform: scale(1.1);
        }

        .crm-glass-pane::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background: rgba(8, 12, 19, .43);
            pointer-events: none;
        }

        .crm-glass-form-wrap {
            width: min(330px, calc(100% - 64px));
            max-height: calc(100% - 48px);
            padding: 4px 2px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, .24) transparent;
        }

        .crm-glass-form-wrap::-webkit-scrollbar { width: 5px; }
        .crm-glass-form-wrap::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(255, 255, 255, .24);
        }

        .crm-glass-brand {
            display: grid;
            justify-items: center;
            margin-bottom: 32px;
            text-align: center;
        }

        .crm-glass-logo {
            display: grid;
            place-items: center;
            width: 66px;
            height: 54px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 8px;
            color: #fff;
            background: rgba(255, 255, 255, .08);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .12);
            font-size: 1rem;
            font-weight: 800;
        }

        .crm-glass-logo img {
            width: 100%;
            height: 100%;
            padding: 8px;
            object-fit: contain;
        }

        .crm-glass-brand h1 {
            margin: 17px 0 0;
            color: #fff;
            font-size: 1.05rem;
            font-weight: 680;
            line-height: 1.35;
            letter-spacing: 0;
        }

        .crm-glass-brand p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, .58);
            font-size: .74rem;
            line-height: 1.45;
        }

        .crm-glass-status {
            margin: -15px 0 20px;
            padding: 9px 11px;
            border: 1px solid rgba(134, 239, 172, .28);
            border-radius: 6px;
            color: #dcfce7;
            background: rgba(22, 101, 52, .20);
            font-size: .74rem;
            line-height: 1.45;
        }

        .crm-glass-form {
            display: grid;
            gap: 18px;
        }

        .crm-glass-field { display: block; }
        .crm-glass-field > span {
            display: block;
            margin-bottom: 7px;
            color: rgba(255, 255, 255, .66);
            font-size: .7rem;
            font-weight: 520;
        }

        .crm-glass-field input[type="text"],
        .crm-glass-field input[type="password"] {
            width: 100%;
            height: 40px;
            padding: 0 1px;
            border: 0;
            border-bottom: 1px solid rgba(255, 255, 255, .46);
            border-radius: 0;
            outline: 0;
            color: #fff;
            background: transparent;
            font: inherit;
            font-size: .86rem;
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        .crm-glass-field input::placeholder { color: rgba(255, 255, 255, .34); }
        .crm-glass-field input:focus {
            border-color: #fff;
            box-shadow: 0 1px 0 #fff;
        }

        .crm-glass-error {
            margin-top: 7px;
            color: #fecaca;
            font-size: .72rem;
            line-height: 1.35;
        }

        .crm-glass-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .crm-glass-check {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: rgba(255, 255, 255, .68);
            font-size: .72rem;
            cursor: pointer;
        }

        .crm-glass-check input {
            width: 14px;
            height: 14px;
            margin: 0;
            accent-color: #fff;
        }

        .crm-glass-link,
        .crm-glass-back {
            border: 0;
            padding: 0;
            color: rgba(255, 255, 255, .76);
            background: transparent;
            font: inherit;
            font-size: .72rem;
            text-decoration: none;
            cursor: pointer;
            transition: color .16s ease;
        }

        .crm-glass-link:hover,
        .crm-glass-link:focus-visible,
        .crm-glass-back:hover,
        .crm-glass-back:focus-visible {
            color: #fff;
            outline: none;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .crm-glass-submit {
            width: 100%;
            height: 42px;
            border: 1px solid rgba(255, 255, 255, .82);
            border-radius: 3px;
            color: #111827;
            background: rgba(255, 255, 255, .93);
            font: inherit;
            font-size: .76rem;
            font-weight: 680;
            cursor: pointer;
            transition: background .16s ease, transform .16s ease, box-shadow .16s ease;
        }

        .crm-glass-submit:hover {
            background: #fff;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .18);
            transform: translateY(-1px);
        }

        .crm-glass-submit:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 3px;
        }

        .crm-glass-submit[disabled] {
            cursor: wait;
            opacity: .62;
            transform: none;
        }

        .crm-glass-account {
            display: flex;
            align-items: center;
            gap: 11px;
            margin: -8px 0 4px;
            padding: 11px 0;
            border-top: 1px solid rgba(255, 255, 255, .14);
            border-bottom: 1px solid rgba(255, 255, 255, .14);
        }

        .crm-glass-avatar,
        .crm-glass-avatar-fallback {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            border: 1px solid rgba(255, 255, 255, .46);
            border-radius: 50%;
            object-fit: cover;
        }

        .crm-glass-avatar-fallback {
            display: grid;
            place-items: center;
            color: #fff;
            background: rgba(255, 255, 255, .12);
            font-size: .75rem;
            font-weight: 700;
        }

        .crm-glass-account strong {
            display: block;
            color: #fff;
            font-size: .82rem;
            font-weight: 650;
            line-height: 1.35;
        }

        .crm-glass-account span {
            display: block;
            margin-top: 3px;
            color: rgba(255, 255, 255, .54);
            font-size: .7rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .crm-glass-back { justify-self: center; }
        .crm-glass-footnote {
            margin: 28px 0 0;
            color: rgba(255, 255, 255, .38);
            text-align: center;
            font-size: .66rem;
            line-height: 1.4;
        }

        @media (max-width: 800px) {
            .crm-glass-login {
                padding: 14px;
                background-image:
                    linear-gradient(rgba(5, 8, 14, .58), rgba(5, 8, 14, .72)),
                    var(--crm-login-background);
            }

            .crm-glass-shell {
                display: block;
                width: 100%;
                height: min(720px, calc(100dvh - 28px));
                min-height: 0;
                border-radius: 12px;
            }

            .crm-glass-photo { display: none; }
            .crm-glass-pane {
                width: 100%;
                height: 100%;
                border-left: 0;
                background: rgba(10, 14, 22, .56);
            }

            .crm-glass-form-wrap {
                width: min(350px, calc(100% - 40px));
                max-height: calc(100% - 34px);
                padding: 12px 2px;
                -webkit-overflow-scrolling: touch;
            }

            .crm-glass-field input[type="text"],
            .crm-glass-field input[type="password"] {
                min-height: 44px;
                font-size: 16px;
            }

            .crm-glass-submit {
                min-height: 46px;
                font-size: .82rem;
            }

            .crm-glass-link,
            .crm-glass-back,
            .crm-glass-check { font-size: .78rem; }
        }

        @media (max-height: 620px) and (min-width: 801px) {
            .crm-glass-login { padding: 18px; }
            .crm-glass-shell {
                width: min(1040px, calc(100vw - 36px));
                height: calc(100dvh - 36px);
                min-height: 0;
            }
            .crm-glass-form-wrap { max-height: calc(100% - 30px); }
            .crm-glass-brand { margin-bottom: 20px; }
            .crm-glass-logo { width: 56px; height: 46px; }
            .crm-glass-footnote { margin-top: 18px; }
        }
    </style>

    <section class="crm-glass-shell" aria-label="Đăng nhập {{ $brandName }}">
        <aside class="crm-glass-photo" aria-hidden="true">
            <div class="crm-glass-photo-caption">
                <strong>{{ $brandName }}</strong>
                <span>{{ $subtitle }}</span>
            </div>
        </aside>

        <main class="crm-glass-pane">
            <div class="crm-glass-form-wrap">
                <header class="crm-glass-brand">
                    <div class="crm-glass-logo">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="{{ $brandName }}">
                        @else
                            <span>3RD</span>
                        @endif
                    </div>
                    <h1>{{ $user ? 'Xin chào, '.$user->name : $title }}</h1>
                    <p>{{ $user ? 'Nhập mật khẩu để tiếp tục' : $subtitle }}</p>
                </header>

                @if (session('status'))
                    <div class="crm-glass-status" role="status">{{ session('status') }}</div>
                @endif

                @if (! $user)
                    <form class="crm-glass-form" wire:submit.prevent="identify">
                        <label class="crm-glass-field">
                            <span>User/UID</span>
                            <input
                                type="text"
                                wire:model.defer="data.identifier"
                                autocomplete="username"
                                placeholder="Nhập User/UID"
                                autofocus
                            >
                            @error('data.identifier')
                                <div class="crm-glass-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <div class="crm-glass-row">
                            <span></span>
                            <a class="crm-glass-link" href="{{ url('/authen/forgot-username') }}">Quên tên đăng nhập?</a>
                        </div>

                        <button class="crm-glass-submit" type="submit" wire:loading.attr="disabled" wire:target="identify">
                            Tiếp tục
                        </button>
                    </form>
                @else
                    <div class="crm-glass-account">
                        @if ($avatar)
                            <img class="crm-glass-avatar" src="{{ $avatar }}" alt="Avatar {{ $user->name }}">
                        @else
                            <span class="crm-glass-avatar-fallback">{{ $initials ?: '3' }}</span>
                        @endif
                        <div>
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->uid ?: $user->employee_code ?: $user->email }}</span>
                        </div>
                    </div>

                    <form class="crm-glass-form" wire:submit.prevent="authenticate">
                        <label class="crm-glass-field">
                            <span>Mật khẩu</span>
                            <input
                                type="password"
                                wire:model.defer="data.password"
                                autocomplete="current-password"
                                placeholder="Nhập mật khẩu"
                                autofocus
                            >
                            @error('data.password')
                                <div class="crm-glass-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <div class="crm-glass-row">
                            <label class="crm-glass-check">
                                <input type="checkbox" wire:model.defer="data.remember">
                                <span>Ghi nhớ</span>
                            </label>
                            <a class="crm-glass-link" href="{{ url('/authen/forgot-password') }}">Quên mật khẩu?</a>
                        </div>

                        <button class="crm-glass-submit" type="submit" wire:loading.attr="disabled" wire:target="authenticate">
                            Đăng nhập
                        </button>

                        <button class="crm-glass-back" type="button" wire:click="changeIdentifier">Quay lại</button>
                    </form>
                @endif

                <p class="crm-glass-footnote">{{ $brandName }} · Truy cập nội bộ được bảo vệ</p>
            </div>
        </main>
    </section>
</div>
