<?php

namespace Tests\Feature;

use App\Enums\FeolSubmitState;
use App\Jobs\SubmitFeolApplicationToPartner;
use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\FeolApplicationSync;
use App\Support\Applications\FeolPartnerSubmitter;
use App\Support\Applications\FeolConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeolProxySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_code_link_opens_public_registration_without_login(): void
    {
        $user = User::factory()->create([
            'employment_status' => User::STATUS_ACTIVE,
            'sales_projects' => ['fe-deeplink'],
            'sales_codes' => ['fe-deeplink' => '26801'],
        ]);

        $response = $this->get(route('feol.registration.show', ['salesCode' => '26801']));

        $response
            ->assertOk()
            ->assertSee('Đăng ký khoản vay')
            ->assertSee('images/fe-credit.svg', false)
            ->assertSee(FeolConsent::TEXT)
            ->assertSee('data-date-mask', false)
            ->assertSee('placeholder="dd/mm/yyyy"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('26801')
            ->assertSee(route('feol.registration.store', ['salesCode' => '26801']), false);
        $this->assertGuest();
        $this->assertSame('26801', data_get($user->fresh()->sales_codes, 'fe-deeplink'));
    }

    public function test_sales_code_registration_creates_crm_application_and_queues_partner_submission(): void
    {
        Queue::fake();
        $user = User::factory()->create([
            'employment_status' => User::STATUS_ACTIVE,
            'sales_projects' => ['fe-deeplink'],
            'sales_codes' => ['fe-deeplink' => '26801'],
        ]);
        $project = SalesProject::query()->create([
            'name' => 'FE Deeplink',
            'slug' => 'fe-deeplink',
            'is_active' => true,
        ]);

        $response = $this->post(route('feol.registration.store', ['salesCode' => '26801']), [
            'applicant_name' => 'Nguyen Van Moi',
            'phone' => '0901234567',
            'identity_number' => '012345678901',
            'date_of_birth' => '20/05/1995',
            'email' => 'CUSTOMER@EXAMPLE.COM',
            'loan_amount' => '20.000.000',
            'loan_term_months' => 24,
            'customer_consent' => '1',
        ]);

        $application = Application::query()->sole();
        $integration = $application->feolIntegration;

        $response->assertRedirect(route('feol.landing.success', ['token' => $integration->public_token]));
        $this->assertSame($project->getKey(), $application->sales_project_id);
        $this->assertSame($user->getKey(), $application->created_by_id);
        $this->assertSame($user->getKey(), $application->assigned_sale_id);
        $this->assertSame('Nguyen Van Moi', $application->applicant_name);
        $this->assertSame('customer@example.com', data_get($application->payload, 'fields.email'));
        $this->assertSame('26801', data_get($application->payload, 'fields.referral_code'));
        $this->assertSame(20000000, data_get($application->payload, 'fields.loan_amount'));
        $this->assertTrue((bool) data_get($application->payload, 'fields.customer_consent'));
        $this->assertSame(FeolSubmitState::QUEUED, $integration->submit_state);
        Queue::assertPushed(SubmitFeolApplicationToPartner::class, fn ($job): bool => $job->applicationId === $application->getKey());
    }

    public function test_sales_code_registration_is_saved_then_automatically_sent_to_partner(): void
    {
        $key = '3MQSbZ3xuwbmSHpo';
        $plain = '/landing?cam=fe-cashloan-deeplink&sale=SGBOCTV13765&rid=full-flow';

        config()->set('queue.default', 'sync');
        config()->set('services.feol_bridge.landing_encrypt_key', $key);
        config()->set('services.feol_bridge.landing_campaign', 'fe-cashloan-deeplink');
        config()->set('services.feol_bridge.landing_sale_code', 'SGBOCTV13765');
        config()->set('services.feol_bridge.partner_landing_url', 'https://os.saigonbpo.vn/landing?data='.rawurlencode((string) openssl_encrypt($plain, 'AES-128-CBC', $key, 0, $key)));
        config()->set('services.feol_bridge.partner_submit_url', 'https://partner.test/landingPageFE/createFEOL');

        Http::fake([
            'https://partner.test/*' => Http::response([
                'code' => 200,
                'message' => 'OK',
                'data' => ['leadId' => 'PARTNER-LEAD-001'],
            ]),
        ]);

        $user = User::factory()->create([
            'employment_status' => User::STATUS_ACTIVE,
            'sales_projects' => ['fe-deeplink'],
            'sales_codes' => ['fe-deeplink' => '26801'],
        ]);
        SalesProject::query()->create([
            'name' => 'FE Deeplink',
            'slug' => 'fe-deeplink',
            'is_active' => true,
        ]);

        $response = $this->post(route('feol.registration.store', ['salesCode' => '26801']), [
            'applicant_name' => 'Khach Hang Full Flow',
            'phone' => '0901234567',
            'identity_number' => '012345678901',
            'date_of_birth' => '20/05/1995',
            'email' => 'customer@example.com',
            'loan_amount' => 50000000,
            'loan_term_months' => 24,
            'customer_consent' => '1',
        ]);

        $application = Application::query()->with('feolIntegration')->sole();

        $response->assertRedirect(route('feol.landing.success', ['token' => $application->feolIntegration->public_token]));
        $this->assertSame($user->getKey(), $application->created_by_id);
        $this->assertSame(FeolSubmitState::SUBMITTED, $application->feolIntegration->submit_state);
        $this->assertSame('PARTNER-LEAD-001', $application->feolIntegration->partner_lead_id);
        $this->assertNotNull($application->feolIntegration->partner_submitted_at);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://partner.test/landingPageFE/createFEOL'
            && $request['referralCode'] === '26801'
            && $request['consent_content'] === FeolConsent::TEXT
            && $request['customer_name'] === 'Khach Hang Full Flow');
    }

    public function test_inactive_or_unknown_sales_code_cannot_open_public_registration(): void
    {
        User::factory()->create([
            'employment_status' => User::STATUS_DEACTIVE,
            'sales_projects' => ['fe-deeplink'],
            'sales_codes' => ['fe-deeplink' => '26801'],
        ]);

        $this->get(route('feol.registration.show', ['salesCode' => '26801']))->assertNotFound();
        $this->get(route('feol.registration.show', ['salesCode' => '99999']))->assertNotFound();
    }

    public function test_pending_bridge_payload_exposes_the_three_match_keys_and_last_fingerprint(): void
    {
        config()->set('services.feol_bridge.token', 'bridge-test-token');
        [$application] = $this->application();
        $application->feolIntegration()->update([
            'raw_payload' => ['_bridge' => ['fingerprint' => 'fp-previous']],
            'next_sync_at' => now()->subSecond(),
            'last_error' => 'Không tìm thấy hồ sơ ở CRM đối tác.',
        ]);

        $response = $this
            ->withToken('bridge-test-token')
            ->getJson(route('api.integration.feol.pending'));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $application->getKey())
            ->assertJsonPath('data.0.name', 'Khach hang cu')
            ->assertJsonPath('data.0.phone', '0900000000')
            ->assertJsonPath('data.0.referral_code', '26801')
            ->assertJsonPath('data.0.partner_fingerprint', 'fp-previous')
            ->assertJsonPath('data.0.last_error', 'Không tìm thấy hồ sơ ở CRM đối tác.');
    }

    public function test_synced_historical_record_without_next_poll_is_not_returned_to_bridge(): void
    {
        config()->set('services.feol_bridge.token', 'bridge-test-token');
        [$application] = $this->application();
        $application->feolIntegration()->update([
            'sync_state' => 'synced',
            'last_synced_at' => now(),
            'next_sync_at' => null,
            'sync_requested_at' => null,
        ]);

        $this
            ->withToken('bridge-test-token')
            ->getJson(route('api.integration.feol.pending'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_public_form_saves_crm_before_queuing_partner_submission(): void
    {
        Queue::fake();
        [$application, $token] = $this->application();

        $this->get(route('feol.landing.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Đăng ký khoản vay')
            ->assertSee('images/fe-credit.svg', false)
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

    public function test_partner_submission_is_blocked_without_customer_consent(): void
    {
        [$application] = $this->application();
        $payload = $application->payload;
        data_forget($payload, 'fields.customer_consent');
        $application->forceFill(['payload' => $payload])->save();

        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Hồ sơ chưa có xác nhận đồng ý cung cấp dữ liệu cá nhân.');

        try {
            app(FeolPartnerSubmitter::class)->submit($application);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_duplicate_queue_jobs_can_submit_the_same_lead_only_once(): void
    {
        [$application] = $this->application();
        $application->feolIntegration()->update([
            'submit_state' => FeolSubmitState::QUEUED,
            'submit_attempts' => 0,
        ]);

        $key = '3MQSbZ3xuwbmSHpo';
        $plain = '/landing?cam=fe-cashloan-deeplink&sale=SGBOCTV13765&rid=one-shot';
        config()->set('services.feol_bridge.landing_encrypt_key', $key);
        config()->set('services.feol_bridge.landing_campaign', 'fe-cashloan-deeplink');
        config()->set('services.feol_bridge.landing_sale_code', 'SGBOCTV13765');
        config()->set('services.feol_bridge.partner_landing_url', 'https://os.saigonbpo.vn/landing?data='.rawurlencode((string) openssl_encrypt($plain, 'AES-128-CBC', $key, 0, $key)));
        config()->set('services.feol_bridge.partner_submit_url', 'https://partner.test/landingPageFE/createFEOL');
        Http::fake(['https://partner.test/*' => Http::response(['code' => 200, 'message' => 'OK', 'data' => ['leadId' => 'ONCE-001']])]);

        $submitter = app(FeolPartnerSubmitter::class);
        (new SubmitFeolApplicationToPartner($application->getKey()))->handle($submitter);
        (new SubmitFeolApplicationToPartner($application->getKey()))->handle($submitter);

        Http::assertSentCount(1);
        $this->assertSame(1, $application->feolIntegration()->value('submit_attempts'));
        $this->assertSame('ONCE-001', $application->feolIntegration()->value('partner_lead_id'));
        $this->assertSame(1, (new SubmitFeolApplicationToPartner($application->getKey()))->tries);
    }

    public function test_failed_partner_submission_cannot_be_resubmitted_from_the_same_customer_link(): void
    {
        Queue::fake();
        [$application, $token] = $this->application();
        $application->feolIntegration()->update([
            'submit_state' => FeolSubmitState::FAILED,
            'submit_attempts' => 1,
        ]);

        $this->post(route('feol.landing.store', ['token' => $token]), [
            'applicant_name' => 'Nguyen Van A',
            'phone' => '0901234567',
            'identity_number' => '012345678901',
            'date_of_birth' => '20/05/1995',
            'email' => 'sale@example.com',
            'loan_amount' => 50000000,
            'loan_term_months' => 24,
            'customer_consent' => '1',
        ])->assertConflict();

        Queue::assertNothingPushed();
        $this->assertSame(1, $application->feolIntegration()->value('submit_attempts'));
    }

    public function test_partner_sync_never_overwrites_internal_employee_or_manager_chain(): void
    {
        [$application] = $this->application();
        $manager = User::factory()->create();
        $payload = $application->payload;
        data_set($payload, 'fields.pic', 'PIC nội bộ 3RDVN');
        $application->forceFill([
            'assigned_sale_id' => $application->created_by_id,
            'team_leader_id' => $manager->getKey(),
            'am_id' => $manager->getKey(),
            'zd_id' => $manager->getKey(),
            'payload' => $payload,
        ])->save();

        app(FeolApplicationSync::class)->sync($application, [
            'sub_status' => 'Eligible',
            'pic' => 'Nhân viên đối tác',
            'employee_code' => 'PARTNER-001',
            'manager_id' => 999,
            'raw_payload' => [
                'username' => 'Nhân viên đối tác',
                'manager_name' => 'Quản lý đối tác',
            ],
        ]);

        $application->refresh();
        $this->assertSame($application->created_by_id, $application->assigned_sale_id);
        $this->assertSame($manager->getKey(), $application->team_leader_id);
        $this->assertSame($manager->getKey(), $application->am_id);
        $this->assertSame($manager->getKey(), $application->zd_id);
        $this->assertSame('PIC nội bộ 3RDVN', data_get($application->payload, 'fields.pic'));
        $this->assertSame('Nhân viên đối tác', data_get($application->feolIntegration->raw_payload, 'username'));
    }

    public function test_partner_status_transition_writes_bridge_history_for_the_view(): void
    {
        [$application] = $this->application();

        app(FeolApplicationSync::class)->sync($application, [
            'partner_lead_id' => '504834',
            'main_status' => 'Screening',
            'sub_status' => 'Eligible',
        ]);

        $application->refresh();

        $this->assertSame('eligible', $application->status);
        $this->assertDatabaseHas('record_change_logs', [
            'record_type' => Application::class,
            'record_id' => $application->getKey(),
            'action' => 'feol_synced',
        ]);
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
                'customer_consent' => true,
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
