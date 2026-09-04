@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $user = auth()->user();
    $userCode = $user ? ($user->employee_code ?: ($user->uid ?: ('ID: ' . $user->id))) : 'GUEST';
    $userName = $user ? $user->name : 'Khách';
    $userRole = $user ? (method_exists($user, 'getRoleNames') && $user->getRoleNames()->isNotEmpty() ? $user->getRoleNames()->join(', ') : ($user->role ?? 'Thành viên')) : 'Khách';
    $userInitials = $user ? Str::substr($user->name, 0, 2) : '3R';
    $userWatermarkName = $user ? ($userName . ' (' . $userCode . ')') : 'GUEST';
    $systemWatermark = ($brandName . ' LOS') . ' · ' . request()->getHost() . ' · ' . $userWatermarkName;
@endphp
<!doctype html>
<html lang="vi" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAPP LOS · Tra cứu Hồ sơ &middot; {{ $brandName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script>
        // Synchronous theme check to prevent flash of wrong theme
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
            --row-hover-bg: rgba(59, 130, 246, 0.06);
            --active-row-bg: rgba(59, 130, 246, 0.1);
            --inline-dossier-bg: #090f1e;
            --inline-header-bg: #060a14;
            --hero-bg: #070d1a;
            --tab-bar-bg: #040812;
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

        /* ─── Dấu chìm bảo mật toàn màn hình (Security Watermark) ─── */
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

        /* ─── Topbar ─── */
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
        .brand-meta span { font-size: 11px; color: var(--text-muted); }

        .header-actions { display: flex; align-items: center; gap: 10px; }

        /* Theme Toggle Button */
        .btn-theme-toggle {
            background: var(--card-bg);
            border: 1px solid var(--navy-border);
            color: var(--text-body);
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .btn-theme-toggle:hover {
            background: var(--active-row-bg);
            border-color: rgba(59, 130, 246, 0.4);
            color: var(--brand-primary);
        }

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

        /* ─── Main ─── */
        .sapp-main {
            width: min(1600px, 100%);
            margin: 0 auto;
            padding: 14px 20px 60px;
            flex: 1;
        }

        /* ─── Compact Inline Toolbar ─── */
        .toolbar-panel {
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: var(--radius-lg);
            padding: 8px 12px;
            margin-bottom: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .inline-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .omnibox-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--input-bg);
            border: 1px solid rgba(59, 130, 246, 0.4);
            border-radius: 6px;
            padding: 2px 4px 2px 10px;
            width: 320px;
            max-width: 100%;
            transition: all 0.15s ease;
        }
        .omnibox-box:focus-within {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .omnibox-icon { color: var(--brand-primary); display: flex; }
        .omnibox-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-title);
            font-size: 13px;
            outline: none;
            padding: 6px 0;
        }
        .omnibox-input::placeholder { color: var(--text-muted); }
        
        .btn-search {
            background: var(--brand-gradient);
            border: none;
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: opacity 0.15s ease;
        }
        .btn-search:hover { opacity: 0.9; }
        .btn-search:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Compact Filters */
        .filter-group {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--input-bg);
            border: 1px solid var(--navy-border);
            border-radius: 6px;
            padding: 3px 8px;
            transition: border-color 0.15s ease;
        }
        .filter-group:hover, .filter-group:focus-within { border-color: rgba(59, 130, 246, 0.4); }
        .filter-label {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        
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
            min-width: 220px;
            max-width: 320px;
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
            max-height: 220px;
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
            border-radius: 6px;
            padding: 3px 8px;
            transition: border-color 0.15s ease;
        }
        .date-picker-group:hover, .date-picker-group:focus-within {
            border-color: rgba(56, 189, 248, 0.4);
        }
        .los-date-input {
            background: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 4px;
            outline: none;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }
        .los-date-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
        }

        .btn-reset {
            background: transparent;
            border: 1px solid var(--navy-border);
            color: var(--text-muted);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            margin-left: auto;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .btn-reset:hover { background: var(--card-bg); color: var(--text-title); }

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
        .dblclick-hint { font-size: 11px; color: var(--brand-primary); font-weight: 600; }

        .table-card {
            background: var(--navy-panel);
            border: 1px solid var(--navy-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            transition: background-color 0.2s ease, border-color 0.2s ease;
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
        }
        .sapp-table tbody tr.data-row:hover { background: var(--row-hover-bg); }
        .sapp-table tbody tr.data-row.active-row { background: var(--active-row-bg); border-bottom-color: transparent; }
        .sapp-table td { padding: 9px 10px; vertical-align: middle; }

        .code-link {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: #0284c7;
            font-size: 12px;
            cursor: pointer;
            user-select: none;
            display: inline-block;
        }
        [data-theme="dark"] .code-link { color: #38bdf8; }
        .code-link:hover { text-decoration: underline; color: var(--brand-primary); }
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

        /* Status Chips */
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

        .reason-cell {
            max-width: 220px;
            font-size: 11px;
            color: #ef4444;
            line-height: 1.3;
            word-break: break-word;
        }

        .btn-detail {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: var(--brand-primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .btn-detail:hover { background: var(--brand-primary); color: #ffffff; }

        /* ─── INLINE DOSSIER VIEW (NGAY PHÍA DƯỚI HỒ SƠ) ─── */
        .inline-dossier-row {
            background: var(--card-bg);
            border-bottom: 2px solid rgba(59, 130, 246, 0.35);
        }
        .inline-dossier-panel {
            background: var(--inline-dossier-bg);
            border-left: 3px solid var(--brand-primary);
            border-right: 1px solid var(--navy-border);
            border-bottom: 1px solid var(--navy-border);
            margin: 4px 10px 14px 10px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: background-color 0.2s ease, border-color 0.2s ease;
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
        .inline-dossier-title { display: flex; align-items: center; gap: 8px; }
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
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .btn-close-inline:hover { background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.4); }

        /* 🌟 FIXED CUSTOMER HERO BANNER (TRONG KHUNG INLINE) */
        .modal-hero-banner {
            background: var(--hero-bg);
            border-bottom: 1px solid var(--navy-border);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
            transition: background-color 0.2s ease;
        }
        .hero-avatar-box { flex-shrink: 0; }
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
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .hero-meta-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1.4fr 1.3fr 0.9fr 1.4fr 1.2fr 1.3fr;
            gap: 4px 12px;
        }
        .hero-lbl {
            font-size: 9px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 1px;
        }
        .hero-val {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-title);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hero-val-highlight {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-title);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Inline Tabs Navigation Bar */
        .modal-tab-bar {
            height: 38px;
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 14px;
            background: var(--tab-bar-bg);
            border-bottom: 1px solid var(--navy-border);
            overflow-x: auto;
            flex-shrink: 0;
            transition: background-color 0.2s ease;
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
            transition: all 0.15s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .tab-btn:hover { color: var(--text-title); background: var(--card-bg); }
        .tab-btn.active {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.4);
            color: var(--brand-primary);
        }

        /* Scrollable Inline Dossier Body */
        .inline-dossier-body {
            padding: 16px;
            max-height: 480px;
            overflow-y: auto;
        }

        .modal-loading {
            padding: 30px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--brand-primary);
            font-weight: 600;
            font-size: 12.5px;
            gap: 10px;
        }

        .modal-fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 8px;
        }
        .modal-field-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            padding: 8px 10px;
        }
        .modal-field-card--alert {
            grid-column: 1 / -1;
            background: rgba(239, 68, 68, 0.06);
            border-color: rgba(239, 68, 68, 0.25);
        }
        .modal-field-lbl { font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px; }
        .modal-field-val { font-size: 12px; font-weight: 600; color: var(--text-title); word-break: break-word; }

        /* Documents Grid & Preview */
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 12px;
        }
        .doc-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .doc-preview-box {
            height: 160px;
            background: var(--navy-bg);
            display: grid;
            place-items: center;
            overflow: hidden;
            position: relative;
        }
        .doc-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.2s ease;
        }
        .doc-preview-img:hover { transform: scale(1.05); }
        .doc-card-meta {
            padding: 8px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--inline-header-bg);
            border-top: 1px solid var(--navy-border);
        }
        .doc-card-title { font-size: 11.5px; font-weight: 700; color: var(--text-title); }
        .btn-view-doc {
            font-size: 10.5px;
            color: var(--brand-primary);
            text-decoration: none;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .btn-view-doc:hover { background: var(--brand-primary); color: #ffffff; }

        /* Timeline History Styles */
        .timeline-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 4px 6px;
        }
        .timeline-item {
            display: flex;
            gap: 12px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 22px;
            bottom: -12px;
            width: 2px;
            background: var(--navy-border);
        }
        .timeline-item:last-child::before { display: none; }
        .timeline-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--navy-card);
            border: 2px solid var(--brand-primary);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            z-index: 2;
        }
        .timeline-dot--danger { border-color: #ef4444; }
        .timeline-dot--success { border-color: #10b981; }
        .timeline-dot--warning { border-color: #f59e0b; }
        .timeline-dot-inner {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--brand-primary);
        }
        .timeline-dot--danger .timeline-dot-inner { background: #ef4444; }
        .timeline-dot--success .timeline-dot-inner { background: #10b981; }
        .timeline-dot--warning .timeline-dot-inner { background: #f59e0b; }

        .timeline-content-card {
            flex: 1;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            padding: 8px 12px;
        }
        .timeline-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .timeline-title { font-size: 12.5px; font-weight: 700; color: var(--text-title); }
        .timeline-time { font-size: 10.5px; color: var(--text-muted); }
        .timeline-actor { font-size: 11px; color: var(--brand-primary); font-weight: 600; margin-bottom: 3px; }
        .timeline-note { font-size: 11.5px; color: var(--text-body); line-height: 1.35; }

        /* Empty State */
        .empty-placeholder {
            padding: 50px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: var(--brand-primary);
            display: grid;
            place-items: center;
            margin: 0 auto 12px;
        }
        .empty-title { font-size: 15px; font-weight: 800; color: var(--text-title); margin-bottom: 4px; }
        .empty-desc { font-size: 12.5px; color: var(--text-muted); max-width: 420px; margin: 0 auto; }

        /* Loading Skeleton */
        .skeleton-row td { padding: 12px; }
        .skeleton-bar {
            height: 12px;
            border-radius: 3px;
            background: linear-gradient(90deg, var(--card-bg) 25%, var(--navy-border) 50%, var(--card-bg) 75%);
            background-size: 200% 100%;
            animation: shimmerAnim 1.5s infinite;
        }
        .spin-icon { animation: spinAnim 0.8s linear infinite; }

        @keyframes shimmerAnim { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @keyframes spinAnim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <!-- ─── DẤU CHÌM BẢO MẬT TOÀN HỆ THỐNG (SECURITY WATERMARK) ─── -->
    <div class="security-watermark-layer" id="securityWatermarkLayer"></div>

    <!-- Topbar -->
    <header class="sapp-header">
        <div style="display: flex; align-items: center; gap: 24px;">
            <a href="{{ route('los.index') }}" class="header-brand">
                <div class="brand-icon-box">3R</div>
                <div class="brand-meta">
                    <strong>{{ $brandName }} LOS <span class="sapp-tag">SAPP LOS</span></strong>
                </div>
            </a>

            <!-- Navigation Tabs -->
            <nav style="display: flex; align-items: center; gap: 4px;">
                <a href="{{ route('los.index') }}" style="text-decoration: none; font-size: 12.5px; font-weight: 700; padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; color: var(--brand-primary); background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.3);">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <span>Tra cứu nhanh</span>
                </a>
                <a href="{{ route('los.management.index') }}" style="text-decoration: none; font-size: 12.5px; font-weight: 700; padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); transition: all 0.15s ease;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                    <span>Quản lý hồ sơ</span>
                </a>
            </nav>
        </div>

        <div class="header-actions">
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
        <!-- Compact Inline Toolbar -->
        <section class="toolbar-panel">
            <form id="sappSearchForm" method="POST" action="{{ route('los.search') }}" class="inline-toolbar">
                @csrf
                
                <!-- Omnibox (Compact 320px) -->
                <div class="omnibox-box">
                    <span class="omnibox-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input 
                        type="text" 
                        id="keywordInput"
                        class="omnibox-input" 
                        name="keyword" 
                        placeholder="Mã hồ sơ, Mã GD (DG...), CCCD, SĐT..."
                        value="{{ $keyword }}"
                        autofocus
                    >
                </div>

                <button type="submit" id="submitBtn" class="btn-search">
                    <span id="btnIcon">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <span id="btnText">Tra cứu</span>
                </button>

                <!-- Clean Filters Inline -->
                <div class="filter-group">
                    <span class="filter-label">Dự án:</span>
                    <select class="native-select" id="projectSelect" name="project" onchange="triggerLiveSearch()">
                        <option value="all" @selected($project === 'all')>Tất cả</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p['slug'] }}" @selected($project === $p['slug'])>{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <span class="filter-label">Nguồn:</span>
                    <select class="native-select" id="systemSelect" name="system" onchange="triggerLiveSearch()">
                        <option value="all" @selected($system === 'all')>Tất cả</option>
                        <option value="affiliate" @selected($system === 'affiliate')>Tiếp thị liên kết (Affiliate)</option>
                        <option value="internal" @selected($system === 'internal')>LOS CRM Nội bộ</option>
                        <option value="feol" @selected($system === 'feol')>FEOL Deeplink</option>
                    </select>
                </div>

                <div class="filter-group">
                    <span class="filter-label">Trạng thái:</span>
                    <select class="native-select" id="statusSelect" name="status" onchange="triggerLiveSearch()">
                        <option value="all" @selected($status === 'all')>Tất cả</option>
                        <option value="pending" @selected($status === 'pending')>Chờ xử lý / Đang thẩm định</option>
                        <option value="approved" @selected($status === 'approved')>Phê duyệt thành công</option>
                        <option value="rejected" @selected($status === 'rejected')>Bị từ chối / Không đạt</option>
                        <option value="disbursed" @selected($status === 'disbursed')>Đã giải ngân</option>
                    </select>
                </div>

                <div class="filter-group">
                    <span class="filter-label">Thời gian:</span>
                    <select class="native-select" id="dateSelect" name="date_range" onchange="triggerLiveSearch()">
                        <option value="all" @selected($dateRange === 'all')>Toàn thời gian</option>
                        <option value="today" @selected($dateRange === 'today')>Hôm nay</option>
                        <option value="yesterday" @selected($dateRange === 'yesterday')>Hôm qua</option>
                        <option value="7days" @selected($dateRange === '7days')>7 ngày qua</option>
                        <option value="30days" @selected($dateRange === '30days')>30 ngày qua</option>
                        <option value="this_month" @selected($dateRange === 'this_month')>Tháng này</option>
                        <option value="last_month" @selected($dateRange === 'last_month')>Tháng trước</option>
                    </select>
                </div>

                <!-- Lọc Từ ngày - Đến ngày -->
                <div class="filter-group date-picker-group">
                    <span class="filter-label">Từ:</span>
                    <input type="date" class="los-date-input" id="dateFromInput" name="date_from" value="{{ $dateFrom ?? '' }}" onchange="triggerLiveSearch()" title="Chọn ngày bắt đầu">
                    <span class="filter-label" style="margin-left: 2px;">Đến:</span>
                    <input type="date" class="los-date-input" id="dateToInput" name="date_to" value="{{ $dateTo ?? '' }}" onchange="triggerLiveSearch()" title="Chọn ngày kết thúc">
                </div>

                <a href="{{ route('los.index') }}" class="btn-reset">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    <span>Đặt lại</span>
                </a>
            </form>
        </section>

        <!-- Dynamic Results Area -->
        <div id="resultsContainer">
            @if ($results === null)
                <div class="table-card">
                    <div class="empty-placeholder">
                        <div class="empty-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <h2 class="empty-title">HỆ THỐNG SAPP LOS</h2>
                        <p class="empty-desc">
                            Vui lòng nhập <strong>Mã hồ sơ, Mã giao dịch (DG...), CCCD, Số điện thoại</strong> hoặc chọn bộ lọc dự án để bắt đầu tra cứu.
                        </p>
                    </div>
                </div>
            @else
                <div class="results-header">
                    <div class="results-title">
                        Danh sách: <span>{{ is_countable($results) ? count($results) : 0 }}</span> hồ sơ
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-wrapper">
                        <table class="sapp-table">
                            <thead>
                                <tr>
                                    <th>Mã Hồ sơ / Giao dịch</th>
                                    <th>Khách hàng</th>
                                    <th>Dự án / Gói vay</th>
                                    <th>Khoản vay đề xuất</th>
                                    <th>Khoản vay phê duyệt</th>
                                    <th>Nguồn / Mã NV</th>
                                    <th>Trạng thái CRM</th>
                                    <th>Phản hồi chi tiết</th>
                                    <th>Thời gian</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($results as $result)
                                    @php
                                        $tone = $result['status_tone'] ?? 'primary';
                                        $badgeClass = match($tone) {
                                            'success' => 'status-badge--success',
                                            'danger' => 'status-badge--danger',
                                            'warning' => 'status-badge--warning',
                                            default => 'status-badge--primary',
                                        };
                                        
                                        $reason = null;
                                        foreach ($result['application_fields'] as $f) {
                                            if (str_contains($f['label'], 'Lý do') || str_contains($f['label'], 'Phản hồi') || str_contains($f['label'], 'events')) {
                                                if ($f['value'] !== '-') {
                                                    $reason = $f['value'];
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    <tr 
                                        class="data-row" 
                                        id="row-{{ $result['id'] }}"
                                        ondblclick="toggleInlineDossier('{{ $result['id'] }}', '{{ $result['application_code'] }}')"
                                        title="Nhấp đúp chuột để mở/đóng chi tiết ngay bên dưới"
                                    >
                                        <td>
                                            <span 
                                                class="code-link" 
                                                title="Nhấp đúp 2 lần để xem chi tiết ngay bên dưới"
                                            >
                                                {{ $result['application_code'] }}
                                            </span>
                                            <span class="scheme-sub" title="{{ $result['scheme_or_product'] ?? ($result['scheme'] ?? 'SAPP LOS') }}">
                                                {{ $result['scheme_or_product'] ?? ($result['scheme'] ?? 'SAPP LOS') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="applicant-name">{{ $result['applicant_name'] }}</span>
                                            <span class="applicant-sub">
                                                @if($result['identity_number'] && $result['identity_number'] !== '-')
                                                    CCCD: {{ $result['identity_number'] }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <span class="project-badge">{{ $result['project'] }}</span>
                                        </td>
                                        <td>
                                            @if(!empty($result['requested_loan_amount']))
                                                <strong style="color: var(--brand-primary); font-size: 12px;">{{ number_format($result['requested_loan_amount'], 0, ',', '.') }} đ</strong>
                                            @else
                                                <span style="color: var(--text-muted);">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($result['approved_loan_amount']))
                                                <strong style="color: #10b981; font-size: 12px;">{{ number_format($result['approved_loan_amount'], 0, ',', '.') }} đ</strong>
                                            @else
                                                <span style="color: var(--text-muted);">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; color: var(--text-body); font-weight: 600;">{{ $result['creator'] ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="status-badge {{ $badgeClass }}">
                                                {{ $result['status_label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="reason-cell">
                                                @if ($reason)
                                                    <span>{{ $reason }}</span>
                                                @else
                                                    <span style="color: var(--text-muted);">-</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 11px; color: var(--text-muted); white-space: nowrap;">
                                                {{ $result['updated_at'] ?? ($result['created_at'] ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-detail" type="button" onclick="toggleInlineDossier('{{ $result['id'] }}', '{{ $result['application_code'] }}')">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                                <span>Chi tiết</span>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- 🌟 INLINE DOSSIER VIEW (NGAY PHÍA DƯỚI HỒ SƠ) -->
                                    <tr id="drawer-{{ $result['id'] }}" class="inline-dossier-row" style="display: none;">
                                        <td colspan="10" style="padding: 0;">
                                            <div class="inline-dossier-panel" id="inline-panel-{{ $result['id'] }}">
                                                <!-- Header of inline panel -->
                                                <div class="inline-dossier-header">
                                                    <div class="inline-dossier-title">
                                                        <span class="modal-code">{{ $result['application_code'] }}</span>
                                                        <span class="project-badge">{{ $result['project'] }}</span>
                                                        <span class="status-badge {{ $badgeClass }}">{{ $result['status_label'] }}</span>
                                                    </div>
                                                    <button type="button" class="btn-close-inline" onclick="closeInlineDossier('{{ $result['id'] }}')">
                                                        ✕ Đóng lại
                                                    </button>
                                                </div>

                                                <!-- Hero Summary Banner -->
                                                <div class="modal-hero-banner">
                                                    <div class="hero-avatar-box">
                                                        <div class="hero-avatar">{{ Str::substr($result['applicant_name'], 0, 2) }}</div>
                                                    </div>
                                                    <div class="hero-meta-grid">
                                                        <div>
                                                            <div class="hero-lbl">Khách hàng</div>
                                                            <div class="hero-val-highlight">{{ $result['applicant_name'] }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="hero-lbl">CCCD · SĐT (Bảo mật)</div>
                                                            <div class="hero-val">{{ $result['identity_number'] ?: '-' }} · {{ $result['phone_number'] ?: '-' }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="hero-lbl">Ngày sinh</div>
                                                            <div class="hero-val">{{ $result['dob'] ?: '-' }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="hero-lbl">Sản phẩm / Scheme</div>
                                                            <div class="hero-val">{{ $result['scheme_or_product'] ?: '-' }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="hero-lbl">Số tiền đề nghị</div>
                                                            <div class="hero-val" style="color: var(--brand-primary); font-weight: 800;">{{ $result['requested_loan_amount_label'] ?: '-' }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="hero-lbl">Tạo · Cập nhật</div>
                                                            <div class="hero-val">{{ $result['created_at'] ?: '-' }} · {{ $result['updated_at'] ?: '-' }}</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Tab Navigation Bar -->
                                                <div class="modal-tab-bar" id="tab-bar-{{ $result['id'] }}">
                                                    <!-- Dynamic Tabs -->
                                                </div>

                                                <!-- Tab Body -->
                                                <div class="inline-dossier-body" id="tab-body-{{ $result['id'] }}">
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
                                        <td colspan="10" class="empty-placeholder">
                                            <h3 class="empty-title">Không tìm thấy hồ sơ phù hợp</h3>
                                            <p class="empty-desc">Thử đổi từ khóa hoặc đặt lại bộ lọc.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </main>

    <!-- Footer Bản Quyền -->
    <footer class="sapp-footer" style="margin-top: auto; background: var(--footer-bg, #070a13); border-top: 1px solid var(--navy-border); padding: 18px 20px; font-size: 12px; color: var(--text-muted); transition: background-color 0.2s ease, border-color 0.2s ease;">
        <div style="width: min(1600px, 100%); margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>© {{ date('Y') }} <strong style="color: var(--text-title); font-weight: 700;">{{ $brandName }}</strong>. Bản quyền thuộc về {{ $brandName }}.</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 11.5px;">
                <span style="background: var(--card-bg); border: 1px solid var(--navy-border); color: var(--brand-primary); font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px;">SAPP LOS</span>
            </div>
        </div>
    </footer>

    <script>
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

        // ─── QUẢN LÝ CHẾ ĐỘ SÁNG / TỐI (LIGHT / DARK THEME) ───
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

        // ─── TẠO DẤU CHÌM BẢO MẬT TOÀN MÀN HÌNH (WATERMARK) ───
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
                
                // Dòng 1: Tên hệ thống + Link hệ thống
                ctx.font = "700 13px Inter, -apple-system, sans-serif";
                ctx.fillStyle = theme === 'light' ? "rgba(15, 23, 42, 0.55)" : "rgba(255, 255, 255, 0.75)";
                ctx.fillText(`${systemName} · ${hostUrl}`, -40, 130);

                // Dòng 2: Tên user / Mã NV + Thời gian
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

        // ─── INLINE DOSSIER MANAGER (HIỂN THỊ NGAY PHÍA DƯỚI HỒ SƠ) ───
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

            // Open drawer
            drawer.style.display = 'table-row';
            if (parentRow) parentRow.classList.add('active-row');

            // Scroll slightly into view smoothly
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
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(res => {
                if (!res.success || !res.data) {
                    tabBody.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 20px;">${res.message || 'Không tìm thấy chi tiết hồ sơ.'}</div>`;
                    return;
                }
                dossierCache[code] = res.data;
                renderInlineTabs(id, res.data);
            })
            .catch(err => {
                console.error(err);
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

            // 1. Documents Tab
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

            // 2. Timeline History Tab
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

            // 3. Standard Fields Tab
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

        // ─── Live AJAX Search with Shimmer Skeleton ───
        const form = document.getElementById('sappSearchForm');
        const resultsContainer = document.getElementById('resultsContainer');
        const submitBtn = document.getElementById('submitBtn');
        const btnIcon = document.getElementById('btnIcon');
        const btnText = document.getElementById('btnText');

        function triggerLiveSearch() {
            showLoadingSkeleton();
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                renderResults(data);
            })
            .catch(err => {
                console.error(err);
                form.submit();
            })
            .finally(() => {
                restoreSubmitBtn();
            });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            triggerLiveSearch();
        });

        function showLoadingSkeleton() {
            submitBtn.disabled = true;
            btnIcon.innerHTML = `<svg class="spin-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`;
            btnText.textContent = "Đang tra cứu...";

            resultsContainer.innerHTML = `
                <div class="results-header">
                    <div class="results-title">Đang truy vấn hệ thống...</div>
                </div>
                <div class="table-card">
                    <div class="table-wrapper">
                        <table class="sapp-table">
                            <thead>
                                <tr>
                                    <th>Mã Hồ sơ / Giao dịch</th>
                                    <th>Khách hàng</th>
                                    <th>Dự án / Gói vay</th>
                                    <th>Khoản vay đề xuất</th>
                                    <th>Khoản vay phê duyệt</th>
                                    <th>Nguồn / Mã NV</th>
                                    <th>Trạng thái CRM</th>
                                    <th>Phản hồi chi tiết</th>
                                    <th>Thời gian</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${Array(4).fill(0).map(() => `
                                    <tr class="skeleton-row">
                                        <td><div class="skeleton-bar" style="width: 110px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 130px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 80px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 90px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 90px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 100px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 110px; border-radius: 99px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 140px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 70px;"></div></td>
                                        <td><div class="skeleton-bar" style="width: 60px; border-radius: 4px;"></div></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        function restoreSubmitBtn() {
            submitBtn.disabled = false;
            btnIcon.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>`;
            btnText.textContent = "Tra cứu";
        }

        function renderResults(data) {
            const results = data.results || [];
            const count = data.count || 0;

            if (results.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="results-header">
                        <div class="results-title">Danh sách hồ sơ: <span>0</span> kết quả</div>
                    </div>
                    <div class="table-card">
                        <div class="empty-placeholder">
                            <h3 class="empty-title">Không tìm thấy hồ sơ phù hợp</h3>
                            <p class="empty-desc">Thử đổi từ khóa hoặc đặt lại bộ lọc.</p>
                        </div>
                    </div>
                `;
                return;
            }

            let html = `
                <div class="results-header">
                    <div class="results-title">Danh sách: <span>${count}</span> hồ sơ</div>
                </div>
                <div class="table-card">
                    <div class="table-wrapper">
                        <table class="sapp-table">
                            <thead>
                                <tr>
                                    <th>Mã Hồ sơ / Giao dịch</th>
                                    <th>Khách hàng</th>
                                    <th>Dự án / Gói vay</th>
                                    <th>Khoản vay đề xuất</th>
                                    <th>Khoản vay phê duyệt</th>
                                    <th>Nguồn / Mã NV</th>
                                    <th>Trạng thái CRM</th>
                                    <th>Phản hồi chi tiết</th>
                                    <th>Thời gian</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
            `;

            results.forEach(r => {
                let badgeClass = 'status-badge--primary';
                if (r.status_tone === 'success') badgeClass = 'status-badge--success';
                else if (r.status_tone === 'danger') badgeClass = 'status-badge--danger';
                else if (r.status_tone === 'warning') badgeClass = 'status-badge--warning';

                let reason = null;
                if (r.application_fields) {
                    r.application_fields.forEach(f => {
                        if ((f.label.includes('Lý do') || f.label.includes('Phản hồi') || f.label.includes('events')) && f.value !== '-') {
                            reason = f.value;
                        }
                    });
                }

                const reqStr = r.requested_loan_amount ? new Intl.NumberFormat('vi-VN').format(r.requested_loan_amount) + ' đ' : '-';
                const appStr = r.approved_loan_amount ? new Intl.NumberFormat('vi-VN').format(r.approved_loan_amount) + ' đ' : '-';

                html += `
                    <tr 
                        class="data-row" 
                        id="row-${r.id}"
                        ondblclick="toggleInlineDossier('${r.id}', '${r.application_code}')"
                        title="Nhấp đúp chuột để mở/đóng chi tiết ngay bên dưới"
                    >
                        <td>
                            <span 
                                class="code-link" 
                                title="Nhấp đúp 2 lần để xem chi tiết ngay bên dưới"
                            >
                                ${r.application_code}
                            </span>
                            <span class="scheme-sub" title="${r.scheme_or_product || (r.scheme || 'SAPP LOS')}">
                                ${r.scheme_or_product || (r.scheme || 'SAPP LOS')}
                            </span>
                        </td>
                        <td>
                            <span class="applicant-name">${r.applicant_name}</span>
                            <span class="applicant-sub">${r.identity_number && r.identity_number !== '-' ? 'CCCD: ' + r.identity_number : ''}</span>
                        </td>
                        <td><span class="project-badge">${r.project}</span></td>
                        <td>
                            ${r.requested_loan_amount ? `<strong style="color: var(--brand-primary); font-size: 12px;">${reqStr}</strong>` : `<span style="color: var(--text-muted);">-</span>`}
                        </td>
                        <td>
                            ${r.approved_loan_amount ? `<strong style="color: #10b981; font-size: 12px;">${appStr}</strong>` : `<span style="color: var(--text-muted);">-</span>`}
                        </td>
                        <td><span style="font-size: 12px; color: var(--text-body); font-weight: 600;">${r.creator || '-'}</span></td>
                        <td><span class="status-badge ${badgeClass}">${r.status_label}</span></td>
                        <td>
                            <div class="reason-cell">
                                ${reason ? `<span>${reason}</span>` : `<span style="color: var(--text-muted);">-</span>`}
                            </div>
                        </td>
                        <td><span style="font-size: 11px; color: var(--text-muted); white-space: nowrap;">${r.updated_at || r.created_at || '-'}</span></td>
                        <td>
                            <button class="btn-detail" type="button" onclick="toggleInlineDossier('${r.id}', '${r.application_code}')">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                <span>Chi tiết</span>
                            </button>
                        </td>
                    </tr>
                    <tr id="drawer-${r.id}" class="inline-dossier-row" style="display: none;">
                        <td colspan="10" style="padding: 0;">
                            <div class="inline-dossier-panel" id="inline-panel-${r.id}">
                                <div class="inline-dossier-header">
                                    <div class="inline-dossier-title">
                                        <span class="modal-code">${r.application_code}</span>
                                        <span class="project-badge">${r.project}</span>
                                        <span class="status-badge ${badgeClass}">${r.status_label}</span>
                                    </div>
                                    <button type="button" class="btn-close-inline" onclick="closeInlineDossier('${r.id}')">✕ Đóng lại</button>
                                </div>
                                <div class="modal-hero-banner">
                                    <div class="hero-avatar-box">
                                        <div class="hero-avatar">${r.applicant_name ? r.applicant_name.substring(0, 2).toUpperCase() : 'KH'}</div>
                                    </div>
                                    <div class="hero-meta-grid">
                                        <div>
                                            <div class="hero-lbl">Khách hàng</div>
                                            <div class="hero-val-highlight">${r.applicant_name}</div>
                                        </div>
                                        <div>
                                            <div class="hero-lbl">CCCD · SĐT (Bảo mật)</div>
                                            <div class="hero-val">${r.identity_number || '-'} · ${r.phone_number || '-'}</div>
                                        </div>
                                        <div>
                                            <div class="hero-lbl">Ngày sinh</div>
                                            <div class="hero-val">${r.dob || '-'}</div>
                                        </div>
                                        <div>
                                            <div class="hero-lbl">Sản phẩm / Scheme</div>
                                            <div class="hero-val">${r.scheme_or_product || '-'}</div>
                                        </div>
                                        <div>
                                            <div class="hero-lbl">Số tiền đề nghị</div>
                                            <div class="hero-val" style="color: var(--brand-primary); font-weight: 800;">${r.requested_loan_amount_label || '-'}</div>
                                        </div>
                                        <div>
                                            <div class="hero-lbl">Tạo · Cập nhật</div>
                                            <div class="hero-val">${r.created_at || '-'} · ${r.updated_at || '-'}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-tab-bar" id="tab-bar-${r.id}"></div>
                                <div class="inline-dossier-body" id="tab-body-${r.id}">
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

            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            resultsContainer.innerHTML = html;
        }

        // ─── INIT SEARCHABLE SELECTS FOR LOS ───
        function initLosSearchableSelects() {
            document.querySelectorAll('select.native-select').forEach(select => {
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
                        const isSelected = select.value === opt.value;
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
                            select.dispatchEvent(new Event('change', { bubbles: true }));
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

        document.addEventListener('DOMContentLoaded', () => {
            initLosSearchableSelects();
        });
    </script>
</body>
</html>
