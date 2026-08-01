<x-filament-panels::page>
    <div class="performance-dashboard">
        <header class="performance-header">
            <div class="performance-heading">
                <div class="performance-heading-icon">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" />
                </div>
                <div class="performance-heading-copy">
                    <p>{{ $profile['eyebrow'] }}</p>
                    <h1>{{ $profile['title'] }}</h1>
                    <small>{{ $profile['subtitle'] }}</small>
                    <div class="performance-scope" aria-label="Phạm vi dashboard">
                        <span><x-filament::icon icon="heroicon-o-user-circle" />{{ $viewer['name'] }}</span>
                        <span><x-filament::icon icon="heroicon-o-identification" />{{ $viewer['role'] }}</span>
                        <span><x-filament::icon icon="heroicon-o-building-office-2" />{{ $viewer['context'] }}</span>
                        <span><x-filament::icon icon="heroicon-o-calendar-days" />{{ $periodLabel }}</span>
                    </div>
                </div>
            </div>

            <div class="performance-header-controls">
                <span class="performance-live"><i></i>Dữ liệu trực tiếp</span>
                <div class="performance-period" role="group" aria-label="Khoảng thời gian">
                    @foreach ([7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày'] as $days => $label)
                        <button
                            type="button"
                            wire:click="setPeriod({{ $days }})"
                            @class(['is-active' => $period === $days])
                            @if ($period === $days) aria-pressed="true" @else aria-pressed="false" @endif
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </header>

        <nav class="performance-actions" aria-label="Truy cập nhanh">
            <span class="performance-actions-label">Truy cập nhanh</span>
            @if ($links['leads'])
                <a href="{{ $links['leads'] }}">
                    <x-filament::icon icon="heroicon-o-user-plus" />
                    <span>Lead</span>
                </a>
            @endif
            @if ($links['applications'])
                <a href="{{ $links['applications'] }}">
                    <x-filament::icon icon="heroicon-o-briefcase" />
                    <span>Application</span>
                </a>
            @endif
            @if ($links['reports'])
                <a href="{{ $links['reports'] }}">
                    <x-filament::icon icon="heroicon-o-chart-bar" />
                    <span>Báo cáo</span>
                </a>
            @endif
        </nav>

        <section class="performance-metrics" aria-label="Chỉ số vận hành">
            @foreach ($metrics as $metric)
                <article class="performance-metric perf-tone-{{ $metric['tone'] }}">
                    <div class="performance-metric-icon">
                        <x-filament::icon :icon="$metric['icon']" />
                    </div>
                    <div class="performance-metric-copy">
                        <span>{{ $metric['label'] }}</span>
                        <strong>{{ is_numeric($metric['value']) ? number_format($metric['value']) : $metric['value'] }}</strong>
                        <small class="perf-direction-{{ $metric['direction'] > 0 ? 'up' : ($metric['direction'] < 0 ? 'down' : 'flat') }}">
                            @if ($metric['direction'] > 0)
                                <x-filament::icon icon="heroicon-o-arrow-trending-up" />
                            @elseif ($metric['direction'] < 0)
                                <x-filament::icon icon="heroicon-o-arrow-trending-down" />
                            @else
                                <x-filament::icon icon="heroicon-o-minus" />
                            @endif
                            {{ $metric['meta'] }}
                        </small>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="performance-primary-grid">
            <article class="performance-panel performance-trend-panel">
                <header class="performance-panel-header">
                    <div>
                        <h2>{{ $profile['trendTitle'] }}</h2>
                        <p>{{ $profile['trendDescription'] }}</p>
                    </div>
                    <div class="performance-legend" aria-label="Chú giải">
                        <span><i class="legend-lead"></i>Lead</span>
                        <span><i class="legend-application"></i>Application</span>
                    </div>
                </header>

                @php
                    $chartWidth = 960;
                    $chartTop = 18;
                    $chartBottom = 202;
                    $chartLeft = 30;
                    $chartRight = 930;
                    $chartSteps = max(1, count($trend) - 1);
                    $leadPoints = collect($trend)->map(fn (array $point, int $index): string => sprintf(
                        '%.2f,%.2f',
                        $chartLeft + (($chartRight - $chartLeft) * ($index / $chartSteps)),
                        $chartBottom - (($chartBottom - $chartTop) * ($point['leads'] / $trendMax)),
                    ))->implode(' ');
                    $applicationPoints = collect($trend)->map(fn (array $point, int $index): string => sprintf(
                        '%.2f,%.2f',
                        $chartLeft + (($chartRight - $chartLeft) * ($index / $chartSteps)),
                        $chartBottom - (($chartBottom - $chartTop) * ($point['applications'] / $trendMax)),
                    ))->implode(' ');
                @endphp
                <div class="performance-chart-scroll">
                    <div class="performance-chart">
                        <svg viewBox="0 0 {{ $chartWidth }} 230" role="img" aria-label="Biểu đồ xu hướng Lead và Application">
                            <defs>
                                <linearGradient id="lead-area" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#22d3ee" stop-opacity=".28" />
                                    <stop offset="100%" stop-color="#22d3ee" stop-opacity="0" />
                                </linearGradient>
                                <linearGradient id="application-area" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#34d399" stop-opacity=".2" />
                                    <stop offset="100%" stop-color="#34d399" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <g class="performance-chart-grid" aria-hidden="true">
                                @foreach ([18, 64, 110, 156, 202] as $gridY)
                                    <line x1="30" y1="{{ $gridY }}" x2="930" y2="{{ $gridY }}" />
                                @endforeach
                            </g>
                            <polygon class="chart-area chart-area-lead" points="30,202 {{ $leadPoints }} 930,202" />
                            <polygon class="chart-area chart-area-application" points="30,202 {{ $applicationPoints }} 930,202" />
                            <polyline class="chart-line chart-line-lead" points="{{ $leadPoints }}" />
                            <polyline class="chart-line chart-line-application" points="{{ $applicationPoints }}" />
                            @foreach ($trend as $point)
                                @php
                                    $pointX = $chartLeft + (($chartRight - $chartLeft) * ($loop->index / $chartSteps));
                                    $leadY = $chartBottom - (($chartBottom - $chartTop) * ($point['leads'] / $trendMax));
                                    $applicationY = $chartBottom - (($chartBottom - $chartTop) * ($point['applications'] / $trendMax));
                                @endphp
                                <circle class="chart-point chart-point-lead" cx="{{ $pointX }}" cy="{{ $leadY }}" r="4">
                                    <title>{{ $point['label'] }} · Lead: {{ $point['leads'] }}</title>
                                </circle>
                                <circle class="chart-point chart-point-application" cx="{{ $pointX }}" cy="{{ $applicationY }}" r="4">
                                    <title>{{ $point['label'] }} · Application: {{ $point['applications'] }}</title>
                                </circle>
                            @endforeach
                        </svg>
                        <div class="performance-chart-labels" style="--chart-columns: {{ count($trend) }}">
                            @foreach ($trend as $point)
                                <span>{{ $point['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>

            <article class="performance-panel performance-overview-panel">
                <header class="performance-panel-header">
                    <div>
                        <h2>{{ $profile['overviewTitle'] }}</h2>
                        <p>{{ $profile['overviewDescription'] }}</p>
                    </div>
                </header>

                <div class="performance-overview-list">
                    @foreach ($overview as $item)
                        <div class="performance-overview-item perf-status-{{ $item['tone'] }}">
                            <i></i>
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ number_format($item['value']) }}</strong>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="performance-secondary-grid">
            <article class="performance-panel">
                <header class="performance-panel-header">
                    <div>
                        <h2>{{ $profile['leadTitle'] }}</h2>
                        <p>{{ $profile['leadDescription'] }}</p>
                    </div>
                    @if ($links['leads'])
                        <a href="{{ $links['leads'] }}">Xem tất cả</a>
                    @endif
                </header>

                <div class="performance-record-list">
                    @forelse ($recentLeads as $lead)
                        <a class="performance-record" href="{{ $lead['url'] }}">
                            <div class="performance-record-icon perf-record-lead">
                                <x-filament::icon icon="heroicon-o-user-plus" />
                            </div>
                            <div class="performance-record-main">
                                <strong>{{ $lead['code'] }}</strong>
                                <span>{{ $lead['name'] }} · {{ $lead['project'] }}</span>
                            </div>
                            <div class="performance-record-meta">
                                <span>{{ $lead['status'] }}</span>
                                <small>{{ $lead['owner'] }} · {{ $lead['time'] }}</small>
                            </div>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="performance-record-arrow" />
                        </a>
                    @empty
                        <div class="performance-empty">Chưa có Lead trong phạm vi được xem.</div>
                    @endforelse
                </div>
            </article>

            <article class="performance-panel">
                <header class="performance-panel-header">
                    <div>
                        <h2>{{ $profile['applicationTitle'] }}</h2>
                        <p>{{ $profile['applicationDescription'] }}</p>
                    </div>
                    @if ($links['applications'])
                        <a href="{{ $links['applications'] }}">Xem tất cả</a>
                    @endif
                </header>

                <div class="performance-record-list">
                    @forelse ($recentApplications as $application)
                        <a class="performance-record" href="{{ $application['url'] }}">
                            <div class="performance-record-icon perf-record-application">
                                <x-filament::icon icon="heroicon-o-briefcase" />
                            </div>
                            <div class="performance-record-main">
                                <strong>{{ $application['code'] }}</strong>
                                <span>{{ $application['name'] }} · {{ $application['project'] }}</span>
                            </div>
                            <div class="performance-record-meta">
                                <span>{{ $application['status'] }}</span>
                                <small>{{ $application['owner'] }} · {{ $application['time'] }}</small>
                            </div>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="performance-record-arrow" />
                        </a>
                    @empty
                        <div class="performance-empty">Chưa có Application trong phạm vi được xem.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="performance-panel performance-project-panel">
            <header class="performance-panel-header">
                <div>
                    <h2>{{ $profile['projectTitle'] }}</h2>
                    <p>{{ $profile['projectDescription'] }}</p>
                </div>
            </header>

            <div class="performance-table-wrap">
                <table class="performance-table">
                    <thead>
                        <tr>
                            <th>Dự án</th>
                            <th>Lead</th>
                            <th>Đạt sơ bộ</th>
                            <th>Application</th>
                            <th>Tỷ lệ đạt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td>
                                    <strong>{{ $project['name'] }}</strong>
                                    <span>{{ $project['slug'] }}</span>
                                </td>
                                <td>{{ number_format($project['leads']) }}</td>
                                <td>{{ number_format($project['qualified']) }}</td>
                                <td>{{ number_format($project['applications']) }}</td>
                                <td>
                                    @php($rateValue = min(100, max(0, (float) rtrim($project['rate'], '%'))))
                                    <div class="performance-rate">
                                        <span>{{ $project['rate'] }}</span>
                                        <i aria-hidden="true"><b style="width: {{ $rateValue }}%"></b></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="performance-empty">Chưa có dự án khả dụng.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <style>
        .performance-dashboard {
            --perf-bg: #f5f7fa;
            --perf-surface: #ffffff;
            --perf-soft: #f8fafc;
            --perf-text: #101828;
            --perf-muted: #667085;
            --perf-border: #e4e7ec;
            --perf-blue: #2563eb;
            --perf-green: #059669;
            --perf-amber: #d97706;
            --perf-rose: #db2777;
            --perf-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 8px 24px rgba(16, 24, 40, .05);
            display: grid;
            gap: 16px;
            width: 100%;
            max-width: 1680px;
            margin: 0 auto;
            padding-bottom: 24px;
            color: var(--perf-text);
        }

        .performance-dashboard *,
        .performance-dashboard *::before,
        .performance-dashboard *::after {
            box-sizing: border-box;
            letter-spacing: 0;
        }

        .performance-dashboard svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
        }

        .performance-header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            min-height: 132px;
            padding: 22px 24px 22px 28px;
            overflow: hidden;
            border: 1px solid #1f2b3d;
            border-radius: 8px;
            background-color: #0b1220;
            background-image:
                linear-gradient(rgba(148, 163, 184, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, .07) 1px, transparent 1px);
            background-size: 28px 28px;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .16);
        }

        .performance-header::before {
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: #22d3ee;
            content: '';
            box-shadow: 0 0 18px rgba(34, 211, 238, .75);
        }

        .performance-header::after {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 240px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #22d3ee 35%, #34d399 70%, transparent);
            content: '';
        }
        .performance-heading {
            display: flex;
            align-items: center;
            min-width: 0;
            gap: 16px;
        }

        .performance-heading-icon {
            display: grid;
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            place-items: center;
            border-radius: 8px;
            border: 1px solid rgba(34, 211, 238, .35);
            background: rgba(34, 211, 238, .1);
            color: #67e8f9;
            box-shadow: inset 0 0 18px rgba(34, 211, 238, .08), 0 0 22px rgba(34, 211, 238, .08);
        }

        .performance-heading-icon svg {
            width: 24px;
            height: 24px;
        }

        .performance-heading-copy {
            min-width: 0;
        }

        .performance-heading-copy > p {
            margin: 0 0 4px;
            color: #67e8f9;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .performance-heading-copy h1 {
            margin: 0;
            color: #f8fafc;
            font-size: 25px;
            font-weight: 800;
            line-height: 1.18;
        }

        .performance-heading-copy > small {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.4;
        }

        .performance-scope {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 12px;
        }

        .performance-scope span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: 4px 8px;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 6px;
            background: rgba(15, 23, 42, .55);
            color: #cbd5e1;
            font-size: 11px;
            font-weight: 650;
            white-space: nowrap;
            backdrop-filter: blur(8px);
        }

        .performance-scope svg {
            width: 14px;
            height: 14px;
            color: #67e8f9;
        }

        .performance-header-controls {
            display: grid;
            justify-items: end;
            gap: 10px;
            flex: 0 0 auto;
        }

        .performance-live {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #a7f3d0;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .performance-live i {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #34d399;
            box-shadow: 0 0 0 4px rgba(52, 211, 153, .12), 0 0 14px rgba(52, 211, 153, .7);
            animation: performance-live-pulse 1.8s ease-in-out infinite;
        }

        @keyframes performance-live-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .45; }
        }
        .performance-period {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 3px;
            padding: 4px;
            border: 1px solid rgba(148, 163, 184, .2);
            border-radius: 8px;
            background: rgba(15, 23, 42, .62);
            backdrop-filter: blur(10px);
        }

        .performance-period button {
            min-width: 68px;
            min-height: 36px;
            padding: 0 12px;
            border: 0;
            border-radius: 6px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: color .16s ease, background-color .16s ease, box-shadow .16s ease;
        }

        .performance-period button:hover {
            color: #f8fafc;
            background: rgba(148, 163, 184, .1);
        }

        .performance-period button.is-active {
            color: #062a30;
            background: #67e8f9;
            box-shadow: 0 0 18px rgba(34, 211, 238, .22);
        }
        .performance-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            min-height: 52px;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px solid var(--perf-border);
        }

        .performance-actions-label {
            margin-right: auto;
            color: #98a2b3;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .performance-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 0 13px;
            border: 1px solid var(--perf-border);
            border-radius: 7px;
            background: #ffffff;
            color: #344054;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
            transition: transform .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease;
        }

        .performance-actions a:hover {
            transform: translateY(-1px);
            border-color: #b8c4d6;
            color: var(--perf-blue);
            box-shadow: 0 5px 14px rgba(16, 24, 40, .08);
        }

        .performance-actions a:first-of-type {
            border-color: #101828;
            background: #101828;
            color: #ffffff;
        }

        .performance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
        }

        .performance-metric {
            --metric-color: var(--perf-blue);
            --metric-soft: #eff6ff;
            position: relative;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 13px;
            min-height: 138px;
            padding: 18px;
            overflow: hidden;
            border: 1px solid var(--perf-border);
            border-radius: 8px;
            background: var(--perf-surface);
            box-shadow: var(--perf-shadow);
        }

        .performance-metric::before {
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: var(--metric-color);
            content: '';
        }

        .performance-metric::after {
            position: absolute;
            right: -32px;
            bottom: -42px;
            width: 104px;
            height: 104px;
            border: 18px solid var(--metric-soft);
            border-radius: 50%;
            content: '';
            pointer-events: none;
        }

        .performance-metric.perf-tone-green {
            --metric-color: var(--perf-green);
            --metric-soft: #ecfdf3;
        }

        .performance-metric.perf-tone-amber {
            --metric-color: var(--perf-amber);
            --metric-soft: #fffaeb;
        }

        .performance-metric.perf-tone-rose {
            --metric-color: var(--perf-rose);
            --metric-soft: #fdf2f8;
        }

        .performance-metric.perf-tone-violet {
            --metric-color: #7c3aed;
            --metric-soft: #f5f3ff;
        }

        .performance-metric.perf-tone-red {
            --metric-color: #dc2626;
            --metric-soft: #fef2f2;
        }

        .performance-metric-icon {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 8px;
            background: var(--metric-soft);
            color: var(--metric-color);
        }

        .performance-metric-icon svg {
            width: 21px;
            height: 21px;
        }

        .performance-metric-copy {
            position: relative;
            z-index: 1;
            min-width: 0;
        }

        .performance-metric-copy > span {
            display: block;
            min-height: 34px;
            color: #667085;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .performance-metric-copy > strong {
            display: block;
            margin-top: 4px;
            color: #101828;
            font-size: 29px;
            font-weight: 800;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .performance-metric-copy > small {
            display: flex;
            align-items: flex-start;
            gap: 5px;
            margin-top: 11px;
            color: #667085;
            font-size: 10.5px;
            font-weight: 600;
            line-height: 1.35;
        }

        .performance-metric-copy > small svg {
            width: 13px;
            height: 13px;
            margin-top: 1px;
        }

        .perf-direction-up { color: var(--perf-green) !important; }
        .perf-direction-down { color: #dc2626 !important; }
        .perf-direction-flat { color: #667085 !important; }

        .performance-primary-grid,
        .performance-secondary-grid {
            display: grid;
            gap: 12px;
        }

        .performance-primary-grid {
            grid-template-columns: minmax(0, 1.8fr) minmax(300px, .72fr);
        }

        .performance-secondary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .performance-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--perf-border);
            border-radius: 8px;
            background: var(--perf-surface);
            box-shadow: var(--perf-shadow);
        }

        .performance-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 70px;
            padding: 15px 18px;
            border-bottom: 1px solid var(--perf-border);
        }

        .performance-panel-header h2 {
            margin: 0;
            color: #101828;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.3;
        }

        .performance-panel-header p {
            margin: 4px 0 0;
            color: #98a2b3;
            font-size: 11px;
            line-height: 1.35;
        }

        .performance-panel-header > a {
            flex: 0 0 auto;
            color: #67e8f9;
            font-size: 11px;
            font-weight: 750;
            text-decoration: none;
        }

        .performance-legend {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #667085;
            font-size: 10px;
            font-weight: 700;
        }

        .performance-legend span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .performance-legend i {
            width: 8px;
            height: 8px;
            border-radius: 2px;
        }

        .legend-lead { background: var(--perf-blue); }
        .legend-application { background: var(--perf-green); }

        .performance-chart-scroll {
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            scrollbar-width: thin;
        }

        .performance-chart {
            min-width: 650px;
            padding: 16px 18px 12px;
            background: #0b1220;
        }

        .performance-chart svg {
            display: block;
            width: 100%;
            height: 250px;
            overflow: visible;
        }

        .performance-chart-grid line {
            stroke: rgba(148, 163, 184, .15);
            stroke-width: 1;
            stroke-dasharray: 4 7;
        }

        .chart-area-lead { fill: url(#lead-area); }
        .chart-area-application { fill: url(#application-area); }

        .chart-line {
            fill: none;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            vector-effect: non-scaling-stroke;
        }

        .chart-line-lead {
            stroke: #22d3ee;
            filter: drop-shadow(0 0 5px rgba(34, 211, 238, .35));
        }

        .chart-line-application {
            stroke: #34d399;
            filter: drop-shadow(0 0 5px rgba(52, 211, 153, .3));
        }

        .chart-point {
            stroke: #0b1220;
            stroke-width: 2;
            vector-effect: non-scaling-stroke;
        }

        .chart-point-lead { fill: #67e8f9; }
        .chart-point-application { fill: #6ee7b7; }

        .performance-chart-labels {
            display: grid;
            grid-template-columns: repeat(var(--chart-columns), minmax(0, 1fr));
            gap: 0;
            padding: 0 3.1%;
        }

        .performance-chart-labels span {
            overflow: hidden;
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .performance-overview-list {
            display: grid;
            gap: 0;
            padding: 7px 18px 12px;
        }

        .performance-overview-item {
            --status-color: var(--perf-blue);
            display: grid;
            grid-template-columns: 9px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            min-height: 50px;
            border-bottom: 1px solid #eef1f5;
        }

        .performance-overview-item:last-child { border-bottom: 0; }
        .performance-overview-item.perf-status-green { --status-color: var(--perf-green); }
        .performance-overview-item.perf-status-amber { --status-color: var(--perf-amber); }
        .performance-overview-item.perf-status-red { --status-color: #dc2626; }
        .performance-overview-item.perf-status-violet { --status-color: #7c3aed; }
        .performance-overview-item.perf-status-rose { --status-color: var(--perf-rose); }

        .performance-overview-item > i {
            width: 8px;
            height: 8px;
            border: 2px solid var(--status-color);
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--status-color) 12%, transparent);
        }

        .performance-overview-item > span {
            color: #475467;
            font-size: 11.5px;
            font-weight: 650;
            line-height: 1.35;
        }

        .performance-overview-item > strong {
            color: #101828;
            font-size: 15px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .performance-record-list {
            display: grid;
        }

        .performance-record {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) minmax(130px, auto) 16px;
            align-items: center;
            gap: 11px;
            min-height: 68px;
            padding: 10px 16px;
            border-bottom: 1px solid #eef1f5;
            color: inherit;
            text-decoration: none;
            transition: background-color .15s ease;
        }

        .performance-record:last-child { border-bottom: 0; }
        .performance-record:hover { background: #f8fafc; }

        .performance-record-icon {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: 7px;
        }

        .perf-record-lead {
            background: #eff6ff;
            color: var(--perf-blue);
        }

        .perf-record-application {
            background: #ecfdf3;
            color: var(--perf-green);
        }

        .performance-record-main,
        .performance-record-meta {
            min-width: 0;
        }

        .performance-record-main strong {
            display: block;
            overflow: hidden;
            color: #101828;
            font-size: 11.5px;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .performance-record-main span,
        .performance-record-meta small {
            display: block;
            overflow: hidden;
            margin-top: 3px;
            color: #98a2b3;
            font-size: 10px;
            line-height: 1.35;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .performance-record-meta {
            text-align: right;
        }

        .performance-record-meta > span {
            display: inline-block;
            max-width: 170px;
            overflow: hidden;
            padding: 3px 7px;
            border: 1px solid #e4e7ec;
            border-radius: 5px;
            background: #f9fafb;
            color: #475467;
            font-size: 9.5px;
            font-weight: 750;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .performance-record-arrow {
            width: 15px !important;
            height: 15px !important;
            color: #c0c6d0;
        }

        .performance-project-panel {
            overflow: hidden;
        }

        .performance-table-wrap {
            max-width: 100%;
            overflow-x: auto;
        }

        .performance-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            font-size: 11px;
        }

        .performance-table th {
            height: 42px;
            padding: 0 18px;
            border-bottom: 1px solid var(--perf-border);
            background: #f8fafc;
            color: #667085;
            font-size: 9.5px;
            font-weight: 800;
            text-align: left;
            text-transform: uppercase;
        }

        .performance-table td {
            height: 58px;
            padding: 8px 18px;
            border-bottom: 1px solid #eef1f5;
            color: #344054;
            font-variant-numeric: tabular-nums;
        }

        .performance-table tr:last-child td { border-bottom: 0; }
        .performance-table tbody tr:hover { background: #fafbfc; }

        .performance-table td:first-child strong {
            display: block;
            color: #101828;
            font-size: 11.5px;
            font-weight: 800;
        }

        .performance-table td:first-child span {
            display: block;
            margin-top: 3px;
            color: #98a2b3;
            font-size: 9.5px;
        }

        .performance-rate {
            display: grid;
            grid-template-columns: 40px minmax(72px, 1fr);
            align-items: center;
            gap: 9px;
            min-width: 130px;
        }

        .performance-rate > span {
            color: #101828;
            font-weight: 800;
        }

        .performance-rate > i {
            display: block;
            height: 5px;
            overflow: hidden;
            border-radius: 5px;
            background: #e9edf3;
        }

        .performance-rate > i > b {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--perf-green);
        }

        .performance-empty {
            padding: 28px 18px;
            color: #98a2b3;
            font-size: 11px;
            text-align: center;
        }

        .dark .performance-dashboard {
            --perf-surface: #121826;
            --perf-soft: #18202f;
            --perf-text: #f2f4f7;
            --perf-muted: #98a2b3;
            --perf-border: #2a3445;
            --perf-shadow: none;
        }

        .dark .performance-panel,
        .dark .performance-metric,
        .dark .performance-actions a {
            background: var(--perf-surface);
        }

        .dark .performance-heading-copy h1,
        .dark .performance-panel-header h2,
        .dark .performance-metric-copy > strong,
        .dark .performance-overview-item > strong,
        .dark .performance-record-main strong,
        .dark .performance-table td:first-child strong,
        .dark .performance-rate > span {
            color: #f2f4f7;
        }

        .dark .performance-scope span,
        .dark .performance-period,
        .dark .performance-record-meta > span,
        .dark .performance-table th {
            border-color: #303b4d;
            background: #18202f;
            color: #c7ced8;
        }

        .dark .performance-period button.is-active {
            background: #2a3445;
            color: #ffffff;
        }


        .dark .performance-record:hover,
        .dark .performance-table tbody tr:hover {
            background: #18202f;
        }

        .dark .performance-record,
        .dark .performance-overview-item,
        .dark .performance-table td {
            border-color: #253044;
        }

        @media (max-width: 1180px) {
            .performance-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .performance-primary-grid {
                grid-template-columns: minmax(0, 1fr) 300px;
            }
        }

        @media (max-width: 920px) {
            .performance-primary-grid,
            .performance-secondary-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 760px) {
            .performance-dashboard {
                gap: 12px;
            }

            .performance-header {
                align-items: stretch;
                flex-direction: column;
                min-height: 0;
                padding: 18px 16px 18px 20px;
            }

            .performance-heading {
                align-items: flex-start;
            }

            .performance-heading-icon {
                width: 42px;
                height: 42px;
                flex-basis: 42px;
            }

            .performance-heading-copy h1 {
                font-size: 21px;
            }

            .performance-header-controls {
                width: 100%;
                justify-items: stretch;
            }

            .performance-live {
                justify-content: flex-end;
            }

            .performance-period {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                width: 100%;
            }

            .performance-period button {
                min-width: 0;
                min-height: 40px;
            }

            .performance-actions {
                justify-content: flex-start;
                overflow-x: auto;
                scrollbar-width: none;
            }

            .performance-actions::-webkit-scrollbar { display: none; }
            .performance-actions-label { display: none; }

            .performance-actions a {
                min-height: 42px;
                flex: 0 0 auto;
            }

            .performance-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .performance-metric {
                grid-template-columns: 38px minmax(0, 1fr);
                min-height: 126px;
                padding: 15px;
            }

            .performance-metric-icon {
                width: 38px;
                height: 38px;
            }

            .performance-metric-copy > strong {
                font-size: 25px;
            }

            .performance-panel-header {
                align-items: flex-start;
            }

            .performance-record {
                grid-template-columns: 38px minmax(0, 1fr) 14px;
            }

            .performance-record-meta {
                grid-column: 2;
                text-align: left;
            }

            .performance-record-arrow {
                grid-column: 3;
                grid-row: 1 / span 2;
            }
        }

        @media (max-width: 520px) {
            .performance-metrics {
                grid-template-columns: minmax(0, 1fr);
            }

            .performance-metric {
                min-height: 112px;
            }

            .performance-scope {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            }

            .performance-scope span {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .performance-legend {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
</x-filament-panels::page>
