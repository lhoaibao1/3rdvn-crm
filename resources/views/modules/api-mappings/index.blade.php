@extends('layouts.app', ['title' => 'API Mapping'])

@section('content')
<section class="panel">
    <div class="panel-head"><div><h2>API Mapping</h2><p>Cấu hình endpoint để CRM kéo/đẩy dữ liệu sau này</p></div><a class="primary-btn" href="{{ route('api-mappings.create') }}">Thêm mapping</a></div>
    @if($mappings->count())
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Tên</th><th>Hệ thống</th><th>Method</th><th>Active</th><th></th></tr></thead><tbody>
        @foreach($mappings as $mapping)<tr><td>{{ $mapping->mapping_name }}</td><td>{{ $mapping->target_system }}</td><td>{{ $mapping->method }}</td><td><span class="badge">{{ $mapping->is_active ? 'Có' : 'Không' }}</span></td><td class="text-right"><a class="secondary-btn" href="{{ route('api-mappings.edit', $mapping) }}">Sửa</a></td></tr>@endforeach
        </tbody></table></div>
    @else
        <div class="empty"><strong>Chưa có API Mapping</strong><span>Tạo mapping khi cần kết nối hệ thống ngoài.</span></div>
    @endif
</section>
@endsection
