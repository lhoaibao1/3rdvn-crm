<?php

namespace Tests\Unit;

use App\Support\Filament\DocumentPreview;
use PHPUnit\Framework\TestCase;

class AclMixConsentDocumentTest extends TestCase
{
    public function test_acl_consent_upload_is_stored_as_a_document(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Leads/Pages/CreateLead.php',
        );

        $this->assertStringContainsString(
            "\$documents['consent_6088'] = \$data['consent_6088']",
            $source,
        );
        $this->assertStringContainsString("unset(\$data['consent_6088'])", $source);
        $this->assertStringContainsString("\$payload['documents'] = \$documents", $source);
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

    public function test_acl_create_lead_form_exposes_the_consent_upload_in_the_first_form(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Leads/Pages/CreateLead.php',
        );

        $this->assertStringContainsString("FileUpload::make('consent_6088')", $source);
        $this->assertStringContainsString("->label('Chứng từ Consent gửi đến 6088')", $source);
        $this->assertStringContainsString("->directory('leads/acl-mix/consent-6088')", $source);
        $this->assertLessThan(
            strpos($source, "TextInput::make('birthday')"),
            strpos($source, "FileUpload::make('consent_6088')"),
            'Ô upload Consent phải xuất hiện ngay ở đầu form tạo Lead ACL.',
        );
    }
}
