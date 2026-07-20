@extends('layouts.app', ['title' => 'Sửa Role'])

@section('content')
<form class="form-card" method="POST" action="{{ route('roles.update', $role) }}">
    @csrf @method('PUT')
    <h2>{{ $role->name }}</h2>
    <div class="form-grid">
        @foreach($permissions as $permission)
            <label class="checkbox"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->hasPermissionTo($permission->name))><span>{{ $permission->name }}</span></label>
        @endforeach
    </div>
    <div class="actions"><a class="secondary-btn" href="{{ route('roles.index') }}">Hủy</a><button class="primary-btn">Lưu quyền</button></div>
</form>
@endsection
