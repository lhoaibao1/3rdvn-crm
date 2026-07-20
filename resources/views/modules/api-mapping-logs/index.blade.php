@extends('layouts.app', ['title' => 'API Logs'])

@section('content')
<section class="panel">
    <div class="panel-head"><div><h2>API Mapping Logs</h2><p>Append-only logs</p></div></div>
    @if($logs->count())
        <div class="table-wrap"><table class="data-table"><thead><tr><th>System</th><th>Endpoint</th><th>Result</th><th>Time</th></tr></thead><tbody>
        @foreach($logs as $log)<tr><td>{{ $log->target_system }}</td><td>{{ $log->endpoint_url }}</td><td>{{ $log->result }}</td><td>{{ $log->created_at }}</td></tr>@endforeach
        </tbody></table></div>
    @else
        <div class="empty"><strong>Chưa có log</strong><span>Log sẽ sinh khi service API được gọi.</span></div>
    @endif
</section>
@endsection
