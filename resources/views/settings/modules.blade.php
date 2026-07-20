@extends('layouts.app', ['title' => 'Module Settings'])

@section('content')
<form class="panel" method="POST" action="{{ route('settings.modules.update') }}">
    @csrf @method('PUT')
    <div class="panel-head"><div><h2>Modules</h2><p>Bật, tắt và sắp xếp menu làm việc</p></div><button class="primary-btn">Lưu modules</button></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Active</th><th>Label</th><th>Icon</th><th>Order</th><th>Roles CSV</th><th>Permissions CSV</th></tr></thead><tbody>
    @foreach($modules as $module)
        <tr>
            <td><input type="hidden" name="modules[{{ $loop->index }}][id]" value="{{ $module->id }}"><input type="checkbox" name="modules[{{ $loop->index }}][is_active]" value="1" @checked($module->is_active)></td>
            <td><input class="form-control" name="modules[{{ $loop->index }}][label]" value="{{ $module->label }}"></td>
            <td><input class="form-control" name="modules[{{ $loop->index }}][icon]" value="{{ $module->icon }}"></td>
            <td><input class="form-control" type="number" name="modules[{{ $loop->index }}][sort_order]" value="{{ $module->sort_order }}"></td>
            <td><input class="form-control" name="modules[{{ $loop->index }}][required_roles]" value="{{ implode(',', $module->required_roles ?? []) }}"></td>
            <td><input class="form-control" name="modules[{{ $loop->index }}][required_permissions]" value="{{ implode(',', $module->required_permissions ?? []) }}"></td>
        </tr>
    @endforeach
    </tbody></table></div>
</form>
@endsection
