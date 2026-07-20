<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\ApiMapping;
use Illuminate\Http\Request;

class ApiMappingController extends Controller
{
    public function index()
    {
        return view('modules.api-mappings.index', ['mappings' => ApiMapping::latest()->paginate(20)]);
    }

    public function create()
    {
        return view('modules.api-mappings.form', ['mapping' => new ApiMapping()]);
    }

    public function store(Request $request)
    {
        ApiMapping::create($this->validateMapping($request));
        return redirect()->route('api-mappings.index')->with('success', 'Đã tạo API Mapping.');
    }

    public function edit(ApiMapping $api_mapping)
    {
        return view('modules.api-mappings.form', ['mapping' => $api_mapping]);
    }

    public function show(ApiMapping $api_mapping)
    {
        return view('modules.api-mappings.show', ['mapping' => $api_mapping]);
    }

    public function update(Request $request, ApiMapping $api_mapping)
    {
        $api_mapping->update($this->validateMapping($request));
        return redirect()->route('api-mappings.index')->with('success', 'Đã cập nhật API Mapping.');
    }

    public function destroy(ApiMapping $api_mapping)
    {
        $api_mapping->delete();
        return redirect()->route('api-mappings.index')->with('success', 'Đã xóa API Mapping.');
    }

    private function validateMapping(Request $request): array
    {
        return $request->validate([
            'mapping_name' => ['required', 'string', 'max:255'],
            'target_system' => ['required', 'string', 'max:255'],
            'endpoint_url' => ['nullable', 'url'],
            'method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE'],
            'auth_type' => ['required', 'in:None,Bearer Token,Basic,API Key'],
            'request_headers_json' => ['nullable', 'json'],
            'field_mapping_json' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);
    }
}
