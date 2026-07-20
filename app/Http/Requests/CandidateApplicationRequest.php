<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'website' => ['nullable', 'max:0'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'regex:/^(?:\+?84|0)(?:\d[ .-]?){8,10}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'job_vacancy_id' => ['required', 'integer', Rule::exists('job_vacancies', 'id')->whereNull('deleted_at')],
            'current_position' => ['nullable', 'string', 'max:150'],
            'latest_company' => ['nullable', 'string', 'max:190'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'education_level' => ['nullable', 'string', 'max:100'],
            'expected_salary' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'available_from' => ['nullable', 'date'],
            'address_line' => ['nullable', 'string', 'max:500'],
            'province_code' => ['required', 'string', 'max:20'],
            'district_code' => ['required', 'string', 'max:20'],
            'ward_code' => ['required', 'string', 'max:20'],
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.max' => 'Yêu cầu không hợp lệ.',
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại.',
            'job_vacancy_id.required' => 'Vui lòng chọn vị trí ứng tuyển.',
            'job_vacancy_id.exists' => 'Vị trí tuyển dụng không tồn tại hoặc đã bị gỡ.',
            'province_code.required' => 'Vui lòng chọn tỉnh/thành phố.',
            'district_code.required' => 'Vui lòng chọn quận/huyện.',
            'ward_code.required' => 'Vui lòng chọn phường/xã.',
            'cv.required' => 'Vui lòng đính kèm CV.',
            'cv.mimes' => 'CV chỉ chấp nhận PDF, DOC hoặc DOCX.',
            'cv.max' => 'CV không được vượt quá 10 MB.',
            'consent.accepted' => 'Bạn cần đồng ý chính sách xử lý dữ liệu để gửi hồ sơ.',
        ];
    }
}
