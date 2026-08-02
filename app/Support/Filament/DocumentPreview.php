<?php

namespace App\Support\Filament;

use App\Support\LotteFinanceDocuments;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DocumentPreview
{
    public static function lotteDocuments(array $payload): HtmlString
    {
        $fields = data_get($payload, 'fields', []);
        $groups = [];

        foreach (LotteFinanceDocuments::definitions() as $key => $label) {
            $paths = self::paths(data_get($payload, 'documents.'.$key));

            if ($key === 'doc100') {
                $paths = array_values(array_unique([
                    ...self::paths(data_get($fields, 'ocr_front_image')),
                    ...self::paths(data_get($fields, 'ocr_back_image')),
                    ...$paths,
                ]));
            }
            $groups[] = [
                'key' => $key,
                'label' => $label,
                'description' => self::folderDescription($key),
                'files' => collect($paths)
                    ->map(fn (string $path, int $index): ?array => self::file(
                        count($paths) > 1 ? 'Chứng từ '.($index + 1) : 'Chứng từ',
                        $path,
                    ))
                    ->filter()
                    ->values()
                    ->all(),
            ];
        }

        $documentCount = collect($groups)->sum(fn (array $group): int => count($group['files']));

        $folders = collect($groups)
            ->map(fn (array $group): string => self::folderHtml($group))
            ->join('');

        return new HtmlString(
            self::style()
            .'<div class="crm-document-library">'
            .'<div class="crm-document-library-head"><div><strong>Thư mục chứng từ</strong><span>'.count($groups).' thư mục · '.$documentCount.' file</span></div><small>Chọn Xem để mở ngay trên trang</small></div>'
            .'<div class="crm-document-folders">'.$folders.'</div>'
            .'</div>',
        );
    }

    private static function folderHtml(array $group): string
    {
        $count = count($group['files']);
        $hasFiles = $count > 0;
        $files = collect($group['files'])->map(fn (array $file): string => self::fileHtml($file))->join('');
        $modal = $hasFiles
            ? '<template x-teleport="body"><div class="crm-document-overlay" x-cloak x-show="open" x-transition.opacity @keydown.escape.window="open = false" @click.self="open = false">'
                .'<section class="crm-document-modal" role="dialog" aria-modal="true" aria-label="'.e($group['label']).'">'
                .'<header><div><strong>'.e($group['label']).'</strong><span>'.$count.' file chứng từ</span></div><button type="button" @click="open = false" aria-label="Đóng">×</button></header>'
                .'<div class="crm-document-modal-body">'.$files.'</div>'
                .'</section></div></template>'
            : '';

        return '<div class="crm-document-folder" x-data="{ open: false }">'
            .'<div class="crm-document-folder-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.75 6.75A1.75 1.75 0 0 1 5.5 5h4.1l1.65 1.75h7.25a1.75 1.75 0 0 1 1.75 1.75v8A2.5 2.5 0 0 1 17.75 19H6.25a2.5 2.5 0 0 1-2.5-2.5V6.75Z"/></svg><span>'.$count.'</span></div>'
            .'<div class="crm-document-folder-copy"><strong>'.e($group['label']).'</strong><span>'.e($group['description']).'</span></div>'
            .($hasFiles
                ? '<button type="button" class="crm-document-view" @click="open = true">Xem</button>'
                : '<button type="button" class="crm-document-view" disabled>Trống</button>')
            .$modal
            .'</div>';
    }

    private static function fileHtml(array $file): string
    {
        $preview = match ($file['type']) {
            'image' => '<img data-src="'.e($file['url']).'" x-bind:src="open ? $el.dataset.src : \'\'" alt="'.e($file['label']).'">',
            'pdf' => '<iframe data-src="'.e($file['url']).'" x-bind:src="open ? $el.dataset.src : \'about:blank\'" title="'.e($file['label']).'" loading="lazy"></iframe>',
            'video' => '<video controls preload="metadata" data-src="'.e($file['url']).'" x-bind:src="open ? $el.dataset.src : \'\'"></video>',
            default => '<div class="crm-document-generic"><span>DOC</span><strong>Không hỗ trợ xem trước định dạng này</strong></div>',
        };

        return '<article class="crm-document-file">'
            .'<div class="crm-document-file-head"><div><strong>'.e($file['label']).'</strong><span>'.e($file['name']).'</span></div><a href="'.e($file['url']).'" download="'.e($file['name']).'">Tải về</a></div>'
            .'<div class="crm-document-preview">'.$preview.'</div>'
            .'</article>';
    }

    private static function file(string $label, ?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }

        $url = self::fileUrl($path);
        if (blank($url)) {
            return null;
        }

        $pathPart = (string) (parse_url($path, PHP_URL_PATH) ?: $path);
        $extension = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION));
        $type = match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) => 'image',
            $extension === 'pdf' => 'pdf',
            in_array($extension, ['mp4', 'mov', 'webm'], true) => 'video',
            default => 'file',
        };

        return [
            'label' => $label,
            'name' => urldecode(basename($pathPart)) ?: 'chung-tu.'.$extension,
            'url' => $url,
            'type' => $type,
        ];
    }

    private static function folderDescription(string $key): string
    {
        return match ($key) {
            'doc100' => 'Giấy tờ tùy thân đã chuẩn hóa',
            'doc101' => 'Giấy tờ xác minh nơi cư trú',
            'doc105_customer_sale' => 'Ảnh khách hàng chụp cùng nhân viên Sale',
            'doc133_salary' => 'Sao kê hoặc tài liệu chứng minh thu nhập',
            'doc141_customer' => 'Ảnh chân dung khách hàng',
            'doc105_lookup' => 'Ảnh chụp màn hình kết quả tra cứu',
            'doc157_vssid' => 'Tài liệu ứng dụng VSSID',
            'doc1571_vssid_video' => 'Video đối chiếu VSSID',
            'doc158_insurance' => 'Giấy yêu cầu bảo hiểm khoản vay',
            'doc159_kyc' => 'Tài liệu xác minh KYC',
            default => 'Chứng từ hồ sơ',
        };
    }

    private static function paths(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(fn (mixed $path): string => trim((string) $path), $values),
            fn (string $path): bool => $path !== '',
        ));
    }

    private static function fileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private static function style(): string
    {
        return <<<'HTML'
<style>
.crm-document-library{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.crm-document-library-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;padding:2px}.crm-document-library-head>div{display:flex;align-items:baseline;gap:9px}.crm-document-library-head strong{color:#0f172a;font-size:15px;font-weight:820}.crm-document-library-head span,.crm-document-library-head small{color:#64748b;font-size:12px}.crm-document-folders{display:grid;grid-template-columns:repeat(auto-fill,minmax(245px,1fr));gap:12px}.crm-document-folder{position:relative;display:grid;grid-template-columns:48px minmax(0,1fr) auto;align-items:center;gap:11px;min-height:86px;padding:13px;border:1px solid #dbe5f0;border-radius:13px;background:linear-gradient(145deg,#fff,#f7fbff);box-shadow:0 5px 14px rgba(15,23,42,.035);transition:.16s ease}.crm-document-folder:hover{border-color:#9bcdf1;box-shadow:0 10px 24px rgba(8,120,209,.1);transform:translateY(-1px)}.crm-document-folder-icon{position:relative;display:grid;place-items:center;width:48px;height:44px;border-radius:12px;background:#e5f4ff;color:#0878d1}.crm-document-folder-icon svg{width:27px;height:27px;fill:#73bdf0;stroke:#0878d1;stroke-width:1}.crm-document-folder-icon span{position:absolute;right:-4px;bottom:-4px;display:grid;place-items:center;min-width:21px;height:21px;padding:0 5px;border:2px solid #fff;border-radius:999px;background:#0878d1;color:#fff;font-size:10px;font-weight:850}.crm-document-folder-copy{display:flex;min-width:0;flex-direction:column;gap:4px}.crm-document-folder-copy strong{overflow:hidden;color:#0f172a;font-size:12px;font-weight:790;text-overflow:ellipsis;white-space:nowrap}.crm-document-folder-copy span{display:-webkit-box;overflow:hidden;color:#64748b;font-size:11px;line-height:1.35;-webkit-box-orient:vertical;-webkit-line-clamp:2}.crm-document-view{min-width:54px;height:32px;padding:0 10px;border:1px solid #9bcdf1;border-radius:9px;background:#eef8ff;color:#0878d1;font-size:11px;font-weight:800}.crm-document-view:hover:not(:disabled){border-color:#0878d1;background:#0878d1;color:#fff}.crm-document-view:disabled{cursor:not-allowed;opacity:.46}.crm-document-overlay{position:fixed;inset:0;z-index:190;display:grid;place-items:center;padding:22px;background:rgba(7,18,38,.68);backdrop-filter:blur(5px)}.crm-document-modal{display:flex;width:min(1120px,96vw);height:min(820px,92dvh);flex-direction:column;overflow:hidden;border:1px solid rgba(255,255,255,.35);border-radius:18px;background:#f4f8fc;box-shadow:0 30px 90px rgba(0,0,0,.36)}.crm-document-modal>header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px 18px;border-bottom:1px solid #dbe5f0;background:#fff}.crm-document-modal>header>div{display:flex;min-width:0;flex-direction:column;gap:2px}.crm-document-modal>header strong{overflow:hidden;color:#0f172a;font-size:16px;font-weight:820;text-overflow:ellipsis;white-space:nowrap}.crm-document-modal>header span{color:#64748b;font-size:12px}.crm-document-modal>header button{display:grid;place-items:center;width:36px;height:36px;flex:0 0 auto;border-radius:10px;background:#f1f5f9;color:#475569;font-size:25px;line-height:1}.crm-document-modal>header button:hover{background:#fee2e2;color:#b91c1c}.crm-document-modal-body{display:grid;gap:14px;min-height:0;padding:15px;overflow-y:auto;overscroll-behavior:contain}.crm-document-file{overflow:hidden;border:1px solid #dbe5f0;border-radius:14px;background:#fff}.crm-document-file-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 13px;border-bottom:1px solid #e8eef5}.crm-document-file-head>div{display:flex;min-width:0;flex-direction:column;gap:2px}.crm-document-file-head strong{color:#0f172a;font-size:13px;font-weight:780}.crm-document-file-head span{overflow:hidden;color:#64748b;font-size:11px;text-overflow:ellipsis;white-space:nowrap}.crm-document-file-head a{flex:0 0 auto;padding:7px 11px;border-radius:8px;background:#0878d1;color:#fff;font-size:11px;font-weight:800;text-decoration:none}.crm-document-file-head a:hover{background:#075fa5}.crm-document-preview{display:grid;min-height:360px;place-items:center;padding:12px;background:#eaf0f6}.crm-document-preview img,.crm-document-preview video{max-width:100%;max-height:620px;border-radius:9px;object-fit:contain}.crm-document-preview iframe{width:100%;height:620px;border:0;border-radius:9px;background:#fff}.crm-document-generic{display:grid;place-items:center;gap:9px;color:#64748b}.crm-document-generic span{display:grid;place-items:center;width:64px;height:64px;border-radius:16px;background:#dbeafe;color:#1d4ed8;font-size:14px;font-weight:900}.crm-document-modal-empty,.crm-document-empty{display:grid;place-items:center;min-height:180px;padding:28px;text-align:center}.crm-document-modal-empty strong,.crm-document-empty strong{color:#334155;font-size:14px}.crm-document-modal-empty span,.crm-document-empty p{margin-top:4px;color:#94a3b8;font-size:12px}.crm-document-empty>span{display:grid;place-items:center;width:48px;height:48px;border-radius:14px;background:#e0f2fe;color:#0284c7;font-size:28px}.dark .crm-document-folder,.dark .crm-document-modal>header,.dark .crm-document-file{border-color:#334155;background:#0f172a}.dark .crm-document-folder-copy strong,.dark .crm-document-library-head strong,.dark .crm-document-modal>header strong,.dark .crm-document-file-head strong{color:#f8fafc}.dark .crm-document-modal{background:#111827}.dark .crm-document-preview{background:#0b1220}@media(max-width:700px){.crm-document-library-head{align-items:flex-start;flex-direction:column}.crm-document-library-head>div{align-items:flex-start;flex-direction:column;gap:2px}.crm-document-folders{grid-template-columns:1fr}.crm-document-overlay{padding:0}.crm-document-modal{width:100vw;height:100dvh;border:0;border-radius:0}.crm-document-modal-body{padding:10px}.crm-document-preview{min-height:280px}.crm-document-preview iframe{height:70dvh}}
</style>
HTML;
    }
}
