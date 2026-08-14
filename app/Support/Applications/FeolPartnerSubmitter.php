<?php

namespace App\Support\Applications;

use App\Models\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FeolPartnerSubmitter
{
    public function __construct(private readonly FeolPartnerLanding $landing) {}

    /**
     * @throws ConnectionException|RequestException
     */
    public function submit(Application $application): array
    {
        $application->loadMissing(['feolIntegration', 'createdBy']);
        $integration = $application->feolIntegration;

        if (! $integration) {
            throw new RuntimeException('Hồ sơ chưa có cấu hình tích hợp FEOL.');
        }

        $fields = data_get($application->payload, 'fields', []);
        $response = Http::asJson()
            ->acceptJson()
            ->timeout((int) config('services.feol_bridge.partner_timeout_seconds', 20))
            ->retry(2, 750, throw: false)
            ->post((string) config('services.feol_bridge.partner_submit_url'), [
                'customer_name' => $application->applicant_name,
                'customer_phone' => $application->phone,
                'id_card_no' => $application->identity_number,
                'salesman' => (string) (data_get($fields, 'salesman_code') ?: config('services.feol_bridge.landing_sale_code')),
                'request_id' => $integration->partner_request_id,
                'request_time' => now()->toIso8601String(),
                'referralCode' => data_get($fields, 'referral_code'),
                'consent_tickbox' => 'YES',
                'consent_content' => FeolConsent::TEXT,
                'cam_url' => (string) config('services.feol_bridge.landing_campaign'),
                'original_unique_url' => $this->landing->originalUrl(),
                'encrypt_unique_url' => $this->landing->encryptedUrl(),
                'dob' => data_get($fields, 'date_of_birth'),
                'customer_email' => data_get($fields, 'email'),
                'loan_amount' => (int) data_get($fields, 'loan_amount'),
                'tenor' => (int) data_get($fields, 'loan_term_months'),
            ]);

        $response->throw();
        $body = $response->json();

        if (! is_array($body) || (int) ($body['code'] ?? 0) !== 200) {
            throw new RuntimeException((string) ($body['message'] ?? 'Đối tác từ chối hồ sơ FEOL.'));
        }

        return $body;
    }
}
