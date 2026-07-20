<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        return view('roles.index', ['roles' => Role::query()->withCount('permissions')->orderBy('name')->get()]);
    }

    public function edit(Role $role)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        return view('roles.edit', [
            'role' => $role,
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        $data = $request->validate(['permissions' => ['array']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Đã cập nhật role.');
    }
}
