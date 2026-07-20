@php
    $user = auth()->user();
    $roles = $user?->getRoleNames()->implode(', ');
    $initial = mb_substr($user->name ?: $user->email, 0, 1);
@endphp
<header class="topbar">
    <div class="topbar-title">
        <button class="icon-button mobile-only" type="button" @click="sidebarOpen = true">
            <svg viewBox="0 0 24 24"><path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z"/></svg>
        </button>
        <button class="icon-button desktop-only" type="button" @click="collapsed = !collapsed" :title="collapsed ? 'Mở sidebar' : 'Ẩn sidebar'">
            <svg x-show="!collapsed" viewBox="0 0 24 24"><path d="M15 6 9 12l6 6V6Z"/></svg>
            <svg x-show="collapsed" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6V6Z"/></svg>
        </button>
        <div class="topbar-brand"><span class="topbar-brand-mark">3</span>{{ $settings->app_name }}</div>
        <div>
            <h1>{{ $title }}</h1>
            <p>{{ now()->format('d/m/Y') }}</p>
        </div>
    </div>
    <div class="topbar-actions">
        @if($settings->show_search)
            <label class="search-box">
                <svg viewBox="0 0 24 24"><path d="m21 19.6-4.7-4.7a7.5 7.5 0 1 0-1.4 1.4l4.7 4.7 1.4-1.4ZM4.5 10.5a6 6 0 1 1 12 0 6 6 0 0 1-12 0Z"/></svg>
                <input placeholder="Tìm lead, hồ sơ, SĐT...">
            </label>
        @endif
        <button class="icon-button notify-button" type="button" title="Thông báo">
            <svg viewBox="0 0 24 24"><path d="M12 22a2.6 2.6 0 0 0 2.45-1.75h-4.9A2.6 2.6 0 0 0 12 22Zm7-6.2V11a7 7 0 1 0-14 0v4.8L3.25 18v1h17.5v-1L19 15.8Z"/></svg>
            <span class="notify-dot"></span>
        </button>
        <div class="user-chip">
            <div class="avatar">{{ strtoupper($initial) }}</div>
            <div>
                <strong>{{ $user->name }}</strong>
                <span>
                    @if($settings->show_employee_code && $user->employee_code){{ $user->employee_code }}@endif
                    @if($settings->show_user_role && $roles){{ $user->employee_code ? ' · ' : '' }}{{ $roles }}@endif
                </span>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="icon-button danger" title="Đăng xuất">
                <svg viewBox="0 0 24 24"><path d="M10 17v-2h4v-2h-4v-2l-4 3 4 3Zm1-15h9v20h-9v-2h7V4h-7V2Z"/></svg>
            </button>
        </form>
    </div>
</header>
