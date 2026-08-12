@php
    $settings = \App\Models\UiSetting::current();
    $brandName = $settings->logo_text ?: ($settings->app_name ?: '3RDVN CRM');
    $logo = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LOS · Truy vấn hồ sơ</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root {
            color-scheme: light;
            --los-primary: #2563eb;
            --los-ink: #172033;
            --los-muted: #667085;
            --los-border: #e3e8f0;
            --los-surface: #ffffff;
            --los-bg: #f5f7fb;
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            color: var(--los-ink);
            background:
                radial-gradient(circle at 10% 0%, rgba(37, 99, 235, .08), transparent 28rem),
                var(--los-bg);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        button, input { font: inherit; }
        button { cursor: pointer; }

        .los-topbar {
            position: sticky;
            z-index: 20;
            top: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 68px;
            padding: 0 24px;
            border-bottom: 1px solid rgba(227, 232, 240, .92);
            background: rgba(255, 255, 255, .9);
            backdrop-filter: blur(14px);
        }

        .los-brand,
        .los-user {
            display: flex;
            align-items: center;
        }

        .los-brand { gap: 12px; }
        .los-brand-mark {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            overflow: hidden;
            border-radius: 11px;
            color: #fff;
            background: #102033;
            font-weight: 850;
        }

        .los-brand-mark img {
            width: 100%;
            height: 100%;
            padding: 6px;
            object-fit: contain;
        }

        .los-brand strong,
        .los-brand span { display: block; }
        .los-brand strong { font-size: 15px; }
        .los-brand span { margin-top: 2px; color: var(--los-muted); font-size: 12px; }
        .los-user { gap: 12px; }
        .los-user-copy { text-align: right; }
        .los-user-copy strong,
        .los-user-copy span { display: block; }
        .los-user-copy strong { font-size: 13px; }
        .los-user-copy span { margin-top: 2px; color: var(--los-muted); font-size: 11px; }

        .los-logout {
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid var(--los-border);
            border-radius: 9px;
            color: #344054;
            background: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .los-main {
            width: min(1640px, 100%);
            margin: 0 auto;
            padding: 34px 24px 48px;
        }

        .los-hero { margin-bottom: 22px; }
        .los-eyebrow {
            margin: 0 0 7px;
            color: var(--los-primary);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .los-hero h1 {
            margin: 0;
            color: #101828;
            font-size: clamp(25px, 3vw, 36px);
            letter-spacing: -.035em;
        }


        .los-search-card,
        .los-results {
            border: 1px solid var(--los-border);
            border-radius: 16px;
            background: var(--los-surface);
            box-shadow: 0 1px 2px rgba(16, 24, 40, .03), 0 18px 44px rgba(42, 56, 82, .04);
        }

        .los-search-card { padding: 20px; }
        .los-search-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(220px, 1fr)) auto;
            align-items: end;
            gap: 12px;
        }

        .los-search-control { position: relative; }
        .los-search-field { display: grid; gap: 7px; }
        .los-search-field > span { color: #344054; font-size: 12px; font-weight: 800; }
        .los-search-icon {
            position: absolute;
            z-index: 1;
            top: 50%;
            left: 15px;
            width: 20px;
            height: 20px;
            color: #7a8699;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .los-search-control .los-search-input { padding-left: 46px; }
        .los-search-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
        }
        .los-search-button svg { width: 18px; height: 18px; }

        .los-search-input {
            width: 100%;
            height: 48px;
            padding: 0 15px;
            border: 1px solid #d7deea;
            border-radius: 11px;
            outline: none;
            color: #172033;
            background: #fff;
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        .los-search-input:focus {
            border-color: var(--los-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .1);
        }

        .los-search-button {
            min-width: 132px;
            height: 48px;
            padding: 0 18px;
            border: 0;
            border-radius: 11px;
            color: #fff;
            background: linear-gradient(180deg, #3474f3, #2563eb);
            box-shadow: 0 9px 20px rgba(37, 99, 235, .22);
            font-weight: 750;
        }

        .los-error {
            margin: 0 0 14px;
            padding: 11px 13px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #b42318;
            background: #fff1f1;
            font-size: 13px;
            font-weight: 650;
        }

        .los-results { margin-top: 20px; overflow: hidden; }
        .los-results-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--los-border);
        }

        .los-results-head h2 { margin: 0; font-size: 16px; }
        .los-results-head span { color: var(--los-muted); font-size: 12px; }
        .los-table-wrap { overflow-x: auto; }
        .los-table {
            width: 100%;
            min-width: 1420px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .los-table th,
        .los-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #edf1f6;
            text-align: left;
            vertical-align: middle;
        }

        .los-table th {
            color: #596780;
            background: #f8fafc;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .035em;
            white-space: nowrap;
        }

        .los-table td { color: #344054; font-size: 12px; }
        .los-table tbody:last-child tr:last-child td { border-bottom: 0; }
        .los-code { color: var(--los-primary); font-weight: 800; white-space: nowrap; }
        .los-name { color: #101828; font-weight: 750; min-width: 160px; }
        .los-nowrap { white-space: nowrap; }
        .los-eye {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            margin: 0 auto;
            border: 1px solid #d7e4ff;
            border-radius: 9px;
            color: var(--los-primary);
            background: #eff5ff;
        }

        .los-eye:hover,
        .los-eye[aria-expanded="true"] { color: #fff; background: var(--los-primary); }
        .los-eye svg { width: 18px; height: 18px; }
        .los-detail-row[hidden] { display: none; }
        .los-detail-cell { padding: 0 !important; background: #f8fbff; }
        .los-detail {
            margin: 0;
            padding: 20px;
            border-top: 2px solid rgba(37, 99, 235, .18);
        }

        .los-detail h3 {
            margin: 0 0 15px;
            color: #101828;
            font-size: 15px;
        }

        .los-detail-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .los-field {
            min-width: 0;
            padding: 12px;
            border: 1px solid #e4eaf3;
            border-radius: 10px;
            background: #fff;
        }

        .los-field dt {
            margin: 0 0 6px;
            color: #475467;
            font-size: 11px;
            font-weight: 800;
        }

        .los-field dd {
            margin: 0;
            color: #172033;
            font-size: 13px;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .los-result-list {
            display: grid;
            gap: 14px;
            padding: 16px;
            background: #f8fafc;
        }

        .los-result-card {
            overflow: hidden;
            border: 1px solid #dfe6f0;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 6px 20px rgba(16, 24, 40, .035);
        }

        .los-result-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 20px 15px;
            border-bottom: 1px solid #edf1f6;
        }

        .los-result-title {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        .los-result-avatar {
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(145deg, #2563eb, #174dbd);
            box-shadow: 0 8px 18px rgba(37, 99, 235, .2);
            font-size: 14px;
            font-weight: 850;
        }

        .los-result-title h3 { margin: 0; color: #101828; font-size: 16px; line-height: 1.35; }
        .los-result-title p { margin: 4px 0 0; color: var(--los-muted); font-size: 12px; }
        .los-result-actions { display: flex; flex: 0 0 auto; align-items: center; gap: 10px; }

        .los-status {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            min-height: 26px;
            padding: 4px 9px;
            border: 1px solid #dfe5ee;
            border-radius: 999px;
            color: #475467;
            background: #f8fafc;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.2;
        }

        .los-status--primary { border-color: #bfdbfe; color: #1d4ed8; background: #eff6ff; }
        .los-status--success { border-color: #a7f3d0; color: #047857; background: #ecfdf5; }
        .los-status--danger { border-color: #fecaca; color: #b91c1c; background: #fef2f2; }
        .los-status--warning { border-color: #fde68a; color: #a16207; background: #fffbeb; }
        .los-status--info { border-color: #bae6fd; color: #0369a1; background: #f0f9ff; }

        .los-eye--label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: 40px;
            margin: 0;
            padding: 0 12px;
            gap: 8px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .los-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin: 0;
            padding: 5px 20px 18px;
            gap: 0 18px;
        }

        .los-summary-item { min-width: 0; padding: 13px 0 11px; border-bottom: 1px dashed #e3e8f0; }
        .los-summary-item dt,
        .los-field dt { margin: 0 0 6px; color: #475467; font-size: 11px; font-weight: 800; }
        .los-summary-item dd { margin: 0; color: #172033; font-size: 13px; line-height: 1.45; overflow-wrap: anywhere; }

        .los-detail[hidden] { display: none; }
        .los-detail { padding: 20px; border-top: 1px solid #dbe6f5; background: linear-gradient(180deg, #f5f9ff, #f9fbfe); }
        .los-detail-header { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; }
        .los-detail-header svg { width: 20px; height: 20px; color: var(--los-primary); }
        .los-detail-header h3 { margin: 0; font-size: 16px; }
        .los-detail-header p { margin: 3px 0 0; color: var(--los-muted); font-size: 11px; }
        .los-detail-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .los-field {
            min-width: 0;
            padding: 0 0 13px;
            border: 0;
            border-bottom: 1px solid #dfe7f2;
            border-radius: 0;
            background: transparent;
        }
        .los-field--wide { grid-column: 1 / -1; }
        .los-field dd { font-weight: 520; }

        .los-empty {
            padding: 44px 20px;
            color: var(--los-muted);
            text-align: center;
            font-size: 14px;
        }

        .los-empty strong { display: block; margin-bottom: 6px; color: #253044; font-size: 16px; }

        @media (max-width: 1000px) {
            .los-detail-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .los-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 680px) {
            .los-topbar { padding: 0 14px; }
            .los-user-copy { display: none; }
            .los-main { padding: 24px 14px 38px; }
            .los-search-card { padding: 15px; }
            .los-search-form { grid-template-columns: 1fr; }
            .los-search-button { width: 100%; }
            .los-result-main { align-items: flex-start; padding: 16px; }
            .los-result-actions { align-items: flex-end; flex-direction: column; }
            .los-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); padding-inline: 16px; }
            .los-detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .los-eye--label span { display: none; }
            .los-eye--label { width: 40px; padding: 0; }
        }

        @media (max-width: 430px) {
            .los-brand > div:last-child > span { display: none; }
            .los-summary-grid { grid-template-columns: 1fr; }
            .los-detail-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="los-topbar">
        <div class="los-brand">
            <div class="los-brand-mark">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $brandName }}">
                @else
                    <span>3</span>
                @endif
            </div>
            <div>
                <strong>3RDVN LOS</strong>
                <span>Truy vấn hồ sơ</span>
            </div>
        </div>

        <div class="los-user">
            <div class="los-user-copy">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->uid ?: auth()->user()->employee_code }}</span>
            </div>
            <form method="POST" action="{{ route('los.logout') }}">
                @csrf
                <button class="los-logout" type="submit">Đăng xuất</button>
            </form>
        </div>
    </header>

    <main class="los-main">
        <section class="los-hero">
            <p class="los-eyebrow">Loan Origination System</p>
            <h1>Truy vấn hồ sơ</h1>
        </section>

        <section class="los-search-card">
            @if ($errors->any())
                <div class="los-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="los-search-form" method="POST" action="{{ route('los.search') }}">
                @csrf
                <label class="los-search-field">
                    <span>Mã hồ sơ</span>
                    <div class="los-search-control">
                        <svg class="los-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m20 20-3.8-3.8"/>
                        </svg>
                        <input
                            class="los-search-input"
                            type="text"
                            name="application_code"
                            value="{{ old('application_code', $applicationCode) }}"
                            placeholder="Nhập mã hồ sơ"
                            minlength="4"
                            maxlength="50"
                            autocomplete="off"
                            autofocus
                        >
                    </div>
                </label>

                <label class="los-search-field">
                    <span>CCCD/CMND</span>
                    <div class="los-search-control">
                        <svg class="los-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m20 20-3.8-3.8"/>
                        </svg>
                        <input
                            class="los-search-input"
                            type="text"
                            name="identity_number"
                            value="{{ old('identity_number', $identityNumber) }}"
                            placeholder="Nhập CCCD/CMND"
                            maxlength="20"
                            inputmode="numeric"
                            autocomplete="off"
                        >
                    </div>
                </label>

                <button class="los-search-button" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.8-3.8"/>
                    </svg>
                    <span>Truy vấn hồ sơ</span>
                </button>
            </form>
        </section>

        @if (! is_null($results))
            <section class="los-results" aria-live="polite">
                <header class="los-results-head">
                    <h2>Kết quả truy vấn</h2>
                    <span>{{ $results->count() }} hồ sơ</span>
                </header>

                @if ($results->isEmpty())
                    <div class="los-empty">
                        <strong>Không tìm thấy hồ sơ</strong>
                        Kiểm tra lại mã hồ sơ hoặc CCCD/CMND đã nhập.
                    </div>
                @else
                    <div class="los-result-list">
                        @foreach ($results as $result)

                            <article class="los-result-card">
                                <header class="los-result-main">
                                    <div class="los-result-title">
                                        <div class="los-result-avatar" aria-hidden="true">{{ str($result['applicant_name'])->substr(0, 2)->upper() }}</div>
                                        <div>
                                            <h3>{{ $result['applicant_name'] }}</h3>
                                            <p><span class="los-code">{{ $result['application_code'] }}</span> · {{ $result['project'] }}</p>
                                        </div>
                                    </div>
                                    <div class="los-result-actions">
                                        <span class="los-status los-status--{{ $result['status_tone'] }}">{{ $result['status_label'] }}</span>
                                        <button
                                            class="los-eye los-eye--label"
                                            type="button"
                                            title="Xem Thông tin Application"
                                            aria-label="Xem Thông tin Application của {{ $result['application_code'] }}"
                                            aria-expanded="false"
                                            aria-controls="los-detail-{{ $result['id'] }}"
                                            data-los-detail="los-detail-{{ $result['id'] }}"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/>
                                                <circle cx="12" cy="12" r="2.75"/>
                                            </svg>
                                            <span>Xem Application</span>
                                        </button>
                                    </div>
                                </header>

                                <dl class="los-summary-grid">
                                    @foreach ($result['summary_fields'] as $field)
                                        <div class="los-summary-item">
                                            <dt>{{ $field['label'] }}</dt>
                                            <dd>{{ $field['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>

                                <section class="los-detail" id="los-detail-{{ $result['id'] }}" hidden>
                                    <header class="los-detail-header">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path d="M7 3.5h10a2 2 0 0 1 2 2v15H5v-15a2 2 0 0 1 2-2Z"/>
                                            <path d="M9 3.5v-1h6v1M8.5 9h7M8.5 13h7M8.5 17h4"/>
                                        </svg>
                                        <div>
                                            <h3>Thông tin Application</h3>
                                            <p>{{ $result['application_code'] }} · {{ $result['applicant_name'] }}</p>
                                        </div>
                                    </header>
                                    <dl class="los-detail-grid">
                                        @foreach ($result['application_fields'] as $field)
                                            <div class="los-field {{ $field['wide'] ? 'los-field--wide' : '' }}">
                                                <dt>{{ $field['label'] }}</dt>
                                                <dd>
                                                    @if (filled($field['tone']))
                                                        <span class="los-status los-status--{{ $field['tone'] }}">{{ $field['value'] }}</span>
                                                    @else
                                                        {{ $field['value'] }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </section>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </main>

    <script>
        document.querySelectorAll('[data-los-detail]').forEach((button) => {
            button.addEventListener('click', () => {
                const detail = document.getElementById(button.dataset.losDetail);

                if (! detail) {
                    return;
                }

                const willOpen = detail.hidden;
                detail.hidden = ! willOpen;
                button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

                if (willOpen) {
                    detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    </script>
</body>
</html>
