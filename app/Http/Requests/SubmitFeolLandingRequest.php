<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFeolLandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'applicant_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'identity_number' => ['required', 'regex:/^\d{12}$/'],
            'date_of_birth' => ['required', 'date_format:d/m/Y', 'before_or_equal:today'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'loan_amount' => ['required', 'integer', 'min:1000000', 'max:1000000000'],
            'loan_term_months' => ['required', 'integer', 'min:1', 'max:120'],
            'referral_code' => ['nullable', 'regex:/^\d{5}$/'],
            'customer_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại phải gồm 10 số và bắt đầu bằng 0.',
            'identity_number.regex' => 'Số CCCD phải gồm đúng 12 số.',
            'date_of_birth.date_format' => 'Ngày sinh phải theo định dạng ngày/tháng/năm.',
            'referral_code.regex' => 'Mã giới thiệu phải gồm đúng 5 số.',
            'customer_consent.accepted' => 'Khách hàng phải đồng ý trước khi gửi đăng ký.',
        ];
    }
}
