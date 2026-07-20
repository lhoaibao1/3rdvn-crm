<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Trò chuyện · {{ \App\Models\UiSetting::current()->app_name ?: '3RDVN CRM' }}</title>

    @if($favicon = \App\Models\UiSetting::current()->favicon_path)
        <link rel="icon" href="{{ asset('storage/'.$favicon) }}">
    @endif

    <link rel="stylesheet" href="{{ asset('fonts/filament/filament/inter/index.css') }}">
    @vite(['resources/css/wirechat.css', 'resources/js/app.js'])
    @livewireStyles
    @wirechatStyles
</head>
<body>
    <main class="wc-app-shell">
        <section class="wc-app-frame">
            @yield('content', $slot ?? null)
        </section>
    </main>

    @livewireScripts
    @wirechatAssets
</body>
</html>
