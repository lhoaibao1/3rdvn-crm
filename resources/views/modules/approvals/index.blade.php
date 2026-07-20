@extends('layouts.app', ['title' => 'Phê duyệt'])

@section('content')
<section class="panel">
    <div class="panel-head"><div><h2>Hồ sơ chờ duyệt</h2><p>Reject bắt buộc nhập lý do</p></div></div>
    @if($profiles->count())
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Khách hàng</th><th>SĐT</th><th></th></tr></thead><tbody>
        @foreach($profiles as $profile)
            <tr><td>{{ $profile->customer_name }}</td><td>{{ $profile->phone ?: '—' }}</td><td class="text-right"><form method="POST" action="{{ route('profiles.approve', $profile) }}" style="display:inline">@csrf<button class="primary-btn">Duyệt</button></form><form method="POST" action="{{ route('profiles.reject', $profile) }}" style="display:inline">@csrf<input name="rejection_reason" placeholder="Lý do" required><button class="danger-btn">Từ chối</button></form></td></tr>
        @endforeach
        </tbody></table></div>
    @else
        <div class="empty"><strong>Không có hồ sơ chờ duyệt</strong><span>Hồ sơ được sale gửi duyệt sẽ xuất hiện ở đây.</span></div>
    @endif
</section>
@endsection
