<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        return view('users.index', ['users' => User::query()->latest()->paginate(20)]);
    }

    public function create()
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        return view('users.form', ['user' => new User(), 'roles' => Role::query()->orderBy('name')->get(), 'teams' => CrmTeam::query()->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'employee_code' => ['nullable', 'string', 'max:80', 'unique:users,employee_code'],
            'phone' => ['nullable', 'string', 'max:50'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
            'roles' => ['array'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::query()->create($data);
        $user->syncRoles($data['roles'] ?? []);

        return redirect()->route('users.index')->with('success', 'Đã tạo user.');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        return view('users.form', ['user' => $user, 'roles' => Role::query()->orderBy('name')->get(), 'teams' => CrmTeam::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'employee_code' => ['nullable', 'string', 'max:80', 'unique:users,employee_code,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
            'roles' => ['array'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles($data['roles'] ?? []);

        return redirect()->route('users.index')->with('success', 'Đã cập nhật user.');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);
        abort_if($user->is(auth()->user()), 422, 'Không thể xóa chính bạn.');
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa user.');
    }
}
