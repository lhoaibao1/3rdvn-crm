@php
    $settings = \App\Models\UiSetting::current();
    $title = $settings->login_title ?: 'Đăng nhập 3RDVN CRM';
    $subtitle = $settings->login_subtitle ?: 'Hệ thống CRM nội bộ';
@endphp

<x-auth.crm-login-shell :title="$title" :subtitle="$subtitle">
    @if (session('status'))
        <div class="crm-login-status" role="status">{{ session('status') }}</div>
    @endif

    <form
        class="crm-login-form"
        x-on:submit.prevent="
            if ($el.dataset.authPending === 'true') return;

            $el.dataset.authPending = 'true';
            $wire.authenticate()
                .catch(() => {})
                .finally(() => { $el.dataset.authPending = 'false'; });
        "
    >
        <div class="crm-login-field">
            <label class="crm-login-label" for="crm-login-identifier">User / UID</label>
            <div class="crm-login-control @error('data.identifier') is-invalid @enderror">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
                <input
                    id="crm-login-identifier"
                    type="text"
                    wire:model.defer="data.identifier"
                    autocomplete="username"
                    placeholder="Nhập User hoặc UID"
                    aria-invalid="@error('data.identifier') true @else false @enderror"
                    @error('data.identifier') aria-describedby="crm-login-identifier-error" @enderror
                    autofocus
                >
            </div>
            @error('data.identifier')
                <div id="crm-login-identifier-error" class="crm-login-error" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="crm-login-field">
            <label class="crm-login-label" for="crm-login-password">Mật khẩu</label>
            <div class="crm-login-control @error('data.password') is-invalid @enderror">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 10V7a5 5 0 0 1 10 0v3M6.5 10h11A1.5 1.5 0 0 1 19 11.5v7A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-7A1.5 1.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
                <input
                    id="crm-login-password"
                    x-bind:type="passwordVisible ? 'text' : 'password'"
                    wire:model.defer="data.password"
                    autocomplete="current-password"
                    placeholder="Nhập mật khẩu"
                    aria-invalid="@error('data.password') true @else false @enderror"
                    @error('data.password') aria-describedby="crm-login-password-error" @enderror
                >
                <button
                    class="crm-login-password-toggle"
                    type="button"
                    x-on:click="passwordVisible = ! passwordVisible"
                    x-bind:aria-label="passwordVisible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                >
                    <svg x-show="! passwordVisible" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7"/>
                        <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.7"/>
                    </svg>
                    <svg x-cloak x-show="passwordVisible" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m4 4 16 16M10.6 6.15A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a14.3 14.3 0 0 1-2.45 3.05M6.55 6.55C3.9 8.28 2.5 12 2.5 12s3.5 6 9.5 6a9.9 9.9 0 0 0 2.1-.22M9.88 9.88a3 3 0 0 0 4.24 4.24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            @error('data.password')
                <div id="crm-login-password-error" class="crm-login-error" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="crm-login-options">
            <label class="crm-login-remember">
                <input type="checkbox" wire:model.defer="data.remember">
                <span>Ghi nhớ đăng nhập</span>
            </label>
            <a class="crm-login-link" href="{{ route('crm.password.request') }}">Quên mật khẩu?</a>
        </div>

        <button
            class="crm-login-submit"
            type="submit"
            wire:loading.attr="disabled"
            wire:target="authenticate"
        >
            <span wire:loading.remove wire:target="authenticate">Đăng nhập</span>
            <svg wire:loading.remove wire:target="authenticate" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="crm-login-spinner" wire:loading wire:target="authenticate" aria-hidden="true"></span>
            <span wire:loading wire:target="authenticate">Đang xác thực...</span>
        </button>
    </form>

    <p class="crm-login-secondary">
        Không nhớ tên đăng nhập?
        <a class="crm-login-link" href="{{ route('crm.username.request') }}">Khôi phục User/UID</a>
    </p>
</x-auth.crm-login-shell>
