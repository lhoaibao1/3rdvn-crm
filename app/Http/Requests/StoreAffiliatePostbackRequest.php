<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StoreAffiliatePostbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $configured = (string) config('services.affiliate.postback_secret');
        $provided = (string) $this->header('X-Affiliate-Secret', $this->input('secret', ''));

        return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach ($this->all() as $key => $value) {
            $normalized[$key] = is_string($value) && trim($value) === '' ? null : $value;
        }

        foreach (['click_time', 'conversion_time', 'conversion_modified_time', 'conversion_status_updated_time'] as $field) {
            $value = $normalized[$field] ?? null;
            if (is_numeric($value) && (int) $value > 10_000_000_000) {
                $normalized[$field] = CarbonImmutable::createFromTimestampMs((int) $value)
                    ->setTimezone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'))
                    ->toDateTimeString();
            }
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'conversion_id' => ['required', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'click_id' => ['nullable', 'string', 'max:255'],
            'offer_id' => ['nullable', 'string', 'max:255'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'conversion_status' => ['nullable', 'string', 'max:100'],
            'conversion_status_code' => ['nullable', 'string', 'max:100'],
            'conversion_sale_amount' => ['nullable', 'numeric', 'min:0'],
            'conversion_publisher_payout' => ['nullable', 'numeric', 'min:0'],
            'click_time' => ['nullable', 'date'],
            'conversion_time' => ['nullable', 'date'],
            'conversion_modified_time' => ['nullable', 'date'],
            'conversion_status_updated_time' => ['nullable', 'date'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'product_url' => ['nullable', 'string', 'max:2000'],
            'product_sku' => ['nullable', 'string', 'max:255'],
            'product_category_id' => ['nullable', 'string', 'max:255'],
            'product_category' => ['nullable', 'string', 'max:255'],
            'aff_sub1' => ['nullable', 'string', 'max:255'],
            'aff_sub2' => ['nullable', 'string', 'max:255'],
            'aff_sub3' => ['nullable', 'string', 'max:255'],
            'aff_sub4' => ['nullable', 'string', 'max:255'],
            'landing_page' => ['nullable', 'string', 'max:2000'],
            'events' => ['nullable', 'string', 'max:255'],
            'status_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
