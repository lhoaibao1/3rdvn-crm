<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expected = (string) config('services.vpn_directory.token', '');
        $provided = (string) $this->bearerToken();

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
