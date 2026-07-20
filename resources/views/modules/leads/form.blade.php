@extends('layouts.app', ['title' => $lead->exists ? 'Sửa Lead' : 'Thêm Lead'])

@section('content')
<form class="form-card" method="POST" action="{{ $lead->exists ? route('leads.update', $lead) : route('leads.store') }}">
    @csrf @if($lead->exists) @method('PUT') @endif
    <div class="form-grid">
        @php($salesProjects = $salesProjects ?? [])
        @if(count($salesProjects) > 0 || $lead->sales_project_id)
            <label class="field">
                <span>Dự án b?n h?ng</span>
                <select name="sales_project_id" required>
                    <option value="">Chọn dự án</option>
                    @foreach($salesProjects as $projectId => $projectName)
                        <option value="{{ $projectId }}" @selected((string) old('sales_project_id', $lead->sales_project_id) === (string) $projectId)>{{ $projectName }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <label class="field"><span>Tên lead</span><input name="lead_name" value="{{ old('lead_name', $lead->lead_name) }}" required></label>
        <label class="field"><span>SĐT</span><input name="phone" value="{{ old('phone', $lead->phone) }}"></label>
        <label class="field"><span>Email</span><input name="email" value="{{ old('email', $lead->email) }}"></label>
        <label class="field"><span>Nguồn</span><input name="source" value="{{ old('source', $lead->source) }}"></label>
        <label class="field full"><span>Ghi chú</span><textarea name="note">{{ old('note', $lead->note) }}</textarea></label>
    </div>
    <div class="actions"><a class="secondary-btn" href="{{ route('leads.index') }}">Hủy</a><button class="primary-btn">Lưu</button></div>
</form>
@endsection
