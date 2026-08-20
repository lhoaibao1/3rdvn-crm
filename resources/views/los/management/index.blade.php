@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN');
    $user = auth()->user();
    $userCode = $user ? ($user->employee_code ?: ($user->uid ?: ('ID: ' . $user->id))) : 'GUEST';
    $userName = $user ? $user->name : 'Khách';
    $userRole = $user ? (method_exists($user, 'getRoleNames') && $user->getRoleNames()->isNotEmpty() ? $user->getRoleNames()->join(', ') : ($user->role ?? 'Thành viên')) : 'Khách';
    $userInitials = $user ? Str::substr($user->name, 0, 2) : '3R';
    $userWatermarkName = $user ? ($userName . ' (' . $userCode . ')') : 'GUEST';
@endphp
<!doctype html>
<html lang="vi" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAPP LOS · Quản lý Hồ sơ &middot; {{ $brandName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <script>
        (function() {
            var t = localStorage.getItem('los_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <style>
        :root, [data-theme="dark"] {
            --brand-primary: #3b82f6;
            --brand-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --navy-bg: #070a13;
            --navy-panel: #0d1322;
            --navy-card: #131b2e;
            --navy-border: rgba(255, 255, 255, 0.09);
            --text-title: #f8fafc;
            --text-body: #cbd5e1;
            --text-muted: #64748b;
            --card-bg: rgba(255, 255, 255, 0.02);
            --card-border: rgba(255, 255, 255, 0.06);
            --input-bg: #020617;
            --header-bg: #0d1322;
            --table-header-bg: #040914;
            --row-hover-bg: rgba(59, 130, 246, 0.08);
            --active-row-bg: rgba(59, 130, 246, 0.12);
            --inline-dossier-bg: #090f1e;
            --inline-header-bg: #060a14;
            --hero-bg: #070d1a;
            --tab-bar-bg: #040812;
            --footer-bg: #070a13;
            --card-money-bg: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(6, 78, 59, 0.12) 100%);
            --card-money-border: rgba(16, 185, 129, 0.35);
            --watermark-text: rgba(255, 255, 255, 0.75);
            --watermark-sub: rgba(148, 163, 184, 0.75);
            --watermark-opacity: 0.11;
            --radius-lg: 10px;
            --radius-md: 6px;
        }

        [data-theme="light"] {
            --brand-primary: #2563eb;
            --brand-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --navy-bg: #f8fafc;
            --navy-panel: #ffffff;
            --navy-card: #f1f5f9;
            --navy-border: #e2e8f0;
            --text-title: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --card-bg: #f8fafc;
            --card-border: #e2e8f0;
            --input-bg: #ffffff;
            --header-bg: #ffffff;
            --table-header-bg: #f1f5f9;
            --row-hover-bg: #f8fafc;
            --active-row-bg: #eff6ff;
            --inline-dossier-bg: #ffffff;
            --inline-header-bg: #f8fafc;
            --hero-bg: #f8fafc;
            --tab-bar-bg: #f1f5f9;
            --footer-bg: #f1f5f9;
            --card-money-bg: linear-gradient(135deg, rgba(16, 185, 129, 0.06) 0%, rgba(209, 250, 229, 0.4) 100%);
            --card-money-border: rgba(16, 185, 129, 0.35);
            --watermark-text: rgba(15, 23, 42, 0.65);
            --watermark-sub: rgba(100, 116, 139, 0.65);
            --watermark-opacity: 0.07;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: var(--text-body);
            background: var(--navy-bg);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        button, input, select { font: inherit; }

        /* Security Watermark */
        .security-watermark-layer {
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
            user-select: none;
            overflow: hidden;
            opacity: var(--watermark-opacity);
            transition: opacity 0.2s ease;
        }

        /* Topbar */
        .sapp-header {
            position: sticky;
            top: 0;
            z-index: 40;
            height: 56px;
            padding: 0 20px;
            background: var(--header-bg);
            border-bottom: 1px solid var(--navy-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .header-left { display: flex; align-items: center; gap: 24px; }
        .header-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--brand-gradient);
            display: grid;
            place-items: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 13.5px;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        .brand-meta strong {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--text-title);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sapp-tag {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #3b82f6;
            font-size: 9.5px;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 4px;
        }

        /* Header Navigation Tabs */
        .nav-links { display: flex; align-items: center; gap: 4px; }
        .nav-item {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 12.5px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .nav-item:hover { color: var(--text-title); background: var(--card-bg); }
        .nav-item.active {
            color: var(--brand-primary);
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .header-actions { display: flex; align-items: center; gap: 10px; }

        /* Realtime Live Status Pill */
        .realtime-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 4px 6px 4px 10px;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            color: #10b981;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 4px rgba(16, 185, 129, 0.12);
        }

        .realtime-status-pill:hover {
            background: rgba(16, 185, 129, 0.16);
            border-color: #10b981;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.25);
            transform: translateY(-1px);
        }

        .realtime-status-pill.live-paused {
            background: rgba(148, 163, 184, 0.1);
            border-color: rgba(148, 163, 184, 0.3);
            color: var(--text-muted);
            box-shadow: none;
        }

        .realtime-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            position: relative;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.8);
        }

        .realtime-pulse-dot.active::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            border: 1.5px solid #10b981;
            animation: livePulseRadar 1.8s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        }

        .realtime-pulse-dot.paused {
            background: #94a3b8;
            box-shadow: none;
        }

        @keyframes livePulseRadar {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.4); opacity: 0; }
        }

        .btn-sync-now {
            background: transparent;
            border: none;
            color: inherit;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
        }

        .btn-sync-now:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .btn-sync-now.syncing svg {
            animation: spinSync 0.7s linear infinite;
        }

        @keyframes spinSync {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Row Flash Highlight on Realtime update */
        @keyframes liveRowFlash {
            0% { background-color: rgba(59, 130, 246, 0.32); }
            50% { background-color: rgba(59, 130, 246, 0.15); }
            100% { background-color: transparent; }
        }

        .live-row-pulse {
            animation: liveRowFlash 2.5s ease-out;
        }

        /* Floating Realtime Live Toast */
        .live-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .live-toast {
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: 12px;
            padding: 10px 16px;
            color: var(--text-title);
            font-size: 12.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.08);
            opacity: 0;
            transform: translateY(12px) scale(0.95);
            transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: auto;
            backdrop-filter: blur(16px);
        }

        .live-toast.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .live-toast--success { border-left: 4px solid #10b981; }
        .live-toast--info { border-left: 4px solid var(--brand-primary); }
        .live-toast--warning { border-left: 4px solid #f59e0b; }

        /* User Menu & Dropdown Popover */
        .user-menu-wrapper {
            position: relative;
            display: inline-block;
        }

        .user-badge-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px 4px 5px;
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            border-radius: 999px;
            cursor: pointer;
            color: var(--text-title);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
            outline: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .user-badge-btn:hover, .user-badge-btn.active {
            background: var(--navy-card);
            border-color: rgba(59, 130, 246, 0.45);
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.15);
            transform: translateY(-1px);
        }

        .user-avatar-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--brand-gradient);
            display: grid;
            place-items: center;
            font-size: 10.5px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .user-code-label {
            font-size: 12px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-title);
            letter-spacing: 0.02em;
        }

        .user-chevron {
            color: var(--text-muted);
            transition: transform 0.2s ease;
            margin-left: 2px;
        }

        .user-badge-btn.active .user-chevron {
            transform: rotate(180deg);
            color: var(--brand-primary);
        }

        /* User Dropdown Menu Popover */
        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 275px;
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: 14px;
            box-shadow: 0 16px 40px -6px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.05);
            padding: 12px;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(0.97);
            transform-origin: top right;
            transition: opacity 0.18s ease, transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.18s;
            backdrop-filter: blur(16px);
        }

        .user-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        /* Profile Card in Dropdown */
        .user-profile-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 4px 4px 10px;
        }

        .user-profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--brand-gradient);
            display: grid;
            place-items: center;
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(37, 99, 235, 0.35);
            text-transform: uppercase;
        }

        .user-profile-info {
            flex: 1;
            min-width: 0;
        }

        .user-profile-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-title);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .user-profile-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .user-profile-code {
            font-size: 10.5px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-muted);
        }

        .user-role-badge {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.35);
            color: var(--brand-primary);
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .user-menu-divider {
            height: 1px;
            background: var(--navy-border);
            margin: 8px 0;
        }

        /* Theme Switcher inside Dropdown */
        .theme-switch-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s ease;
            user-select: none;
        }

        .theme-switch-item:hover {
            background: var(--card-bg);
        }

        .theme-item-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-icon-circle {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: var(--input-bg);
            border: 1px solid var(--navy-border);
            color: var(--brand-primary);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .menu-item-text {
            display: flex;
            flex-direction: column;
        }

        .menu-item-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-title);
            line-height: 1.2;
        }

        .menu-item-subtitle {
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .theme-toggle-switch {
            width: 38px;
            height: 22px;
            border-radius: 999px;
            background: var(--input-bg);
            border: 1px solid var(--navy-border);
            position: relative;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            flex-shrink: 0;
        }

        [data-theme="light"] .theme-toggle-switch {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }

        .theme-toggle-slider {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--brand-primary);
            position: absolute;
            top: 2px;
            left: 2px;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
        }

        [data-theme="light"] .theme-toggle-slider {
            transform: translateX(16px);
            background: #f59e0b;
        }

        /* Logout Button inside Dropdown */
        .user-logout-form {
            margin: 0;
            padding: 0;
        }

        .user-logout-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 12px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 10px;
            color: #ef4444;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .user-logout-btn:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
            transform: translateY(-1px);
        }

        /* Main Container */
        .sapp-main {
            width: min(1600px, 100%);
            margin: 0 auto;
            padding: 16px 20px 30px;
            flex: 1;
        }

        /* ─── 5-Card Metric Overview Dashboard ─── */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 14px;
        }
        @media (max-width: 1280px) {
            .metrics-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        }

        .metric-card {
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: var(--radius-lg);
            padding: 16px 18px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        .metric-card--primary { border-top: 3px solid var(--brand-primary); }
        .metric-card--primary:hover { border-color: var(--brand-primary); }

        .metric-card--warning { border-top: 3px solid #f59e0b; }
        .metric-card--warning:hover { border-color: #f59e0b; }

        .metric-card--success { border-top: 3px solid #10b981; }
        .metric-card--success:hover { border-color: #10b981; }

        .metric-card--danger { border-top: 3px solid #ef4444; }
        .metric-card--danger:hover { border-color: #ef4444; }

        .metric-card--money {
            background: var(--card-money-bg);
            border: 1px solid var(--card-money-border);
            border-top: 3px solid #10b981;
        }
        .metric-card--money:hover {
            border-color: #10b981;
            box-shadow: 0 8px 28px rgba(16, 185, 129, 0.2);
        }

        .metric-content { flex: 1; min-width: 0; }
        .metric-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .metric-lbl {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .metric-pill {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
            text-transform: uppercase;
        }
        .metric-pill--primary { background: rgba(59, 130, 246, 0.12); color: var(--brand-primary); border: 1px solid rgba(59, 130, 246, 0.25); }
        .metric-pill--warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25); }
        .metric-pill--success { background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); }
        .metric-pill--danger { background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25); }
        .metric-pill--money { background: rgba(16, 185, 129, 0.2); color: #059669; border: 1px solid rgba(16, 185, 129, 0.4); }

        .metric-val {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-title);
            line-height: 1.1;
            margin-bottom: 8px;
            font-family: 'JetBrains Mono', monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .metric-val--warning { color: #f59e0b; }
        .metric-val--success { color: #10b981; }
        .metric-val--danger { color: #ef4444; }
        .metric-val--money {
            color: #10b981;
            font-size: 21px;
            text-shadow: 0 0 16px rgba(16, 185, 129, 0.2);
        }

        .metric-footer { display: flex; flex-direction: column; gap: 4px; }
        .metric-mini-bar-track {
            height: 4px;
            border-radius: 999px;
            background: var(--input-bg);
            overflow: hidden;
            width: 100%;
        }
        .metric-mini-bar-fill { height: 100%; border-radius: 999px; }
        .metric-mini-bar-fill--warning { background: #f59e0b; }
        .metric-mini-bar-fill--success { background: #10b981; }
        .metric-mini-bar-fill--danger { background: #ef4444; }

        .metric-trend-text {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .metric-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            margin-left: 10px;
            transition: transform 0.2s ease;
        }
        .metric-card:hover .metric-icon-box { transform: scale(1.08); }
        .metric-icon-box--primary { background: rgba(59, 130, 246, 0.12); color: var(--brand-primary); }
        .metric-icon-box--warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .metric-icon-box--success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .metric-icon-box--danger { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
        .metric-icon-box--money { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); box-shadow: 0 2px 10px rgba(16, 185, 129, 0.25); }

        /* ─── Visual Distribution Bar (Biểu đồ phân bổ tỷ lệ) ─── */
        .distribution-panel {
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: var(--radius-lg);
            padding: 12px 18px;
            margin-bottom: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .dist-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .dist-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }
        .dist-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: var(--text-muted);
        }
        .dist-legend-item strong { color: var(--text-title); }
        .dist-dot { width: 9px; height: 9px; border-radius: 50%; }
        .dist-dot--warning { background: #f59e0b; box-shadow: 0 0 6px rgba(245, 158, 11, 0.5); }
        .dist-dot--success { background: #10b981; box-shadow: 0 0 6px rgba(16, 185, 129, 0.5); }
        .dist-dot--danger { background: #ef4444; box-shadow: 0 0 6px rgba(239, 68, 68, 0.5); }

        .dist-progress-bar {
            height: 9px;
            border-radius: 999px;
            background: var(--input-bg);
            overflow: hidden;
            display: flex;
            width: 100%;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        .dist-segment { height: 100%; transition: width 0.3s ease; }
        .dist-segment--warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .dist-segment--success { background: linear-gradient(90deg, #10b981, #059669); }
        .dist-segment--danger { background: linear-gradient(90deg, #ef4444, #dc2626); }

        /* ─── Filter Panel (Modern Redesign) ─── */
        .toolbar-panel {
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 14px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(12px);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .inline-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 9px;
        }

        /* Omnibox (Search Input) */
        .omnibox-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--input-bg);
            border: 1px solid var(--navy-border);
            border-radius: 10px;
            padding: 0 10px 0 12px;
            height: 38px;
            width: 270px;
            max-width: 100%;
            transition: all 0.2s ease;
            position: relative;
        }

        .omnibox-box:focus-within {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
            background: var(--navy-card);
        }

        .omnibox-icon {
            color: var(--brand-primary);
            display: flex;
            align-items: center;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .omnibox-box:focus-within .omnibox-icon {
            transform: scale(1.1);
        }

        .omnibox-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-title);
            font-size: 12.5px;
            font-weight: 500;
            outline: none;
            padding: 0;
            font-family: inherit;
            min-width: 0;
        }

        .omnibox-input::placeholder {
            color: var(--text-muted);
            font-size: 12px;
        }

        .btn-clear-search {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 3px;
            border-radius: 50%;
            display: none;
            place-items: center;
            transition: color 0.15s ease;
        }
        .btn-clear-search:hover { color: var(--text-title); }

        /* Filter Group Chips */
        .filter-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--input-bg);
            border: 1px solid var(--navy-border);
            border-radius: 10px;
            padding: 0 10px 0 10px;
            height: 38px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }

        .filter-group:hover {
            border-color: rgba(59, 130, 246, 0.45);
            background: var(--navy-card);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter-group.active-filter {
            background: rgba(59, 130, 246, 0.09);
            border-color: rgba(59, 130, 246, 0.45);
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.25);
        }

        .filter-label {
            font-size: 10px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            white-space: nowrap;
            letter-spacing: 0.04em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .filter-group.active-filter .filter-label {
            color: var(--brand-primary);
        }

        .filter-dot-indicator {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--brand-primary);
            box-shadow: 0 0 6px rgba(59, 130, 246, 0.6);
            display: none;
        }
        .filter-group.active-filter .filter-dot-indicator { display: inline-block; }

        /* ─── LUXURY SEARCHABLE SELECT COMPONENT (LOS) ─── */
        .los-custom-select {
            position: relative;
            display: inline-block;
            user-select: none;
        }
        .los-select-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            background: transparent;
            border: none;
            color: var(--text-title);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            padding: 3px 6px;
            border-radius: 5px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .los-select-trigger:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #38bdf8;
        }
        .los-select-trigger .chevron-icon {
            width: 11px;
            height: 11px;
            transition: transform 0.2s ease;
            color: var(--text-muted);
        }
        .los-custom-select.is-open .los-select-trigger .chevron-icon {
            transform: rotate(180deg);
            color: var(--brand-primary);
        }
        .los-select-menu {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            min-width: 230px;
            max-width: 340px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            box-shadow: 0 16px 36px -8px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.1);
            z-index: 9999;
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: losDropdownFade 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes losDropdownFade {
            from { opacity: 0; transform: translateY(-6px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .los-custom-select.is-open .los-select-menu {
            display: flex;
        }
        .los-select-search-wrap {
            padding: 8px 10px;
            border-bottom: 1px solid #1e293b;
            background: #1e293b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .los-select-search-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 6px 10px;
            color: #f8fafc;
            font-size: 11.5px;
            outline: none;
            transition: border-color 0.15s;
        }
        .los-select-search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }
        .los-select-options {
            max-height: 230px;
            overflow-y: auto;
            padding: 4px;
        }
        .los-select-options::-webkit-scrollbar {
            width: 5px;
        }
        .los-select-options::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        .los-select-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 500;
            color: #cbd5e1;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.12s;
        }
        .los-select-option:hover {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }
        .los-select-option.is-selected {
            background: #1e293b;
            color: #38bdf8;
            font-weight: 700;
        }
        .los-select-option.is-selected .check-icon {
            display: inline-block;
            color: #38bdf8;
        }
        .los-select-option .check-icon {
            display: none;
            font-size: 11px;
        }
        .los-select-empty {
            padding: 12px;
            text-align: center;
            font-size: 11.5px;
            color: #64748b;
            font-style: italic;
        }

        /* ─── DATE RANGE PICKER INPUTS ─── */
        .date-picker-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--input-bg);
            border: 1px solid var(--navy-border);
            border-radius: 8px;
            padding: 3px 8px;
            transition: all 0.15s ease;
        }
        .date-picker-group:hover, .date-picker-group:focus-within {
            border-color: rgba(56, 189, 248, 0.4);
        }
        .los-date-input {
            background: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 6px;
            border-radius: 5px;
            outline: none;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }
        .los-date-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
        }
        .btn-clear-date {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            margin-left: 2px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-clear-date:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Action Buttons */
        .btn-search {
            background: var(--brand-gradient);
            border: none;
            color: #ffffff;
            height: 38px;
            padding: 0 16px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.28);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
            white-space: nowrap;
        }

        .btn-search:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.45);
            filter: brightness(1.05);
        }

        .btn-search:active {
            transform: scale(0.97);
        }

        .btn-export {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.18));
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #10b981;
            height: 38px;
            padding: 0 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
            white-space: nowrap;
        }

        .btn-export:hover {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.35);
            transform: translateY(-1px);
        }

        .btn-export:active {
            transform: scale(0.97);
        }

        .btn-reset {
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            color: var(--text-muted);
            height: 38px;
            padding: 0 12px;
            border-radius: 10px;
            font-size: 11.5px;
            font-weight: 600;
            text-decoration: none;
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
            font-family: inherit;
            white-space: nowrap;
        }

        .btn-reset:hover {
            background: var(--navy-card);
            color: var(--text-title);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn-reset:hover svg {
            transform: rotate(-180deg);
        }

        .btn-reset svg {
            transition: transform 0.4s ease;
        }

        /* ─── Table ─── */
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 0 2px;
        }
        .results-title { font-size: 12.5px; font-weight: 700; color: var(--text-muted); }
        .results-title span { color: var(--brand-primary); font-weight: 800; }

        .table-card {
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }
        .table-wrapper { width: 100%; overflow-x: auto; }
        .sapp-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; }
        .sapp-table thead { background: var(--table-header-bg); border-bottom: 1px solid var(--navy-border); }
        .sapp-table th {
            padding: 10px 10px;
            font-size: 10.5px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .sapp-table tbody tr.data-row {
            border-bottom: 1px solid var(--navy-border);
            transition: background 0.1s ease;
            cursor: pointer;
            user-select: none;
        }
        .sapp-table tbody tr.data-row:hover { background: var(--row-hover-bg); }
        .sapp-table tbody tr.data-row.active-row { background: var(--active-row-bg); border-bottom-color: transparent; }
        .sapp-table td { padding: 9px 10px; vertical-align: middle; }

        .stt-badge {
            display: inline-block;
            font-weight: 800;
            color: var(--text-muted);
            font-size: 11px;
            text-align: center;
            min-width: 22px;
        }

        .code-link {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: #0284c7;
            font-size: 12px;
        }
        [data-theme="dark"] .code-link { color: #38bdf8; }
        .scheme-sub { font-size: 10px; color: var(--text-muted); display: block; margin-top: 1px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .applicant-name { font-weight: 700; color: var(--text-title); display: block; font-size: 12.5px; }
        .applicant-sub { font-size: 10.5px; color: var(--text-muted); }

        .project-badge {
            display: inline-block;
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 600;
            color: var(--text-title);
            white-space: nowrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        .status-badge--success { background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; }
        .status-badge--danger { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        .status-badge--warning { background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #f59e0b; }
        .status-badge--primary { background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.3); color: #3b82f6; }

        .btn-view-popup {
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: var(--brand-primary);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Inline Dossier View */
        .inline-dossier-row { background: var(--card-bg); border-bottom: 2px solid rgba(59, 130, 246, 0.35); }
        .inline-dossier-panel {
            background: var(--inline-dossier-bg);
            border-left: 3px solid var(--brand-primary);
            border-right: 1px solid var(--navy-border);
            border-bottom: 1px solid var(--navy-border);
            margin: 4px 10px 14px 10px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .inline-dossier-header {
            height: 42px;
            padding: 0 16px;
            background: var(--inline-header-bg);
            border-bottom: 1px solid var(--navy-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-code { font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 800; color: #0284c7; }
        [data-theme="dark"] .modal-code { color: #38bdf8; }

        .btn-close-inline {
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            color: var(--text-muted);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-close-inline:hover { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

        .modal-hero-banner {
            background: var(--hero-bg);
            border-bottom: 1px solid var(--navy-border);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .hero-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--brand-gradient);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 14.5px;
            color: #ffffff;
        }
        .hero-meta-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1.4fr 1.3fr 0.9fr 1.4fr 1.2fr 1.3fr;
            gap: 4px 12px;
        }
        .hero-lbl { font-size: 9px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1px; }
        .hero-val { font-size: 11.5px; font-weight: 600; color: var(--text-title); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hero-val-highlight { font-size: 13px; font-weight: 800; color: var(--text-title); }

        .modal-tab-bar {
            height: 38px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 14px;
            background: var(--tab-bar-bg);
            border-bottom: 1px solid var(--navy-border);
            overflow-x: auto;
        }
        .tab-btn {
            background: transparent;
            border: 1px solid transparent;
            color: var(--text-muted);
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }
        .tab-btn:hover { color: var(--text-title); background: var(--card-bg); }
        .tab-btn.active {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.4);
            color: var(--brand-primary);
        }

        .inline-dossier-body { padding: 16px; max-height: 480px; overflow-y: auto; }
        .modal-loading { padding: 30px 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--brand-primary); font-weight: 600; font-size: 12.5px; gap: 10px; }
        .modal-fields-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 8px; }
        .modal-field-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 6px; padding: 8px 10px; }
        .modal-field-card--alert { grid-column: 1 / -1; background: rgba(239, 68, 68, 0.06); border-color: rgba(239, 68, 68, 0.25); }
        .modal-field-lbl { font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px; }
        .modal-field-val { font-size: 12px; font-weight: 600; color: var(--text-title); word-break: break-word; }

        /* Documents Grid */
        .documents-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 12px; }
        .doc-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
        .doc-preview-box { height: 160px; background: var(--navy-bg); display: grid; place-items: center; overflow: hidden; }
        .doc-preview-img { width: 100%; height: 100%; object-fit: cover; }
        .doc-card-meta { padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; background: var(--inline-header-bg); border-top: 1px solid var(--navy-border); }
        .doc-card-title { font-size: 11.5px; font-weight: 700; color: var(--text-title); }
        .btn-view-doc { font-size: 10.5px; color: var(--brand-primary); text-decoration: none; font-weight: 700; padding: 2px 7px; border-radius: 4px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); }

        /* Timeline History */
        .timeline-container { display: flex; flex-direction: column; gap: 12px; padding: 4px 6px; }
        .timeline-item { display: flex; gap: 12px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: 11px; top: 22px; bottom: -12px; width: 2px; background: var(--navy-border); }
        .timeline-item:last-child::before { display: none; }
        .timeline-dot { width: 22px; height: 22px; border-radius: 50%; background: var(--navy-card); border: 2px solid var(--brand-primary); display: grid; place-items: center; flex-shrink: 0; z-index: 2; }
        .timeline-dot--danger { border-color: #ef4444; }
        .timeline-dot--success { border-color: #10b981; }
        .timeline-dot--warning { border-color: #f59e0b; }
        .timeline-dot-inner { width: 7px; height: 7px; border-radius: 50%; background: var(--brand-primary); }
        .timeline-dot--danger .timeline-dot-inner { background: #ef4444; }
        .timeline-dot--success .timeline-dot-inner { background: #10b981; }
        .timeline-dot--warning .timeline-dot-inner { background: #f59e0b; }

        .timeline-content-card { flex: 1; background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 6px; padding: 8px 12px; }
        .timeline-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 3px; }
        .timeline-title { font-size: 12.5px; font-weight: 700; color: var(--text-title); }
        .timeline-time { font-size: 10.5px; color: var(--text-muted); }
        .timeline-actor { font-size: 11px; color: var(--brand-primary); font-weight: 600; margin-bottom: 3px; }
        .timeline-note { font-size: 11.5px; color: var(--text-body); line-height: 1.35; }

        /* ─── Pagination Bar ─── */
        .pagination-container {
            padding: 12px 18px;
            background: var(--table-header-bg);
            border-top: 1px solid var(--navy-border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
        }
        .pagination-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .pagination-info { color: var(--text-muted); font-weight: 600; }
        .pagination-info strong { color: var(--text-title); font-weight: 700; }
        
        .perpage-selector {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: var(--text-muted);
        }
        .perpage-select {
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            color: var(--text-title);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11.5px;
            font-weight: 700;
            outline: none;
            cursor: pointer;
        }

        .pagination-links { display: flex; align-items: center; gap: 4px; }
        .page-btn {
            padding: 5px 11px;
            border-radius: 5px;
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            color: var(--text-title);
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            transition: all 0.15s ease;
        }
        .page-btn:hover { background: var(--active-row-bg); border-color: rgba(59, 130, 246, 0.4); color: var(--brand-primary); }
        .page-btn.active { background: var(--brand-primary); border-color: var(--brand-primary); color: #ffffff; font-weight: 800; }
        .page-btn.disabled { opacity: 0.35; pointer-events: none; }

        /* ─── Footer Bản Quyền ─── */
        .sapp-footer {
            margin-top: auto;
            background: var(--footer-bg);
            border-top: 1px solid var(--navy-border);
            padding: 18px 20px;
            font-size: 12px;
            color: var(--text-muted);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .footer-container {
            width: min(1600px, 100%);
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .footer-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .footer-copy strong { color: var(--text-title); font-weight: 700; }
        .footer-tag {
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            color: var(--brand-primary);
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
        }

        .spin-icon { animation: spinAnim 0.8s linear infinite; }
        @keyframes spinAnim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <!-- Security Watermark -->
    <div class="security-watermark-layer" id="securityWatermarkLayer"></div>

    <!-- Topbar -->
    <header class="sapp-header">
        <div class="header-left">
            <a href="{{ route('los.management.index') }}" class="header-brand">
                <div class="brand-icon-box">3R</div>
                <div class="brand-meta">
                    <strong>{{ $brandName }} LOS <span class="sapp-tag">SAPP LOS</span></strong>
                </div>
            </a>

            <!-- Navigation Tabs -->
            <nav class="nav-links">
                <a href="{{ route('los.index') }}" class="nav-item">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <span>Tra cứu nhanh</span>
                </a>
                <a href="{{ route('los.management.index') }}" class="nav-item active">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                    <span>Quản lý hồ sơ</span>
                </a>
            </nav>
        </div>

        <div class="header-actions">
            <!-- Realtime Live Status Pill -->
            <div class="realtime-status-pill live-active" id="realtimeStatusPill" onclick="RealtimeEngine.toggle()" title="Nhấp để Bật / Tắt tự động cập nhật Realtime">
                <span class="realtime-pulse-dot active" id="realtimePulseDot"></span>
                <span class="realtime-status-text" id="realtimeStatusText">Realtime: BẬT</span>
                <button type="button" class="btn-sync-now" id="btnSyncNow" onclick="event.stopPropagation(); RealtimeEngine.sync(true);" title="Nhấp để đồng bộ dữ liệu ngay lập tức">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
                </button>
            </div>

            @if ($user)
                <div class="user-menu-wrapper" id="userMenuWrapper">
                    <button type="button" class="user-badge-btn" id="userMenuBtn" onclick="toggleUserMenu(event)" aria-expanded="false" title="Tài khoản: {{ $userCode }}">
                        <div class="user-avatar-circle">{{ $userInitials }}</div>
                        <span class="user-code-label">{{ $userCode }}</span>
                        <svg class="user-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <!-- Profile Card -->
                        <div class="user-profile-header">
                            <div class="user-profile-avatar">{{ $userInitials }}</div>
                            <div class="user-profile-info">
                                <div class="user-profile-name" title="{{ $userName }}">{{ $userName }}</div>
                                <div class="user-profile-meta">
                                    <span class="user-profile-code">{{ $userCode }}</span>
                                    <span class="user-role-badge">{{ $userRole }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="user-menu-divider"></div>

                        <!-- Theme Toggle Section -->
                        <div class="user-menu-item theme-switch-item" onclick="toggleTheme(event)">
                            <div class="theme-item-left">
                                <div class="menu-icon-circle theme-icon-bg" id="menuThemeIcon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                                </div>
                                <div class="menu-item-text">
                                    <span class="menu-item-title">Chế độ giao diện</span>
                                    <span class="menu-item-subtitle" id="themeStatusText">Đang dùng chế độ Tối</span>
                                </div>
                            </div>
                            <div class="theme-toggle-switch">
                                <div class="theme-toggle-slider" id="themeToggleSlider"></div>
                            </div>
                        </div>

                        <div class="user-menu-divider"></div>

                        <!-- Logout Form -->
                        <form method="POST" action="{{ route('los.logout') }}" class="user-logout-form">
                            @csrf
                            <button type="submit" class="user-logout-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                                <span>Đăng xuất tài khoản</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </header>

    <!-- Main -->
    <main class="sapp-main">
        <!-- 5-Card Metric Overview Dashboard (Theo phạm vi dự án) -->
        <section class="metrics-grid">
            <!-- 1. Tổng số hồ sơ -->
            <div class="metric-card metric-card--primary">
                <div class="metric-content">
                    <div class="metric-top-row">
                        <span class="metric-lbl">Tổng số hồ sơ</span>
                        <span class="metric-pill metric-pill--primary">Dự án</span>
                    </div>
                    <div class="metric-val" id="kpiTotalVal">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                    <div class="metric-footer">
                        <span class="metric-trend-text">Toàn bộ hồ sơ trong quyền hạn</span>
                    </div>
                </div>
                <div class="metric-icon-box metric-icon-box--primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                </div>
            </div>

            <!-- 2. Đang thẩm định / Chờ xử lý -->
            <div class="metric-card metric-card--warning">
                <div class="metric-content">
                    <div class="metric-top-row">
                        <span class="metric-lbl">Đang thẩm định</span>
                        <span class="metric-pill metric-pill--warning" id="kpiProcessingPill">{{ $stats['processing_rate'] }}%</span>
                    </div>
                    <div class="metric-val metric-val--warning" id="kpiProcessingVal">{{ number_format($stats['processing'], 0, ',', '.') }}</div>
                    <div class="metric-footer">
                        <div class="metric-mini-bar-track">
                            <div class="metric-mini-bar-fill metric-mini-bar-fill--warning" id="kpiProcessingBar" style="width: {{ $stats['processing_rate'] }}%;"></div>
                        </div>
                        <span class="metric-trend-text" id="kpiProcessingSub">{{ $stats['processing'] }} hồ sơ đang chờ duyệt</span>
                    </div>
                </div>
                <div class="metric-icon-box metric-icon-box--warning">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>

            <!-- 3. Phê duyệt / Giải ngân -->
            <div class="metric-card metric-card--success">
                <div class="metric-content">
                    <div class="metric-top-row">
                        <span class="metric-lbl">Phê duyệt thành công</span>
                        <span class="metric-pill metric-pill--success" id="kpiApprovedPill">{{ $stats['approval_rate'] }}%</span>
                    </div>
                    <div class="metric-val metric-val--success" id="kpiApprovedVal">{{ number_format($stats['approved'], 0, ',', '.') }}</div>
                    <div class="metric-footer">
                        <div class="metric-mini-bar-track">
                            <div class="metric-mini-bar-fill metric-mini-bar-fill--success" id="kpiApprovedBar" style="width: {{ $stats['approval_rate'] }}%;"></div>
                        </div>
                        <span class="metric-trend-text" id="kpiApprovedSub">{{ $stats['approved'] }} hồ sơ đã giải ngân</span>
                    </div>
                </div>
                <div class="metric-icon-box metric-icon-box--success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>

            <!-- 4. Tổng tiền phê duyệt (Approved Volume) -->
            <div class="metric-card metric-card--money">
                <div class="metric-content">
                    <div class="metric-top-row">
                        <span class="metric-lbl" style="color: #059669;">Tổng tiền giải ngân</span>
                        <span class="metric-pill metric-pill--money">Doanh số</span>
                    </div>
                    <div class="metric-val metric-val--money" id="kpiApprovedMoneyVal">
                        {{ number_format($stats['approved_amount'], 0, ',', '.') }} <span style="font-size: 13px; font-weight: 700; opacity: 0.85;">VNĐ</span>
                    </div>
                    <div class="metric-footer">
                        <span class="metric-trend-text" style="color: #059669; font-weight: 600;">Tổng giá trị giải ngân & phê duyệt thực tế</span>
                    </div>
                </div>
                <div class="metric-icon-box metric-icon-box--money">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="18"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="9" y1="15" x2="15" y2="9"/></svg>
                </div>
            </div>

            <!-- 5. Bị từ chối / Không đạt -->
            <div class="metric-card metric-card--danger">
                <div class="metric-content">
                    <div class="metric-top-row">
                        <span class="metric-lbl">Bị từ chối / Hủy</span>
                        <span class="metric-pill metric-pill--danger" id="kpiRejectedPill">{{ $stats['rejected_rate'] }}%</span>
                    </div>
                    <div class="metric-val metric-val--danger" id="kpiRejectedVal">{{ number_format($stats['rejected'], 0, ',', '.') }}</div>
                    <div class="metric-footer">
                        <div class="metric-mini-bar-track">
                            <div class="metric-mini-bar-fill metric-mini-bar-fill--danger" id="kpiRejectedBar" style="width: {{ $stats['rejected_rate'] }}%;"></div>
                        </div>
                        <span class="metric-trend-text" id="kpiRejectedSub">{{ $stats['rejected'] }} hồ sơ không đạt điều kiện</span>
                    </div>
                </div>
                <div class="metric-icon-box metric-icon-box--danger">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
            </div>
        </section>

        <!-- Visual Distribution Bar (Biểu đồ phân bổ trạng thái theo dự án) -->
        @if ($stats['total'] > 0)
            <section class="distribution-panel" id="distributionPanel">
                <div class="dist-top">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 800; color: var(--text-title);">Phân bổ kết quả theo dự án</span>
                        <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;" id="distTotalSubCount">(Tổng số: {{ number_format($stats['total'], 0, ',', '.') }} hồ sơ)</span>
                    </div>
                    <div class="dist-legend">
                        <div class="dist-legend-item">
                            <span class="dist-dot dist-dot--warning"></span>
                            <span>Đang xử lý: <strong id="distLegendWarningCount">{{ $stats['processing'] }} ({{ $stats['processing_rate'] }}%)</strong></span>
                        </div>
                        <div class="dist-legend-item">
                            <span class="dist-dot dist-dot--success"></span>
                            <span>Phê duyệt: <strong id="distLegendSuccessCount">{{ $stats['approved'] }} ({{ $stats['approval_rate'] }}%)</strong></span>
                        </div>
                        <div class="dist-legend-item">
                            <span class="dist-dot dist-dot--danger"></span>
                            <span>Từ chối: <strong id="distLegendDangerCount">{{ $stats['rejected'] }} ({{ $stats['rejected_rate'] }}%)</strong></span>
                        </div>
                    </div>
                </div>
                <div class="dist-progress-bar">
                    <div class="dist-segment dist-segment--warning" id="distSegmentWarning" style="width: {{ $stats['processing_rate'] }}%;" title="Đang xử lý: {{ $stats['processing_rate'] }}% ({{ $stats['processing'] }} hồ sơ)"></div>
                    <div class="dist-segment dist-segment--success" id="distSegmentSuccess" style="width: {{ $stats['approval_rate'] }}%;" title="Phê duyệt: {{ $stats['approval_rate'] }}% ({{ $stats['approved'] }} hồ sơ)"></div>
                    <div class="dist-segment dist-segment--danger" id="distSegmentDanger" style="width: {{ $stats['rejected_rate'] }}%;" title="Từ chối: {{ $stats['rejected_rate'] }}% ({{ $stats['rejected'] }} hồ sơ)"></div>
                </div>
            </section>
        @endif

        <!-- Filter Toolbar (Modern Redesign) -->
        <section class="toolbar-panel">
            <form id="managementFilterForm" method="GET" action="{{ route('los.management.index') }}" class="inline-toolbar">
                <input type="hidden" name="per_page" id="hiddenPerPageInput" value="{{ $perPage }}">

                <div class="omnibox-box">
                    <span class="omnibox-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input 
                        type="text" 
                        class="omnibox-input" 
                        id="managementKeywordInput"
                        name="keyword" 
                        placeholder="Mã hồ sơ, CCCD, SĐT, Họ tên..."
                        value="{{ $keyword }}"
                        autocomplete="off"
                    >
                    @if(!empty($keyword))
                        <button type="button" class="btn-clear-search" onclick="clearSearchFilter()" title="Xóa tìm kiếm" style="display: grid;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    @endif
                </div>

                <button type="submit" class="btn-search">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <span>Lọc dữ liệu</span>
                </button>

                <!-- Export Excel Button -->
                <a href="#" onclick="exportCurrentReport(event)" class="btn-export" title="Xuất toàn bộ dữ liệu đang lọc ra file Excel (.xlsx)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Xuất báo cáo</span>
                </a>

                <!-- Nguồn / Kênh -->
                <div class="filter-group {{ $system !== 'all' ? 'active-filter' : '' }}">
                    <span class="filter-dot-indicator"></span>
                    <span class="filter-label">Nguồn:</span>
                    <select class="native-select" name="system" onchange="this.form.submit()">
                        <option value="all" @selected($system === 'all')>Tất cả nguồn</option>
                        <option value="affiliate" @selected($system === 'affiliate')>Tiếp thị liên kết (Affiliate)</option>
                        <option value="internal" @selected($system === 'internal')>Hồ sơ CRM LOS</option>
                        <option value="feol" @selected($system === 'feol')>FEOL Deeplink</option>
                    </select>
                </div>

                <!-- Dự án / Chiến dịch -->
                <div class="filter-group {{ $project !== 'all' ? 'active-filter' : '' }}">
                    <span class="filter-dot-indicator"></span>
                    <span class="filter-label">Dự án:</span>
                    <select class="native-select" name="project" onchange="this.form.submit()">
                        <option value="all" @selected($project === 'all')>Tất cả dự án & chiến dịch</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p['slug'] }}" @selected($project === $p['slug'])>{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Trạng thái -->
                <div class="filter-group {{ $status !== 'all' ? 'active-filter' : '' }}">
                    <span class="filter-dot-indicator"></span>
                    <span class="filter-label">Trạng thái:</span>
                    <select class="native-select" name="status" onchange="this.form.submit()">
                        <option value="all" @selected($status === 'all')>Tất cả trạng thái</option>
                        <option value="pending" @selected($status === 'pending')>Đang thẩm định / Chờ xử lý</option>
                        <option value="approved" @selected($status === 'approved')>Phê duyệt / Giải ngân</option>
                        <option value="rejected" @selected($status === 'rejected')>Bị từ chối / Không đạt</option>
                    </select>
                </div>

                @if($sales->count() > 1)
                    <div class="filter-group {{ $saleId !== 'all' ? 'active-filter' : '' }}">
                        <span class="filter-dot-indicator"></span>
                        <span class="filter-label">NVKD:</span>
                        <select class="native-select" name="sale_id" onchange="this.form.submit()">
                            <option value="all" @selected($saleId === 'all')>Tất cả nhân sự</option>
                            @foreach ($sales as $s)
                                <option value="{{ $s->id }}" @selected((string)$saleId === (string)$s->id)>{{ $s->name }} ({{ $s->employee_code ?: $s->uid }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="filter-group {{ ($dateType ?? 'created') !== 'created' ? 'active-filter' : '' }}">
                    <span class="filter-dot-indicator"></span>
                    <span class="filter-label">Lọc theo:</span>
                    <select class="native-select" name="date_type" onchange="this.form.submit()">
                        <option value="created" @selected(($dateType ?? 'created') === 'created')>Ngày tạo</option>
                        <option value="updated" @selected(($dateType ?? 'created') === 'updated')>Ngày cập nhật</option>
                    </select>
                </div>

                <div class="filter-group {{ ($dateRange !== 'all' || !empty($dateFrom) || !empty($dateTo)) ? 'active-filter' : '' }}">
                    <span class="filter-dot-indicator"></span>
                    <span class="filter-label">Thời gian:</span>
                    <select class="native-select" name="date_range" onchange="this.form.submit()">
                        <option value="all" @selected($dateRange === 'all' && empty($dateFrom) && empty($dateTo))>Toàn thời gian</option>
                        <option value="today" @selected($dateRange === 'today')>Hôm nay</option>
                        <option value="yesterday" @selected($dateRange === 'yesterday')>Hôm qua</option>
                        <option value="7days" @selected($dateRange === '7days')>7 ngày qua</option>
                        <option value="30days" @selected($dateRange === '30days')>30 ngày qua</option>
                        <option value="this_month" @selected($dateRange === 'this_month')>Tháng này</option>
                        <option value="last_month" @selected($dateRange === 'last_month')>Tháng trước</option>
                    </select>
                </div>

                <!-- Lọc Từ ngày - Đến ngày -->
                <div class="filter-group date-picker-group {{ (!empty($dateFrom) || !empty($dateTo)) ? 'active-filter' : '' }}">
                    <span class="filter-dot-indicator"></span>
                    <span class="filter-label">Từ ngày:</span>
                    <input type="date" class="los-date-input" name="date_from" value="{{ $dateFrom ?? '' }}" onchange="this.form.submit()" title="Chọn ngày bắt đầu">
                    <span class="filter-label" style="margin-left: 2px;">Đến ngày:</span>
                    <input type="date" class="los-date-input" name="date_to" value="{{ $dateTo ?? '' }}" onchange="this.form.submit()" title="Chọn ngày kết thúc">
                    @if(!empty($dateFrom) || !empty($dateTo))
                        <a href="{{ request()->fullUrlWithQuery(['date_from' => null, 'date_to' => null]) }}" class="btn-clear-date" title="Xóa lọc khoảng ngày">✕</a>
                    @endif
                </div>

                <a href="{{ route('los.management.index') }}" class="btn-reset" title="Xóa toàn bộ bộ lọc về mặc định">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    <span>Đặt lại</span>
                </a>
            </form>
        </section>

        <!-- Applications Table -->
        <div class="results-header">
            <div class="results-title">
                Danh sách: <span id="resultsTotalCount">{{ number_format($paginator->total(), 0, ',', '.') }}</span> hồ sơ
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table class="sapp-table">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;">STT</th>
                            <th>Mã Hồ sơ / Giao dịch</th>
                            <th>Khách hàng</th>
                            <th>Dự án / Gói vay</th>
                            <th>Khoản vay đề xuất</th>
                            <th>Giải ngân / Phê duyệt</th>
                            <th>Nguồn / NVKD</th>
                            <th>Trạng thái CRM</th>
                            <th>Phản hồi chi tiết</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Xem chi tiết</th>
                        </tr>
                    </thead>
                    <tbody id="applicationsTableBody">
                        @forelse ($applications as $app)
                            @php
                                $tone = $app['status_tone'] ?? 'primary';
                                $badgeClass = match($tone) {
                                    'success' => 'status-badge--success',
                                    'danger' => 'status-badge--danger',
                                    'warning' => 'status-badge--warning',
                                    default => 'status-badge--primary',
                                };
                                
                                $reason = null;
                                if (!empty($app['application_fields'])) {
                                    foreach ($app['application_fields'] as $f) {
                                        if (str_contains($f['label'], 'Lý do') || str_contains($f['label'], 'Phản hồi') || str_contains($f['label'], 'Thông điệp')) {
                                            if ($f['value'] !== '-') {
                                                $reason = $f['value'];
                                                break;
                                            }
                                        }
                                    }
                                }

                                $stt = ($paginator->currentPage() - 1) * $paginator->perPage() + $loop->iteration;
                            @endphp
                            <tr 
                                class="data-row" 
                                id="row-{{ $app['id'] }}"
                                onclick="toggleInlineDossier('{{ $app['id'] }}', '{{ $app['application_code'] }}')"
                                title="Bấm vào để mở/đóng chi tiết ngay bên dưới"
                            >
                                <td style="text-align: center;">
                                    <span class="stt-badge">{{ $stt }}</span>
                                </td>
                                <td>
                                    <span class="code-link">{{ $app['application_code'] }}</span>
                                    <span class="scheme-sub" title="{{ $app['scheme_or_product'] ?? ($app['scheme'] ?? '-') }}">
                                        {{ $app['scheme_or_product'] ?? ($app['scheme'] ?? '-') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="applicant-name">{{ $app['applicant_name'] }}</span>
                                    <span class="applicant-sub">
                                        @if($app['identity_number'] && $app['identity_number'] !== '-')
                                            CCCD: {{ $app['identity_number'] }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="project-badge">{{ $app['project'] }}</span>
                                </td>
                                <td>
                                    @if(!empty($app['requested_loan_amount']))
                                        <strong style="color: var(--brand-primary); font-size: 12px;">{{ number_format($app['requested_loan_amount'], 0, ',', '.') }} đ</strong>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($app['approved_loan_amount']))
                                        <strong style="color: #10b981; font-size: 12px;">{{ number_format($app['approved_loan_amount'], 0, ',', '.') }} đ</strong>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size: 12px; color: var(--text-body); font-weight: 600;">{{ $app['creator'] ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $badgeClass }}">
                                        {{ $app['status_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width: 220px; font-size: 11px; color: #ef4444; word-break: break-word;">
                                        {{ $reason ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 11px; color: var(--text-title); font-weight: 600; white-space: nowrap;">
                                        {{ $app['created_at'] ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 11px; color: var(--text-muted); white-space: nowrap;">
                                        {{ $app['updated_at'] ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="btn-view-popup">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                        <span>Xem</span>
                                    </span>
                                </td>
                            </tr>

                            <!-- INLINE DOSSIER VIEW (HIỂN THỊ TRỰC TIẾP DƯỚI DÒNG) -->
                            <tr id="drawer-{{ $app['id'] }}" class="inline-dossier-row" style="display: none;">
                                <td colspan="12" style="padding: 0;">
                                    <div class="inline-dossier-panel" id="inline-panel-${r.id}">
                                        <div class="inline-dossier-header">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span class="modal-code">{{ $app['application_code'] }}</span>
                                                <span class="project-badge">{{ $app['project'] }}</span>
                                                <span class="status-badge {{ $badgeClass }}">{{ $app['status_label'] }}</span>
                                            </div>
                                            <button type="button" class="btn-close-inline" onclick="event.stopPropagation(); closeInlineDossier('{{ $app['id'] }}')">✕ Đóng lại</button>
                                        </div>

                                        <div class="modal-hero-banner">
                                            <div class="hero-avatar">{{ Str::substr($app['applicant_name'], 0, 2) }}</div>
                                            <div class="hero-meta-grid">
                                                <div>
                                                    <div class="hero-lbl">Khách hàng</div>
                                                    <div class="hero-val-highlight">{{ $app['applicant_name'] }}</div>
                                                </div>
                                                <div>
                                                    <div class="hero-lbl">CCCD · SĐT (Bảo mật)</div>
                                                    <div class="hero-val">{{ $app['identity_number'] ?: '-' }} · {{ $app['phone_number'] ?: '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="hero-lbl">Ngày sinh</div>
                                                    <div class="hero-val">{{ $app['dob'] ?: '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="hero-lbl">Sản phẩm / Scheme</div>
                                                    <div class="hero-val">{{ $app['scheme_or_product'] ?: '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="hero-lbl">Số tiền đề nghị</div>
                                                    <div class="hero-val" style="color: var(--brand-primary); font-weight: 800;">{{ $app['requested_loan_amount_label'] ?: '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="hero-lbl">Tạo · Cập nhật</div>
                                                    <div class="hero-val">{{ $app['created_at'] ?: '-' }} · {{ $app['updated_at'] ?: '-' }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-tab-bar" id="tab-bar-{{ $app['id'] }}"></div>
                                        <div class="inline-dossier-body" id="tab-body-{{ $app['id'] }}">
                                            <div class="modal-loading">
                                                <svg class="spin-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                                <span>Đang tải thông tin chi tiết & chứng từ CRM...</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                    <div style="font-size: 14px; font-weight: 700; color: var(--text-title);">Không có dữ liệu phù hợp</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Phân Trang (Chân bảng Pagination Bar luôn hiển thị đầy đủ) -->
            <div class="pagination-container">
                <div class="pagination-left">
                    <div class="pagination-info">
                        Hiển thị <strong>{{ $paginator->total() > 0 ? $paginator->firstItem() : 0 }} - {{ $paginator->total() > 0 ? $paginator->lastItem() : 0 }}</strong> trên tổng số <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> hồ sơ
                    </div>

                    <div class="perpage-selector">
                        <span>Số dòng/trang:</span>
                        <select class="perpage-select" onchange="changePerPage(this.value)">
                            <option value="20" @selected($perPage == 20)>20</option>
                            <option value="50" @selected($perPage == 50)>50</option>
                            <option value="100" @selected($perPage == 100)>100</option>
                        </select>
                    </div>
                </div>

                <div class="pagination-links">
                    @if ($paginator->onFirstPage())
                        <span class="page-btn disabled">« Trước</span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="page-btn">« Trước</a>
                    @endif

                    @php
                        $startPage = max(1, $paginator->currentPage() - 2);
                        $endPage = min($paginator->lastPage(), $paginator->currentPage() + 2);
                    @endphp

                    @if ($startPage > 1)
                        <a href="{{ $paginator->url(1) }}" class="page-btn">1</a>
                        @if ($startPage > 2)
                            <span class="page-btn disabled">...</span>
                        @endif
                    @endif

                    @for ($i = $startPage; $i <= $endPage; $i++)
                        <a href="{{ $paginator->url($i) }}" class="page-btn {{ $i == $paginator->currentPage() ? 'active' : '' }}">{{ $i }}</a>
                    @endfor

                    @if ($endPage < $paginator->lastPage())
                        @if ($endPage < $paginator->lastPage() - 1)
                            <span class="page-btn disabled">...</span>
                        @endif
                        <a href="{{ $paginator->url($paginator->lastPage()) }}" class="page-btn">{{ $paginator->lastPage() }}</a>
                    @endif

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="page-btn">Sau »</a>
                    @else
                        <span class="page-btn disabled">Sau »</span>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Bản Quyền -->
    <footer class="sapp-footer">
        <div class="footer-container">
            <div class="footer-left">
                <span class="footer-copy">© {{ date('Y') }} <strong>{{ $brandName }}</strong>. Bản quyền thuộc về {{ $brandName }}.</span>
            </div>
            <div class="footer-right">
                <span class="footer-tag">SAPP LOS</span>
            </div>
        </div>
    </footer>

    <!-- Realtime Live Toast Container -->
    <div class="live-toast-container" id="liveToastContainer"></div>

    <script>
        // Change Per Page
        function changePerPage(val) {
            const form = document.getElementById('managementFilterForm');
            const hidden = document.getElementById('hiddenPerPageInput');
            if (hidden) hidden.value = val;
            form.submit();
        }

        // Export Current Report based on active filters
        function exportCurrentReport(e) {
            e.preventDefault();
            const form = document.getElementById('managementFilterForm');
            const params = new URLSearchParams(new FormData(form)).toString();
            window.location.href = '{{ route("los.management.export") }}?' + params;
        }

        // User Menu Dropdown Management
        function toggleUserMenu(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('userDropdownMenu');
            const btn = document.getElementById('userMenuBtn');
            if (!menu || !btn) return;
            const isOpen = menu.classList.contains('show');
            if (isOpen) {
                closeUserMenu();
            } else {
                menu.classList.add('show');
                btn.classList.add('active');
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        function closeUserMenu() {
            const menu = document.getElementById('userDropdownMenu');
            const btn = document.getElementById('userMenuBtn');
            if (menu) menu.classList.remove('show');
            if (btn) {
                btn.classList.remove('active');
                btn.setAttribute('aria-expanded', 'false');
            }
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('userMenuWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                closeUserMenu();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeUserMenu();
            }
        });

        // Theme Management
        function toggleTheme(e) {
            if (e) e.stopPropagation();
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('los_theme', next);
            updateThemeUI(next);
            generateWatermark();
        }

        function updateThemeUI(theme) {
            const menuIcon = document.getElementById('menuThemeIcon');
            const statusText = document.getElementById('themeStatusText');
            if (!menuIcon || !statusText) return;
            if (theme === 'light') {
                menuIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>`;
                menuIcon.style.color = '#f59e0b';
                statusText.textContent = 'Đang dùng chế độ Sáng';
            } else {
                menuIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
                menuIcon.style.color = 'var(--brand-primary)';
                statusText.textContent = 'Đang dùng chế độ Tối';
            }
        }
        updateThemeUI(document.documentElement.getAttribute('data-theme') || 'dark');

        function clearSearchFilter() {
            const input = document.getElementById('managementKeywordInput');
            if (input) {
                input.value = '';
                document.getElementById('managementFilterForm').submit();
            }
        }

        // Security Watermark
        function generateWatermark() {
            try {
                const systemName = @json($brandName . ' LOS');
                const hostUrl = @json(request()->getHost());
                const userName = @json($userWatermarkName);
                const theme = document.documentElement.getAttribute('data-theme') || 'dark';

                const canvas = document.createElement('canvas');
                canvas.width = 440;
                canvas.height = 240;
                const ctx = canvas.getContext('2d');
                if (!ctx) return;

                ctx.rotate(-20 * Math.PI / 180);
                ctx.font = "700 13px Inter, -apple-system, sans-serif";
                ctx.fillStyle = theme === 'light' ? "rgba(15, 23, 42, 0.55)" : "rgba(255, 255, 255, 0.75)";
                ctx.fillText(`${systemName} · ${hostUrl}`, -40, 130);

                ctx.font = "600 11.5px Inter, -apple-system, sans-serif";
                ctx.fillStyle = theme === 'light' ? "rgba(71, 85, 105, 0.55)" : "rgba(148, 163, 184, 0.75)";
                ctx.fillText(`User: ${userName}`, -40, 152);

                const layer = document.getElementById('securityWatermarkLayer');
                if (layer) {
                    layer.style.backgroundImage = 'url(' + canvas.toDataURL('image/png') + ')';
                    layer.style.backgroundRepeat = 'repeat';
                }
            } catch (e) {
                console.error("Error generating watermark", e);
            }
        }
        generateWatermark();

        // Inline Dossier
        const dossierCache = {};

        function toggleInlineDossier(id, code) {
            const drawer = document.getElementById('drawer-' + id);
            const parentRow = document.getElementById('row-' + id);
            if (!drawer) return;

            const isCurrentlyOpen = drawer.style.display !== 'none';
            if (isCurrentlyOpen) {
                drawer.style.display = 'none';
                if (parentRow) parentRow.classList.remove('active-row');
                return;
            }

            drawer.style.display = 'table-row';
            if (parentRow) parentRow.classList.add('active-row');
            drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            loadInlineDossierContent(id, code);
        }

        function closeInlineDossier(id) {
            const drawer = document.getElementById('drawer-' + id);
            const parentRow = document.getElementById('row-' + id);
            if (drawer) drawer.style.display = 'none';
            if (parentRow) parentRow.classList.remove('active-row');
        }

        function loadInlineDossierContent(id, code) {
            const tabBar = document.getElementById('tab-bar-' + id);
            const tabBody = document.getElementById('tab-body-' + id);
            if (!tabBody) return;

            if (dossierCache[code]) {
                renderInlineTabs(id, dossierCache[code]);
                return;
            }

            tabBody.innerHTML = `
                <div class="modal-loading">
                    <svg class="spin-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    <span>Đang kết nối API CRM kéo toàn bộ hồ sơ, chứng từ & lịch sử...</span>
                </div>
            `;

            fetch('/tra-cuu/detail/' + encodeURIComponent(code), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.data) {
                    tabBody.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 20px;">${res.message || 'Không tìm thấy chi tiết hồ sơ.'}</div>`;
                    return;
                }
                dossierCache[code] = res.data;
                renderInlineTabs(id, res.data);
            })
            .catch(err => {
                tabBody.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 20px;">Không thể tải dữ liệu từ API CRM (${err.message}).</div>`;
            });
        }

        function renderInlineTabs(id, data) {
            const tabBar = document.getElementById('tab-bar-' + id);
            const tabBody = document.getElementById('tab-body-' + id);
            if (!tabBar || !tabBody) return;

            const tabs = data.tabs || [];
            if (tabs.length === 0) {
                tabBar.style.display = 'none';
                renderFlatInlineFields(tabBody, data.application_fields || []);
                return;
            }

            tabBar.style.display = 'flex';
            tabBar.innerHTML = tabs.map((t, idx) => `
                <button type="button" class="tab-btn ${idx === 0 ? 'active' : ''}" onclick="switchInlineTab('${id}', '${data.application_code}', ${idx})">
                    <span>${t.title}</span>
                </button>
            `).join('');

            switchInlineTab(id, data.application_code, 0);
        }

        function switchInlineTab(id, code, tabIdx) {
            const tabBar = document.getElementById('tab-bar-' + id);
            const tabBody = document.getElementById('tab-body-' + id);
            const data = dossierCache[code];
            if (!tabBar || !tabBody || !data) return;

            tabBar.querySelectorAll('.tab-btn').forEach((btn, idx) => {
                btn.classList.toggle('active', idx === tabIdx);
            });

            const tab = data.tabs[tabIdx];
            if (!tab) return;

            if (tab.documents && tab.documents.length > 0) {
                tabBody.innerHTML = `
                    <div class="documents-grid">
                        ${tab.documents.map(doc => `
                            <div class="doc-card">
                                <div class="doc-preview-box">
                                    ${doc.is_image ? `
                                        <a href="${doc.url}" target="_blank">
                                            <img class="doc-preview-img" src="${doc.url}" alt="${doc.label}" loading="lazy">
                                        </a>
                                    ` : `
                                        <div style="color: var(--text-muted); font-size: 12px;">📄 Tài liệu đính kèm</div>
                                    `}
                                </div>
                                <div class="doc-card-meta">
                                    <span class="doc-card-title">${doc.label}</span>
                                    <a class="btn-view-doc" href="${doc.url}" target="_blank">Xem ảnh gốc ↗</a>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                return;
            }

            if (tab.id === 'tab-documents' && (!tab.documents || tab.documents.length === 0)) {
                tabBody.innerHTML = `
                    <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                        <div style="font-size: 26px; margin-bottom: 6px;">📂</div>
                        <div style="font-weight: 700; color: var(--text-title); margin-bottom: 2px; font-size: 13px;">Chưa có chứng từ đính kèm</div>
                        <div style="font-size: 11.5px;">Hồ sơ này chưa tải lên hình ảnh hoặc tài liệu chứng từ bổ sung trên CRM.</div>
                    </div>
                `;
                return;
            }

            if (tab.timeline && tab.timeline.length > 0) {
                tabBody.innerHTML = `
                    <div class="timeline-container">
                        ${tab.timeline.map(ev => `
                            <div class="timeline-item">
                                <div class="timeline-dot timeline-dot--${ev.tone || 'primary'}">
                                    <div class="timeline-dot-inner"></div>
                                </div>
                                <div class="timeline-content-card">
                                    <div class="timeline-top">
                                        <span class="timeline-title">${ev.title}</span>
                                        <span class="timeline-time">${ev.time}</span>
                                    </div>
                                    <div class="timeline-actor">👤 ${ev.actor}</div>
                                    <div class="timeline-note">${ev.note}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                return;
            }

            if (tab.fields && tab.fields.length > 0) {
                renderFlatInlineFields(tabBody, tab.fields);
            }
        }

        function renderFlatInlineFields(container, fields) {
            let html = '<div class="modal-fields-grid">';
            fields.forEach(f => {
                let valColor = 'var(--text-title)';
                if (f.tone === 'danger') valColor = '#ef4444';
                else if (f.tone === 'success') valColor = '#10b981';
                else if (f.tone === 'primary') valColor = 'var(--brand-primary)';

                html += `
                    <div class="modal-field-card ${f.wide ? 'modal-field-card--alert' : ''}">
                        <div class="modal-field-lbl">${f.label}</div>
                        <div class="modal-field-val" style="color: ${valColor}">${f.value}</div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        // ─── UTILITY HELPERS FOR REALTIME LIVE ───
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setText(id, text) {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        }

        function setStyle(id, prop, val) {
            const el = document.getElementById(id);
            if (el) el.style[prop] = val;
        }

        function animateNumber(elementId, targetValue, duration = 600) {
            const el = document.getElementById(elementId);
            if (!el) return;
            const target = parseInt(targetValue, 10) || 0;
            const rawCurrent = el.textContent.replace(/\D/g, '');
            const start = parseInt(rawCurrent, 10) || 0;
            if (start === target) return;

            const startTime = performance.now();
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease-out cubic
                const ease = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(start + (target - start) * ease);
                el.textContent = current.toLocaleString('vi-VN');
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = target.toLocaleString('vi-VN');
                }
            }
            requestAnimationFrame(update);
        }

        function animateMoney(elementId, targetAmount, duration = 700) {
            const el = document.getElementById(elementId);
            if (!el) return;
            const target = parseInt(targetAmount, 10) || 0;
            const rawCurrent = el.textContent.replace(/\D/g, '');
            const start = parseInt(rawCurrent, 10) || 0;
            if (start === target) return;

            const startTime = performance.now();
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(start + (target - start) * ease);
                el.innerHTML = `${current.toLocaleString('vi-VN')} <span style="font-size: 13px; font-weight: 700; opacity: 0.85;">VNĐ</span>`;
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.innerHTML = `${target.toLocaleString('vi-VN')} <span style="font-size: 13px; font-weight: 700; opacity: 0.85;">VNĐ</span>`;
                }
            }
            requestAnimationFrame(update);
        }

        function showLiveToast(message, type = 'success', duration = 3500) {
            const container = document.getElementById('liveToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `live-toast live-toast--${type}`;
            const icon = type === 'success' ? '⚡' : (type === 'warning' ? '⚠️' : 'ℹ️');
            toast.innerHTML = `<span>${icon}</span><span>${escapeHtml(message)}</span>`;

            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // ─── REALTIME LIVE AUTO-SYNC ENGINE ───
        const RealtimeEngine = {
            enabled: true,
            intervalMs: 8000, // Sync every 8s
            timer: null,
            isSyncing: false,
            lastChecksum: null,
            audioEnabled: true,
            audioCtx: null,

            init() {
                const saved = localStorage.getItem('los_realtime_enabled');
                if (saved !== null) {
                    this.enabled = saved === 'true';
                }
                this.updatePillUI();
                this.start();

                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.stop();
                    } else {
                        if (this.enabled) {
                            this.sync(false);
                            this.start();
                        }
                    }
                });
            },

            start() {
                this.stop();
                if (!this.enabled) return;
                this.timer = setInterval(() => {
                    this.sync(false);
                }, this.intervalMs);
            },

            stop() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },

            toggle() {
                this.enabled = !this.enabled;
                localStorage.setItem('los_realtime_enabled', this.enabled ? 'true' : 'false');
                this.updatePillUI();
                if (this.enabled) {
                    this.sync(true);
                    this.start();
                    showLiveToast('Đã bật chế độ Realtime Live (Tự động đồng bộ mỗi 8s)', 'info');
                } else {
                    this.stop();
                    showLiveToast('Đã tạm dừng chế độ Realtime Live', 'warning');
                }
            },

            updatePillUI() {
                const dot = document.getElementById('realtimePulseDot');
                const text = document.getElementById('realtimeStatusText');
                const pill = document.getElementById('realtimeStatusPill');
                if (!dot || !text || !pill) return;

                if (this.enabled) {
                    pill.classList.add('live-active');
                    pill.classList.remove('live-paused');
                    dot.className = 'realtime-pulse-dot active';
                    text.textContent = 'Realtime: BẬT';
                } else {
                    pill.classList.remove('live-active');
                    pill.classList.add('live-paused');
                    dot.className = 'realtime-pulse-dot paused';
                    text.textContent = 'Realtime: TẮT';
                }
            },

            async sync(isManual = false) {
                if (this.isSyncing) return;
                this.isSyncing = true;

                const btnSync = document.getElementById('btnSyncNow');
                if (btnSync) btnSync.classList.add('syncing');

                try {
                    const form = document.getElementById('managementFilterForm');
                    const formData = form ? new FormData(form) : new FormData();
                    const params = new URLSearchParams(formData);

                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('page')) params.set('page', urlParams.get('page'));

                    const response = await fetch('{{ route("los.management.index") }}?' + params.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) throw new Error('HTTP ' + response.status);

                    const res = await response.json();
                    if (res.success && res.data && res.stats) {
                        const checksum = res.checksum || JSON.stringify(res.stats) + '_' + res.data.map(d => d.id + ':' + d.updated_at).join(',');

                        if (this.lastChecksum && this.lastChecksum !== checksum) {
                            this.applyLiveUpdate(res);
                        } else if (isManual) {
                            showLiveToast('Dữ liệu đã ở trạng thái mới nhất', 'info');
                        }
                        this.lastChecksum = checksum;
                    }
                } catch (e) {
                    console.warn('Realtime sync error:', e);
                } finally {
                    this.isSyncing = false;
                    if (btnSync) {
                        setTimeout(() => btnSync.classList.remove('syncing'), 400);
                    }
                }
            },

            applyLiveUpdate(res) {
                const stats = res.stats;
                const records = res.data;
                const pagination = res.pagination;

                // 1. Smooth animate KPI metrics
                animateNumber('kpiTotalVal', stats.total);
                animateNumber('kpiProcessingVal', stats.processing);
                animateNumber('kpiApprovedVal', stats.approved);
                animateNumber('kpiRejectedVal', stats.rejected);
                animateMoney('kpiApprovedMoneyVal', stats.approved_amount);

                // Update pills & mini bars
                setText('kpiProcessingPill', stats.processing_rate + '%');
                setText('kpiApprovedPill', stats.approval_rate + '%');
                setText('kpiRejectedPill', stats.rejected_rate + '%');

                setStyle('kpiProcessingBar', 'width', stats.processing_rate + '%');
                setStyle('kpiApprovedBar', 'width', stats.approval_rate + '%');
                setStyle('kpiRejectedBar', 'width', stats.rejected_rate + '%');

                setText('kpiProcessingSub', stats.processing + ' hồ sơ đang chờ duyệt');
                setText('kpiApprovedSub', stats.approved + ' hồ sơ đã giải ngân');
                setText('kpiRejectedSub', stats.rejected + ' hồ sơ không đạt điều kiện');

                // 2. Update distribution bar
                setStyle('distSegmentWarning', 'width', stats.processing_rate + '%');
                setStyle('distSegmentSuccess', 'width', stats.approval_rate + '%');
                setStyle('distSegmentDanger', 'width', stats.rejected_rate + '%');

                setText('distTotalSubCount', '(Tổng số: ' + Number(stats.total).toLocaleString('vi-VN') + ' hồ sơ)');
                setText('distLegendWarningCount', stats.processing + ' (' + stats.processing_rate + '%)');
                setText('distLegendSuccessCount', stats.approved + ' (' + stats.approval_rate + '%)');
                setText('distLegendDangerCount', stats.rejected + ' (' + stats.rejected_rate + '%)');

                // 3. Update result counter
                if (pagination) {
                    setText('resultsTotalCount', Number(pagination.total).toLocaleString('vi-VN'));
                }

                // 4. Update Table Rows with pulse animation
                this.renderTableRows(records, pagination);

                // 5. Play audio chime & show toast
                this.playChime();
                showLiveToast('⚡ Đã cập nhật hồ sơ & số liệu mới nhất!', 'success');
            },

            renderTableRows(records, pagination) {
                const tbody = document.getElementById('applicationsTableBody');
                if (!tbody) return;

                const openDrawerIds = Array.from(tbody.querySelectorAll('.inline-dossier-row'))
                    .filter(r => r.style.display !== 'none')
                    .map(r => r.id.replace('drawer-', ''));

                const currentPage = pagination ? pagination.current_page : 1;
                const perPage = pagination ? pagination.per_page : 20;

                if (records.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
                                <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                                <div style="font-weight: 700; color: var(--text-title); font-size: 14px; margin-bottom: 4px;">Không tìm thấy hồ sơ nào phù hợp</div>
                                <div style="font-size: 12px;">Hãy thử điều chỉnh lại bộ lọc hoặc từ khóa tìm kiếm.</div>
                            </td>
                        </tr>
                    `;
                    return;
                }

                let html = '';
                records.forEach((app, idx) => {
                    const stt = (currentPage - 1) * perPage + (idx + 1);
                    const tone = app.status_tone || 'primary';
                    const badgeClass = tone === 'success' ? 'status-badge--success' 
                        : (tone === 'danger' ? 'status-badge--danger' 
                        : (tone === 'warning' ? 'status-badge--warning' : 'status-badge--primary'));

                    let reason = '-';
                    if (app.application_fields && Array.isArray(app.application_fields)) {
                        for (const f of app.application_fields) {
                            if (f.label && (f.label.includes('Lý do') || f.label.includes('Phản hồi') || f.label.includes('Thông điệp'))) {
                                if (f.value && f.value !== '-') {
                                    reason = f.value;
                                    break;
                                }
                            }
                        }
                    }

                    const isDrawerOpen = openDrawerIds.includes(String(app.id));

                    html += `
                        <tr 
                            class="data-row live-row-pulse ${isDrawerOpen ? 'active-row' : ''}" 
                            id="row-${app.id}"
                            onclick="toggleInlineDossier('${app.id}', '${app.application_code}')"
                            title="Bấm vào để mở/đóng chi tiết ngay bên dưới"
                        >
                            <td style="text-align: center;">
                                <span class="stt-badge">${stt}</span>
                            </td>
                            <td>
                                <span class="code-link">${escapeHtml(app.application_code)}</span>
                                <span class="scheme-sub" title="${escapeHtml(app.scheme_or_product || app.scheme || '-')}">
                                    ${escapeHtml(app.scheme_or_product || app.scheme || '-')}
                                </span>
                            </td>
                            <td>
                                <span class="applicant-name">${escapeHtml(app.applicant_name)}</span>
                                <span class="applicant-sub">
                                    ${app.identity_number && app.identity_number !== '-' ? 'CCCD: ' + escapeHtml(app.identity_number) : ''}
                                </span>
                            </td>
                            <td>
                                <span class="project-badge">${escapeHtml(app.project)}</span>
                            </td>
                            <td>
                                ${app.requested_loan_amount ? `<strong style="color: var(--brand-primary); font-size: 12px;">${Number(app.requested_loan_amount).toLocaleString('vi-VN')} đ</strong>` : `<span style="color: var(--text-muted);">-</span>`}
                            </td>
                            <td>
                                ${app.approved_loan_amount ? `<strong style="color: #10b981; font-size: 12px;">${Number(app.approved_loan_amount).toLocaleString('vi-VN')} đ</strong>` : `<span style="color: var(--text-muted);">-</span>`}
                            </td>
                            <td>
                                <span style="font-size: 12px; color: var(--text-body); font-weight: 600;">${escapeHtml(app.creator || '-')}</span>
                            </td>
                            <td>
                                <span class="status-badge ${badgeClass}">
                                    ${escapeHtml(app.status_label)}
                                </span>
                            </td>
                            <td>
                                <div style="max-width: 220px; font-size: 11px; color: #ef4444; word-break: break-word;">
                                    ${escapeHtml(reason)}
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 11px; color: var(--text-title); font-weight: 600; white-space: nowrap;">
                                    ${escapeHtml(app.created_at || '-')}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 11px; color: var(--text-muted); white-space: nowrap;">
                                    ${escapeHtml(app.updated_at || '-')}
                                </span>
                            </td>
                            <td>
                                <span class="btn-view-popup">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    <span>Xem</span>
                                </span>
                            </td>
                        </tr>

                        <!-- INLINE DOSSIER VIEW -->
                        <tr id="drawer-${app.id}" class="inline-dossier-row" style="display: ${isDrawerOpen ? 'table-row' : 'none'};">
                            <td colspan="12" style="padding: 0;">
                                <div class="inline-dossier-panel">
                                    <div class="inline-dossier-header">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="modal-code">${escapeHtml(app.application_code)}</span>
                                            <span class="project-badge">${escapeHtml(app.project)}</span>
                                            <span class="status-badge ${badgeClass}">${escapeHtml(app.status_label)}</span>
                                        </div>
                                        <button type="button" class="btn-close-inline" onclick="event.stopPropagation(); closeInlineDossier('${app.id}')">✕ Đóng lại</button>
                                    </div>

                                    <div class="modal-hero-banner">
                                        <div class="hero-avatar">${escapeHtml((app.applicant_name || '3R').substring(0, 2))}</div>
                                        <div class="hero-meta-grid">
                                            <div>
                                                <div class="hero-lbl">Khách hàng</div>
                                                <div class="hero-val-highlight">${escapeHtml(app.applicant_name)}</div>
                                            </div>
                                            <div>
                                                <div class="hero-lbl">CCCD · SĐT (Bảo mật)</div>
                                                <div class="hero-val">${escapeHtml(app.identity_number || '-')} · ${escapeHtml(app.phone_number || '-')}</div>
                                            </div>
                                            <div>
                                                <div class="hero-lbl">Ngày sinh</div>
                                                <div class="hero-val">${escapeHtml(app.dob || '-')}</div>
                                            </div>
                                            <div>
                                                <div class="hero-lbl">Sản phẩm / Scheme</div>
                                                <div class="hero-val">${escapeHtml(app.scheme_or_product || '-')}</div>
                                            </div>
                                            <div>
                                                <div class="hero-lbl">Số tiền đề nghị</div>
                                                <div class="hero-val" style="color: var(--brand-primary); font-weight: 800;">${escapeHtml(app.requested_loan_amount_label || '-')}</div>
                                            </div>
                                            <div>
                                                <div class="hero-lbl">Tạo · Cập nhật</div>
                                                <div class="hero-val">${escapeHtml(app.created_at || '-')} · ${escapeHtml(app.updated_at || '-')}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-tab-bar" id="tab-bar-${app.id}"></div>
                                    <div class="inline-dossier-body" id="tab-body-${app.id}">
                                        <div class="modal-loading">
                                            <svg class="spin-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                            <span>Đang tải thông tin chi tiết & chứng từ CRM...</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;

                openDrawerIds.forEach(id => {
                    const row = records.find(r => String(r.id) === String(id));
                    if (row) loadInlineDossierContent(id, row.application_code);
                });
            },

            playChime() {
                try {
                    if (!this.audioEnabled) return;
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;
                    if (!this.audioCtx) this.audioCtx = new AudioContext();

                    const ctx = this.audioCtx;
                    if (ctx.state === 'suspended') ctx.resume();

                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.12);
                    gain.gain.setValueAtTime(0.04, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);

                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.36);
                } catch (e) {
                    // Ignore audio restrictions
                }
            }
        };

        // ─── INIT SEARCHABLE SELECTS FOR LOS MANAGEMENT ───
        function initLosSearchableSelects() {
            document.querySelectorAll('select.native-select, select.perpage-select').forEach(select => {
                if (select.dataset.losSelectInit) return;
                select.dataset.losSelectInit = 'true';
                select.style.display = 'none';

                const wrapper = document.createElement('div');
                wrapper.className = 'los-custom-select';

                const getSelectedText = () => {
                    const opt = select.options[select.selectedIndex];
                    return opt ? opt.text : 'Chọn...';
                };

                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = 'los-select-trigger';
                trigger.innerHTML = `
                    <span class="los-trigger-text">${getSelectedText()}</span>
                    <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                `;

                const menu = document.createElement('div');
                menu.className = 'los-select-menu';

                const searchWrap = document.createElement('div');
                searchWrap.className = 'los-select-search-wrap';
                searchWrap.innerHTML = `
                    <input type="text" class="los-select-search-input" placeholder="🔍 Tìm nhanh...">
                `;

                const optionsContainer = document.createElement('div');
                optionsContainer.className = 'los-select-options';

                const renderOptions = (filterText = '') => {
                    optionsContainer.innerHTML = '';
                    const filterLower = filterText.trim().toLowerCase();
                    let visibleCount = 0;

                    Array.from(select.options).forEach((opt) => {
                        if (filterLower && !opt.text.toLowerCase().includes(filterLower)) {
                            return;
                        }
                        visibleCount++;
                        const item = document.createElement('div');
                        const isSelected = String(select.value) === String(opt.value);
                        item.className = `los-select-option ${isSelected ? 'is-selected' : ''}`;
                        item.innerHTML = `
                            <span>${opt.text}</span>
                            <span class="check-icon">✓</span>
                        `;
                        item.onclick = (e) => {
                            e.stopPropagation();
                            select.value = opt.value;
                            trigger.querySelector('.los-trigger-text').textContent = opt.text;
                            wrapper.classList.remove('is-open');
                            if (select.classList.contains('perpage-select')) {
                                changePerPage(opt.value);
                            } else {
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                                if (select.form) {
                                    select.form.submit();
                                }
                            }
                        };
                        optionsContainer.appendChild(item);
                    });

                    if (visibleCount === 0) {
                        optionsContainer.innerHTML = `<div class="los-select-empty">Không tìm thấy kết quả</div>`;
                    }
                };

                renderOptions();

                const searchInput = searchWrap.querySelector('input');
                searchInput.addEventListener('input', (e) => {
                    renderOptions(e.target.value);
                });
                searchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        wrapper.classList.remove('is-open');
                    }
                });

                trigger.onclick = (e) => {
                    e.stopPropagation();
                    const wasOpen = wrapper.classList.contains('is-open');
                    document.querySelectorAll('.los-custom-select.is-open').forEach(w => w.classList.remove('is-open'));
                    if (!wasOpen) {
                        wrapper.classList.add('is-open');
                        renderOptions('');
                        searchInput.value = '';
                        setTimeout(() => searchInput.focus(), 50);
                    }
                };

                menu.appendChild(searchWrap);
                menu.appendChild(optionsContainer);
                wrapper.appendChild(trigger);
                wrapper.appendChild(menu);

                select.parentNode.insertBefore(wrapper, select.nextSibling);
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.los-custom-select')) {
                    document.querySelectorAll('.los-custom-select.is-open').forEach(w => w.classList.remove('is-open'));
                }
            });
        }

        // Initialize Realtime Live Engine & Searchable Selects on page load
        document.addEventListener('DOMContentLoaded', () => {
            RealtimeEngine.init();
            initLosSearchableSelects();
        });
    </script>
</body>
</html>
