<?php

namespace App\Support\Filament;

use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ApplicationAuditLog
{
    public static function render(iterable $logs, callable $statusResolver, string $emptyText = 'Chưa có thay đổi nào được ghi nhận.'): HtmlString
    {
        $collection = $logs instanceof Collection ? $logs->values() : collect($logs)->values();

        if ($collection->isEmpty()) {
            return new HtmlString(self::style().'<div class="crm-audit-log"><div class="crm-audit-empty"><strong>Chưa có Audit Log</strong><span>'.e($emptyText).'</span></div></div>');
        }

        $items = $collection->map(function (object $log) use ($statusResolver): string {
            $changes = self::changes($log, $statusResolver);
            $actor = $log->actor ?? null;
            $actorName = (string) ($actor?->name ?: 'Hệ thống');
            $actorCode = (string) ($actor?->uid ?: ($actor?->employee_code ?: $actor?->email ?: ''));
            $createdAt = $log->created_at ?? null;
            $time = is_object($createdAt) && method_exists($createdAt, 'format') ? $createdAt->format('H:i:s d/m/Y') : '-';
            $action = self::actionLabel((string) ($log->action ?? ''));
            $changeItems = collect($changes)->map(fn (array $change): string => self::changeHtml($change))->join('');
            $changeCount = count($changes);

            if ($changeItems === '') {
                $changeItems = '<div class="crm-audit-no-change">Không có dữ liệu field thay đổi.</div>';
            }

            return '<article class="crm-audit-item">'
                .'<div class="crm-audit-meta"><time>'.e($time).'</time><span class="crm-audit-action">'.e($action).'</span></div>'
                .'<div class="crm-audit-actor"><span>'.e(self::initials($actorName)).'</span><div><strong>'.e($actorName).'</strong>'.($actorCode !== '' ? '<small>'.e($actorCode).'</small>' : '').'</div></div>'
                .'<details class="crm-audit-changes"><summary><span>'.$changeCount.' thay đổi</span><small>Bấm để xem chi tiết</small><i>⌄</i></summary><div class="crm-audit-change-list">'.$changeItems.'</div></details>'
                .'<div class="crm-audit-origin"><span>IP</span><strong>'.e((string) ($log->ip_address ?: '-')).'</strong></div>'
                .'</article>';
        })->join('');

        return new HtmlString(
            self::style()
            .'<div class="crm-audit-log">'
            .'<header><div><strong>Audit Log</strong><span>'.$collection->count().' sự kiện gần nhất</span></div><small>Chi tiết trường dữ liệu cũ → mới được thu gọn theo từng lần cập nhật</small></header>'
            .'<div class="crm-audit-items">'.$items.'</div>'
            .'</div>',
        );
    }

    public static function businessSummary(object $log, callable $statusResolver, int $limit = 5): string
    {
        if (($log->action ?? null) === 'created') {
            return 'Tạo mới: Hồ sơ được khởi tạo trên hệ thống.';
        }

        $changes = self::changes($log, $statusResolver);
        $lines = collect($changes)
            ->sortBy(fn (array $change): int => self::businessPriority($change['path']))
            ->map(function (array $change): string {
                if ($change['path'] === 'status') {
                    if ($change['new'] === 'Trả về Sale') {
                        return 'Trả về Sale: '.$change['old'].' → '.$change['new'];
                    }

                    if ($change['old'] === 'Trả về Sale') {
                        return 'Quay về bước trước khi trả: '.$change['old'].' → '.$change['new'];
                    }

                    return 'Chuyển bước: '.$change['old'].' → '.$change['new'];
                }

                if ($change['path'] === 'payload.workflow.return_to_sale.from') {
                    return 'Bước bị trả về: '.$change['new'];
                }

                if ($change['path'] === 'payload.workflow.return_to_sale.resume_to') {
                    return 'Bước sẽ quay lại sau khi Sale cập nhật: '.$change['new'];
                }

                if (self::isNote($change['path'])) {
                    return $change['label'].': '.$change['new'];
                }

                return $change['label'].': '.$change['old'].' → '.$change['new'];
            });

        if ($lines->isEmpty()) {
            return match ($log->action ?? null) {
                'deleted' => 'Xử lý: Đóng hồ sơ.',
                'restored' => 'Xử lý: Khôi phục hồ sơ.',
                default => 'Xử lý: Cập nhật hồ sơ.',
            };
        }

        $remaining = max(0, $lines->count() - $limit);
        $visible = $lines->take($limit);

        if ($remaining > 0) {
            $visible->push('Thay đổi khác: +'.$remaining.' trường (xem đầy đủ tại Audit Log)');
        }

        return $visible->join("\n");
    }

    public static function changes(object $log, callable $statusResolver): array
    {
        $rawChanges = is_array($log->changes ?? null) ? $log->changes : [];
        $changes = [];

        foreach ($rawChanges as $field => $change) {
            if (is_int($field) && is_array($change) && filled($change['field'] ?? null)) {
                $field = (string) $change['field'];
            }

            if (! is_string($field) || ! is_array($change) || (! array_key_exists('old', $change) && ! array_key_exists('new', $change))) {
                continue;
            }

            $old = self::decodeComposite($change['old'] ?? null);
            $new = self::decodeComposite($change['new'] ?? null);

            if (is_array($old) || is_array($new)) {
                self::flattenDiff(
                    is_array($old) ? $old : [],
                    is_array($new) ? $new : [],
                    $field,
                    $changes,
                    $statusResolver,
                );

                continue;
            }

            self::pushChange($changes, $field, $old, $new, $statusResolver);
        }

        return array_values($changes);
    }

    private static function flattenDiff(array $old, array $new, string $prefix, array &$changes, callable $statusResolver): void
    {
        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            $path = $prefix.'.'.$key;
            $oldValue = self::decodeComposite($old[$key] ?? null);
            $newValue = self::decodeComposite($new[$key] ?? null);

            if (is_array($oldValue) || is_array($newValue)) {
                self::flattenDiff(
                    is_array($oldValue) ? $oldValue : [],
                    is_array($newValue) ? $newValue : [],
                    $path,
                    $changes,
                    $statusResolver,
                );

                continue;
            }

            self::pushChange($changes, $path, $oldValue, $newValue, $statusResolver);
        }
    }

    private static function pushChange(array &$changes, string $path, mixed $old, mixed $new, callable $statusResolver): void
    {
        if ($old === $new
            || in_array($path, ['updated_at', 'payload.updated_at'], true)
            || str_contains($path, 'otp_updated_by_id')
            || str_contains($path, 'otp_updated_at')
            || str_contains($path, 'last_otp_update')
            || str_contains($path, 'payload.workflow.last_transition')) {
            return;
        }

        $changes[] = [
            'path' => $path,
            'label' => self::fieldLabel($path),
            'old' => self::value($path, $old, $statusResolver),
            'new' => self::value($path, $new, $statusResolver),
        ];
    }

    private static function changeHtml(array $change): string
    {
        return '<div class="crm-audit-change">'
            .'<strong>'.e($change['label']).'<small>'.e($change['path']).'</small></strong>'
            .'<div><span class="crm-audit-old">'.e($change['old']).'</span><i>→</i><span class="crm-audit-new">'.e($change['new']).'</span></div>'
            .'</div>';
    }

    private static function decodeComposite(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || ! in_array($trimmed[0] ?? '', ['{', '['], true)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private static function value(string $path, mixed $value, callable $statusResolver): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if ($path === 'status' || self::isWorkflowStatusPath($path)) {
            return (string) $statusResolver((string) $value);
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        if (is_array($value)) {
            return Str::limit((string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 160);
        }

        if (self::isMoney($path) && is_numeric($value)) {
            return number_format((float) $value, 0, ',', '.').' VNĐ';
        }

        if (str_contains($path, 'documents.') || str_contains($path, '_image')) {
            return basename((string) $value) ?: '-';
        }

        return Str::limit(trim(strip_tags((string) $value)), 180);
    }

    private static function fieldLabel(string $path): string
    {
        $leaf = (string) str($path)->afterLast('.');

        if (str_contains($path, 'documents.')) {
            return 'Chứng từ '.str($leaf)->upper();
        }

        return match ($leaf) {
            'application_code' => 'Mã hồ sơ',
            'applicant_name', 'customer_name' => 'Khách hàng',
            'phone' => 'Số điện thoại',
            'identity_number', 'cccd', 'cmnd' => 'CCCD/CMND',
            'status' => 'Trạng thái',
            'assigned_sale_id' => 'Người xử lý',
            'created_by_id' => 'Người tạo',
            'team_id' => 'Team',
            'team_leader_id' => 'Team Leader',
            'am_id' => 'AM',
            'zd_id' => 'ZD',
            'note', 'processing_note' => 'Ghi chú xử lý',
            'from' => str_contains($path, 'return_to_sale') ? 'Bước bị trả về' : 'Từ bước',
            'to' => 'Đến bước',
            'resume_to' => 'Quay về bước trước khi trả',
            'returned_by_id' => 'Người trả về Sale',
            'returned_at' => 'Thời gian trả về Sale',
            'review_note' => 'Ghi chú Pre-Check',
            'approval_note' => 'Ghi chú Approval',
            'decision' => 'Kết quả Pre-Check',
            'otp' => 'OTP',
            'product', 'scheme_product' => 'Sản phẩm',
            'scheme_code' => 'Scheme',
            'loan_amount' => 'Số tiền vay',
            'maximum_limit' => 'Hạn mức tối đa',
            'approved_amount', 'pre_approved_amount' => 'Số tiền phê duyệt',
            'estimated_interest_rate', 'scheme_interest_rate', 'pre_approved_interest_rate' => 'Lãi suất',
            'approved_at' => 'Thời gian Approval',
            'reviewed_at' => 'Thời gian Pre-Check',
            'ocr_front_image' => 'CCCD mặt trước',
            'ocr_back_image' => 'CCCD mặt sau',
            default => str($leaf)->replace('_', ' ')->headline()->toString(),
        };
    }

    private static function actionLabel(string $action): string
    {
        return match ($action) {
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Đóng hồ sơ',
            'restored' => 'Khôi phục',
            default => str($action)->replace('_', ' ')->headline()->toString() ?: 'Xử lý',
        };
    }

    private static function businessPriority(string $path): int
    {
        return match (true) {
            $path === 'status' => 0,
            self::isNote($path) => 1,
            str_contains($path, 'assigned_sale_id') => 2,
            str_contains($path, 'review.') => 3,
            str_contains($path, 'documents.') => 8,
            default => 5,
        };
    }

    private static function isWorkflowStatusPath(string $path): bool
    {
        return str_contains($path, 'payload.workflow.return_to_sale.')
            && in_array((string) str($path)->afterLast('.'), ['from', 'to', 'resume_to'], true);
    }

    private static function isNote(string $path): bool
    {
        return str_contains($path, 'note') || str_contains($path, 'comment') || str_contains($path, 'remark');
    }

    private static function isMoney(string $path): bool
    {
        return str_contains($path, 'amount')
            || str_contains($path, 'income')
            || str_contains($path, 'limit')
            || str_contains($path, 'payment');
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

        return count($parts) === 1
            ? mb_strtoupper(mb_substr($parts[0], 0, 2))
            : mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[array_key_last($parts)], 0, 1));
    }

    private static function style(): string
    {
        return <<<'HTML'
<style>
.crm-audit-log{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.crm-audit-log>header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;padding:2px}.crm-audit-log>header>div{display:flex;align-items:baseline;gap:8px}.crm-audit-log>header strong{color:#0f172a;font-size:15px;font-weight:820}.crm-audit-log>header span,.crm-audit-log>header small{color:#64748b;font-size:12px}.crm-audit-items{display:grid;gap:8px}.crm-audit-item{display:grid;grid-template-columns:160px 210px minmax(280px,1fr) 130px;align-items:center;gap:12px;padding:10px 12px;border:1px solid #dbe5f0;border-radius:11px;background:#fff;box-shadow:0 3px 10px rgba(15,23,42,.025)}.crm-audit-meta{display:flex;align-items:flex-start;flex-direction:column;gap:5px}.crm-audit-meta time{color:#334155;font-size:11px;font-weight:720;font-variant-numeric:tabular-nums}.crm-audit-action{display:inline-flex;padding:3px 7px;border-radius:999px;background:#e8f4ff;color:#0878d1;font-size:10px;font-weight:820}.crm-audit-actor{display:flex;align-items:center;gap:8px;min-width:0}.crm-audit-actor>span{display:grid;place-items:center;width:30px;height:30px;flex:0 0 auto;border-radius:9px;background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:850}.crm-audit-actor>div{display:flex;min-width:0;flex-direction:column}.crm-audit-actor strong,.crm-audit-actor small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.crm-audit-actor strong{color:#0f172a;font-size:11px;font-weight:760}.crm-audit-actor small{color:#64748b;font-size:10px}.crm-audit-changes{min-width:0}.crm-audit-changes summary{display:flex;align-items:center;gap:8px;min-height:34px;padding:5px 9px;border:1px solid #dbe5f0;border-radius:9px;background:#f8fbfe;cursor:pointer;list-style:none}.crm-audit-changes summary::-webkit-details-marker{display:none}.crm-audit-changes summary span{color:#0f172a;font-size:11px;font-weight:800}.crm-audit-changes summary small{color:#64748b;font-size:10px}.crm-audit-changes summary i{margin-left:auto;color:#0878d1;font-size:16px;font-style:normal;transition:transform .15s}.crm-audit-changes[open] summary i{transform:rotate(180deg)}.crm-audit-change-list{display:grid;gap:6px;margin-top:7px}.crm-audit-change{display:grid;grid-template-columns:minmax(125px,.45fr) minmax(0,1fr);gap:10px;padding:8px;border-radius:8px;background:#f8fafc}.crm-audit-change>strong{display:flex;min-width:0;flex-direction:column;color:#334155;font-size:10px;font-weight:800}.crm-audit-change>strong small{overflow:hidden;color:#94a3b8;font-size:9px;font-weight:600;text-overflow:ellipsis;white-space:nowrap}.crm-audit-change>div{display:grid;grid-template-columns:minmax(0,1fr) 18px minmax(0,1fr);align-items:center;gap:5px;min-width:0}.crm-audit-change>div span{padding:5px 7px;border-radius:6px;font-size:10px;line-height:1.35;overflow-wrap:anywhere}.crm-audit-change>div i{text-align:center;color:#94a3b8;font-size:12px;font-style:normal}.crm-audit-old{background:#fff1f2;color:#9f1239}.crm-audit-new{background:#ecfdf5;color:#047857}.crm-audit-origin{display:flex;align-items:center;gap:6px;color:#64748b;font-size:10px}.crm-audit-origin span{font-weight:800}.crm-audit-origin strong{overflow:hidden;color:#334155;font-weight:650;text-overflow:ellipsis;white-space:nowrap}.crm-audit-no-change{padding:8px;color:#94a3b8;font-size:10px}.crm-audit-empty{display:grid;place-items:center;min-height:170px;padding:25px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;text-align:center}.crm-audit-empty strong{color:#334155;font-size:14px}.crm-audit-empty span{margin-top:4px;color:#94a3b8;font-size:12px}.dark .crm-audit-item,.dark .crm-audit-changes summary{border-color:#334155;background:#0f172a}.dark .crm-audit-actor strong,.dark .crm-audit-meta time,.dark .crm-audit-changes summary span{color:#f8fafc}.dark .crm-audit-change{background:#111827}@media(max-width:920px){.crm-audit-log>header{align-items:flex-start;flex-direction:column}.crm-audit-log>header>div{align-items:flex-start;flex-direction:column;gap:2px}.crm-audit-item{grid-template-columns:1fr}.crm-audit-change{grid-template-columns:1fr}.crm-audit-origin{display:none}}
</style>
HTML;
    }
}
