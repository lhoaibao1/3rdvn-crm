<div id="crm-page-loader" class="crm-page-loader" role="status" aria-live="polite" aria-label="Đang tải hệ thống" style="display: none">
    <div class="crm-page-loader__panels" aria-hidden="true">
        @for ($panel = 0; $panel < 5; $panel++)
            <span class="crm-page-loader__panel"></span>
        @endfor
    </div>

    <div class="crm-page-loader__center">
        <img class="crm-page-loader__logo" src="{{ $logoUrl }}" alt="{{ $appName }}">
        <div class="crm-page-loader__label">
            <span class="crm-page-loader__dot" aria-hidden="true"></span>
            <span>Đang tải hệ thống</span>
        </div>
    </div>

    <div class="crm-page-loader__meta">3RDVN CRM · {{ now()->year }}</div>
    <div class="crm-page-loader__percent" data-loader-percent>0%</div>
    <div class="crm-page-loader__track" aria-hidden="true"><span data-loader-progress></span></div>
</div>

<style>
    .crm-page-loader {
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        overflow: hidden;
        background: #fff;
        color: #0a0a0a;
        isolation: isolate;
        animation: crm-loader-failsafe .01s 8s forwards;
    }

    .crm-page-loader__panels {
        position: absolute;
        inset: 0;
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    .crm-page-loader__panel {
        background: #fff;
        will-change: transform;
    }

    .crm-page-loader__panel + .crm-page-loader__panel {
        border-left: 1px solid rgba(15, 23, 42, .035);
    }

    .crm-page-loader__center {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 20px;
        padding: 28px;
        pointer-events: none;
        animation: crm-loader-logo-in .9s cubic-bezier(.22, 1, .36, 1) .12s both;
    }

    .crm-page-loader__logo {
        display: block;
        width: min(96vw, 1280px);
        max-height: 42vh;
        object-fit: contain;
        animation: crm-loader-logo-float 2.2s ease-in-out 1.1s infinite alternate;
    }

    .crm-page-loader__label {
        display: flex;
        align-items: center;
        gap: 9px;
        color: rgba(10, 10, 10, .38);
        font-family: Georgia, 'Times New Roman', serif;
        font-size: clamp(1rem, 2vw, 1.4rem);
        font-style: italic;
        letter-spacing: .04em;
    }

    .crm-page-loader__dot {
        width: 8px;
        height: 8px;
        flex: 0 0 auto;
        border-radius: 999px;
        background: rgba(10, 10, 10, .58);
        animation: crm-loader-dot .65s ease-in-out infinite alternate;
    }

    .crm-page-loader__meta {
        position: absolute;
        z-index: 2;
        bottom: 28px;
        left: 24px;
        color: rgba(10, 10, 10, .26);
        font: 600 .52rem/1 ui-sans-serif, system-ui, sans-serif;
        letter-spacing: .25em;
        text-transform: uppercase;
        pointer-events: none;
    }

    .crm-page-loader__percent {
        position: absolute;
        z-index: 2;
        right: 24px;
        bottom: 18px;
        color: rgba(10, 10, 10, .84);
        font: 900 clamp(4rem, 10vw, 8rem)/.82 ui-sans-serif, system-ui, sans-serif;
        letter-spacing: -.05em;
        font-variant-numeric: tabular-nums;
        pointer-events: none;
    }

    .crm-page-loader__track {
        position: absolute;
        z-index: 3;
        inset: auto 0 0;
        height: 1px;
        background: rgba(10, 10, 10, .08);
        pointer-events: none;
    }

    .crm-page-loader__track > span {
        display: block;
        width: 100%;
        height: 100%;
        background: rgba(10, 10, 10, .4);
        transform: scaleX(0);
        transform-origin: left center;
        will-change: transform;
    }

    @keyframes crm-loader-logo-in {
        from { opacity: 0; transform: translateY(18px); filter: blur(8px); }
        to { opacity: 1; transform: translateY(0); filter: blur(0); }
    }

    @keyframes crm-loader-logo-float {
        from { transform: translateY(0); }
        to { transform: translateY(-6px); }
    }

    @keyframes crm-loader-dot { to { opacity: .15; } }
    @keyframes crm-loader-failsafe { to { visibility: hidden; pointer-events: none; } }

    @media (max-width: 640px) {
        .crm-page-loader__center { gap: 15px; padding: 20px; }
        .crm-page-loader__logo { width: 92vw; max-height: 32vh; }
        .crm-page-loader__percent { right: 16px; bottom: 16px; }
        .crm-page-loader__meta { left: 16px; bottom: 22px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .crm-page-loader__center,
        .crm-page-loader__logo,
        .crm-page-loader__dot { animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
    }
</style>

<script data-navigate-once>
    (() => {
        const loader = document.getElementById('crm-page-loader');
        if (!loader || loader.dataset.initialized === 'true') return;

        loader.dataset.initialized = 'true';

        const loginPath = window.location.pathname.startsWith('/authen/login');
        let shouldShow = true;

        try {
            const visitedKey = '3rdvn:loader:visited';
            const afterLoginKey = '3rdvn:loader:after-login';
            const firstVisit = sessionStorage.getItem(visitedKey) !== '1';
            const afterLogin = sessionStorage.getItem(afterLoginKey) === '1';

            shouldShow = loginPath || firstVisit || afterLogin;
            sessionStorage.setItem(visitedKey, '1');

            if (afterLogin) sessionStorage.removeItem(afterLoginKey);

            if (loginPath) {
                document.addEventListener('submit', () => {
                    sessionStorage.setItem(afterLoginKey, '1');
                }, { capture: true, once: true });
            }
        } catch (_) {
            shouldShow = true;
        }

        if (!shouldShow) {
            loader.remove();
            return;
        }

        loader.style.display = '';

        const percent = loader.querySelector('[data-loader-percent]');
        const progress = loader.querySelector('[data-loader-progress]');
        const panels = [...loader.querySelectorAll('.crm-page-loader__panel')];
        const center = loader.querySelector('.crm-page-loader__center');
        const meta = loader.querySelector('.crm-page-loader__meta');
        const previousOverflow = document.body.style.overflow;
        const startedAt = performance.now();
        let current = 0;
        let ready = document.readyState === 'complete';
        let frame = 0;
        let exiting = false;

        document.body.style.overflow = 'hidden';

        const markReady = () => { ready = true; };
        window.addEventListener('load', markReady, { once: true });
        const fallback = window.setTimeout(() => {
            ready = true;
        }, 6000);

        const finish = async () => {
            if (exiting) return;
            exiting = true;

            const fadeAnimations = [center, percent, meta].filter(Boolean).map((element, index) => element.animate(
                [{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(-16px)' }],
                { duration: 360, delay: index * 45, easing: 'cubic-bezier(.4,0,1,1)', fill: 'forwards' },
            ));
            const panelAnimations = panels.map((panel, index) => panel.animate(
                [{ transform: 'translateY(0)' }, { transform: 'translateY(-100%)' }],
                { duration: 780, delay: 180 + index * 65, easing: 'cubic-bezier(.76,0,.24,1)', fill: 'forwards' },
            ));

            await Promise.all([...fadeAnimations, ...panelAnimations].map((animation) => animation.finished.catch(() => null)));
            cancelAnimationFrame(frame);
            window.clearTimeout(fallback);
            window.removeEventListener('load', markReady);
            document.body.style.overflow = previousOverflow;
            loader.remove();
        };

        const tick = (time) => {
            const canFinish = ready && time - startedAt >= 700;
            const target = canFinish ? 1 : .9;
            current += (target - current) * .075;
            if (canFinish && current > .995) current = 1;

            const value = Math.min(100, Math.round(current * 100));
            if (percent) percent.textContent = `${value}%`;
            if (progress) progress.style.transform = `scaleX(${current})`;

            if (current >= 1) return void finish();
            frame = requestAnimationFrame(tick);
        };

        frame = requestAnimationFrame(tick);
    })();
</script>

<noscript><style>#crm-page-loader { display: none !important; }</style></noscript>
