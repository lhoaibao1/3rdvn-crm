<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

class SyncFeolApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partner_lead_id' => ['nullable', 'string', 'max:100'],
            'partner_app_id' => ['nullable', 'string', 'max:100'],
            'main_status' => ['nullable', 'string', 'max:100'],
            'sub_status' => ['nullable', 'string', 'max:100'],
            'b1_url' => ['nullable', 'url:http,https', 'max:4000'],
            'deeplink_url' => ['nullable', 'url:http,https', 'max:4000'],
            'product' => ['nullable', 'string', 'max:100'],
            'offer_amount' => ['nullable', 'integer', 'min:0'],
            'disbursed_amount' => ['nullable', 'integer', 'min:0'],
            'topup_amount' => ['nullable', 'integer', 'min:0'],
            'insurance_amount' => ['nullable', 'integer', 'min:0'],
            'fee_amount' => ['nullable', 'integer', 'min:0'],
            'disbursed_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'error' => ['nullable', 'string', 'max:2000'],
            'raw_payload' => ['nullable', 'array'],
        ];
    }
}
