<x-filament-panels::page>
    @php
        $maxPipeline = max(1, (int) collect($pipeline)->max('value'));
        $qualificationRate = $kpis[1]['value'] ?? '0%';
        $todayLeadValue = (int) ($kpis[0]['value'] ?? 0);
        $todayApplicationValue = (int) ($kpis[3]['value'] ?? 0);
        $totalPipeline = collect($pipeline)->sum('value');
        $palette = ['blue', 'emerald', 'amber', 'fuchsia', 'cyan', 'rose'];
    @endphp

    <div class="crm-performance-dashboard">
        <section class="perf-hero">
            <div class="perf-hero-copy">
                <div class="perf-hero-mark">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v13A2.5 2.5 0 0 1 17.5 21h-11A2.5 2.5 0 0 1 4 18.5v-13Zm3 1.25v2h10v-2H7Zm0 4.25v2h4v-2H7Zm6 0v2h4v-2h-4Zm-6 4.25v2h4v-2H7Zm6 0v2h4v-2h-4Z"/></svg>
                </div>
                <div>
                    <h1>Performance Center</h1>
                    <p>{{ now()->format('d/m/Y') }} · {{ number_format($todayLeadValue) }} lead mới · {{ number_format($todayApplicationValue) }} application mới</p>
                </div>
            </div>

            <div class="perf-hero-stats">
                <div>
                    <span>Tỷ lệ đạt</span>
                    <strong>{{ $qualificationRate }}</strong>
                </div>
                <div>
                    <span>Tổng luồng</span>
                    <strong>{{ number_format($totalPipeline) }}</strong>
                </div>
            </div>

            <nav class="perf-actions" aria-label="Dashboard shortcuts">
                <a href="{{ $links['leads'] }}">
                    <svg viewBox="0 0 24 24"><path d="M5 3h14v18H5V3Zm2 2v14h10V5H7Zm2 3h6v2H9V8Zm0 4h6v2H9v-2Z"/></svg>
                    Lead
                </a>
                <a href="{{ $links['profiles'] }}">
                    <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4 0-7 2-7 4.5V20h14v-1.5C19 16 16 14 12 14Z"/></svg>
                    Hồ sơ
                </a>
                <a href="{{ $links['applications'] }}">
                    <svg viewBox="0 0 24 24"><path d="M4 4h16v4H4V4Zm0 6h7v10H4V10Zm9 0h7v10h-7V10Z"/></svg>
                    Application
                </a>
            </nav>
        </section>

        <section class="perf-kpis" aria-label="KPI">
            @foreach ($kpis as $index => $kpi)
                @php($tone = $palette[$index % count($palette)])
                <article class="perf-kpi perf-card-{{ $tone }}">
                    <div class="perf-kpi-top">
                        <span>{{ $kpi['label'] }}</span>
                        <i></i>
                    </div>
                    <strong>{{ is_numeric($kpi['value']) ? number_format($kpi['value']) : $kpi['value'] }}</strong>
                    <small>{{ $kpi['meta'] }}</small>
                    <div class="perf-spark" aria-hidden="true">
                        <b style="height: 34%"></b><b style="height: 58%"></b><b style="height: 44%"></b><b style="height: 74%"></b><b style="height: 62%"></b><b style="height: 88%"></b>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="perf-grid-main">
            <article class="perf-panel perf-pipeline-panel">
                <header class="perf-panel-head">
                    <div>
                        <h2>Phễu vận hành</h2>
                        <span>Lead → Application → Hồ sơ</span>
                    </div>
                    <b>{{ $qualificationRate }}</b>
                </header>

                <div class="perf-pipeline">
                    @foreach ($pipeline as $index => $step)
                        @php($percent = max(5, round(((int) $step['value'] / $maxPipeline) * 100)))
                        @php($tone = $palette[$index % count($palette)])
                        <div class="perf-pipeline-row">
                            <div class="perf-pipeline-label">
                                <span>{{ $step['label'] }}</span>
                                <strong>{{ number_format($step['value']) }}</strong>
                            </div>
                            <div class="perf-pipeline-track">
                                <i class="perf-bg-{{ $tone }}" style="width: {{ $percent }}%"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="perf-panel perf-alert-panel">
                <header class="perf-panel-head">
                    <div>
                        <h2>Cảnh báo dữ liệu</h2>
                        <span>Điểm cần xử lý</span>
                    </div>
                </header>

                <div class="perf-health-list">
                    @foreach ($health as $index => $item)
                        @php($tone = $index === 2 ? 'emerald' : 'rose')
                        <div class="perf-health-item perf-border-{{ $tone }}">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ is_numeric($item['value']) ? number_format($item['value']) : $item['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="perf-grid-lists">
            <article class="perf-panel">
                <header class="perf-panel-head">
                    <div>
                        <h2>Lead mới nhất</h2>
                        <span>Nguồn vào gần đây</span>
                    </div>
                    <a href="{{ $links['leads'] }}">Mở Lead</a>
                </header>

                <div class="perf-list">
                    @forelse ($recentLeads as $lead)
                        <a class="perf-record" href="{{ $links['leads'] }}">
                            <div class="perf-record-mark">{{ mb_substr($lead->lead_name ?: $lead->lead_code ?: 'L', 0, 1) }}</div>
                            <div class="perf-record-body">
                                <strong>{{ $lead->lead_code ?: 'Lead #'.$lead->getKey() }}</strong>
                                <span>{{ $lead->lead_name ?: '-' }}</span>
                            </div>
                            <div class="perf-record-side">
                                <span>{{ $lead->salesProject?->name ?: 'Chưa có dự án' }}</span>
                                <small>{{ $lead->created_at?->format('H:i d/m') }}</small>
                            </div>
                        </a>
                    @empty
                        <div class="perf-empty">Chưa có lead.</div>
                    @endforelse
                </div>
            </article>

            <article class="perf-panel">
                <header class="perf-panel-head">
                    <div>
                        <h2>Hồ sơ cần xử lý</h2>
                        <span>Hàng chờ quyết định</span>
                    </div>
                    <a href="{{ $links['profiles'] }}">Mở hồ sơ</a>
                </header>

                <div class="perf-list">
                    @forelse ($processingQueue as $profile)
                        <a class="perf-record" href="{{ $links['profiles'] }}">
                            <div class="perf-record-mark perf-record-mark-alt">HS</div>
                            <div class="perf-record-body">
                                <strong>HS #{{ $profile->getKey() }}</strong>
                                <span>{{ $profile->customer_name ?: '-' }}</span>
                            </div>
                            <div class="perf-record-side">
                                <span>{{ $profile->processingOwner?->name ?: 'Chưa phân xử lý' }}</span>
                                <small>{{ $profile->created_at?->format('H:i d/m') }}</small>
                            </div>
                        </a>
                    @empty
                        <div class="perf-empty">Không có hồ sơ đang chờ.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="perf-panel perf-project-panel">
            <header class="perf-panel-head">
                <div>
                    <h2>Hiệu suất theo dự án</h2>
                    <span>Dự án đang có quyền truy cập</span>
                </div>
            </header>

            <div class="perf-projects">
                @forelse ($projects as $index => $project)
                    @php($tone = $palette[$index % count($palette)])
                    <div class="perf-project perf-border-{{ $tone }}">
                        <div>
                            <strong>{{ $project['name'] }}</strong>
                            <span>{{ $project['slug'] }}</span>
                        </div>
                        <b>{{ number_format($project['count']) }}</b>
                    </div>
                @empty
                    <div class="perf-empty">Chưa có dự án khả dụng.</div>
                @endforelse
            </div>
        </section>
    </div>

    <style>
        .crm-performance-dashboard {
            --crm-text: #08111f;
            --crm-muted: #64748b;
            --crm-line: #dce7f4;
            display: grid;
            gap: 14px;
            padding: 0 0 18px;
            color: var(--crm-text);
        }

        .crm-performance-dashboard svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .perf-hero,
        .perf-panel,
        .perf-kpi {
            border: 1px solid rgba(148, 163, 184, .28);
            box-shadow: 0 16px 38px rgba(15, 23, 42, .08);
        }

        .perf-hero {
            position: relative;
            isolation: isolate;
            min-height: 126px;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 18px;
            padding: 20px;
            border-radius: 20px;
            background:
                linear-gradient(115deg, rgba(13, 20, 43, .96) 0%, rgba(31, 41, 86, .94) 44%, rgba(10, 102, 194, .88) 100%),
                repeating-linear-gradient(135deg, rgba(255,255,255,.14) 0 1px, transparent 1px 16px);
            color: #fff;
        }

        .perf-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background:
                linear-gradient(90deg, transparent 0 14%, rgba(34,211,238,.24) 14% 15%, transparent 15% 42%, rgba(251,191,36,.22) 42% 43%, transparent 43% 71%, rgba(244,114,182,.22) 71% 72%, transparent 72%),
                linear-gradient(180deg, rgba(255,255,255,.10), transparent 70%);
        }

        .perf-hero-copy {
            display: flex;
            align-items: center;
            min-width: 0;
            gap: 14px;
        }

        .perf-hero-mark {
            width: 54px;
            height: 54px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 16px;
            color: #fff;
            background: linear-gradient(135deg, #22d3ee, #2563eb 58%, #a855f7);
            box-shadow: 0 18px 35px rgba(37, 99, 235, .36), inset 0 0 0 1px rgba(255,255,255,.32);
        }

        .perf-hero h1 {
            margin: 0;
            font-size: clamp(1.75rem, 2.7vw, 2.65rem);
            line-height: 1.02;
            font-weight: 900;
            letter-spacing: 0;
        }

        .perf-hero p {
            margin: 8px 0 0;
            color: rgba(255,255,255,.78);
            font-size: .9rem;
            font-weight: 700;
        }

        .perf-hero-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(110px, 1fr));
            gap: 10px;
        }

        .perf-hero-stats div {
            min-height: 70px;
            padding: 12px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 16px;
            background: rgba(255,255,255,.10);
            backdrop-filter: blur(14px);
        }

        .perf-hero-stats span {
            display: block;
            color: rgba(255,255,255,.72);
            font-size: .74rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .perf-hero-stats strong {
            display: block;
            margin-top: 6px;
            color: #fff;
            font-size: 1.45rem;
            line-height: 1;
            font-weight: 900;
        }

        .perf-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .perf-actions a,
        .perf-panel-head a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 0 13px;
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 12px;
            background: rgba(255,255,255,.12);
            color: #fff;
            font-size: .84rem;
            font-weight: 820;
            text-decoration: none;
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        .perf-actions a:first-child {
            border-color: transparent;
            background: #facc15;
            color: #111827;
            box-shadow: 0 12px 22px rgba(250, 204, 21, .24);
        }

        .perf-panel-head a {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            backdrop-filter: none;
        }

        .perf-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .perf-kpi {
            position: relative;
            min-height: 138px;
            overflow: hidden;
            padding: 16px;
            border-radius: 18px;
            color: #fff;
        }

        .perf-kpi::after {
            content: '';
            position: absolute;
            inset: auto -20px -42px 34%;
            height: 92px;
            border-radius: 40px 0 0 0;
            background: rgba(255,255,255,.16);
            transform: skewX(-18deg);
        }

        .perf-card-blue { background: linear-gradient(135deg, #2563eb, #06b6d4); }
        .perf-card-emerald { background: linear-gradient(135deg, #059669, #22c55e); }
        .perf-card-amber { background: linear-gradient(135deg, #d97706, #facc15); }
        .perf-card-fuchsia { background: linear-gradient(135deg, #7c3aed, #ec4899); }
        .perf-card-cyan { background: linear-gradient(135deg, #0891b2, #38bdf8); }
        .perf-card-rose { background: linear-gradient(135deg, #e11d48, #fb7185); }

        .perf-kpi-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .perf-kpi-top span {
            color: rgba(255,255,255,.82);
            font-size: .72rem;
            font-weight: 880;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .perf-kpi-top i {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 0 0 5px rgba(255,255,255,.18);
        }

        .perf-kpi strong {
            position: relative;
            z-index: 1;
            display: block;
            margin-top: 14px;
            color: #fff;
            font-size: clamp(1.7rem, 2.4vw, 2.2rem);
            line-height: 1;
            font-weight: 930;
            letter-spacing: 0;
        }

        .perf-kpi small {
            position: relative;
            z-index: 1;
            display: block;
            margin-top: 9px;
            color: rgba(255,255,255,.82);
            font-size: .82rem;
            font-weight: 700;
        }

        .perf-spark {
            position: absolute;
            right: 16px;
            bottom: 14px;
            z-index: 1;
            display: flex;
            align-items: end;
            gap: 4px;
            width: 72px;
            height: 32px;
            opacity: .72;
        }

        .perf-spark b {
            flex: 1;
            min-width: 4px;
            border-radius: 999px 999px 2px 2px;
            background: rgba(255,255,255,.82);
        }

        .perf-grid-main,
        .perf-grid-lists {
            display: grid;
            gap: 14px;
        }

        .perf-grid-main {
            grid-template-columns: minmax(0, 1.45fr) minmax(320px, .55fr);
        }

        .perf-grid-lists {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .perf-panel {
            min-width: 0;
            overflow: hidden;
            border-radius: 18px;
            background: linear-gradient(180deg, #fff, #fbfdff);
        }

        .perf-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 16px 12px;
            border-bottom: 1px solid #eef2f7;
        }

        .perf-panel-head h2 {
            margin: 0;
            color: var(--crm-text);
            font-size: 1rem;
            line-height: 1.25;
            font-weight: 880;
        }

        .perf-panel-head span {
            display: block;
            margin-top: 4px;
            color: var(--crm-muted);
            font-size: .8rem;
            font-weight: 650;
        }

        .perf-panel-head b {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            font-size: .95rem;
            font-weight: 900;
        }

        .perf-pipeline,
        .perf-health-list,
        .perf-list,
        .perf-projects {
            display: grid;
            gap: 9px;
            padding: 14px 16px 16px;
        }

        .perf-pipeline-row {
            display: grid;
            grid-template-columns: 178px minmax(0, 1fr);
            align-items: center;
            gap: 12px;
        }

        .perf-pipeline-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-width: 0;
        }

        .perf-pipeline-label span {
            color: #334155;
            font-size: .86rem;
            font-weight: 760;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .perf-pipeline-label strong {
            color: var(--crm-text);
            font-size: .93rem;
            font-weight: 900;
        }

        .perf-pipeline-track {
            height: 13px;
            overflow: hidden;
            border-radius: 999px;
            background: #edf2f7;
            box-shadow: inset 0 0 0 1px rgba(148,163,184,.18);
        }

        .perf-pipeline-track i {
            display: block;
            height: 100%;
            border-radius: inherit;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.35);
        }

        .perf-health-item,
        .perf-record,
        .perf-project {
            border: 1px solid #e6edf5;
            border-radius: 14px;
            background: #fff;
        }

        .perf-health-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 56px;
            padding: 12px;
            border-left-width: 5px;
        }

        .perf-health-item span {
            color: #475569;
            font-size: .84rem;
            font-weight: 760;
        }

        .perf-health-item strong {
            color: var(--crm-text);
            font-size: 1.08rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .perf-record,
        .perf-project {
            display: grid;
            align-items: center;
            gap: 11px;
            padding: 11px;
            text-decoration: none;
            transition: background .14s ease, border-color .14s ease, transform .14s ease, box-shadow .14s ease;
        }

        .perf-record {
            grid-template-columns: 40px minmax(0, 1fr) minmax(120px, auto);
        }

        .perf-record:hover,
        .perf-project:hover {
            border-color: #93c5fd;
            background: #f8fbff;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(37, 99, 235, .10);
        }

        .perf-record-mark {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1d4ed8;
            font-size: .9rem;
            font-weight: 920;
        }

        .perf-record-mark-alt {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            color: #b45309;
            font-size: .75rem;
        }

        .perf-record-body,
        .perf-record-side,
        .perf-project div {
            min-width: 0;
        }

        .perf-record strong,
        .perf-project strong {
            display: block;
            color: var(--crm-text);
            font-size: .9rem;
            line-height: 1.25;
            font-weight: 860;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .perf-record span,
        .perf-record small,
        .perf-project span {
            display: block;
            margin-top: 3px;
            color: var(--crm-muted);
            font-size: .78rem;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .perf-record-side {
            text-align: right;
        }

        .perf-projects {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .perf-project {
            grid-template-columns: minmax(0, 1fr) auto;
            min-height: 76px;
            border-left-width: 5px;
        }

        .perf-project b {
            color: #1d4ed8;
            font-size: 1.2rem;
            font-weight: 930;
        }

        .perf-empty {
            padding: 18px;
            border: 1px dashed #cbd5e1;
            border-radius: 13px;
            background: #f8fafc;
            color: var(--crm-muted);
            font-size: .86rem;
            font-weight: 680;
            text-align: center;
        }

        .perf-bg-blue { background: linear-gradient(90deg, #60a5fa, #2563eb); }
        .perf-bg-emerald { background: linear-gradient(90deg, #6ee7b7, #059669); }
        .perf-bg-amber { background: linear-gradient(90deg, #fde68a, #d97706); }
        .perf-bg-fuchsia { background: linear-gradient(90deg, #f0abfc, #c026d3); }
        .perf-bg-cyan { background: linear-gradient(90deg, #67e8f9, #0891b2); }
        .perf-bg-rose { background: linear-gradient(90deg, #fda4af, #e11d48); }

        .perf-border-blue { border-left-color: #2563eb; }
        .perf-border-emerald { border-left-color: #059669; }
        .perf-border-amber { border-left-color: #d97706; }
        .perf-border-fuchsia { border-left-color: #c026d3; }
        .perf-border-cyan { border-left-color: #0891b2; }
        .perf-border-rose { border-left-color: #e11d48; }

        @media (max-width: 1180px) {
            .perf-hero { grid-template-columns: 1fr; align-items: stretch; }
            .perf-actions { justify-content: flex-start; }
            .perf-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .perf-grid-main,
            .perf-grid-lists { grid-template-columns: 1fr; }
            .perf-projects { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 760px) {
            .crm-performance-dashboard { gap: 10px; }
            .perf-hero { padding: 14px; border-radius: 16px; }
            .perf-hero-copy { align-items: flex-start; }
            .perf-hero-mark { width: 44px; height: 44px; border-radius: 13px; }
            .perf-hero h1 { font-size: 1.65rem; }
            .perf-hero-stats { grid-template-columns: 1fr 1fr; }
            .perf-actions a { flex: 1 1 auto; min-width: 0; }
            .perf-kpis { grid-template-columns: 1fr; gap: 10px; }
            .perf-kpi { min-height: 118px; padding: 14px; border-radius: 15px; }
            .perf-panel { border-radius: 15px; }
            .perf-panel-head { padding: 14px 14px 10px; }
            .perf-pipeline,
            .perf-health-list,
            .perf-list,
            .perf-projects { padding: 12px 14px 14px; }
            .perf-pipeline-row { grid-template-columns: 1fr; gap: 7px; }
            .perf-record { grid-template-columns: 38px minmax(0, 1fr); }
            .perf-record-side { grid-column: 2; text-align: left; }
            .perf-projects { grid-template-columns: 1fr; }
            .perf-health-item { align-items: flex-start; flex-direction: column; gap: 5px; }
        }
    </style>
</x-filament-panels::page>
