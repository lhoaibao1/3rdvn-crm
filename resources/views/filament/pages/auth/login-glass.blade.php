@php
    $settings = \App\Models\UiSetting::current();
    $title = $settings->login_title ?: 'Đăng nhập 3RDVN CRM';
    $subtitle = $settings->login_subtitle ?: 'Hệ thống CRM nội bộ';
@endphp

<x-auth.glass-shell :title="$title" :subtitle="$subtitle">
    @if (session('status'))
        <div class="crm-auth-status" role="status">{{ session('status') }}</div>
    @endif

    <form class="crm-auth-form" wire:submit.prevent="authenticate">
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

        <label class="crm-auth-field">
            <span>Mật khẩu</span>
            <input
                type="password"
                wire:model.defer="data.password"
                autocomplete="current-password"
                placeholder="Nhập mật khẩu"
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

        <a class="crm-auth-link-button" href="{{ route('crm.username.request') }}">
            Quên tên đăng nhập?
        </a>
    </form>
</x-auth.glass-shell>
