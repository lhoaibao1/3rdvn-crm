<?php

namespace Tests\Unit;

use App\Support\Filament\DocumentPreview;
use PHPUnit\Framework\TestCase;

class DocumentPreviewTest extends TestCase
{
    public function test_it_groups_documents_into_folders_and_previews_them_without_navigation(): void
    {
        $html = DocumentPreview::lotteDocuments([
            'fields' => [
                'ocr_front_image' => 'https://cdn.example.test/cccd-front.jpg',
            ],
            'documents' => [
                'doc101' => ['https://cdn.example.test/ho-khau.pdf'],
            ],
        ])->toHtml();

        self::assertStringContainsString('crm-document-folders', $html);
        self::assertStringContainsString('DOC100 - CMND/CCCD/CMTQĐ/Hộ chiếu', $html);
        self::assertStringContainsString('DOC101 - Giấy tờ chứng minh cư trú', $html);
        self::assertStringContainsString('Xem', $html);
        self::assertStringContainsString('x-teleport="body"', $html);
        self::assertStringContainsString('role="dialog"', $html);
        self::assertStringContainsString('Tải về', $html);
        self::assertStringContainsString('download="cccd-front.jpg"', $html);
        self::assertStringContainsString('download="ho-khau.pdf"', $html);
        self::assertStringNotContainsString('target="_blank"', $html);
        self::assertStringNotContainsString('eKYC', $html);
    }

    public function test_it_renders_a_clean_empty_document_state(): void
    {
        $html = DocumentPreview::lotteDocuments([])->toHtml();

        self::assertStringContainsString('crm-document-folders', $html);
        self::assertStringContainsString('10 thư mục · 0 file', $html);
        self::assertStringContainsString('Trống', $html);
        self::assertStringNotContainsString('role="dialog"', $html);
    }
}
