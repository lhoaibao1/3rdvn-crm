@extends('layouts.app', ['title' => 'Chi tiết API Mapping'])

@section('content')
<section class="form-card">
    <h2>{{ $mapping->mapping_name }}</h2>
    <p>System: {{ $mapping->target_system }}</p>
    <p>Endpoint: {{ $mapping->endpoint_url ?: '—' }}</p>
    <p>Method: <span class="badge">{{ $mapping->method }}</span></p>
    <div class="actions"><a class="secondary-btn" href="{{ route('api-mappings.index') }}">Quay lại</a><a class="primary-btn" href="{{ route('api-mappings.edit', $mapping) }}">Sửa</a></div>
</section>
@endsection
