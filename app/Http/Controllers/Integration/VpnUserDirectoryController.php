<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\EmployeeDirectoryRequest;
use App\Http\Resources\Integration\EmployeeDirectoryResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VpnUserDirectoryController extends Controller
{
    public function __invoke(EmployeeDirectoryRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $search = trim((string) ($validated['q'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? $validated['limit'] ?? 100);

        $users = User::query()
            ->with([
                'roles:id,name', 'team:id,name',
                'teamLeader:id,uid,employee_code,name', 'courierManager:id,uid,employee_code,name',
                'am:id,uid,employee_code,name', 'zd:id,uid,employee_code,name',
                'creator:id,uid,employee_code,name',
            ])
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
            ->where(fn (Builder $query) => $query
                ->where('employment_status', User::STATUS_ACTIVE)
                ->orWhereNull('employment_status'))
            ->whereDoesntHave('roles', fn (Builder $query) => $query
                ->whereRaw('LOWER(name) = ?', ['courier']))
            ->when(filled($validated['status'] ?? null), fn (Builder $query) => $query->where('employment_status', $validated['status']))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return EmployeeDirectoryResource::collection($users);
    }
}
