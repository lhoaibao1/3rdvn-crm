@extends('layouts.app', ['title' => 'Hồ sơ'])

@section('content')
<section class="panel">
    <div class="panel-head"><div><h2>Hồ sơ sale</h2><p>CRM nhập liệu và phê duyệt</p></div><a class="primary-btn" href="{{ route('profiles.create') }}">Thêm hồ sơ</a></div>
    @if($profiles->count())
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Khách hàng</th><th>SĐT</th><th>Trạng thái</th><th>Duyệt</th><th></th></tr></thead><tbody>
        @foreach($profiles as $profile)
            <tr><td>{{ $profile->customer_name }}</td><td>{{ $profile->phone ?: '—' }}</td><td>{{ $profile->status }}</td><td><span class="badge">{{ $profile->approval_status }}</span></td><td class="text-right"><a class="secondary-btn" href="{{ route('profiles.edit', $profile) }}">Sửa</a>@if(in_array($profile->approval_status, ['Chưa gửi','Từ chối']))<form method="POST" action="{{ route('profiles.submit', $profile) }}" style="display:inline">@csrf<button class="primary-btn">Gửi duyệt</button></form>@endif</td></tr>
        @endforeach
        </tbody></table></div>
    @else
        <div class="empty"><strong>Chưa có hồ sơ</strong><span>Tạo hồ sơ mới để bắt đầu.</span></div>
    @endif
</section>
@endsection
