@extends('recruitment.layout')
@section('title','Đã nhận hồ sơ')
@section('content')
<section class="card success"><div class="success-icon">✓</div><h1>Đã tiếp nhận hồ sơ</h1><p>Cảm ơn bạn đã quan tâm đến cơ hội nghề nghiệp tại 3RDVN. Bộ phận tuyển dụng sẽ xem xét và liên hệ nếu hồ sơ phù hợp.</p>@if($applicationCode)<div class="code">Mã hồ sơ: {{ $applicationCode }}</div>@endif<div><a class="back" href="{{ route('recruitment.apply') }}">Quay lại trang ứng tuyển</a></div></section>
@endsection
