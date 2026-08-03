<?php

namespace Tests\Unit;

use App\Support\Applications\AclMixWorkflow;
use App\Support\Filament\DocumentPreview;
use PHPUnit\Framework\TestCase;

class AclMixConsentDocumentTest extends TestCase
{
    public function test_acl_consent_upload_is_stored_as_an_application_document(): void
    {
        $payload = AclMixWorkflow::creationPayload(
            ['consent_6088' => 'applications/acl-mix/consent-6088/xac-nhan.png'],
            ['customer_name' => 'NGUYỄN VĂN A'],
        );

        $this->assertSame(
            'applications/acl-mix/consent-6088/xac-nhan.png',
            data_get($payload, 'documents.consent_6088'),
        );
        $this->assertSame('NGUYỄN VĂN A', data_get($payload, 'module_fields.customer_name'));
        $this->assertArrayNotHasKey('consent_6088', data_get($payload, 'module_fields', []));
    }

    public function test_acl_consent_document_can_be_viewed_and_downloaded_in_its_folder(): void
    {
        $html = DocumentPreview::projectDocuments([
            'documents' => [
                'consent_6088' => 'https://uat.example.test/storage/consent-6088.png',
            ],
        ], 'acl-mix')->toHtml();

        $this->assertStringContainsString('Consent gửi 6088', $html);
        $this->assertStringContainsString('Xem', $html);
        $this->assertStringContainsString('Tải về', $html);
        $this->assertStringContainsString('https://uat.example.test/storage/consent-6088.png', $html);
    }

    public function test_acl_application_create_form_places_compact_consent_upload_at_the_bottom(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Applications/Schemas/AclMixApplicationForm.php',
        );

        $this->assertStringContainsString("Section::make('Thông tin kiểm tra ban đầu')", $source);
        $this->assertStringContainsString("FileUpload::make('consent_6088')", $source);
        $this->assertStringContainsString("->label('Chứng từ Consent gửi đến 6088')", $source);
        $this->assertStringContainsString("->directory('applications/acl-mix/consent-6088')", $source);
        $this->assertStringContainsString("->panelLayout('compact')", $source);
        $this->assertStringContainsString('->columnSpan(1)', $source);
        $this->assertGreaterThan(
            strpos($source, "Select::make('ward_code')"),
            strpos($source, "FileUpload::make('consent_6088')"),
        );
    }
}
