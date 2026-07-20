@php
    $user = auth()->user();
    $modules = \App\Models\CrmModule::query()->where('is_active', true)->orderBy('sort_order')->get()
        ->filter(fn ($module) => \App\Support\Permissions\RoleAccess::canSeeModule($user, $module));
    $icons = [
        'grid' => 'M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z',
        'squares' => 'M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z',
        'user-plus' => 'M15 14a4 4 0 0 0-8 0v1h8v-1ZM11 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm7 7V7h2v3h3v2h-3v3h-2v-3h-3v-2h3Z',
        'file' => 'M6 2h9l5 5v15H6V2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Z',
        'clipboard' => 'M9 3h6l1 2h3v17H5V5h3l1-2Zm1.2 2-.5 1h4.6l-.5-1h-3.6ZM7 8v12h10V8H7Zm2 3h6v2H9v-2Zm0 4h6v2H9v-2Z',
        'check' => 'M9.5 16.6 4.9 12l1.4-1.4 3.2 3.2 8.2-8.2L19.1 7 9.5 16.6Z',
        'check-badge' => 'M12 2 14.2 4.1 17.2 3.5 18.2 6.4 21 7.6 19.8 10.4 21 13.2 18.2 14.4 17.2 17.3 14.2 16.7 12 18.8 9.8 16.7 6.8 17.3 5.8 14.4 3 13.2 4.2 10.4 3 7.6 5.8 6.4 6.8 3.5 9.8 4.1 12 2Zm-1 11.2-2.4-2.4-1.4 1.4L11 16l6-6-1.4-1.4-4.6 4.6Z',
        'link' => 'M8 12a4 4 0 0 1 4-4h3v2h-3a2 2 0 0 0 0 4h3v2h-3a4 4 0 0 1-4-4Zm1 1h6v-2H9v2Zm0-5H6a4 4 0 0 0 0 8h3v-2H6a2 2 0 0 1 0-4h3V8Z',
        'arrows' => 'M7 7h9.2l-2.6-2.6L15 3l5 5-5 5-1.4-1.4L16.2 9H7V7Zm10 10H7.8l2.6 2.6L9 21l-5-5 5-5 1.4 1.4L7.8 15H17v2Z',
        'users' => 'M16 11a4 4 0 1 0-3.2-6.4A5 5 0 0 1 14 8c0 1.1-.4 2.2-1 3h3Zm-8 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.3 0-6 1.7-6 3.8V20h12v-3.2C14 14.7 11.3 13 8 13Zm8 0c-.5 0-1 0-1.5.1 1 .9 1.5 2.1 1.5 3.7V20h6v-3.2c0-2.1-2.7-3.8-6-3.8Z',
        'shield' => 'M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Zm-1 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L18 9l-7 7Z',
        'settings' => 'M19.4 13.5c.1-.5.1-1 .1-1.5s0-1-.1-1.5l2-1.5-2-3.5-2.4 1a8.7 8.7 0 0 0-2.6-1.5L14 2h-4l-.4 2.5A8.7 8.7 0 0 0 7 6L4.6 5l-2 3.5 2 1.5c-.1.5-.1 1-.1 1.5s0 1 .1 1.5l-2 1.5 2 3.5 2.4-1c.8.7 1.6 1.2 2.6 1.5L10 22h4l.4-2.5c1-.3 1.8-.8 2.6-1.5l2.4 1 2-3.5-2-1.5ZM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z',
        'cog' => 'M19.4 13.5c.1-.5.1-1 .1-1.5s0-1-.1-1.5l2-1.5-2-3.5-2.4 1a8.7 8.7 0 0 0-2.6-1.5L14 2h-4l-.4 2.5A8.7 8.7 0 0 0 7 6L4.6 5l-2 3.5 2 1.5c-.1.5-.1 1-.1 1.5s0 1 .1 1.5l-2 1.5 2 3.5 2.4-1c.8.7 1.6 1.2 2.6 1.5L10 22h4l.4-2.5c1-.3 1.8-.8 2.6-1.5l2.4 1 2-3.5-2-1.5ZM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z',
    ];
@endphp

<aside class="sidebar" :class="{ 'open': sidebarOpen }">
    <nav class="nav-list">
        @foreach($modules as $module)
            <a class="nav-item {{ request()->routeIs($module->route_name) || request()->is($module->slug.'*') ? 'active' : '' }}" href="{{ route($module->route_name) }}" title="{{ $module->label }}">
                <svg viewBox="0 0 24 24"><path d="{{ $icons[$module->icon] ?? $icons['grid'] }}"/></svg>
                <span>{{ $module->label }}</span>
            </a>
        @endforeach
    </nav>
</aside>
<div class="drawer-backdrop" x-show="sidebarOpen" @click="sidebarOpen = false"></div>
