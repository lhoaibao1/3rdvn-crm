@extends('layouts.app', ['title' => 'Roles'])

@section('content')
<section class="panel">
    <div class="panel-head"><div><h2>Roles</h2><p>Core roles không nên xóa</p></div></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Role</th><th>Permissions</th><th></th></tr></thead><tbody>
    @foreach($roles as $role)<tr><td>{{ $role->name }}</td><td>{{ $role->permissions_count }}</td><td class="text-right"><a class="secondary-btn" href="{{ route('roles.edit', $role) }}">Sửa quyền</a></td></tr>@endforeach
    </tbody></table></div>
</section>
@endsection
