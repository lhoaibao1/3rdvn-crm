@php
    $settings = \App\Models\UiSetting::current();
    $user = $this->identifiedUser();
    $avatar = $this->identifiedUserAvatarUrl();
    $title = $settings->login_title ?: 'Đăng nhập 3RDVN CRM';
    $subtitle = $settings->login_subtitle ?: 'Hệ thống CRM nội bộ';
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $pageTitle = $user ? 'Xin chào, '.$user->name : $title;
    $pageSubtitle = $user ? 'Nhập mật khẩu để tiếp tục' : $subtitle;
@endphp

<x-auth.glass-shell :title="$pageTitle" :subtitle="$pageSubtitle">
    @if (session('status'))
        <div class="crm-auth-status" role="status">{{ session('status') }}</div>
    @endif

    @if (! $user)
        <form class="crm-auth-form" wire:submit.prevent="identify">
            <label class="crm-auth-field">
                <span>User/UID</span>
                <input
                    type="text"
                    wire:model.defer="data.identifier"
                    autocomplete="username"
                    placeholder="Nhập User/UID"
                    autofocus
                >
                @error('data.identifier')
                    <div class="crm-auth-inline-error">{{ $message }}</div>
                @enderror
            </label>

            <div class="crm-auth-row">
                <span></span>
                <a class="crm-auth-link" href="{{ route('crm.username.request') }}">Quên tên đăng nhập?</a>
            </div>

            <button
                class="crm-auth-button"
                type="submit"
                wire:loading.attr="disabled"
                wire:target="identify"
            >
                Tiếp tục
            </button>
        </form>
    @else
        <div class="crm-auth-account">
            @if ($avatar)
                <img class="crm-auth-avatar" src="{{ $avatar }}" alt="Avatar {{ $user->name }}">
            @else
                <span class="crm-auth-avatar-fallback">{{ $initials ?: '3' }}</span>
            @endif
            <div>
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->uid ?: $user->employee_code ?: $user->email }}</span>
            </div>
        </div>

        <form class="crm-auth-form" wire:submit.prevent="authenticate">
            <label class="crm-auth-field">
                <span>Mật khẩu</span>
                <input
                    type="password"
                    wire:model.defer="data.password"
                    autocomplete="current-password"
                    placeholder="Nhập mật khẩu"
                    autofocus
                >
                @error('data.password')
                    <div class="crm-auth-inline-error">{{ $message }}</div>
                @enderror
            </label>

            <div class="crm-auth-row">
                <label class="crm-auth-check">
                    <input type="checkbox" wire:model.defer="data.remember">
                    <span>Ghi nhớ</span>
                </label>
                <a class="crm-auth-link" href="{{ route('crm.password.request') }}">Quên mật khẩu?</a>
            </div>

            <button
                class="crm-auth-button"
                type="submit"
                wire:loading.attr="disabled"
                wire:target="authenticate"
            >
                Đăng nhập
            </button>

            <button class="crm-auth-link-button" type="button" wire:click="changeIdentifier">
                Quay lại
            </button>
        </form>
    @endif
</x-auth.glass-shell>
