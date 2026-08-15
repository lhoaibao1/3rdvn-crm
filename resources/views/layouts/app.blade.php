@php($settings = \App\Models\UiSetting::current())
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $settings->app_name }}</title>
    @php($faviconPath = $settings->favicon_path ? public_path('storage/'.$settings->favicon_path) : null)
    @php($faviconUrl = $settings->favicon_path ? asset('storage/'.$settings->favicon_path).(is_file($faviconPath) ? '?v='.filemtime($faviconPath) : '') : ($settings->favicon_url ?: asset('favicon.ico').'?v='.filemtime(public_path('favicon.ico'))))
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="{{ $settings->primary_color ?? '#2563eb' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $settings->app_name ?? '3RDVN CRM' }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-vars', ['settings' => $settings])
</head>
<body class="crm-body" x-data="{ sidebarOpen: false, collapsed: {{ $settings->sidebar_default_collapsed ? 'true' : 'false' }} }" :class="{ 'sidebar-collapsed': collapsed }">
    @include('partials.identity-watermark')
    @include('partials.topbar', ['title' => $title ?? 'Dashboard', 'settings' => $settings])
    <div class="crm-shell">
        @include('partials.sidebar', ['settings' => $settings])
        <div class="crm-main">
            <main class="crm-content">
                @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
                @yield('content')
            </main>
        </div>
    </div>
    <script>
        (() => {
            if (!('serviceWorker' in navigator)) return;
            const local = ['localhost', '127.0.0.1'].includes(location.hostname);
            if (!window.isSecureContext && !local) return;
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).then((registration) => registration.update()).catch(() => {}));
        })();
    </script>
</body>
</html>
