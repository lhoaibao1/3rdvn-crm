<x-filament-panels::page>
    <section class="crm-chat-module" aria-label="Trò chuyện nội bộ">
        <livewire:wirechat panel="chats" />
    </section>

    <style>
        .fi-page:has(.crm-chat-module) {
            gap: 0 !important;
        }

        .fi-page:has(.crm-chat-module) > .fi-page-header {
            display: none !important;
        }

        .crm-chat-module {
            width: 100%;
            height: calc(100dvh - var(--crm-topbar-height, 72px) - 32px);
            min-height: 520px;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--crm-border, #e5e7eb);
            border-radius: var(--crm-radius, 12px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        }

        @media (max-width: 768px) {
            .crm-chat-module {
                height: calc(100dvh - var(--crm-topbar-height, 72px) - 16px);
                min-height: 0;
                border-radius: 10px;
            }
        }
    </style>
</x-filament-panels::page>
