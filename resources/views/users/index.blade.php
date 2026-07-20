@extends('layouts.app', ['title' => 'Users'])

@section('content')
<section class="panel">
    <div class="panel-head"><div><h2>Users</h2><p>Quản lý tài khoản và phân quyền</p></div><a class="primary-btn" href="{{ route('users.create') }}">Thêm user</a></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Employee</th><th>Roles</th><th></th></tr></thead><tbody>
    @foreach($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->employee_code ?: '—' }}</td><td>{{ $user->getRoleNames()->implode(', ') ?: '—' }}</td><td class="text-right"><a class="secondary-btn" href="{{ route('users.edit', $user) }}">Sửa</a></td></tr>@endforeach
    </tbody></table></div>
</section>
@endsection
