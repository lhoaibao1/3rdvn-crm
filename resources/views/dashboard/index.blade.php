@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
<div class="grid-kpi">
    <div class="kpi"><span>Tổng hồ sơ</span><strong>{{ $totalProfiles ? number_format($totalProfiles) : '—' }}</strong></div>
    <div class="kpi"><span>Lead mới</span><strong>{{ $newLeads ? number_format($newLeads) : '—' }}</strong></div>
    <div class="kpi"><span>Chờ duyệt</span><strong>{{ $pendingProfiles ? number_format($pendingProfiles) : '—' }}</strong></div>
    <div class="kpi"><span>Đã duyệt</span><strong>{{ $approvedProfiles ? number_format($approvedProfiles) : '—' }}</strong></div>
</div>
<div class="content-grid">
    <section class="panel">
        <div class="panel-head"><div><h2>Hồ sơ mới</h2><p>Theo dõi hồ sơ vừa cập nhật</p></div><a class="primary-btn" href="{{ route('profiles.create') }}">Thêm hồ sơ</a></div>
        @if($profiles->count())
            <div class="table-wrap"><table class="data-table"><thead><tr><th>Khách hàng</th><th>SĐT</th><th>Duyệt</th></tr></thead><tbody>
                @foreach($profiles as $profile)<tr><td><a href="{{ route('profiles.edit', $profile) }}">{{ $profile->customer_name }}</a></td><td>{{ $profile->phone ?: '—' }}</td><td><span class="badge">{{ $profile->approval_status }}</span></td></tr>@endforeach
            </tbody></table></div>
        @else
            <div class="empty"><strong>Chưa có hồ sơ</strong><span>Tạo hồ sơ đầu tiên để bắt đầu nhập liệu.</span></div>
        @endif
    </section>
    <section class="panel">
        <div class="panel-head"><div><h2>Lead mới</h2><p>Nguồn khách hàng cần chăm sóc</p></div><a class="secondary-btn" href="{{ route('leads.index') }}">Xem lead</a></div>
        @if($recentLeads->count())
            <div class="table-wrap"><table class="data-table"><thead><tr><th>Lead</th><th>SĐT</th><th>Trạng thái</th></tr></thead><tbody>
                @foreach($recentLeads as $lead)<tr><td>{{ $lead->lead_name }}</td><td>{{ $lead->phone ?: '—' }}</td><td><span class="badge">{{ $lead->status }}</span></td></tr>@endforeach
            </tbody></table></div>
        @else
            <div class="empty"><strong>Chưa có lead</strong><span>Tạo lead mới để bắt đầu chăm sóc khách hàng.</span></div>
        @endif
    </section>
</div>
@endsection
