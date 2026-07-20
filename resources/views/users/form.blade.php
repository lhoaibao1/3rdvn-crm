@extends('layouts.app', ['title' => $user->exists ? 'Sửa User' : 'Thêm User'])

@section('content')
<form class="form-card" method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
    @csrf @if($user->exists) @method('PUT') @endif
    <div class="form-grid">
        <label class="field"><span>Name</span><input name="name" value="{{ old('name', $user->name) }}" required></label>
        <label class="field"><span>Email</span><input name="email" value="{{ old('email', $user->email) }}" required></label>
        <label class="field"><span>Password</span><input type="password" name="password" @if(!$user->exists) required @endif></label>
        <label class="field"><span>Employee code</span><input name="employee_code" value="{{ old('employee_code', $user->employee_code) }}"></label>
        <label class="field"><span>Phone</span><input name="phone" value="{{ old('phone', $user->phone) }}"></label>
        <label class="field"><span>Team</span><select name="team_id"><option value="">—</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected(old('team_id', $user->team_id) == $team->id)>{{ $team->name }}</option>@endforeach</select></label>
        <div class="field full"><span>Roles</span>@foreach($roles as $role)<label class="checkbox"><input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($user->hasRole($role->name))><span>{{ $role->name }}</span></label>@endforeach</div>
    </div>
    <div class="actions"><a class="secondary-btn" href="{{ route('users.index') }}">Hủy</a><button class="primary-btn">Lưu</button></div>
</form>
@endsection
