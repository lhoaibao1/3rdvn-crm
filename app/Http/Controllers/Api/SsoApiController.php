<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RoleHierarchy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SsoApiController extends Controller
{
    /**
     * Authenticate SSO request for specific or general application portal
     */
    public function login(Request $request): JsonResponse
    {
        $identifier = trim((string) $request->input('identifier', ''));
        $password = (string) $request->input('password', '');
        $requestedApp = strtolower(trim((string) $request->input('app', '')));

        if ($identifier === '' || $password === '') {
            return $this->respondJson([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
                'message' => 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.',
            ], 422);
        }

        $user = $this->findUserByIdentifier($identifier);

        if (! $user) {
            return $this->respondJson([
                'success' => false,
                'error_code' => 'USER_NOT_FOUND',
                'message' => 'Tài khoản hoặc mật khẩu không chính xác.',
            ], 401);
        }

        if (! Hash::check($password, $user->password)) {
            return $this->respondJson([
                'success' => false,
                'error_code' => 'WRONG_PASSWORD',
                'message' => 'Tài khoản hoặc mật khẩu không chính xác.',
            ], 401);
        }

        if (in_array($user->employment_status, ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED], true)) {
            return $this->respondJson([
                'success' => false,
                'error_code' => 'ACCOUNT_DISABLED',
                'message' => 'Tài khoản này đang bị tạm khóa hoặc đã ngừng hoạt động.',
            ], 403);
        }

        // Validate App Permission
        if ($requestedApp !== '') {
            if (! $user->canAccessApp($requestedApp)) {
                $appName = match ($requestedApp) {
                    'crm' => 'CRM Core',
                    'los' => 'Quản lý hồ sơ LOS',
                    'affiliate' => 'Hub Tiếp Thị Liên Kết',
                    'ess' => 'Cổng Nhân Sự ESS',
                    default => strtoupper($requestedApp),
                };

                return $this->respondJson([
                    'success' => false,
                    'error_code' => 'APP_ACCESS_DENIED',
                    'message' => "Tài khoản của bạn chưa được cấp quyền truy cập cổng {$appName}.",
                ], 403);
            }
        }

        // Generate SSO token
        $token = $this->issueSsoToken($user);
        $roleName = RoleHierarchy::primaryRole($user) ?: ($user->getRoleNames()->first() ?: 'Nhân viên');
        $allowedApps = $user->getAllowedApps();

        return $this->respondJson([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'employee_code' => $user->employee_code,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $roleName,
                'roles' => $user->getRoleNames()->values()->all(),
                'department' => $user->department,
                'position' => $user->position,
                'allowed_apps' => $allowedApps,
                'is_admin' => $user->hasRole('Admin') || $user->hasRole('Super Admin'),
                'team' => $user->team ? [
                    'id' => $user->team->id,
                    'name' => $user->team->name,
                ] : null,
            ],
            'apps' => $this->getAvailableAppsMeta($allowedApps),
        ]);
    }

    /**
     * Verify SSO token and retrieve authenticated user
     */
    public function verify(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return $this->respondJson(['success' => false, 'message' => 'Thiếu token xác thực'], 401);
        }

        $user = $this->findUserByToken($token);

        if (! $user) {
            return $this->respondJson(['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn'], 401);
        }

        if (in_array($user->employment_status, ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED], true)) {
            return $this->respondJson(['success' => false, 'message' => 'Tài khoản đã bị tạm khóa'], 403);
        }

        $roleName = RoleHierarchy::primaryRole($user) ?: ($user->getRoleNames()->first() ?: 'Nhân viên');
        $allowedApps = $user->getAllowedApps();

        return $this->respondJson([
            'success' => true,
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'employee_code' => $user->employee_code,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $roleName,
                'roles' => $user->getRoleNames()->values()->all(),
                'allowed_apps' => $allowedApps,
                'is_admin' => $user->hasRole('Admin') || $user->hasRole('Super Admin'),
                'team' => $user->team ? [
                    'id' => $user->team->id,
                    'name' => $user->team->name,
                ] : null,
            ],
            'apps' => $this->getAvailableAppsMeta($allowedApps),
        ]);
    }

    /**
     * Get Apps metadata based on user's allowed apps
     */
    protected function getAvailableAppsMeta(array $allowedApps): array
    {
        $allApps = [
            'crm' => [
                'id' => 'crm',
                'name' => 'CRM Core',
                'description' => 'Hệ thống Quản Trị Khách Hàng & Vận Hành',
                'url' => 'https://apps2.3rdvn.io.vn',
                'icon' => 'building',
                'allowed' => in_array('crm', $allowedApps, true),
            ],
            'los' => [
                'id' => 'los',
                'name' => 'Quản Lý Hồ Sơ LOS',
                'description' => 'Theo dõi hồ sơ & kết quả thẩm định',
                'url' => 'https://los.3rdvn.io.vn',
                'icon' => 'document-text',
                'allowed' => in_array('los', $allowedApps, true),
            ],
            'affiliate' => [
                'id' => 'affiliate',
                'name' => 'Hub Tiếp Thị Liên Kết',
                'description' => 'Lấy link chiến dịch, tracking click & hoa hồng',
                'url' => 'https://pub2-aff.3rdvn.io.vn',
                'icon' => 'globe-alt',
                'allowed' => in_array('affiliate', $allowedApps, true),
            ],
            'ess' => [
                'id' => 'ess',
                'name' => 'Cổng Nhân Sự ESS',
                'description' => 'Cổng tự phục vụ nhân viên, bảng lương & ngày phép',
                'url' => 'https://ess.3rdvn.io.vn',
                'icon' => 'user-group',
                'allowed' => in_array('ess', $allowedApps, true),
            ],
        ];

        return array_values($allApps);
    }

    /**
     * Case-insensitive lookup by email, employee_code, username, uid, or phone
     */
    protected function findUserByIdentifier(string $identifier): ?User
    {
        $clean = trim($identifier);
        $cleanLower = strtolower($clean);

        return User::query()
            ->where(function ($query) use ($clean, $cleanLower) {
                $query->whereRaw('LOWER(email) = ?', [$cleanLower])
                    ->orWhereRaw('LOWER(employee_code) = ?', [$cleanLower])
                    ->orWhereRaw('LOWER(username) = ?', [$cleanLower])
                    ->orWhereRaw('LOWER(uid) = ?', [$cleanLower])
                    ->orWhere('phone', $clean)
                    ->orWhere('identity_number', $clean);
            })
            ->first();
    }

    protected function issueSsoToken(User $user): string
    {
        if (method_exists($user, 'createToken')) {
            try {
                return $user->createToken('sso_portal_access')->plainTextToken;
            } catch (\Throwable $e) {}
        }

        $payload = [
            'uid' => $user->id,
            'code' => $user->employee_code,
            't' => time(),
            'r' => Str::random(16),
        ];

        return base64_encode(json_encode($payload)) . '.' . hash_hmac('sha256', json_encode($payload), config('app.key') ?: '3rdvn_sso_secret');
    }

    protected function extractToken(Request $request): ?string
    {
        $auth = $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return trim(substr($auth, 7));
        }

        return $request->query('token') ?: $request->input('token');
    }

    protected function findUserByToken(string $token): ?User
    {
        // Try Sanctum PersonalAccessToken
        if (class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
            $model = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($model && $model->tokenable instanceof User) {
                return $model->tokenable;
            }
        }

        // Try Signed Token Fallback
        if (str_contains($token, '.')) {
            [$payloadB64, $signature] = explode('.', $token, 2);
            $json = base64_decode($payloadB64);
            $expectedSig = hash_hmac('sha256', $json, config('app.key') ?: '3rdvn_sso_secret');
            if (hash_equals($expectedSig, $signature)) {
                $data = json_decode($json, true);
                if (isset($data['uid'])) {
                    return User::query()->find($data['uid']);
                }
            }
        }

        return null;
    }

    protected function respondJson(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ]);
    }
}
