@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN CRM');
    $logo = $settings->logo_path
        ? asset('storage/'.$settings->logo_path)
        : asset('icons/3rdvn-icon.svg');
    $primary = $settings->primary_color ?: '#2563eb';
@endphp

<div
    id="crm-login-entry-transition"
    class="crm-entry-transition"
    style="display: none; --crm-entry-primary: {{ $primary }};"
    aria-hidden="true"
>
    <div class="crm-entry-panel crm-entry-panel--left"></div>
    <div class="crm-entry-panel crm-entry-panel--right"></div>
    <div class="crm-entry-glow"></div>
    <div class="crm-entry-scan"></div>

    <div class="crm-entry-stage" data-entry-stage>
        <span class="crm-entry-ring"></span>
        <span class="crm-entry-ring"></span>
        <div class="crm-entry-emblem">
            <img src="{{ $logo }}" alt="">
        </div>
    </div>

    <div class="crm-entry-copy">
        <span class="crm-entry-kicker">Phiên đăng nhập đã xác thực</span>
        <strong data-entry-label>Đang chuẩn bị không gian làm việc</strong>
        <div class="crm-entry-track" aria-hidden="true"><span></span></div>
        <small>{{ $brandName }} · CRM Workspace</small>
    </div>
</div>

<style>
    .crm-entry-transition,
    .crm-entry-transition * {
        box-sizing: border-box;
    }

    .crm-entry-transition {
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        overflow: hidden;
        color: #fff;
        background: transparent;
        pointer-events: all;
        animation: crm-entry-failsafe .01s linear 5s forwards;
    }

    .crm-entry-panel {
        position: absolute;
        inset: 0 auto 0 0;
        width: 50.05%;
        overflow: hidden;
        background:
            radial-gradient(circle at 34% 38%, rgba(59, 130, 246, .26), transparent 32%),
            linear-gradient(135deg, #07111f 0%, #0c1d38 100%);
        transition: transform 1.05s cubic-bezier(.76, 0, .24, 1) .24s;
        will-change: transform;
    }

    .crm-entry-panel::after {
        content: '';
        position: absolute;
        inset: 0;
        opacity: .42;
        background-image:
            linear-gradient(rgba(148, 163, 184, .07) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, .07) 1px, transparent 1px);
        background-size: 54px 54px;
    }

    .crm-entry-panel--right {
        right: 0;
        left: auto;
        background:
            radial-gradient(circle at 66% 62%, rgba(14, 165, 233, .2), transparent 32%),
            linear-gradient(225deg, #07111f 0%, #0c1d38 100%);
    }

    .crm-entry-glow {
        position: absolute;
        top: 50%;
        left: 50%;
        z-index: 2;
        width: min(76vw, 820px);
        aspect-ratio: 1;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(37, 99, 235, .28), rgba(37, 99, 235, .07) 40%, transparent 70%);
        filter: blur(14px);
        transform: translate(-50%, -50%);
        animation: crm-entry-breathe 2.7s ease-in-out both;
        transition: opacity .45s ease, filter .45s ease, transform .7s ease;
    }

    .crm-entry-scan {
        position: absolute;
        inset: -45% -20%;
        z-index: 3;
        opacity: .46;
        background: linear-gradient(108deg, transparent 42%, rgba(147, 197, 253, .03) 47%, rgba(255, 255, 255, .24) 50%, rgba(96, 165, 250, .05) 53%, transparent 58%);
        transform: translateX(-52%);
        animation: crm-entry-scan 2.5s cubic-bezier(.22, 1, .36, 1) .18s both;
        pointer-events: none;
    }

    .crm-entry-stage {
        position: fixed;
        top: 50%;
        left: 50%;
        z-index: 5;
        display: grid;
        place-items: center;
        width: min(540px, 88vw);
        height: min(290px, 48vw);
        transform: translate(-50%, -50%);
        animation: crm-entry-arrive .92s cubic-bezier(.16, 1, .3, 1) both;
        transition:
            top 1.12s cubic-bezier(.16, 1, .3, 1),
            left 1.12s cubic-bezier(.16, 1, .3, 1),
            width 1.12s cubic-bezier(.16, 1, .3, 1),
            height 1.12s cubic-bezier(.16, 1, .3, 1),
            filter .5s ease;
        will-change: top, left, width, height;
    }

    .crm-entry-ring {
        position: absolute;
        inset: 0;
        border: 1px solid rgba(147, 197, 253, .24);
        border-radius: 36%;
        transform: rotate(45deg);
        animation: crm-entry-ring 3s linear infinite;
        transition: opacity .28s ease;
    }

    .crm-entry-ring::before {
        content: '';
        position: absolute;
        top: -4px;
        left: 50%;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #dbeafe;
        box-shadow: 0 0 8px #60a5fa, 0 0 26px rgba(96, 165, 250, .95);
    }

    .crm-entry-ring:nth-child(2) {
        inset: 25px;
        border-color: rgba(96, 165, 250, .18);
        border-radius: 50%;
        animation-direction: reverse;
        animation-duration: 3.4s;
    }

    .crm-entry-emblem {
        position: relative;
        z-index: 2;
        display: grid;
        place-items: center;
        width: min(440px, 80vw);
        height: min(136px, 25vw);
        padding: clamp(18px, 3vw, 27px);
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 22px;
        background: rgba(255, 255, 255, .1);
        box-shadow:
            0 28px 80px rgba(0, 0, 0, .32),
            inset 0 1px 0 rgba(255, 255, 255, .17),
            0 0 52px rgba(59, 130, 246, .18);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition:
            width 1.12s cubic-bezier(.16, 1, .3, 1),
            height 1.12s cubic-bezier(.16, 1, .3, 1),
            padding 1.12s cubic-bezier(.16, 1, .3, 1),
            border-radius 1.12s cubic-bezier(.16, 1, .3, 1),
            border-color .5s ease,
            background .5s ease,
            box-shadow .5s ease;
    }

    .crm-entry-emblem img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: brightness(0) invert(1);
        transition: filter .5s ease;
    }

    .crm-entry-copy {
        position: absolute;
        top: calc(50% + 122px);
        left: 50%;
        z-index: 6;
        display: grid;
        justify-items: center;
        width: min(430px, 82vw);
        text-align: center;
        transform: translateX(-50%);
        transition: opacity .3s ease, filter .35s ease, transform .4s ease;
    }

    .crm-entry-kicker {
        color: #93c5fd;
        font-size: .64rem;
        font-weight: 760;
        letter-spacing: .18em;
        text-transform: uppercase;
        animation: crm-entry-copy-in .55s ease .2s both;
    }

    .crm-entry-copy strong {
        margin-top: 13px;
        font-size: clamp(.95rem, 2vw, 1.18rem);
        font-weight: 720;
        letter-spacing: .03em;
        animation: crm-entry-copy-in .55s ease .3s both;
    }

    .crm-entry-track {
        width: min(300px, 66vw);
        height: 2px;
        margin-top: 20px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(148, 163, 184, .14);
    }

    .crm-entry-track span {
        display: block;
        width: 100%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, transparent, #60a5fa 25%, #fff 50%, #60a5fa 75%, transparent);
        transform: translateX(-100%);
        animation: crm-entry-track 1.7s cubic-bezier(.22, 1, .36, 1) .18s forwards;
    }

    .crm-entry-copy small {
        margin-top: 13px;
        color: rgba(191, 219, 254, .58);
        font-size: .62rem;
        font-weight: 650;
        letter-spacing: .14em;
        text-transform: uppercase;
        animation: crm-entry-copy-in .55s ease .42s both;
    }

    .crm-entry-transition.is-docking {
        pointer-events: none;
    }

    .crm-entry-transition.is-docking .crm-entry-panel--left {
        transform: translate3d(-101%, 0, 0);
    }

    .crm-entry-transition.is-docking .crm-entry-panel--right {
        transform: translate3d(101%, 0, 0);
    }

    .crm-entry-transition.is-docking .crm-entry-glow,
    .crm-entry-transition.is-docking .crm-entry-scan {
        opacity: 0;
        filter: blur(10px);
        transform: translate(-50%, -50%) scale(.9);
    }

    .crm-entry-transition.is-docking .crm-entry-stage {
        top: var(--crm-entry-target-y, 36px);
        left: var(--crm-entry-target-x, 56px);
        width: var(--crm-entry-target-width, 48px);
        height: var(--crm-entry-target-height, 48px);
        filter: none;
    }

    .crm-entry-transition.is-docking .crm-entry-ring {
        opacity: 0;
    }

    .crm-entry-transition.is-docking .crm-entry-emblem {
        width: 100%;
        height: 100%;
        padding: 0;
        border-color: transparent;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    .crm-entry-transition.is-docking .crm-entry-emblem img {
        filter: none;
    }

    .crm-entry-transition.is-docking .crm-entry-copy {
        opacity: 0;
        filter: blur(8px);
        transform: translate(-50%, -12px);
    }

    html.crm-entry-running {
        overflow: hidden !important;
    }

    @keyframes crm-entry-failsafe {
        to { visibility: hidden; pointer-events: none; }
    }

    @keyframes crm-entry-arrive {
        from { opacity: 0; transform: translate(-50%, -50%) translateY(24px) scale(.75) rotate(-3deg); filter: blur(10px); }
        to { opacity: 1; transform: translate(-50%, -50%) translateY(0) scale(1) rotate(0); filter: blur(0); }
    }

    @keyframes crm-entry-ring {
        to { transform: rotate(405deg); }
    }

    @keyframes crm-entry-breathe {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(.62); }
        55% { opacity: 1; }
        100% { opacity: .74; transform: translate(-50%, -50%) scale(1.08); }
    }

    @keyframes crm-entry-scan {
        from { opacity: 0; transform: translateX(-52%); }
        28% { opacity: .46; }
        to { opacity: 0; transform: translateX(52%); }
    }

    @keyframes crm-entry-copy-in {
        from { opacity: 0; transform: translateY(9px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes crm-entry-track {
        0% { transform: translateX(-100%); }
        70% { transform: translateX(-8%); }
        100% { transform: translateX(0); }
    }

    @media (max-width: 640px) {
        .crm-entry-copy {
            top: calc(50% + 104px);
        }

        .crm-entry-stage {
            width: 88vw;
            height: 48vw;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .crm-entry-transition {
            display: none !important;
        }
    }
</style>

<script>
    (() => {
        const overlay = document.getElementById('crm-login-entry-transition');
        if (! overlay) return;

        const storageKey = '3rdvn:login-entry';
        let started = false;
        let timers = [];

        const clearTimers = () => {
            timers.forEach((timer) => window.clearTimeout(timer));
            timers = [];
        };

        const visibleLogoTarget = () => {
            const selectors = [
                '.fi-topbar .fi-logo:not(.fi-logo-dark)',
                '.fi-sidebar-header .fi-logo:not(.fi-logo-dark)',
                '.fi-topbar .fi-logo',
                '.fi-sidebar-header .fi-logo',
                '.fi-logo',
            ];

            for (const selector of selectors) {
                for (const element of document.querySelectorAll(selector)) {
                    const rect = element.getBoundingClientRect();

                    if (rect.width > 1 && rect.height > 1) {
                        return { element, rect };
                    }
                }
            }

            return null;
        };

        const validMarker = () => {
            try {
                const raw = window.sessionStorage.getItem(storageKey);
                const timestamp = Number(raw);

                return Boolean(raw) && Number.isFinite(timestamp) && (Date.now() - timestamp < 120000);
            } catch (_) {
                return false;
            }
        };

        const consumeMarker = () => {
            try {
                window.sessionStorage.removeItem(storageKey);
            } catch (_) {
                // Storage may be unavailable in strict privacy mode.
            }
        };

        const finish = () => {
            clearTimers();
            document.documentElement.classList.remove('crm-entry-running');
            overlay.remove();
        };

        const dock = () => {
            const target = visibleLogoTarget();
            const fallback = {
                left: window.innerWidth <= 640 ? 24 : 34,
                top: 18,
                width: window.innerWidth <= 640 ? 44 : 154,
                height: window.innerWidth <= 640 ? 44 : 48,
            };
            const rect = target?.rect ?? fallback;
            const width = Math.max(36, Math.min(rect.width, 190));
            const height = Math.max(36, Math.min(rect.height, 58));
            const x = target ? rect.left + (rect.width / 2) : fallback.left + (width / 2);
            const y = target ? rect.top + (rect.height / 2) : fallback.top + (height / 2);

            overlay.style.setProperty('--crm-entry-target-x', `${x}px`);
            overlay.style.setProperty('--crm-entry-target-y', `${y}px`);
            overlay.style.setProperty('--crm-entry-target-width', `${width}px`);
            overlay.style.setProperty('--crm-entry-target-height', `${height}px`);
            overlay.classList.add('is-docking');
        };

        const start = () => {
            if (started || window.location.pathname.startsWith('/authen/login')) return;

            if (! validMarker()) {
                overlay.remove();

                return;
            }

            started = true;
            consumeMarker();
            document.documentElement.classList.add('crm-entry-running');
            overlay.style.display = '';

            const label = overlay.querySelector('[data-entry-label]');

            timers.push(window.setTimeout(() => {
                if (label) label.textContent = 'Đang đồng bộ dữ liệu và phân quyền';
            }, 720));

            timers.push(window.setTimeout(() => {
                if (label) label.textContent = 'Không gian làm việc đã sẵn sàng';
            }, 1320));

            timers.push(window.setTimeout(dock, 1800));
            timers.push(window.setTimeout(finish, 3350));
            timers.push(window.setTimeout(finish, 4800));
        };

        start();
        document.addEventListener('livewire:navigated', start);
    })();
</script>
