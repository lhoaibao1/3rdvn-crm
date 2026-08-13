<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EssAuthenticationController extends Controller
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function __invoke(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.vpn_directory.token', '');
        $providedToken = (string) $request->bearerToken();

        abort_unless(
            $expectedToken !== ''
                && $providedToken !== ''
                && hash_equals($expectedToken, $providedToken),
            401,
            'Invalid integration token.',
        );

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $identifier = mb_strtolower(trim($validated['identifier']));
        $user = User::query()
            ->with('roles:id,name')
            ->where(function ($query) use ($identifier): void {
                $query
                    ->whereRaw('LOWER(uid) = ?', [$identifier])
                    ->orWhereRaw('LOWER(employee_code) = ?', [$identifier])
                    ->orWhereRaw('LOWER(username) = ?', [$identifier])
                    ->orWhereRaw('LOWER(email) = ?', [$identifier]);
            })
            ->first();

        $passwordHash = $user?->password ?: self::DUMMY_PASSWORD_HASH;
        $passwordValid = Hash::check($validated['password'], $passwordHash);
        $inactive = $user && in_array($user->employment_status, [
            'inactive',
            User::STATUS_DEACTIVE,
            'resigned',
            User::STATUS_DELETED,
        ], true);

        if (! $user || ! $passwordValid || $inactive) {
            return response()->json([
                'message' => 'Thông tin đăng nhập không đúng hoặc tài khoản đã ngừng hoạt động.',
            ], 401);
        }

        return response()->json([
            'data' => [
                'id' => (string) $user->getKey(),
                'uid' => $user->uid,
                'employee_code' => $user->employee_code,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'position' => $user->position,
                'department' => $user->department,
                'company' => $user->company_name,
                'branch' => $user->branch_name,
                'status' => $user->employment_status ?: User::STATUS_ACTIVE,
                'roles' => $user->roles->pluck('name')->values(),
            ],
        ]);
    }
}
