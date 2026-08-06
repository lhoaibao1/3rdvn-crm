<?php

namespace App\Support\Filament;

use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use Illuminate\Support\HtmlString;

class WorkflowOverview
{
    public static function render(SalesProject $project): HtmlString
    {
        $steps = ProjectWorkflowConfiguration::forProject($project);
        $labels = collect($steps)->mapWithKeys(fn (array $step): array => [$step['status'] => $step['label']]);

        $rows = collect($steps)->values()->map(function (array $step, int $index) use ($labels, $project): string {
            $next = self::nextStepHtml($project, $step, $labels);

            return '<article class="crm-workflow-step">'
                .'<div class="crm-workflow-order">'.($index + 1).'</div>'
                .'<div class="crm-workflow-status"><strong>'.e($step['label']).'</strong><code>'.e($step['status']).'</code></div>'
                .'<div class="crm-workflow-mode"><small>CÁCH XỬ LÝ</small><strong>'.e(ProjectWorkflowConfiguration::modeLabel($step['mode'])).'</strong></div>'
                .'<div class="crm-workflow-next"><small>ĐƯỢC CHUYỂN ĐẾN</small><div>'.$next.'</div></div>'
                .'<div class="crm-workflow-rule"><small>QUY TẮC</small><span>'.e($step['note']).'</span></div>'
                .'</article>';
        })->join('');

        return new HtmlString(
            self::style()
            .'<div class="crm-workflow-overview">'
            .'<header><div><strong>'.e($project->name).'</strong><span>'.count($steps).' trạng thái</span></div><code>'.e($project->slug).'</code></header>'
            .'<div class="crm-workflow-steps">'.$rows.'</div>'
            .'</div>',
        );
    }

    private static function nextStepHtml(SalesProject $project, array $step, $labels): string
    {
        if (self::isReturnToSaleStep($project, (string) ($step['status'] ?? ''))) {
            return '<span class="crm-workflow-dynamic-return">Quay về bước trước khi trả<small>resume_to</small></span>';
        }

        $next = collect($step['next_statuses'])
            ->map(fn (string $status): string => '<span>'.e((string) ($labels[$status] ?? $status)).'<small>'.e($status).'</small></span>')
            ->join('');

        return $next !== '' ? $next : '<span class="crm-workflow-terminal">Kết thúc quy trình</span>';
    }

    private static function isReturnToSaleStep(SalesProject $project, string $status): bool
    {
        return ($project->slug === 'acl-mix' && $status === 'returned_to_sale')
            || ($project->slug === 'lotte-finance' && $status === 'lotte_returned_to_sale');
    }

    private static function style(): string
    {
        return <<<'HTML'
<style>
.crm-workflow-overview{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.crm-workflow-overview>header{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:12px;padding:12px 14px;border:1px solid #cfe5f7;border-radius:12px;background:linear-gradient(110deg,#e8f5ff,#f9fcff)}.crm-workflow-overview>header>div{display:flex;align-items:baseline;gap:9px}.crm-workflow-overview>header strong{color:#075f9f;font-size:15px;font-weight:840}.crm-workflow-overview>header span{color:#64748b;font-size:11px;font-weight:720}.crm-workflow-overview>header code{padding:5px 8px;border-radius:7px;background:#fff;color:#0878d1;font-size:10px;font-weight:780}.crm-workflow-steps{display:grid;gap:8px}.crm-workflow-step{display:grid;grid-template-columns:36px minmax(150px,.85fr) minmax(150px,.85fr) minmax(230px,1.25fr) minmax(210px,1.2fr);align-items:center;gap:11px;padding:10px 12px;border:1px solid #dbe5f0;border-radius:11px;background:#fff;box-shadow:0 3px 10px rgba(15,23,42,.025)}.crm-workflow-order{display:grid;place-items:center;width:29px;height:29px;border-radius:9px;background:#0878d1;color:#fff;font-size:11px;font-weight:850}.crm-workflow-status,.crm-workflow-mode,.crm-workflow-next,.crm-workflow-rule{display:flex;min-width:0;flex-direction:column;gap:4px}.crm-workflow-status strong{color:#0f172a;font-size:12px;font-weight:820}.crm-workflow-status code{width:max-content;max-width:100%;overflow:hidden;padding:3px 6px;border-radius:5px;background:#eef5fb;color:#52667d;font-size:9px;font-weight:720;text-overflow:ellipsis;white-space:nowrap}.crm-workflow-step small{color:#7890a7;font-size:8px;font-weight:850;letter-spacing:.045em}.crm-workflow-mode strong{color:#334155;font-size:10px;font-weight:750}.crm-workflow-next>div{display:flex;flex-wrap:wrap;gap:4px}.crm-workflow-next span{display:flex;align-items:center;gap:4px;padding:4px 6px;border-radius:6px;background:#eaf6ff;color:#0878d1;font-size:9px;font-weight:760}.crm-workflow-next span small{color:#6da5ce;font-size:7px;text-transform:none}.crm-workflow-next .crm-workflow-terminal{background:#f1f5f9;color:#64748b}.crm-workflow-next .crm-workflow-dynamic-return{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}.crm-workflow-rule span{color:#52667d;font-size:9px;line-height:1.4}.dark .crm-workflow-overview>header,.dark .crm-workflow-step{border-color:#334155;background:#0f172a}.dark .crm-workflow-status strong{color:#f8fafc}@media(max-width:1050px){.crm-workflow-step{grid-template-columns:36px minmax(150px,1fr) minmax(150px,1fr)}.crm-workflow-next,.crm-workflow-rule{grid-column:2/-1}}@media(max-width:640px){.crm-workflow-overview>header{align-items:flex-start;flex-direction:column}.crm-workflow-step{grid-template-columns:32px minmax(0,1fr)}.crm-workflow-mode,.crm-workflow-next,.crm-workflow-rule{grid-column:2}}
</style>
HTML;
    }
}
