<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VpnUserDirectoryController extends Controller
{
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
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 20);

        $users = User::query()
            ->select([
                'id', 'uid', 'employee_code', 'username', 'name', 'email', 'phone',
                'position', 'department', 'company_name', 'branch_name', 'employment_status',
            ])
            ->with('roles:id,name')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('uid', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $user): array => [
                'uid' => $user->uid ?: $user->employee_code ?: (string) $user->id,
                'employee_code' => $user->employee_code,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->roles->pluck('name')->first(),
                'position' => $user->position,
                'department' => $user->department,
                'company' => $user->company_name,
                'branch' => $user->branch_name,
                'status' => $user->employment_status ?: User::STATUS_ACTIVE,
            ]);

        return response()->json(['data' => $users]);
    }
}
