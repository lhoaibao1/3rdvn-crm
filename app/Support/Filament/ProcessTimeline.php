<?php

namespace App\Support\Filament;

use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ProcessTimeline
{
    public static function render(iterable $logs, callable $titleResolver, callable $bodyResolver, callable $toneResolver, string $emptyText = 'Chưa có lịch sử thao tác.'): HtmlString
    {
        $collection = $logs instanceof Collection ? $logs->values() : collect($logs)->values();
        $collection = $collection
            ->sortBy(fn (object $log): int => self::timestamp($log))
            ->values();

        if ($collection->isEmpty()) {
            return new HtmlString(self::style().self::emptyState($emptyText));
        }

        $rows = $collection->map(function (object $log, int $index) use ($collection, $titleResolver, $bodyResolver, $toneResolver): string {
            $actor = $log->actor ?? null;
            $isApiActivity = str_starts_with((string) ($log->action ?? ''), 'feol_')
                || str_contains(mb_strtolower((string) ($log->user_agent ?? '')), 'feol bridge');
            $actorName = (string) ($actor?->name ?: ($isApiActivity ? 'API Đồng bộ FEOL' : 'Hệ thống'));
            $actorCode = (string) ($actor?->uid ?: ($actor?->employee_code ?: $actor?->email ?: ''));
            $title = trim((string) $titleResolver($log)) ?: '-';
            $body = trim(strip_tags((string) $bodyResolver($log)));
            $tone = array_replace([
                'label' => 'Xử lý',
                'color' => '#475569',
                'soft' => '#f1f5f9',
                'border' => '#cbd5e1',
            ], (array) $toneResolver($log));
            $nextLog = $collection->get($index + 1);
            $elapsed = is_object($nextLog) ? self::elapsed($log, $nextLog) : '-';
            $createdAt = $log->created_at ?? null;
            $createdDate = is_object($createdAt) && method_exists($createdAt, 'format') ? $createdAt->format('d/m/Y') : '-';
            $createdTime = is_object($createdAt) && method_exists($createdAt, 'format') ? $createdAt->format('H:i:s') : '';
            $search = mb_strtolower(implode(' ', [
                $index + 1,
                $createdDate,
                $createdTime,
                $title,
                $body,
                $tone['label'],
                $actorName,
                $actorCode,
                $elapsed,
            ]));

            return '<tr data-history-row data-search="'.e($search).'" x-show="isVisible($el)">'
                .'<td data-label="STT" class="crm-history-index"><span>'.($index + 1).'</span></td>'
                .'<td data-label="Thời gian"><time class="crm-history-time"><strong>'.e($createdDate).'</strong>'.($createdTime !== '' ? '<span>'.e($createdTime).'</span>' : '').'</time></td>'
                .'<td data-label="Tác vụ"><strong class="crm-history-action">'.e($title).'</strong></td>'
                .'<td data-label="Nội dung xử lý">'.self::bodyHtml($body).'</td>'
                .'<td data-label="Trạng thái"><span class="crm-history-status" style="--history-color:'.e(self::color($tone['color'], '#475569')).';--history-soft:'.e(self::color($tone['soft'] ?? $tone['bg'] ?? null, '#f1f5f9')).';--history-border:'.e(self::color($tone['border'], '#cbd5e1')).'">'.e((string) $tone['label']).'</span></td>'
                .'<td data-label="Người xử lý"><div class="crm-history-actor"><span class="crm-history-avatar">'.e(self::initials($actorName)).'</span><span><strong>'.e($actorName).'</strong>'.($actorCode !== '' ? '<small>'.e($actorCode).'</small>' : '').'</span></div></td>'
                .'<td data-label="Thời lượng"><span class="crm-history-duration">'.e($elapsed).'</span></td>'
                .'</tr>';
        })->join('');

        return new HtmlString(self::style().self::table($rows, $collection->count()));
    }

    private static function table(string $rows, int $total): string
    {
        $alpine = e(<<<'JS'
{
    query: '',
    page: 1,
    perPage: 10,
    get matchedRows() {
        const query = this.query.trim().toLocaleLowerCase('vi');
        return Array.from(this.$refs.body.querySelectorAll('[data-history-row]'))
            .filter((row) => ! query || row.dataset.search.includes(query));
    },
    get total() { return this.matchedRows.length; },
    get pages() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
    get first() { return this.total === 0 ? 0 : ((this.page - 1) * this.perPage) + 1; },
    get last() { return Math.min(this.page * this.perPage, this.total); },
    isVisible(row) {
        const index = this.matchedRows.indexOf(row);
        return index >= (this.page - 1) * this.perPage && index < this.page * this.perPage;
    },
    goTo(value) { this.page = Math.min(Math.max(1, value), this.pages); },
}
JS);

        return '<div class="crm-history" x-data="'.$alpine.'">'
            .'<div class="crm-history-toolbar">'
            .'<div class="crm-history-count"><span></span><strong x-text="total">'.$total.'</strong><small>hoạt động</small></div>'
            .'<div class="crm-history-controls">'
            .'<label class="crm-history-search"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg><input type="search" x-model.debounce.200ms="query" @input="page = 1" placeholder="Tìm trong lịch sử..." aria-label="Tìm trong lịch sử xử lý"></label>'
            .'<label class="crm-history-limit"><span>Hiển thị</span><select x-model.number="perPage" @change="page = 1" aria-label="Số dòng trên một trang"><option value="10">10 dòng</option><option value="20">20 dòng</option><option value="50">50 dòng</option></select></label>'
            .'</div>'
            .'</div>'
            .'<div class="crm-history-scroll">'
            .'<table class="crm-history-table">'
            .'<thead><tr><th class="crm-history-index">STT</th><th>Thời gian</th><th>Tác vụ</th><th>Nội dung xử lý</th><th>Trạng thái</th><th>Người xử lý</th><th>Thời lượng</th></tr></thead>'
            .'<tbody x-ref="body">'.$rows.'<tr class="crm-history-no-result" x-show="total === 0" style="display:none"><td colspan="7"><strong>Không tìm thấy kết quả</strong><span>Hãy thử từ khóa khác.</span></td></tr></tbody>'
            .'</table>'
            .'</div>'
            .'<div class="crm-history-pagination">'
            .'<span>Hiển thị <b x-text="first">1</b>–<b x-text="last">'.min(10, $total).'</b> / <b x-text="total">'.$total.'</b></span>'
            .'<div><button type="button" @click="goTo(1)" :disabled="page <= 1" aria-label="Trang đầu">«</button><button type="button" @click="goTo(page - 1)" :disabled="page <= 1" aria-label="Trang trước">‹</button><strong><span x-text="page">1</span> / <span x-text="pages">'.max(1, (int) ceil($total / 10)).'</span></strong><button type="button" @click="goTo(page + 1)" :disabled="page >= pages" aria-label="Trang sau">›</button><button type="button" @click="goTo(pages)" :disabled="page >= pages" aria-label="Trang cuối">»</button></div>'
            .'</div>'
            .'</div>';
    }

    private static function emptyState(string $emptyText): string
    {
        return '<div class="crm-history-empty"><span>⌁</span><strong>Chưa có hoạt động</strong><p>'.e($emptyText).'</p></div>';
    }

    private static function bodyHtml(string $body): string
    {
        $lines = collect(preg_split('/\R+/', $body) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line): string {
                [$label, $value] = array_pad(explode(':', $line, 2), 2, null);

                if ($value !== null && trim($label) !== '') {
                    return '<span class="crm-history-detail"><b>'.e(trim($label)).'</b><span>'.e(trim($value)).'</span></span>';
                }

                return '<span class="crm-history-detail"><span>'.e($line).'</span></span>';
            })
            ->join('');

        return $lines !== '' ? '<div class="crm-history-details">'.$lines.'</div>' : '<span class="crm-history-muted">-</span>';
    }

    private static function timestamp(object $log): int
    {
        $createdAt = $log->created_at ?? null;

        if (is_object($createdAt) && method_exists($createdAt, 'getTimestamp')) {
            return $createdAt->getTimestamp();
        }

        return strtotime((string) $createdAt) ?: PHP_INT_MAX;
    }

    private static function elapsed(object $current, object $next): string
    {
        if (! ($current->created_at ?? null) || ! ($next->created_at ?? null)) {
            return '-';
        }

        $seconds = (int) abs($current->created_at->diffInSeconds($next->created_at));

        if ($seconds < 60) {
            return $seconds.' giây';
        }

        if ($seconds < 3600) {
            return floor($seconds / 60).' phút';
        }

        if ($seconds < 86400) {
            $hours = (int) floor($seconds / 3600);
            $minutes = (int) floor(($seconds % 3600) / 60);

            return $hours.' giờ'.($minutes > 0 ? ' '.$minutes.' phút' : '');
        }

        $days = (int) floor($seconds / 86400);
        $hours = (int) floor(($seconds % 86400) / 3600);

        return $days.' ngày'.($hours > 0 ? ' '.$hours.' giờ' : '');
    }

    private static function initials(string $name): string
    {
        if ($name === 'Hệ thống') {
            return 'HT';
        }

        $parts = array_values(array_filter(preg_split('/\s+/u', trim($name)) ?: []));

        if ($parts === []) {
            return '--';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[array_key_last($parts)], 0, 1));
    }

    private static function color(mixed $value, string $fallback): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^#[0-9a-f]{3,8}$/i', $value) === 1 ? $value : $fallback;
    }

    private static function style(): string
    {
        return <<<'HTML'
<style>
.crm-history{overflow:hidden;border:1px solid #dbe4ef;border-radius:16px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.06);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.crm-history-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;border-bottom:1px solid #dbe4ef;background:linear-gradient(180deg,#fff,#f8fbff)}.crm-history-count{display:flex;align-items:center;gap:7px;color:#475569;font-size:13px}.crm-history-count>span{width:9px;height:9px;border-radius:999px;background:#1689e8;box-shadow:0 0 0 4px #e0f2fe}.crm-history-count strong{color:#0f172a;font-size:15px}.crm-history-count small{font-size:13px}.crm-history-controls{display:flex;align-items:center;gap:10px}.crm-history-search{display:flex;align-items:center;gap:8px;width:min(310px,42vw);height:38px;padding:0 11px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;transition:.18s ease}.crm-history-search:focus-within{border-color:#1689e8;box-shadow:0 0 0 3px rgba(22,137,232,.12)}.crm-history-search svg{width:17px;height:17px;fill:none;stroke:#64748b;stroke-width:1.8;stroke-linecap:round}.crm-history-search input{width:100%;border:0!important;outline:0!important;background:transparent!important;padding:0!important;color:#0f172a;font-size:13px;box-shadow:none!important}.crm-history-search input::placeholder{color:#94a3b8}.crm-history-limit{display:flex;align-items:center;gap:7px;color:#64748b;font-size:12px;white-space:nowrap}.crm-history-limit select{height:38px;padding:0 30px 0 10px;border:1px solid #cbd5e1;border-radius:10px;background-color:#fff;color:#334155;font-size:13px;font-weight:650}.crm-history-scroll{width:100%;overflow-x:auto;overscroll-behavior:contain}.crm-history-table{width:100%;min-width:1080px;border-collapse:separate;border-spacing:0;table-layout:fixed}.crm-history-table th{position:sticky;top:0;z-index:1;padding:13px 14px;border-right:1px solid rgba(255,255,255,.2);background:linear-gradient(135deg,#0878d1,#1298ef);color:#fff;text-align:left;font-size:12px;font-weight:800;letter-spacing:.025em;white-space:nowrap}.crm-history-table th:nth-child(1){width:58px;text-align:center}.crm-history-table th:nth-child(2){width:142px}.crm-history-table th:nth-child(3){width:180px}.crm-history-table th:nth-child(4){width:330px}.crm-history-table th:nth-child(5){width:116px}.crm-history-table th:nth-child(6){width:190px}.crm-history-table th:nth-child(7){width:118px}.crm-history-table td{padding:14px;border-right:1px solid #e5edf5;border-bottom:1px solid #e5edf5;color:#334155;vertical-align:top;font-size:13px;line-height:1.42}.crm-history-table tbody tr:nth-child(even){background:#eff8fc}.crm-history-table tbody tr:nth-child(odd){background:#fff}.crm-history-table tbody tr{transition:background-color .15s ease}.crm-history-table tbody tr:hover{background:#e4f4fb}.crm-history-table tbody tr:last-child td{border-bottom:0}.crm-history-table td:last-child,.crm-history-table th:last-child{border-right:0}.crm-history-index{text-align:center!important}.crm-history-index span{display:inline-grid;place-items:center;width:27px;height:27px;border-radius:8px;background:#e0f2fe;color:#0369a1;font-size:12px;font-weight:800}.crm-history-time{display:flex;flex-direction:column;gap:2px;color:#0f172a;font-style:normal;white-space:nowrap}.crm-history-time strong{font-size:13px;font-weight:720}.crm-history-time span{color:#64748b;font-size:12px;font-variant-numeric:tabular-nums}.crm-history-action{display:block;color:#0f172a;font-size:13px;font-weight:750}.crm-history-details{display:grid;gap:5px}.crm-history-detail{display:flex;align-items:flex-start;gap:5px;color:#334155}.crm-history-detail:before{content:"";width:5px;height:5px;flex:0 0 auto;margin-top:7px;border-radius:999px;background:#38bdf8}.crm-history-detail b{color:#475569;font-size:12px;font-weight:750;white-space:nowrap}.crm-history-detail b:after{content:":"}.crm-history-detail span{min-width:0;overflow-wrap:anywhere}.crm-history-muted{color:#94a3b8}.crm-history-status{display:inline-flex;align-items:center;gap:6px;min-height:26px;padding:4px 9px;border:1px solid var(--history-border);border-radius:999px;background:var(--history-soft);color:var(--history-color);font-size:11px;font-weight:800;white-space:nowrap}.crm-history-status:before{content:"";width:6px;height:6px;border-radius:999px;background:currentColor;box-shadow:0 0 0 3px color-mix(in srgb,currentColor 15%,transparent)}.crm-history-actor{display:flex;align-items:center;gap:9px;min-width:0}.crm-history-avatar{display:grid;place-items:center;width:31px;height:31px;flex:0 0 auto;border:1px solid #bae6fd;border-radius:10px;background:linear-gradient(135deg,#e0f2fe,#f0f9ff);color:#0369a1;font-size:10px;font-weight:850}.crm-history-actor>span:last-child{display:flex;min-width:0;flex-direction:column}.crm-history-actor strong{overflow:hidden;color:#0f172a;font-size:12px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}.crm-history-actor small{overflow:hidden;color:#64748b;font-size:11px;text-overflow:ellipsis;white-space:nowrap}.crm-history-duration{display:inline-flex;align-items:center;color:#8a4b08;font-size:12px;font-weight:720;white-space:nowrap}.crm-history-duration:before{content:"";width:13px;height:13px;margin-right:6px;border:1.7px solid #d97706;border-radius:999px;background:radial-gradient(circle at 50% 50%,#d97706 0 1px,transparent 1.5px)}.crm-history-no-result td{padding:40px!important;text-align:center!important}.crm-history-no-result strong,.crm-history-no-result span{display:block}.crm-history-no-result strong{color:#334155;font-size:14px}.crm-history-no-result span{margin-top:4px;color:#94a3b8;font-size:12px}.crm-history-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid #dbe4ef;background:#f8fafc;color:#64748b;font-size:12px}.crm-history-pagination>b,.crm-history-pagination span b{color:#0f172a}.crm-history-pagination>div{display:flex;align-items:center;gap:5px}.crm-history-pagination button{display:grid;place-items:center;width:32px;height:32px;border:1px solid #d4dde8;border-radius:8px;background:#fff;color:#334155;font-size:18px;line-height:1;transition:.15s ease}.crm-history-pagination button:hover:not(:disabled){border-color:#1689e8;background:#eff8ff;color:#0878d1}.crm-history-pagination button:disabled{cursor:not-allowed;opacity:.35}.crm-history-pagination div>strong{min-width:64px;text-align:center;color:#0f172a;font-size:12px}.crm-history-empty{display:grid;place-items:center;min-height:190px;padding:30px;border:1px dashed #cbd5e1;border-radius:16px;background:linear-gradient(180deg,#fff,#f8fafc);text-align:center}.crm-history-empty>span{display:grid;place-items:center;width:46px;height:46px;border-radius:14px;background:#e0f2fe;color:#0284c7;font-size:28px}.crm-history-empty strong{margin-top:12px;color:#0f172a;font-size:15px}.crm-history-empty p{margin:4px 0 0;color:#64748b;font-size:13px}.dark .crm-history{border-color:#334155;background:#0f172a;box-shadow:none}.dark .crm-history-toolbar,.dark .crm-history-pagination{border-color:#334155;background:#111827}.dark .crm-history-search,.dark .crm-history-limit select,.dark .crm-history-pagination button{border-color:#475569;background:#1e293b;color:#e2e8f0}.dark .crm-history-search input{color:#e2e8f0!important}.dark .crm-history-table td{border-color:#334155;color:#cbd5e1}.dark .crm-history-table tbody tr:nth-child(odd){background:#0f172a}.dark .crm-history-table tbody tr:nth-child(even){background:#172033}.dark .crm-history-table tbody tr:hover{background:#1e3a4f}.dark .crm-history-time,.dark .crm-history-action,.dark .crm-history-actor strong,.dark .crm-history-pagination span b{color:#f8fafc}@media(max-width:760px){.crm-history-toolbar{align-items:stretch;flex-direction:column}.crm-history-controls{align-items:stretch;flex-direction:column}.crm-history-search{width:100%}.crm-history-limit{justify-content:space-between}.crm-history-limit select{min-width:118px}.crm-history-scroll{overflow:visible}.crm-history-table{min-width:0;table-layout:auto}.crm-history-table thead{display:none}.crm-history-table tbody{display:grid;gap:10px;padding:10px;background:#f1f5f9}.crm-history-table tbody tr[data-history-row]{display:block!important;overflow:hidden;border:1px solid #dbe4ef;border-radius:12px;background:#fff!important}.crm-history-table tbody tr[data-history-row][style*="display: none"]{display:none!important}.crm-history-table td{display:grid;grid-template-columns:118px minmax(0,1fr);gap:10px;padding:10px 12px;border-right:0;border-bottom:1px solid #e5edf5;font-size:12px}.crm-history-table td:before{content:attr(data-label);color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase}.crm-history-table td:last-child{border-bottom:0}.crm-history-index{text-align:left!important}.crm-history-index span{margin-left:auto}.crm-history-pagination{align-items:flex-start;flex-direction:column}.crm-history-pagination>div{width:100%;justify-content:space-between}.dark .crm-history-table tbody{background:#0b1220}.dark .crm-history-table tbody tr[data-history-row]{border-color:#334155;background:#0f172a!important}}
</style>
HTML;
    }
}
