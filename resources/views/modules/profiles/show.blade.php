@extends('layouts.app', ['title' => 'Chi tiết Hồ sơ'])

@section('content')
<section class="form-card">
    <h2>{{ $profile->customer_name }}</h2>
    <p>SĐT: {{ $profile->phone ?: '—' }}</p>
    <p>Email: {{ $profile->email ?: '—' }}</p>
    <p>Sản phẩm: {{ $profile->product_interest ?: '—' }}</p>
    <p>Duyệt: <span class="badge">{{ $profile->approval_status }}</span></p>
    <div class="actions"><a class="secondary-btn" href="{{ route('profiles.index') }}">Quay lại</a><a class="primary-btn" href="{{ route('profiles.edit', $profile) }}">Sửa</a></div>
</section>
@endsection
