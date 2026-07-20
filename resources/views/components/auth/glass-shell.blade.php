@props([
    'title',
    'subtitle' => null,
])

@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN CRM');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $background = $settings->login_background_type === 'image' && $settings->login_background_image
        ? asset('storage/'.$settings->login_background_image)
        : asset('images/login-mountain.png');
@endphp

<div class="crm-auth-screen" style="--crm-auth-background: url('{{ $background }}')">
    <style>
        html,
        body {
            min-height: 100%;
            margin: 0;
            overflow: hidden;
            background: #080b11;
        }

        .fi-body { min-height: 100%; background: #080b11 !important; }
        .fi-simple-main,
        .fi-simple-main-ctn {
            width: 100% !important;
            max-width: none !important;
            min-height: 100dvh !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .crm-auth-screen,
        .crm-auth-screen * { box-sizing: border-box; }

        .crm-auth-screen {
            position: fixed;
            inset: 0;
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
                var(--crm-auth-background);
            background-position: center;
            background-size: cover;
        }

        .crm-auth-shell {
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

        .crm-auth-photo {
            position: relative;
            min-width: 0;
            background-image:
                linear-gradient(180deg, rgba(7, 12, 21, .08), rgba(5, 8, 14, .44)),
                var(--crm-auth-background);
            background-position: center;
            background-size: cover;
        }

        .crm-auth-photo::after {
            content: '';
            position: absolute;
            inset: auto 0 0;
            height: 36%;
            background: linear-gradient(180deg, transparent, rgba(4, 7, 12, .62));
            pointer-events: none;
        }

        .crm-auth-caption {
            position: absolute;
            z-index: 1;
            left: 34px;
            bottom: 30px;
            max-width: 330px;
        }

        .crm-auth-caption strong {
            display: block;
            font-size: 1rem;
            font-weight: 720;
            line-height: 1.35;
        }

        .crm-auth-caption span {
            display: block;
            margin-top: 7px;
            color: rgba(255, 255, 255, .62);
            font-size: .75rem;
            line-height: 1.45;
        }

        .crm-auth-pane {
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

        .crm-auth-pane::before {
            content: '';
            position: absolute;
            inset: -28px;
            z-index: -2;
            background-image: var(--crm-auth-background);
            background-position: center;
            background-size: cover;
            filter: blur(20px);
            opacity: .46;
            transform: scale(1.1);
        }

        .crm-auth-pane::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background: rgba(8, 12, 19, .43);
            pointer-events: none;
        }

        .crm-auth-content {
            width: min(330px, calc(100% - 64px));
            max-height: calc(100% - 48px);
            padding: 4px 2px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, .24) transparent;
        }

        .crm-auth-content::-webkit-scrollbar { width: 5px; }
        .crm-auth-content::-webkit-scrollbar-thumb {
            border-radius: 8px;
            background: rgba(255, 255, 255, .24);
        }

        .crm-auth-brand {
            display: grid;
            justify-items: center;
            margin-bottom: 28px;
            text-align: center;
        }

        .crm-auth-logo {
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

        .crm-auth-logo img {
            width: 100%;
            height: 100%;
            padding: 8px;
            object-fit: contain;
        }

        .crm-auth-brand h1 {
            margin: 17px 0 0;
            color: #fff;
            font-size: 1.05rem;
            font-weight: 680;
            line-height: 1.35;
            letter-spacing: 0;
        }

        .crm-auth-brand p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, .58);
            font-size: .74rem;
            line-height: 1.45;
        }

        .crm-auth-form {
            display: grid;
            gap: 18px;
        }

        .crm-auth-field { display: block; }
        .crm-auth-field > span {
            display: block;
            margin-bottom: 7px;
            color: rgba(255, 255, 255, .66);
            font-size: .7rem;
            font-weight: 520;
        }

        .crm-auth-field input {
            width: 100%;
            height: 42px;
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

        .crm-auth-field input::placeholder { color: rgba(255, 255, 255, .34); }
        .crm-auth-field input:focus {
            border-color: #fff;
            box-shadow: 0 1px 0 #fff;
        }

        .crm-auth-error,
        .crm-auth-status,
        .crm-auth-hint {
            padding: 9px 11px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 6px;
            font-size: .72rem;
            line-height: 1.45;
        }

        .crm-auth-error {
            color: #fecaca;
            background: rgba(127, 29, 29, .24);
        }

        .crm-auth-status {
            color: #dcfce7;
            background: rgba(22, 101, 52, .22);
        }

        .crm-auth-hint {
            color: #dbeafe;
            background: rgba(30, 64, 175, .22);
        }

        .crm-auth-inline-error {
            margin-top: 7px;
            color: #fecaca;
            font-size: .72rem;
            line-height: 1.35;
        }

        .crm-auth-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .crm-auth-button {
            width: 100%;
            min-height: 44px;
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

        .crm-auth-button:hover {
            background: #fff;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .18);
            transform: translateY(-1px);
        }

        .crm-auth-button:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 3px;
        }

        .crm-auth-link,
        .crm-auth-link-button {
            width: fit-content;
            border: 0;
            padding: 0;
            color: rgba(255, 255, 255, .76);
            background: transparent;
            font: inherit;
            font-size: .72rem;
            text-decoration: none;
            cursor: pointer;
        }

        .crm-auth-link:hover,
        .crm-auth-link:focus-visible,
        .crm-auth-link-button:hover,
        .crm-auth-link-button:focus-visible {
            color: #fff;
            outline: 0;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .crm-auth-footer {
            margin: 26px 0 0;
            color: rgba(255, 255, 255, .38);
            text-align: center;
            font-size: .66rem;
            line-height: 1.4;
        }


        .crm-auth-status + .crm-auth-form,
        .crm-auth-error + .crm-auth-form,
        .crm-auth-hint + .crm-auth-form { margin-top: 18px; }

        .crm-auth-account {
            display: flex;
            align-items: center;
            gap: 11px;
            margin: -6px 0 18px;
            padding: 11px 0;
            border-top: 1px solid rgba(255, 255, 255, .14);
            border-bottom: 1px solid rgba(255, 255, 255, .14);
        }

        .crm-auth-avatar,
        .crm-auth-avatar-fallback {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            border: 1px solid rgba(255, 255, 255, .46);
            border-radius: 50%;
            object-fit: cover;
        }

        .crm-auth-avatar-fallback {
            display: grid;
            place-items: center;
            color: #fff;
            background: rgba(255, 255, 255, .12);
            font-size: .75rem;
            font-weight: 700;
        }

        .crm-auth-account strong {
            display: block;
            color: #fff;
            font-size: .82rem;
            font-weight: 650;
            line-height: 1.35;
        }

        .crm-auth-account span {
            display: block;
            margin-top: 3px;
            color: rgba(255, 255, 255, .54);
            font-size: .7rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .crm-auth-check {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: rgba(255, 255, 255, .68);
            font-size: .72rem;
            cursor: pointer;
        }

        .crm-auth-check input {
            width: 14px;
            height: 14px;
            margin: 0;
            accent-color: #fff;
        }
        @media (max-width: 800px) {
            .crm-auth-screen {
                padding: 14px;
                background-image:
                    linear-gradient(rgba(5, 8, 14, .58), rgba(5, 8, 14, .72)),
                    var(--crm-auth-background);
            }

            .crm-auth-shell {
                display: block;
                width: 100%;
                height: min(720px, calc(100dvh - 28px));
                min-height: 0;
                border-radius: 8px;
            }

            .crm-auth-photo { display: none; }
            .crm-auth-pane {
                width: 100%;
                height: 100%;
                border-left: 0;
                background: rgba(10, 14, 22, .56);
            }

            .crm-auth-content {
                width: min(350px, calc(100% - 40px));
                max-height: calc(100% - 34px);
                padding: 12px 2px;
                -webkit-overflow-scrolling: touch;
            }

            .crm-auth-field input {
                min-height: 44px;
                font-size: 16px;
            }

            .crm-auth-button { min-height: 46px; font-size: .82rem; }
            .crm-auth-link,
            .crm-auth-link-button { font-size: .78rem; }
        }

        @media (max-height: 620px) and (min-width: 801px) {
            .crm-auth-screen { padding: 18px; }
            .crm-auth-shell {
                width: min(1040px, calc(100vw - 36px));
                height: calc(100dvh - 36px);
                min-height: 0;
            }
            .crm-auth-content { max-height: calc(100% - 30px); }
            .crm-auth-brand { margin-bottom: 20px; }
            .crm-auth-logo { width: 56px; height: 46px; }
            .crm-auth-footer { margin-top: 18px; }
        }
    </style>

    <section class="crm-auth-shell" aria-label="{{ $title }}">
        <aside class="crm-auth-photo" aria-hidden="true">
            <div class="crm-auth-caption">
                <strong>{{ $brandName }}</strong>
                <span>{{ $settings->login_subtitle ?: 'Hệ thống CRM nội bộ' }}</span>
            </div>
        </aside>

        <main class="crm-auth-pane">
            <div class="crm-auth-content">
                <header class="crm-auth-brand">
                    <div class="crm-auth-logo">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="{{ $brandName }}">
                        @else
                            <span>3RD</span>
                        @endif
                    </div>
                    <h1>{{ $title }}</h1>
                    @if ($subtitle)
                        <p>{{ $subtitle }}</p>
                    @endif
                </header>

                {{ $slot }}

                <p class="crm-auth-footer">{{ $brandName }} · Truy cập nội bộ được bảo vệ</p>
            </div>
        </main>
    </section>
</div>
