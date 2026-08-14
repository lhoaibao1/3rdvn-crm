<?php

namespace Tests\Feature;

use App\Enums\FeolSubmitState;
use App\Jobs\SubmitFeolApplicationToPartner;
use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\FeolPartnerSubmitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeolProxySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_form_saves_crm_before_queuing_partner_submission(): void
    {
        Queue::fake();
        [$application, $token] = $this->application();

        $this->get(route('feol.landing.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Đăng ký vay FE CREDIT')
            ->assertSee('Khach hang cu');

        $response = $this->post(route('feol.landing.store', ['token' => $token]), [
            'applicant_name' => 'Nguyen Van A',
            'phone' => '0901234567',
            'identity_number' => '012345678901',
            'date_of_birth' => '20/05/1995',
            'email' => 'SALE@EXAMPLE.COM',
            'loan_amount' => 50000000,
            'loan_term_months' => 24,
            'customer_consent' => '1',
        ]);

        $response->assertRedirect(route('feol.landing.success', ['token' => $token]));
        $application->refresh();
        $integration = $application->feolIntegration;

        $this->assertSame('Nguyen Van A', $application->applicant_name);
        $this->assertSame('1995-05-20', data_get($application->payload, 'fields.date_of_birth'));
        $this->assertSame('sale@example.com', data_get($application->payload, 'fields.email'));
        $this->assertSame('26801', data_get($application->payload, 'fields.referral_code'));
        $this->assertSame(FeolSubmitState::QUEUED, $integration->submit_state);
        $this->assertNotNull($integration->consented_at);
        Queue::assertPushed(SubmitFeolApplicationToPartner::class, fn ($job): bool => $job->applicationId === $application->getKey());
    }

    public function test_invalid_customer_data_is_not_saved_or_queued(): void
    {
        Queue::fake();
        [$application, $token] = $this->application();

        $this->post(route('feol.landing.store', ['token' => $token]), [
            'phone' => '123',
            'customer_consent' => '0',
        ])->assertSessionHasErrors(['applicant_name', 'phone', 'identity_number', 'customer_consent']);

        $this->assertSame('Khach hang cu', $application->fresh()->applicant_name);
        $this->assertSame(FeolSubmitState::AWAITING_CUSTOMER, $application->feolIntegration->submit_state);
        Queue::assertNothingPushed();
    }

    public function test_partner_payload_preserves_the_full_encrypted_landing_url(): void
    {
        [$application] = $this->application();
        $key = '3MQSbZ3xuwbmSHpo';
        $plain = '/landing?cam=fe-cashloan-deeplink&sale=SGBOCTV13765&rid=test-rid';
        $encryptedUrl = 'https://os.saigonbpo.vn/landing?data='.rawurlencode((string) openssl_encrypt($plain, 'AES-128-CBC', $key, 0, $key));

        config()->set('services.feol_bridge.landing_encrypt_key', $key);
        config()->set('services.feol_bridge.landing_campaign', 'fe-cashloan-deeplink');
        config()->set('services.feol_bridge.landing_sale_code', 'SGBOCTV13765');
        config()->set('services.feol_bridge.partner_landing_url', $encryptedUrl);
        config()->set('services.feol_bridge.partner_submit_url', 'https://partner.test/landingPageFE/createFEOL');

        Http::fake(['https://partner.test/*' => Http::response(['code' => 200, 'message' => 'OK', 'data' => []])]);

        app(FeolPartnerSubmitter::class)->submit($application);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://partner.test/landingPageFE/createFEOL'
            && $request['encrypt_unique_url'] === $encryptedUrl
            && $request['original_unique_url'] === 'https://os.saigonbpo.vn'.$plain
            && $request['customer_name'] === 'Khach hang cu'
            && $request['referralCode'] === '26801'
            && $request['salesman'] === 'SGBOCTV13765'
            && $request['consent_tickbox'] === 'YES');
    }

    private function application(): array
    {
        $user = User::factory()->create([
            'sales_projects' => ['fe-deeplink'],
            'sales_codes' => ['fe-deeplink' => '26801'],
        ]);
        $project = SalesProject::query()->create([
            'name' => 'FE Deeplink',
            'slug' => 'fe-deeplink',
            'is_active' => true,
        ]);
        $application = Application::query()->create([
            'sales_project_id' => $project->getKey(),
            'application_code' => 'FEDL-'.Str::upper(Str::random(12)),
            'applicant_name' => 'Khach hang cu',
            'phone' => '0900000000',
            'identity_number' => '012345678901',
            'status' => 'pending_submission',
            'created_by_id' => $user->getKey(),
            'assigned_sale_id' => $user->getKey(),
            'payload' => ['fields' => [
                'referral_code' => '26801',
                'salesman_code' => 'SGBOCTV13765',
            ]],
        ]);
        $token = Str::random(48);
        $application->feolIntegration()->create([
            'public_token' => $token,
            'partner_request_id' => 'REQ-'.Str::upper(Str::random(12)),
            'b1_url' => 'https://uat-apps2.3rdvn.io.vn/fe-deeplink/b1/'.$token,
            'submit_state' => FeolSubmitState::AWAITING_CUSTOMER,
        ]);

        return [$application, $token];
    }
}
