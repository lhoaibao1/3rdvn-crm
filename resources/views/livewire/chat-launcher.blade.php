<div
    class="crm-chat-launcher"
    wire:poll.15s.keep-alive="refreshUnreadCount"
    x-data="{
        inboxOpen: false,
        conversationOpen: false,
        isChatModule: @js($isChatModule) || window.location.pathname.replace(/\/$/, '') === '/tro-chuyen',
        searchOpen: false,
        mobileSearchOpen: false,
        soundEnabled: false,
        enableSound() { this.soundEnabled = true },
        playTone() {
            if (! this.soundEnabled) return;

            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (! AudioContext) return;

            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(740, context.currentTime);
            gain.gain.setValueAtTime(0.0001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.12, context.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.22);
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.24);
            oscillator.addEventListener('ended', () => context.close());
        },
    }"
    x-on:pointerdown.window.once="enableSound()"
    x-on:chat-conversation-opened.window="conversationOpen = true; inboxOpen = false; searchOpen = false; mobileSearchOpen = false"
    x-on:chat-launcher-open.window="conversationOpen = true; inboxOpen = false; searchOpen = false; mobileSearchOpen = false"
    x-on:open-chat.window="conversationOpen = true; inboxOpen = false"
    x-on:close-chat.window="conversationOpen = false"
    x-on:chat-unread-updated.window="playTone()"
    x-on:livewire:navigated.window="isChatModule = window.location.pathname.replace(/\/$/, '') === '/tro-chuyen'; if (isChatModule) { inboxOpen = false; conversationOpen = false }"
    x-on:keydown.escape.window="inboxOpen = false; searchOpen = false; mobileSearchOpen = false"
>
    <div class="crm-chat-quick-search" x-bind:class="{ 'is-mobile-open': mobileSearchOpen }" x-on:click.outside="searchOpen = false; mobileSearchOpen = false">
        <div class="crm-chat-search-shell">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.4-3.4"></path>
            </svg>
            <input
                type="search"
                x-ref="chatSearch"
                wire:model.live.debounce.250ms="search"
                x-on:focus="searchOpen = true"
                x-on:input="searchOpen = true"
                placeholder="Tìm người dùng, hồ sơ, Lead..."
                aria-label="Tìm kiếm toàn hệ thống"
                autocomplete="off"
            >
            <span wire:loading wire:target="search" class="crm-chat-search-spinner" aria-hidden="true"></span>
        </div>

        <div
            class="crm-chat-search-results"
            x-cloak
            x-show="searchOpen && $wire.search.trim().length >= 2"
            x-transition.opacity.duration.120ms
            x-on:click.stop
        >
            @forelse ($results as $result)
                @if ($result['type'] === 'user')
                    <button
                        type="button"
                        wire:key="global-search-{{ $result['type'] }}-{{ $result['id'] }}"
                        wire:click="openConversation({{ $result['id'] }})"
                        class="crm-chat-search-result"
                    >
                @else
                    <a
                        wire:key="global-search-{{ $result['type'] }}-{{ $result['id'] }}"
                        href="{{ $result['url'] }}"
                        class="crm-chat-search-result"
                    >
                @endif
                    @if ($result['avatar'])
                        <img src="{{ $result['avatar'] }}" alt="" class="crm-chat-result-avatar">
                    @else
                        <span class="crm-chat-result-avatar crm-chat-result-avatar--fallback {{ $result['type'] !== 'user' ? 'crm-chat-result-avatar--record' : '' }}">{{ $result['initials'] }}</span>
                    @endif

                    <span class="crm-chat-result-copy">
                        <span class="crm-chat-result-heading">
                            <strong>{{ $result['name'] }}</strong>
                            @if ($result['uid'])
                                <small>{{ $result['uid'] }}</small>
                            @endif
                            <em class="crm-chat-result-category">{{ $result['category'] }}</em>
                        </span>

                        <span class="crm-chat-result-details">
                            @foreach ($result['details'] as $detail)
                                <span class="{{ ($detail['wide'] ?? false) ? 'crm-chat-result-detail-wide' : '' }}"><b>{{ $detail['label'] }}</b>{{ $detail['value'] }}</span>
                            @endforeach
                        </span>
                    </span>

                    @if ($result['type'] === 'user')
                        <svg class="crm-chat-result-action" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>
                        </svg>
                    @else
                        <svg class="crm-chat-result-action" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 17 17 7M8 7h9v9"></path>
                        </svg>
                    @endif
                @if ($result['type'] === 'user')
                    </button>
                @else
                    </a>
                @endif
            @empty
                <div class="crm-chat-search-empty">
                    {{ $searchReady ? 'Không tìm thấy dữ liệu phù hợp.' : 'Nhập thêm mã hoặc thông tin cần tìm.' }}
                </div>
            @endforelse
        </div>
    </div>

    <button
        type="button"
        class="crm-chat-mobile-search-button"
        x-on:click="mobileSearchOpen = ! mobileSearchOpen; searchOpen = mobileSearchOpen; if (mobileSearchOpen) $nextTick(() => $refs.chatSearch.focus())"
        aria-label="Tìm kiếm toàn hệ thống"
        x-bind:aria-expanded="mobileSearchOpen"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="m20 20-3.4-3.4"></path>
        </svg>
    </button>

    <button
        type="button"
        class="crm-chat-icon-button"
        x-cloak
        x-show="! isChatModule"
        x-bind:class="{ 'is-active': inboxOpen }"
        x-on:click="inboxOpen = ! inboxOpen; searchOpen = false; mobileSearchOpen = false"
        aria-label="Mở danh sách tin nhắn"
        x-bind:aria-expanded="inboxOpen"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
            <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>
            <path d="M8 9h8M8 13h5"></path>
        </svg>
        @if ($unreadCount > 0)
            <span class="crm-chat-unread-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    @unless ($isChatModule)
    <div
        class="crm-chat-inbox-backdrop"
        x-cloak
        x-show="! isChatModule && inboxOpen"
        x-transition.opacity.duration.120ms
        x-on:click="inboxOpen = false"
        aria-hidden="true"
    ></div>

    <section
        class="crm-chat-inbox"
        x-cloak
        x-show="! isChatModule && inboxOpen"
        x-transition:enter="transition ease-out duration-160"
        x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-110"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
        role="dialog"
        aria-modal="true"
        aria-label="Danh sách tin nhắn"
    >
        <header class="crm-chat-inbox-header">
            <div>
                <strong>Tin nhắn</strong>
                @if ($unreadCount > 0)
                    <span>{{ $unreadCount }} tin chưa đọc</span>
                @else
                    <span>Trò chuyện nội bộ</span>
                @endif
            </div>
            <div class="crm-chat-inbox-actions">
                <a href="{{ url('/tro-chuyen') }}" title="Mở module Trò chuyện" aria-label="Mở module Trò chuyện">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h6v6M10 14 21 3M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"></path>
                    </svg>
                </a>
                <button type="button" x-on:click="inboxOpen = false" title="Đóng" aria-label="Đóng">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m6 6 12 12M18 6 6 18"></path>
                    </svg>
                </button>
            </div>
        </header>

        <div class="crm-chat-inbox-list">
            @forelse ($conversations as $conversation)
                <button
                    type="button"
                    wire:key="chat-conversation-{{ $conversation['id'] }}"
                    wire:click="openExistingConversation({{ $conversation['id'] }})"
                    class="crm-chat-inbox-item {{ $conversation['unread'] ? 'is-unread' : '' }}"
                >
                    @if ($conversation['avatar'])
                        <img src="{{ $conversation['avatar'] }}" alt="" class="crm-chat-inbox-avatar">
                    @else
                        <span class="crm-chat-inbox-avatar crm-chat-inbox-avatar--fallback">{{ $conversation['initials'] }}</span>
                    @endif
                    <span class="crm-chat-inbox-copy">
                        <span class="crm-chat-inbox-row">
                            <strong>{{ $conversation['title'] }}</strong>
                            @if ($conversation['time']) <small>{{ $conversation['time'] }}</small> @endif
                        </span>
                        <span class="crm-chat-inbox-row">
                            <span class="crm-chat-inbox-preview">{{ $conversation['preview'] }}</span>
                            @if ($conversation['unread']) <i aria-label="Chưa đọc"></i> @endif
                        </span>
                    </span>
                </button>
            @empty
                <div class="crm-chat-inbox-empty">Chưa có cuộc trò chuyện.</div>
            @endforelse
        </div>

        <a class="crm-chat-inbox-footer" href="{{ url('/tro-chuyen') }}">Xem tất cả trong Trò chuyện</a>
    </section>

    <section
        class="crm-chat-window"
        x-cloak
        x-show="! isChatModule && conversationOpen"
        x-transition:enter="transition ease-out duration-160"
        x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-110"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-[0.98]"
        role="dialog"
        aria-label="Cửa sổ trò chuyện"
    >
        <div class="crm-chat-window-body">
            <livewire:wirechat panel="chats" />
        </div>
    </section>
    @endunless
</div>
