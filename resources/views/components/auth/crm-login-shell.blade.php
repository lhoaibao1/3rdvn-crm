@props([
    'title',
    'subtitle' => null,
    'workspace' => 'CRM Workspace',
    'environment' => null,
    'environmentDescription' => null,
    'storyKicker' => null,
    'storyTitle' => 'Quản lý hồ sơ.',
    'storyAccent' => 'Rõ ràng từng bước.',
    'storyDescription' => 'Một không gian làm việc thống nhất để đội ngũ tiếp nhận, xử lý, phê duyệt và theo dõi hồ sơ chính xác hơn mỗi ngày.',
    'flow' => null,
])

@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN CRM');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $primary = $settings->primary_color ?: '#2563eb';
    $cover = $settings->login_background_type === 'image' && $settings->login_background_image
        ? asset('storage/'.$settings->login_background_image)
        : null;
    $coverStyle = $cover ? "url('{$cover}')" : 'none';
    $isUat = str_starts_with(request()->getHost(), 'uat-');
    $environment ??= $isUat ? 'UAT' : 'PROD';
    $environmentDescription ??= $isUat
        ? 'Môi trường kiểm thử đang hoạt động'
        : 'Môi trường vận hành đang hoạt động';
    $storyKicker ??= $workspace;
    $flow ??= [
        ['label' => 'Tiếp nhận'],
        ['label' => 'Xử lý'],
        ['label' => 'Phê duyệt'],
        ['label' => 'Báo cáo'],
    ];
@endphp

<div
    class="crm-login-screen"
    x-data="{ passwordVisible: false }"
    style="--crm-login-primary: {{ $primary }}; --crm-login-cover: {{ $coverStyle }};"
>
    <style>
        html,
        body {
            min-height: 100%;
            margin: 0;
            overflow: hidden;
            background: #f8fafc;
        }

        .fi-body {
            min-height: 100%;
            background: #f8fafc !important;
        }

        .fi-simple-main,
        .fi-simple-main-ctn {
            width: 100% !important;
            max-width: none !important;
            min-height: 100dvh !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .crm-login-screen,
        .crm-login-screen * {
            box-sizing: border-box;
        }

        .crm-login-screen {
            position: fixed;
            inset: 0;
            z-index: 1;
            width: 100vw;
            height: 100dvh;
            overflow: hidden;
            color: #0f172a;
            background: #f8fafc;
            font-family: "Inter Variable", Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --crm-login-story-pad-x: clamp(34px, 4.2vw, 68px);
            --crm-login-story-pad-y: clamp(34px, 4.2vw, 68px);
        }

        .crm-login-layout {
            display: grid;
            grid-template-columns: minmax(470px, 1.08fr) minmax(440px, .92fr);
            width: 100%;
            height: 100%;
        }

        .crm-login-story {
            position: relative;
            display: flex;
            min-width: 0;
            overflow: hidden;
            padding: var(--crm-login-story-pad-y) var(--crm-login-story-pad-x);
            color: #fff;
            background:
                radial-gradient(circle at 13% 14%, rgba(59, 130, 246, .28), transparent 30%),
                radial-gradient(circle at 88% 82%, rgba(14, 165, 233, .18), transparent 34%),
                linear-gradient(145deg, #081120 0%, #0d1d38 52%, #102a4f 100%);
            isolation: isolate;
        }

        .crm-login-story::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -2;
            background-image:
                linear-gradient(rgba(148, 163, 184, .065) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, .065) 1px, transparent 1px);
            background-size: 46px 46px;
            mask-image: linear-gradient(135deg, #000 10%, transparent 78%);
            -webkit-mask-image: linear-gradient(135deg, #000 10%, transparent 78%);
        }

        .crm-login-story::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -3;
            background-image:
                linear-gradient(145deg, rgba(8, 17, 32, .82), rgba(16, 42, 79, .68)),
                var(--crm-login-cover);
            background-position: center;
            background-size: cover;
        }

        .crm-login-story-inner {
            display: flex;
            flex-direction: column;
            width: min(100%, 650px);
            min-height: 0;
        }

        .crm-login-story-header,
        .crm-login-mobile-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .crm-login-brand-mark {
            display: grid;
            place-items: center;
            width: 154px;
            height: 48px;
            padding: 8px 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .82);
            border-radius: 12px;
            color: #0f172a;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 8px 28px rgba(2, 8, 23, .18), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .crm-login-brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: none;
        }

        .crm-login-brand-mark span {
            font-size: 1rem;
            font-weight: 850;
            letter-spacing: .05em;
        }

        .crm-login-env {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 28px;
            padding: 0 10px;
            border: 1px solid rgba(147, 197, 253, .28);
            border-radius: 999px;
            color: #bfdbfe;
            background: rgba(37, 99, 235, .16);
            font-size: .7rem;
            font-weight: 750;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .crm-login-env::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #60a5fa;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, .12);
        }

        .crm-login-story-copy {
            margin: auto 0;
            padding: 58px 0 44px;
        }

        .crm-login-kicker {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 24px;
            color: #a9d2ff;
            font-size: .74rem;
            font-weight: 680;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .crm-login-kicker::before {
            content: '';
            width: 32px;
            height: 1px;
            background: #60a5fa;
        }

        .crm-login-story h2 {
            max-width: 620px;
            margin: 0;
            color: #f8fbff;
            font-size: clamp(2.6rem, 4.35vw, 4.55rem);
            font-weight: 640;
            line-height: 1.045;
            letter-spacing: -.045em;
            text-wrap: balance;
        }

        .crm-login-story h2 span {
            display: block;
            margin-top: .08em;
            color: #82beff;
            font-weight: 760;
        }

        .crm-login-story-copy > p {
            max-width: 560px;
            margin: 30px 0 0;
            color: rgba(226, 232, 240, .79);
            font-size: clamp(.96rem, 1.12vw, 1.08rem);
            font-weight: 430;
            line-height: 1.78;
            letter-spacing: -.008em;
        }

        .crm-login-flow {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 40px;
        }

        .crm-login-flow-item {
            min-width: 0;
            padding: 14px 12px;
            border: 1px solid rgba(148, 163, 184, .15);
            border-radius: 12px;
            background: rgba(15, 23, 42, .28);
        }

        .crm-login-flow-item small {
            display: block;
            color: #60a5fa;
            font-size: .64rem;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .crm-login-flow-item strong {
            display: block;
            margin-top: 6px;
            color: #e2e8f0;
            font-size: .75rem;
            font-weight: 690;
            line-height: 1.35;
            white-space: nowrap;
        }

        .crm-login-story-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: rgba(148, 163, 184, .72);
            font-size: .68rem;
            line-height: 1.4;
        }

        .crm-login-story-footer span:last-child {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .crm-login-story-footer span:last-child::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, .1);
        }

        .crm-login-panel {
            position: relative;
            display: grid;
            min-width: 0;
            overflow-y: auto;
            background:
                radial-gradient(circle at 100% 0, rgba(219, 234, 254, .48), transparent 27%),
                #f8fafc;
        }

        .crm-login-form-wrap {
            width: min(500px, calc(100% - 64px));
            margin: auto;
            padding: clamp(34px, 3.6vw, 48px);
            border: 1px solid rgba(203, 213, 225, .72);
            border-radius: 28px;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 30px 80px rgba(15, 23, 42, .12), 0 3px 12px rgba(15, 23, 42, .04);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            transform-origin: center;
        }

        .crm-login-mobile-brand {
            display: none;
            margin-bottom: 42px;
        }

        .crm-login-mobile-brand .crm-login-brand-mark {
            width: 138px;
            height: 44px;
            border-color: #dbe5f1;
            color: #0f172a;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
        }

        .crm-login-mobile-brand .crm-login-brand-mark img {
            filter: none;
        }

        .crm-login-mobile-brand .crm-login-env {
            border-color: #bfdbfe;
            color: #1d4ed8;
            background: #eff6ff;
        }

        .crm-login-welcome {
            margin-bottom: 34px;
        }

        .crm-login-secure {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: .72rem;
            font-weight: 650;
        }

        .crm-login-secure svg {
            width: 16px;
            height: 16px;
            color: #2563eb;
        }

        .crm-login-welcome h1 {
            margin: 16px 0 0;
            color: #0f172a;
            font-size: clamp(2rem, 3vw, 2.65rem);
            font-weight: 790;
            line-height: 1.08;
            letter-spacing: -.04em;
        }

        .crm-login-welcome p {
            margin: 12px 0 0;
            color: #64748b;
            font-size: .92rem;
            line-height: 1.62;
        }

        .crm-login-status {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            padding: 13px 14px;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            color: #166534;
            background: #f0fdf4;
            font-size: .78rem;
            line-height: 1.45;
        }

        .crm-login-status::before {
            content: '✓';
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            color: #fff;
            background: #16a34a;
            font-size: .68rem;
            font-weight: 800;
        }

        .crm-login-form {
            display: grid;
            gap: 20px;
        }

        .crm-login-field {
            display: block;
        }

        .crm-login-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            color: #1e293b;
            font-size: .78rem;
            font-weight: 720;
        }

        .crm-login-control {
            position: relative;
            display: flex;
            align-items: center;
            height: 54px;
            border: 1px solid #d7e0ec;
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }

        .crm-login-control:focus-within {
            border-color: var(--crm-login-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .1), 0 8px 24px rgba(15, 23, 42, .06);
            transform: translateY(-1px);
        }

        .crm-login-control.is-invalid {
            border-color: #f87171;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, .08);
        }

        .crm-login-control > svg {
            flex: 0 0 auto;
            width: 19px;
            height: 19px;
            margin-left: 16px;
            color: #94a3b8;
        }

        .crm-login-control input {
            width: 100%;
            height: 100%;
            min-width: 0;
            padding: 0 15px 0 12px;
            border: 0;
            outline: 0;
            color: #0f172a;
            background: transparent;
            font: inherit;
            font-size: .9rem;
        }

        .crm-login-control input::placeholder {
            color: #a4afbf;
        }

        .crm-login-password-toggle {
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            width: 40px;
            height: 40px;
            margin-right: 6px;
            border: 0;
            border-radius: 9px;
            color: #64748b;
            background: transparent;
            cursor: pointer;
        }

        .crm-login-password-toggle:hover,
        .crm-login-password-toggle:focus-visible {
            color: #1d4ed8;
            background: #eff6ff;
            outline: 0;
        }

        .crm-login-password-toggle svg {
            width: 18px;
            height: 18px;
        }

        .crm-login-error {
            margin-top: 7px;
            color: #dc2626;
            font-size: .72rem;
            font-weight: 580;
            line-height: 1.4;
        }

        .crm-login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: -2px;
        }

        .crm-login-remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #475569;
            font-size: .76rem;
            cursor: pointer;
        }

        .crm-login-remember input {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: var(--crm-login-primary);
        }

        .crm-login-link {
            color: #2563eb;
            font-size: .76rem;
            font-weight: 680;
            text-decoration: none;
        }

        .crm-login-link:hover,
        .crm-login-link:focus-visible {
            color: #1d4ed8;
            outline: 0;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .crm-login-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 54px;
            margin-top: 2px;
            border: 0;
            border-radius: 13px;
            color: #fff;
            background: linear-gradient(135deg, var(--crm-login-primary), #1d4ed8);
            box-shadow: 0 12px 26px rgba(37, 99, 235, .24);
            font: inherit;
            font-size: .84rem;
            font-weight: 750;
            cursor: pointer;
            transition: transform .16s ease, box-shadow .16s ease, filter .16s ease;
        }

        .crm-login-submit:hover {
            filter: brightness(1.04);
            box-shadow: 0 16px 34px rgba(37, 99, 235, .3);
            transform: translateY(-1px);
        }

        .crm-login-submit:focus-visible {
            outline: 3px solid rgba(37, 99, 235, .22);
            outline-offset: 3px;
        }

        .crm-login-submit:disabled {
            cursor: wait;
            filter: saturate(.7);
            opacity: .8;
            transform: none;
        }

        .crm-login-submit svg {
            width: 18px;
            height: 18px;
        }

        .crm-login-spinner {
            width: 17px;
            height: 17px;
            border: 2px solid rgba(255, 255, 255, .42);
            border-top-color: #fff;
            border-radius: 50%;
            animation: crm-login-spin .7s linear infinite;
        }

        .crm-login-secondary {
            margin-top: 18px;
            color: #64748b;
            text-align: center;
            font-size: .76rem;
        }

        .crm-login-secondary .crm-login-link {
            margin-left: 4px;
        }

        .crm-login-trust {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: 38px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            text-align: center;
            font-size: .68rem;
            line-height: 1.45;
        }

        .crm-login-trust svg {
            flex: 0 0 auto;
            width: 15px;
            height: 15px;
        }

        @keyframes crm-login-spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1080px) {
            .crm-login-screen {
                --crm-login-story-pad-x: 42px;
                --crm-login-story-pad-y: 42px;
            }

            .crm-login-layout {
                grid-template-columns: minmax(410px, .92fr) minmax(430px, 1.08fr);
            }

            .crm-login-story {
                padding: var(--crm-login-story-pad-y) var(--crm-login-story-pad-x);
            }

            .crm-login-flow {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .crm-login-story h2 {
                font-size: clamp(2.6rem, 5vw, 4rem);
            }
        }

        @media (max-width: 820px) {
            html,
            body {
                overflow: auto;
            }

            .crm-login-screen {
                position: relative;
                min-height: 100dvh;
                height: auto;
                overflow: visible;
            }

            .crm-login-layout {
                display: block;
                min-height: 100dvh;
            }

            .crm-login-story {
                display: none;
            }

            .crm-login-panel {
                min-height: 100dvh;
                overflow: visible;
                background:
                    radial-gradient(circle at 100% 0, rgba(191, 219, 254, .55), transparent 32%),
                    radial-gradient(circle at 0 100%, rgba(219, 234, 254, .5), transparent 30%),
                    #f8fafc;
            }

            .crm-login-mobile-brand {
                display: flex;
            }

            .crm-login-form-wrap {
                width: min(470px, calc(100% - 40px));
                padding: 34px;
            }
        }

        @media (max-width: 520px) {
            .crm-login-form-wrap {
                width: calc(100% - 24px);
                padding: 28px 22px;
                border-radius: 22px;
            }

            .crm-login-mobile-brand {
                margin-bottom: 34px;
            }

            .crm-login-welcome {
                margin-bottom: 28px;
            }

            .crm-login-welcome h1 {
                font-size: 2rem;
            }

            .crm-login-control,
            .crm-login-submit {
                min-height: 52px;
            }

            .crm-login-control input {
                font-size: 16px;
            }

            .crm-login-options {
                align-items: flex-start;
            }
        }

        @media (max-height: 680px) and (min-width: 821px) {
            .crm-login-screen {
                --crm-login-story-pad-y: 30px;
            }

            .crm-login-story {
                padding-top: var(--crm-login-story-pad-y);
                padding-bottom: var(--crm-login-story-pad-y);
            }

            .crm-login-story-copy {
                padding: 28px 0 22px;
            }

            .crm-login-story h2 {
                font-size: clamp(2.3rem, 4.2vw, 3.6rem);
            }

            .crm-login-story-copy > p {
                margin-top: 18px;
            }

            .crm-login-flow {
                margin-top: 24px;
            }

            .crm-login-form-wrap {
                padding: 30px 34px;
            }

            .crm-login-welcome {
                margin-bottom: 24px;
            }

            .crm-login-trust {
                margin-top: 24px;
                padding-top: 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .crm-login-screen *,
            .crm-login-screen *::before,
            .crm-login-screen *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>

    <div class="crm-login-layout">
        <aside class="crm-login-story" aria-label="Giới thiệu {{ $brandName }}">
            <div class="crm-login-story-inner">
                <header class="crm-login-story-header">
                    <div class="crm-login-brand-mark">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="{{ $brandName }}">
                        @else
                            <span>{{ $brandName }}</span>
                        @endif
                    </div>
                    <span class="crm-login-env">{{ $environment }}</span>
                </header>

                <div class="crm-login-story-copy">
                    <span class="crm-login-kicker">{{ $storyKicker }}</span>
                    <h2>{{ $storyTitle }}<span>{{ $storyAccent }}</span></h2>
                    <p>{{ $storyDescription }}</p>

                    <div class="crm-login-flow" aria-label="Quy trình {{ $workspace }}">
                        @foreach ($flow as $index => $step)
                            <div class="crm-login-flow-item">
                                <small>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</small>
                                <strong>{{ $step['label'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>

                <footer class="crm-login-story-footer">
                    <span>© {{ now()->year }} {{ $brandName }}</span>
                    <span>{{ $environmentDescription }}</span>
                </footer>
            </div>
        </aside>

        <main class="crm-login-panel">
            <div class="crm-login-form-wrap">
                <div class="crm-login-mobile-brand">
                    <div class="crm-login-brand-mark">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="{{ $brandName }}">
                        @else
                            <span>{{ $brandName }}</span>
                        @endif
                    </div>
                    <span class="crm-login-env">{{ $environment }}</span>
                </div>

                <header class="crm-login-welcome">
                    <span class="crm-login-secure">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 10V7a5 5 0 0 1 10 0v3M6.5 10h11A1.5 1.5 0 0 1 19 11.5v7A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-7A1.5 1.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M12 14v2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        Khu vực truy cập nội bộ
                    </span>
                    <h1>{{ $title }}</h1>
                    @if ($subtitle)
                        <p>{{ $subtitle }}</p>
                    @endif
                </header>

                {{ $slot }}

                <div class="crm-login-trust">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3 5 6v5c0 4.6 2.8 8 7 10 4.2-2 7-5.4 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m9.5 12 1.7 1.7 3.5-3.7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Phiên đăng nhập được bảo vệ và ghi nhận theo chính sách nội bộ
                </div>
            </div>
        </main>
    </div>
</div>
