@extends('layouts.app', ['title' => 'Lead'])

@section('content')
<section class="panel">
    <div class="panel-head"><div><h2>Lead</h2><p>Danh sách khách hàng tiềm năng</p></div><a class="primary-btn" href="{{ route('leads.create') }}">Thêm lead</a></div>
    @if($leads->count())
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Dự án</th><th>Tên</th><th>SĐT</th><th>Trạng thái</th><th></th></tr></thead><tbody>
        @foreach($leads as $lead)
            <tr><td>{{ $lead->salesProject?->name ?: '-' }}</td><td>{{ $lead->lead_name }}</td><td>{{ $lead->phone ?: '-' }}</td><td><span class="badge">{{ $lead->status }}</span></td><td class="text-right"><a class="secondary-btn" href="{{ route('leads.edit', $lead) }}">Sửa</a>@if(!$lead->converted_sale_profile_id)<form method="POST" action="{{ route('leads.convert', $lead) }}" style="display:inline">@csrf<button class="primary-btn">Convert</button></form>@endif</td></tr>
        @endforeach
        </tbody></table></div>
    @else
        <div class="empty"><strong>Chưa có lead</strong><span>Tạo lead mới để bắt đầu.</span></div>
    @endif
</section>
@endsection
