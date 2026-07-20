<x-filament-panels::page>
    @php($mailboxUrl = $this->mailboxUrl())

    <script>
        (() => {
            const isMobile = window.matchMedia(
                '(max-width: 768px), (pointer: coarse)',
            ).matches;

            if (isMobile && (window.top === window.self)) {
                window.location.replace(@js($mailboxUrl));
            }
        })();
    </script>

    <section class="crm-mail-module">
        <div class="crm-mail-mobile-loading" aria-live="polite">
            <span>Đang mở hộp thư...</span>
            <a href="{{ $mailboxUrl }}">Mở hộp thư</a>
        </div>
        <div class="crm-mail-frame" wire:ignore>
            <iframe
                id="crm-mail-frame"
                src="{{ $mailboxUrl }}"
                title="3RDVN Mail"
                loading="eager"
                referrerpolicy="same-origin"
                allow="clipboard-read; clipboard-write"
            ></iframe>
        </div>
    </section>

    <style>
        .fi-page:has(.crm-mail-module) {
            gap: 0 !important;
        }

        .fi-page:has(.crm-mail-module) > .fi-page-header {
            display: none !important;
        }

        .crm-mail-module {
            width: 100%;
            height: calc(100dvh - var(--crm-topbar-height, 72px) - 32px);
            min-height: 520px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--crm-border, #e5e7eb);
            border-radius: var(--crm-radius, 12px);
            box-shadow: 0 10px 30px rgb(15 23 42 / 6%);
        }

        .crm-mail-mobile-loading {
            display: none;
            flex: 1 1 auto;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
            color: #64748b;
            font-weight: 600;
        }

        .crm-mail-frame {
            min-height: 0;
            flex: 1 1 auto;
            overflow: hidden;
            background: #fff;
        }

        .crm-mail-frame iframe {
            width: 100%;
            height: 100%;
            display: block;
            border: 0;
            background: #fff;
        }

        .crm-mail-mobile-loading a {
            color: var(--primary-600, #2563eb);
            font-weight: 700;
            text-decoration: none;
        }

        .crm-mail-mobile-loading a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px), (pointer: coarse) {
            .crm-mail-module {
                height: calc(100dvh - var(--crm-topbar-height, 72px) - 16px);
                min-height: 0;
                border-radius: 10px;
            }

            .crm-mail-mobile-loading {
                display: flex;
            }

            .crm-mail-frame {
                display: none;
            }
        }
    </style>
</x-filament-panels::page>
