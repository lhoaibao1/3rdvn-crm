<?php

namespace App\Support\Filament;

use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ProcessTimeline
{
    public static function render(iterable $logs, callable $titleResolver, callable $bodyResolver, callable $toneResolver, string $emptyText = 'Chưa có lịch sử thao tác.'): HtmlString
    {
        $collection = $logs instanceof Collection ? $logs->values() : collect($logs)->values();

        if ($collection->isEmpty()) {
            return new HtmlString('<div style="border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;padding:18px;color:#64748b;font-size:14px">'.e($emptyText).'</div>');
        }

        $items = $collection->map(function ($log, int $index) use ($collection, $titleResolver, $bodyResolver, $toneResolver): string {
            $actor = $log->actor;
            $actorName = e($actor?->name ?: 'Hệ thống');
            $actorCode = e($actor?->uid ?: ($actor?->employee_code ?: $actor?->email ?: ''));
            $actorLabel = $actorCode !== '' ? $actorCode : $actorName;
            $title = e((string) $titleResolver($log));
            $body = self::bodyHtml((string) $bodyResolver($log));
            $tone = array_replace([
                'label' => 'Xử lý',
                'color' => '#be185d',
                'soft' => '#fce7f3',
                'border' => '#f9a8d4',
            ], (array) $toneResolver($log));
            $nextLog = $collection->get($index + 1);
            $elapsed = is_object($nextLog) ? self::elapsed($log, $nextLog) : '';

            return '<div class="crm-process-row">'
                .'<div class="crm-process-time"><time>'.e($log->created_at?->format('d/m/Y H:i:s') ?: '-').'</time>'.($elapsed !== '' ? '<span>'.$elapsed.'</span>' : '').'</div>'
                .'<div class="crm-process-rail"><i style="border-color:'.e($tone['color']).';background:'.e($tone['soft']).'"></i></div>'
                .'<div class="crm-process-content">'
                .'<div class="crm-process-actor"><span style="border-color:'.e($tone['border']).';background:'.e($tone['soft']).';color:'.e($tone['color']).'">'.$actorLabel.'</span></div>'
                .'<div class="crm-process-title"><b>'.$title.'</b><em style="color:'.e($tone['color']).'">'.e($tone['label']).'</em></div>'
                .($actorCode !== '' ? '<div class="crm-process-by">'.$actorName.'</div>' : '')
                .$body
                .'</div>'
                .'</div>';
        })->join('');

        return new HtmlString(self::style().'<div class="crm-process-timeline">'.$items.'</div>');
    }

    private static function bodyHtml(string $body): string
    {
        $lines = collect(preg_split('/\R+/', trim(strip_tags($body))) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line): string {
                $class = str_contains(mb_strtolower($line), 'ghi chú') ? 'comment' : 'step';

                if (str_contains(mb_strtolower($line), 'trạng thái') || str_contains(mb_strtolower($line), 'từ chối') || str_contains(mb_strtolower($line), 'pass')) {
                    $class = 'status';
                }

                return '<div class="crm-process-line crm-process-line-'.$class.'"><i></i><span>'.e($line).'</span></div>';
            })
            ->join('');

        return $lines !== '' ? '<div class="crm-process-lines">'.$lines.'</div>' : '';
    }

    private static function elapsed(object $current, object $next): string
    {
        if (! $current->created_at || ! $next->created_at) {
            return '';
        }

        $seconds = (int) abs($current->created_at->diffInSeconds($next->created_at));

        if ($seconds < 60) {
            return $seconds.' giây';
        }

        if ($seconds < 3600) {
            return floor($seconds / 60).' phút';
        }

        if ($seconds < 86400) {
            return floor($seconds / 3600).' giờ';
        }

        return floor($seconds / 86400).' ngày';
    }

    private static function style(): string
    {
        return <<<'HTML'
<style>
.crm-process-timeline{max-width:1040px;display:grid;gap:0;padding:8px 0 4px;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.crm-process-row{position:relative;display:grid;grid-template-columns:178px 34px minmax(0,1fr);gap:10px;min-height:86px}.crm-process-row:not(:last-child) .crm-process-rail:after{content:"";position:absolute;top:19px;bottom:-14px;left:50%;width:2px;transform:translateX(-50%);background:#e5e7eb}.crm-process-time{padding-top:1px;text-align:right;color:#111827;font-size:14px;font-weight:650;line-height:1.25}.crm-process-time time{display:block;white-space:nowrap}.crm-process-time span{display:inline-flex;align-items:center;gap:5px;margin-top:19px;color:#b45309;font-size:13px;font-weight:720}.crm-process-time span:before{content:"";width:13px;height:13px;border:2px solid #d97706;border-radius:999px;box-shadow:inset 4px 0 0 transparent}.crm-process-rail{position:relative;display:flex;justify-content:center}.crm-process-rail i{position:relative;z-index:1;width:13px;height:13px;margin-top:3px;border:3px solid #be185d;border-radius:999px;background:#fff}.crm-process-content{min-width:0;padding:0 0 20px}.crm-process-actor{height:25px;display:flex;align-items:flex-start}.crm-process-actor span{display:inline-flex;align-items:center;max-width:100%;min-height:24px;padding:2px 9px;border:1px solid #bae6fd;border-radius:8px;background:#ecfeff;color:#0369a1;font-size:13px;font-weight:760;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.crm-process-actor span:before{content:"";width:12px;height:12px;margin-right:6px;border:1.8px solid currentColor;border-radius:999px;box-shadow:0 7px 0 -4px currentColor}.crm-process-title{display:flex;align-items:center;gap:8px;min-width:0;margin-top:3px;color:#1f2937}.crm-process-title b{font-size:16px;line-height:1.32;font-weight:760}.crm-process-title em{font-style:normal;font-size:12px;font-weight:800}.crm-process-by{margin-top:2px;color:#64748b;font-size:12px;font-weight:620}.crm-process-lines{display:grid;gap:5px;margin-top:5px}.crm-process-line{display:flex;align-items:flex-start;gap:8px;color:#374151;font-size:15px;font-weight:620;line-height:1.35}.crm-process-line i{width:14px;height:14px;flex:0 0 auto;margin-top:3px;border-radius:999px;border:2px solid #a3e635;background:#f7fee7}.crm-process-line-step i{border-color:#a855f7;background:#faf5ff;border-radius:3px;transform:rotate(45deg)}.crm-process-line-status i{border-color:#84cc16;background:#ecfccb}.crm-process-line-comment i{border-color:#cbd5e1;background:#fff;border-radius:4px}@media(max-width:760px){.crm-process-timeline{gap:8px}.crm-process-row{grid-template-columns:24px minmax(0,1fr);gap:8px;min-height:auto}.crm-process-time{grid-column:2;text-align:left;display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding-top:0;font-size:13px}.crm-process-time span{margin-top:0}.crm-process-rail{grid-column:1;grid-row:1 / span 2}.crm-process-rail i{margin-top:2px}.crm-process-row:not(:last-child) .crm-process-rail:after{bottom:-18px}.crm-process-content{grid-column:2;padding-bottom:10px}.crm-process-title{flex-wrap:wrap}.crm-process-title b{font-size:15px}.crm-process-line{font-size:14px}}
</style>
HTML;
    }
}
