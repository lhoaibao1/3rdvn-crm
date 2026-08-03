<?php

namespace Tests\Unit;

use App\Models\SalesProject;
use App\Support\Filament\DocumentPreview;
use App\Support\Filament\LeadCreate\CreatesLeadRecords;
use PHPUnit\Framework\TestCase;

class AclMixConsentDocumentTest extends TestCase
{
    public function test_acl_consent_upload_is_stored_as_a_document(): void
    {
        $project = new SalesProject([
            'name' => 'ACL Mix',
            'slug' => 'acl-mix',
        ]);

        $data = AclMixLeadNormalizer::normalizeLeadData(
            [
                'customer_name' => 'Nguyễn Văn A',
                'consent_6088' => 'leads/acl-mix/consent-6088/xac-nhan.png',
            ],
            $project,
            ['customer_name'],
            ['consent_6088'],
        );

        $this->assertSame(
            'leads/acl-mix/consent-6088/xac-nhan.png',
            data_get($data, 'payload.documents.consent_6088'),
        );
        $this->assertArrayNotHasKey('consent_6088', data_get($data, 'payload.fields', []));
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
            dirname(__DIR__, 2).'/app/Support/Filament/LeadCreate/CreateAclMixLeadAction.php',
        );

        $this->assertStringContainsString("FileUpload::make('consent_6088')", $source);
        $this->assertStringContainsString("->label('Chứng từ Consent gửi 6088')", $source);
        $this->assertStringContainsString("->directory('leads/acl-mix/consent-6088')", $source);
        $this->assertStringContainsString(
            "true, ['consent_6088']",
            $source,
        );
    }
}

class AclMixLeadNormalizer
{
    use CreatesLeadRecords {
        normalizeLeadData as public;
    }
}
