@extends('layouts.app', ['title' => 'Chi tiết Lead'])

@section('content')
<section class="form-card">
    <h2>{{ $lead->lead_name }}</h2>
    <p>Dự án: {{ $lead->salesProject?->name ?: '-' }}</p>
    <p>SĐT: {{ $lead->phone ?: '-' }}</p>
    <p>Email: {{ $lead->email ?: '-' }}</p>
    <p>Nguồn: {{ $lead->source ?: '-' }}</p>
    <p>Trạng thái: <span class="badge">{{ $lead->status }}</span></p>
    <div class="actions"><a class="secondary-btn" href="{{ route('leads.index') }}">Quay lại</a><a class="primary-btn" href="{{ route('leads.edit', $lead) }}">Sửa</a></div>
</section>
@endsection
