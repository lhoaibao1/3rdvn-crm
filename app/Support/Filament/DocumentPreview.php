<?php

namespace App\Support\Filament;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DocumentPreview
{
    public static function lotteDocuments(array $payload): HtmlString
    {
        $fields = data_get($payload, 'fields', []);
        $front = self::firstPath(data_get($fields, 'ocr_front_image'));
        $back = self::firstPath(data_get($fields, 'ocr_back_image'));
        $items = [
            ['CCCD mặt trước', $front],
            ['CCCD mặt sau', $back],
        ];

        $hasDocument = false;

        foreach ($items as $item) {
            if (filled($item[1])) {
                $hasDocument = true;
                break;
            }
        }

        $ekycRows = [
            ['Trạng thái eKYC', self::value(data_get($fields, 'ekyc_status'))],
            ['Request ID', self::value(data_get($fields, 'ekyc_request_id'))],
            ['Hoàn tất lúc', self::value(data_get($fields, 'ekyc_completed_at'))],
            ['Kết quả', self::value(data_get($fields, 'ekyc_result_note'))],
            ['Ghi chú API', self::value(data_get($fields, 'api_workflow_note'))],
        ];

        if (! $hasDocument && $ekycRows[0][1] === '-' && $ekycRows[1][1] === '-') {
            return new HtmlString('<div style="border:1px dashed #cbd5e1;border-radius:14px;padding:18px;background:#f8fafc;color:#64748b;font-weight:700;text-align:center">Chưa có chứng từ.</div>');
        }

        $html = '<div style="display:grid;gap:14px">';
        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">';

        foreach ($items as [$label, $path]) {
            $url = self::fileUrl($path);
            $html .= '<div style="border:1px solid #e2e8f0;border-radius:16px;background:#fff;overflow:hidden;box-shadow:0 8px 22px rgba(15,23,42,.06)">';
            $html .= '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-bottom:1px solid #eef2f7">';
            $html .= '<strong style="font-size:14px;color:#0f172a">'.e($label).'</strong>';

            if ($url) {
                $html .= '<a href="'.e($url).'" target="_blank" rel="noopener" style="font-size:13px;font-weight:800;color:#2563eb;text-decoration:none">Mở ảnh</a>';
            }

            $html .= '</div>';
            $html .= '<div style="min-height:220px;background:#f8fafc;display:grid;place-items:center;padding:10px">';

            if ($url) {
                $html .= '<img src="'.e($url).'" alt="'.e($label).'" style="max-width:100%;max-height:280px;object-fit:contain;border-radius:12px">';
            } else {
                $html .= '<span style="color:#94a3b8;font-weight:700">Chưa có ảnh</span>';
            }

            $html .= '</div></div>';
        }

        $html .= '</div>';
        $html .= '<div style="border:1px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">';

        foreach ($ekycRows as [$label, $value]) {
            $html .= '<div style="min-width:0">';
            $html .= '<div style="font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase">'.e($label).'</div>';
            $html .= '<div style="margin-top:5px;color:#0f172a;font-size:14px;font-weight:700;line-height:1.35;word-break:break-word">'.e($value).'</div>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return new HtmlString($html);
    }

    private static function firstPath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        if (blank($value)) {
            return null;
        }

        return (string) $value;
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

    private static function value(mixed $value): string
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return filled($value) ? (string) $value : '-';
    }
}
