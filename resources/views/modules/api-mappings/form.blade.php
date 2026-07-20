@extends('layouts.app', ['title' => $mapping->exists ? 'Sửa API Mapping' : 'Thêm API Mapping'])

@section('content')
<form class="form-card" method="POST" action="{{ $mapping->exists ? route('api-mappings.update', $mapping) : route('api-mappings.store') }}">
    @csrf @if($mapping->exists) @method('PUT') @endif
    <div class="form-grid">
        <label class="field"><span>Tên mapping</span><input name="mapping_name" value="{{ old('mapping_name', $mapping->mapping_name) }}" required></label>
        <label class="field"><span>Hệ thống</span><input name="target_system" value="{{ old('target_system', $mapping->target_system) }}" required></label>
        <label class="field full"><span>Endpoint URL</span><input name="endpoint_url" value="{{ old('endpoint_url', $mapping->endpoint_url) }}"></label>
        <label class="field"><span>Method</span><select name="method">@foreach(['GET','POST','PUT','PATCH','DELETE'] as $method)<option @selected(old('method', $mapping->method ?: 'POST') === $method)>{{ $method }}</option>@endforeach</select></label>
        <label class="field"><span>Auth type</span><select name="auth_type">@foreach(['None','Bearer Token','Basic','API Key'] as $type)<option @selected(old('auth_type', $mapping->auth_type ?: 'None') === $type)>{{ $type }}</option>@endforeach</select></label>
        <label class="checkbox full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $mapping->is_active))><span>Active</span></label>
        <label class="field full"><span>Headers JSON</span><textarea name="request_headers_json">{{ old('request_headers_json', $mapping->request_headers_json) }}</textarea></label>
        <label class="field full"><span>Field mapping JSON</span><textarea name="field_mapping_json">{{ old('field_mapping_json', $mapping->field_mapping_json) }}</textarea></label>
        <label class="field full"><span>Ghi chú</span><textarea name="note">{{ old('note', $mapping->note) }}</textarea></label>
    </div>
    <div class="actions"><a class="secondary-btn" href="{{ route('api-mappings.index') }}">Hủy</a><button class="primary-btn">Lưu</button></div>
</form>
@endsection
