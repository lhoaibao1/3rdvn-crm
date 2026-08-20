<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateConversion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AffiliatePortalApiController extends Controller
{
    private const TOKEN_SECRET = '3rdvn_affiliate_portal_sec_token_2026';

    /**
     * CORS Helper
     */
    private function jsonWithCors(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Affiliate-Token, X-Requested-With');
    }

    /**
     * Handle Preflight OPTIONS
     */
    public function options(): JsonResponse
    {
        return $this->jsonWithCors(['status' => 'ok']);
    }

    /**
     * Authenticate Publisher / Sale via Any CRM User Credentials
     */
    public function login(Request $request): JsonResponse
    {
        $identifier = trim((string) $request->input('identifier', $request->input('username', $request->input('email', ''))));
        $password = (string) $request->input('password', '');

        if ($identifier === '' || $password === '') {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Vui lòng nhập thông tin tài khoản và mật khẩu.',
            ], 422);
        }

        $normalizedPhone = preg_replace('/\D+/', '', $identifier) ?: $identifier;
        $identLower = strtolower($identifier);

        // 1. Exact match by Email, Employee Code, Username, UID, Phone, Identity
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$identLower])
            ->orWhereRaw('LOWER(COALESCE(employee_code, \'\')) = ?', [$identLower])
            ->orWhereRaw('LOWER(COALESCE(username, \'\')) = ?', [$identLower])
            ->orWhereRaw('LOWER(COALESCE(uid, \'\')) = ?', [$identLower])
            ->orWhere('phone', $identifier)
            ->orWhere('phone', $normalizedPhone)
            ->orWhere('identity_number', $identifier)
            ->first();

        // 2. Fallback to name search if not found
        if (! $user) {
            $user = User::query()
                ->where('name', 'ilike', "%{$identifier}%")
                ->first();
        }

        if (! $user) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Thông tin đăng nhập không đúng.',
            ], 401);
        }

        if (! Hash::check($password, $user->password)) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Mật khẩu không chính xác.',
            ], 401);
        }

        if (in_array($user->employment_status, ['inactive', 'deactive', 'resigned', 'deleted'], true)) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Tài khoản nhân sự của bạn đang bị tạm khóa.',
            ], 403);
        }

        if (method_exists($user, 'canAccessApp') && ! $user->canAccessApp('affiliate')) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Tài khoản của bạn chưa được cấp quyền truy cập vào Hub Tiếp Thị Liên Kết.',
            ], 403);
        }

        $code = $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))));
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
        $hierarchy = $this->getAccessibleHierarchy($user);
        $token = $this->generateToken($user);

        return $this->jsonWithCors([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_code' => $code,
                'email' => $user->email,
                'phone' => $user->phone ?? '-',
                'role' => $roleName,
                'role_title' => $this->formatRoleTitle($roleName),
                'is_admin' => in_array($roleName, ['Admin', 'Super Admin', 'Sales Admin']),
                'can_manage_campaigns' => in_array($roleName, ['Admin', 'Super Admin', 'Sales Admin']),
                'team' => $user->team?->name ?? '3RD Affiliate Network',
                'managed_members' => $hierarchy ? $hierarchy['members_count'] : 'Toàn hệ thống',
            ],
        ]);
    }

    /**
     * Get Current Authenticated User Info
     */
    public function getMe(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Chưa đăng nhập hoặc phiên làm việc hết hạn.'], 401);
        }

        $code = $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))));
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
        $hierarchy = $this->getAccessibleHierarchy($user);

        return $this->jsonWithCors([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_code' => $code,
                'email' => $user->email,
                'phone' => $user->phone ?? '-',
                'role' => $roleName,
                'role_title' => $this->formatRoleTitle($roleName),
                'is_admin' => in_array($roleName, ['Admin', 'Super Admin', 'Sales Admin']),
                'can_manage_campaigns' => in_array($roleName, ['Admin', 'Super Admin', 'Sales Admin']),
                'team' => $user->team?->name ?? '3RD Affiliate Network',
                'managed_members' => $hierarchy ? $hierarchy['members_count'] : 'Toàn hệ thống',
            ],
        ]);
    }

    /**
     * Get Active Affiliate Campaigns with AccessTrade Pub2 Style Metadata
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        $code = $user ? ($user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))))) : 'RD260001';

        $campaigns = AffiliateCampaign::query()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $data = $campaigns->map(function ($camp) use ($code) {
            $slug = $camp->slug ?: Str::slug($camp->name);
            $baseUrl = "https://3rdvn.io.vn/affiliate/{$slug}";
            $trackingUrl = "{$baseUrl}?ref=" . urlencode($code);

            // AccessTrade Pub2 style Campaign Metadata (Clean separation for each partner)
            $payoutInfo = match(strtolower($slug)) {
                'vpbank-upl', 'vpbank' => [
                    'badge' => 'DỰ ÁN MỚI',
                    'category' => 'Vay tín chấp VPBank',
                    'partner_name' => 'VPBank UPL',
                    'partner_logo' => '/static/logo-vpbank.svg',
                    'loan_limit' => '20 - 200 Triệu VNĐ',
                    'tenure' => '12 - 60 Tháng',
                    'disbursement_time' => 'Duyệt trong 2H',
                    'target_audience' => 'Khách hàng đi làm hưởng lương, tự doanh (20 - 60 tuổi)',
                    'highlights' => ['Hạn mức đến 200 Triệu VNĐ', 'Lãi suất từ 1.2%/tháng', 'Không thế chấp tài sản', 'Đăng ký online 100%'],
                    'terms' => 'Quy trình ghi nhận: Khách hàng click link -> Điền thông tin vay trên cổng VPBank -> VPBank thẩm định -> Giải ngân thành công.',
                    'rejection_reasons' => 'Nợ xấu CIC, không chứng minh được thu nhập, hủy yêu cầu tư vấn.',
                ],
                'tinvay', 'tinvay-vietcredit' => [
                    'badge' => 'DUYỆT NHANH',
                    'category' => 'Vay tiền mặt trực tuyến',
                    'partner_name' => 'Tin Vay',
                    'partner_logo' => '/static/logo-tinvay.svg',
                    'loan_limit' => '5 - 100 Triệu VNĐ',
                    'tenure' => 'Linh hoạt',
                    'disbursement_time' => 'Duyệt hồ sơ tự động',
                    'target_audience' => 'Khách hàng 18 - 60 tuổi có thu nhập ổn định',
                    'highlights' => ['Hạn mức đến 100 Triệu VNĐ', 'Duyệt hồ sơ tự động', 'Không thế chấp tài sản', 'Đăng ký online 100% với CCCD'],
                    'terms' => 'Quy trình ghi nhận: Khách hàng đăng ký qua link -> Hoàn tất định danh eKYC -> Được giải ngân thành công.',
                    'rejection_reasons' => 'Không hoàn tất bước chụp ảnh CCCD, không thỏa điều kiện thu nhập tối thiểu.',
                ],
                'shb-finance', 'shbfinance' => [
                    'badge' => 'HOT NHẤT',
                    'category' => 'Vay tiêu dùng tín chấp',
                    'partner_name' => 'SHB Finance',
                    'partner_logo' => '/static/logo-shb.svg',
                    'loan_limit' => '10 - 100 Triệu VNĐ',
                    'tenure' => '12 - 36 Tháng',
                    'disbursement_time' => 'Giải ngân trong 24H',
                    'target_audience' => 'Khách hàng đi làm hưởng lương, tự kinh doanh (20 - 60 tuổi)',
                    'highlights' => ['Hạn mức đến 100 Triệu VNĐ', 'Giải ngân nhanh 24H', 'Không thẩm định người thân', 'Lãi suất ưu đãi từ 1.6%/tháng'],
                    'terms' => 'Quy trình ghi nhận: Khách hàng click link -> Điền thông tin đăng ký -> SHBFinance thẩm định và liên hệ tư vấn -> Ký hợp đồng & Giải ngân tiền thành công.',
                    'rejection_reasons' => 'Nợ xấu nhóm 2 trở lên, thông tin CCCD không trùng khớp, không nghe máy cuộc gọi thẩm định.',
                ],
                default => [
                    'badge' => 'CHIẾN DỊCH',
                    'category' => 'Tài chính - Ngân hàng',
                    'partner_name' => $camp->name,
                    'partner_logo' => $camp->logo_url ?: '/static/logo.jpg',
                    'loan_limit' => 'Theo chính sách dự án',
                    'tenure' => 'Theo quy định đối tác',
                    'disbursement_time' => 'Duyệt nhanh',
                    'target_audience' => 'Mọi đối tượng khách hàng phù hợp',
                    'highlights' => ['Duyệt hồ sơ tự động', 'Tỷ lệ duyệt cao', 'Đăng ký online 100%'],
                    'terms' => 'Quy trình ghi nhận theo đúng quy chuẩn và hợp đồng hợp tác với đơn vị đối tác.',
                    'rejection_reasons' => 'Thông tin không chính xác hoặc trùng lặp trong hệ thống.',
                ],
            };

            return [
                'id' => $camp->id,
                'name' => $camp->name,
                'slug' => $slug,
                'logo_url' => $camp->logo_url ?: $payoutInfo['partner_logo'],
                'summary' => $camp->summary ?: 'Dự án tiếp thị liên kết chính thức',
                'details' => $camp->details,
                'raw_tracking_url' => $camp->tracking_url,
                'attribution_param' => $camp->attribution_param ?: 'aff_sub1',
                'publisher_url' => $trackingUrl,
                'publisher_base_url' => $baseUrl,
                'meta' => $payoutInfo,
            ];
        });

        return $this->jsonWithCors([
            'success' => true,
            'data' => $data,
            'publisher_code' => $code,
        ]);
    }

    /**
     * Update Campaign Logo (URL or uploaded file)
     */
    public function updateCampaignLogo(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng đăng nhập.'], 401);
        }

        $isAdmin = method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Admin', 'Super Admin', 'Sales Admin']);
        if (! $isAdmin) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Chỉ Quản trị viên (Admin) mới có quyền đổi logo chiến dịch.'], 403);
        }

        $campaign = AffiliateCampaign::find($id);
        if (! $campaign) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Chiến dịch không tồn tại.'], 404);
        }

        $action = (string) $request->input('action', '');
        if ($action === 'reset') {
            $defaultLogo = match(strtolower($campaign->slug ?: '')) {
                'shb-finance', 'shbfinance' => '/static/logo-shb.svg',
                'tinvay-vietcredit', 'tinvay' => '/static/logo-vietcredit.svg',
                default => null,
            };
            $campaign->logo_url = $defaultLogo;
            $campaign->save();

            return $this->jsonWithCors([
                'success' => true,
                'logo_url' => $defaultLogo ?: '/static/logo.jpg',
                'message' => 'Đã khôi phục logo mặc định của chiến dịch ' . $campaign->name,
            ]);
        }

        $logoUrl = trim((string) $request->input('logo_url', ''));
        $logoBase64 = (string) $request->input('logo_base64', '');

        if ($logoBase64 !== '') {
            try {
                $base64Data = $logoBase64;
                $ext = '.png';
                if (str_contains($base64Data, 'base64,')) {
                    $header = explode('base64,', $base64Data)[0];
                    if (str_contains($header, 'jpeg') || str_contains($header, 'jpg')) $ext = '.jpg';
                    elseif (str_contains($header, 'svg')) $ext = '.svg';
                    elseif (str_contains($header, 'webp')) $ext = '.webp';
                    $base64Data = explode('base64,', $base64Data)[1];
                }
                $base64Data = preg_replace('/\s+/', '', $base64Data);
                $filename = "campaign-{$id}-logo{$ext}";
                $dest1 = "/opt/3rdvn-affiliate/public/{$filename}";
                @file_put_contents($dest1, base64_decode($base64Data));
                $logoUrl = "/static/{$filename}?v=" . time();
            } catch (\Throwable $e) {}
        }

        if ($logoUrl === '') {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng chọn file ảnh hoặc nhập URL logo.'], 422);
        }

        $campaign->logo_url = $logoUrl;
        $campaign->save();

        return $this->jsonWithCors([
            'success' => true,
            'logo_url' => $campaign->logo_url,
            'message' => 'Đã cập nhật logo cho chiến dịch ' . $campaign->name,
        ]);
    }

    /**
     * Get Conversions / Transactions List (Filtered by CRM Role Hierarchy)
     */
    public function getConversions(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $query = AffiliateConversion::query()
            ->with(['createdBy'])
            ->orderBy('conversion_time', 'desc')
            ->orderBy('id', 'desc');

        // Apply CRM Role Hierarchy filter
        $hierarchy = $this->getAccessibleHierarchy($user);
        if ($hierarchy !== null) {
            $codes = $hierarchy['codes'];
            $userIds = $hierarchy['user_ids'];

            $query->where(function ($q) use ($codes, $userIds) {
                if (!empty($codes)) {
                    $q->where(function($sq) use ($codes) {
                        foreach ($codes as $c) {
                            $sq->orWhere('aff_sub1', $c)
                               ->orWhere('aff_sub1', 'like', "{$c}%");
                        }
                    });
                }
                if (!empty($userIds)) {
                    $q->orWhereIn('created_by_id', $userIds);
                }
            });
        }

        // Filter: Campaign
        $campaign = trim((string) $request->input('campaign', ''));
        if ($campaign !== '' && $campaign !== 'all') {
            if ($campaign === 'vpbank' || $campaign === 'vpbank-upl') {
                $query->where(function ($q) {
                    $q->where('campaign_name', 'ilike', '%vpbank%')
                      ->orWhere('partner', 'ilike', '%isclix%');
                });
            } elseif ($campaign === 'shb' || $campaign === 'shb-finance' || $campaign === 'shbfinance') {
                $query->where(function ($q) {
                    $q->where('campaign_name', 'ilike', '%shb%')
                      ->orWhere('partner', 'ilike', '%hyperlead%');
                });
            } elseif ($campaign === 'tinvay' || $campaign === 'tinvay-vietcredit') {
                $query->where(function ($q) {
                    $q->where('campaign_name', 'ilike', '%tinvay%')
                      ->orWhere('partner', 'ilike', '%accesstrade%');
                });
            } else {
                $query->where('campaign_name', 'ilike', "%{$campaign}%");
            }
        }

        // Filter: Status
        $status = trim((string) $request->input('status', ''));
        if ($status !== '' && $status !== 'all') {
            if ($status === 'approved' || $status === 'success') {
                $query->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid']);
            } elseif ($status === 'rejected' || $status === 'cancelled') {
                $query->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'cancelled', 'failed', 'declined', 'trash']);
            } elseif ($status === 'pending') {
                $query->where(function ($q) {
                    $q->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])
                      ->orWhereNull('conversion_status');
                });
            }
        }

        // Filter: Sub-ID
        $subId = trim((string) $request->input('sub_id', ''));
        if ($subId !== '') {
            $query->where(function ($q) use ($subId) {
                $q->where('aff_sub1', 'like', "%{$subId}%")
                  ->orWhere('aff_sub2', 'like', "%{$subId}%");
            });
        }

        // Filter: Search keyword
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('conversion_id', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhere('aff_sub1', 'like', "%{$search}%")
                  ->orWhere('aff_sub2', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        // Filter: Date range
        if ($request->filled('date_from')) {
            $query->where('conversion_time', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('conversion_time', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $paginator = $query->paginate($perPage);

        $userCodeMap = User::all(['id', 'name', 'employee_code'])->filter(fn($u) => filled($u->employee_code))->keyBy('employee_code');

        $items = collect($paginator->items())->map(function (AffiliateConversion $item) use ($userCodeMap) {
            $statusLower = strtolower((string) ($item->conversion_status ?? 'pending'));
            $tone = match (true) {
                in_array($statusLower, ['success', 'approved', 'disbursed', 'completed', 'paid']) => 'success',
                in_array($statusLower, ['rejected', 'cancelled', 'failed', 'declined', 'trash']) => 'danger',
                default => 'warning',
            };

            $statusLabel = match ($tone) {
                'success' => 'Thành công (Giải ngân)',
                'danger' => 'Bị từ chối / Hủy',
                default => 'Đang thẩm định / Chờ',
            };

            $campaignLabel = match (true) {
                str_contains(strtolower($item->campaign_name . $item->partner), 'vpbank') => 'VPBank UPL',
                str_contains(strtolower($item->campaign_name . $item->partner), 'shb') => 'SHB Finance',
                str_contains(strtolower($item->campaign_name . $item->partner), 'tinvay') => 'TinVay - VietCredit',
                default => $item->campaign_name ?: 'Tiếp thị liên kết',
            };

            $creatorName = $item->createdBy?->name ?: ($userCodeMap[$item->aff_sub1]->name ?? null);

            return [
                'id' => $item->id,
                'conversion_id' => $item->conversion_id ?: ('CONV-' . $item->id),
                'transaction_id' => $item->transaction_id ?: '-',
                'partner' => $item->partner ?: 'Partner',
                'campaign_name' => $item->campaign_name ?: '-',
                'campaign_label' => $campaignLabel,
                'product_name' => $item->product_name ?: '-',
                'sale_amount' => (float) ($item->sale_amount ?? 0),
                'sale_amount_formatted' => number_format((float) ($item->sale_amount ?? 0), 0, ',', '.') . ' đ',
                'conversion_status' => $item->conversion_status ?: 'Đang xử lý',
                'conversion_status_label' => $statusLabel,
                'status_tone' => $tone,
                'created_by_name' => $creatorName ?: ($item->aff_sub1 ?: '-'),
                'creator_name' => $creatorName ?: ($item->aff_sub1 ?: '-'),
                'aff_sub1' => $item->aff_sub1 ?: '-',
                'aff_sub2' => $item->aff_sub2 ?: '-',
                'click_time' => $item->click_time ? Carbon::parse($item->click_time)->format('H:i d/m/Y') : '-',
                'conversion_time' => $item->conversion_time ? Carbon::parse($item->conversion_time)->format('H:i d/m/Y') : '-',
                'created_at' => $item->created_at ? $item->created_at->format('H:i d/m/Y') : '-',
            ];
        });

        return $this->jsonWithCors([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Get Aggregated Performance Stats (Filtered by CRM Role Hierarchy)
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $hierarchy = $this->getAccessibleHierarchy($user);
        $baseQuery = AffiliateConversion::query();

        if ($hierarchy !== null) {
            $codes = $hierarchy['codes'];
            $userIds = $hierarchy['user_ids'];

            $baseQuery->where(function ($q) use ($codes, $userIds) {
                if (!empty($codes)) {
                    $q->where(function($sq) use ($codes) {
                        foreach ($codes as $c) {
                            $sq->orWhere('aff_sub1', $c)
                               ->orWhere('aff_sub1', 'like', "{$c}%");
                        }
                    });
                }
                if (!empty($userIds)) {
                    $q->orWhereIn('created_by_id', $userIds);
                }
            });
        }

        $totalConversions = (clone $baseQuery)->count();
        $approvedCount = (clone $baseQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $rejectedCount = (clone $baseQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $pendingCount = (clone $baseQuery)->where(function ($q) {
            $q->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])
              ->orWhereNull('conversion_status');
        })->count();

        $totalSaleAmount = (float) (clone $baseQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');

        // Realtime Click Query
        $clickQuery = \App\Models\AffiliateClick::query();
        if ($hierarchy !== null) {
            $codes = $hierarchy['codes'];
            $userIds = $hierarchy['user_ids'];

            $clickQuery->where(function ($q) use ($codes, $userIds) {
                if (!empty($codes)) {
                    $q->whereIn('employee_code', $codes);
                }
                if (!empty($userIds)) {
                    $q->orWhereIn('user_id', $userIds);
                }
            });
        }

        $recordedClicks = (clone $clickQuery)->count();
        $totalClicks = $recordedClicks + (clone $baseQuery)->whereNotNull('click_time')->count();
        if ($totalClicks === 0 && $totalConversions > 0) {
            $totalClicks = $totalConversions * 3;
        }

        $approvalRate = $totalConversions > 0 ? round(($approvedCount / $totalConversions) * 100, 1) : 0;

        // Breakdown by Campaign
        $vpbankQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('campaign_name', 'ilike', '%vpbank%')->orWhere('partner', 'ilike', '%isclix%');
        });
        $vpbankTotal = (clone $vpbankQuery)->count();
        $vpbankApproved = (clone $vpbankQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $vpbankPending = (clone $vpbankQuery)->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $vpbankSaleAmount = (float) (clone $vpbankQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');
        $vpbankClicks = (clone $clickQuery)->where(function ($q) {
            $q->where('campaign_slug', 'ilike', '%vpbank%')->orWhere('partner', 'isclix');
        })->count();

        $shbQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('campaign_name', 'ilike', '%shb%')->orWhere('partner', 'ilike', '%hyperlead%');
        });
        $shbTotal = (clone $shbQuery)->count();
        $shbApproved = (clone $shbQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $shbPending = (clone $shbQuery)->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $shbSaleAmount = (float) (clone $shbQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');
        $shbClicks = (clone $clickQuery)->where(function ($q) {
            $q->where('campaign_slug', 'ilike', '%shb%')->orWhere('partner', 'hyperlead');
        })->count();

        $tinvayQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('campaign_name', 'ilike', '%tinvay%')->orWhere('partner', 'ilike', '%accesstrade%');
        });
        $tinvayTotal = (clone $tinvayQuery)->count();
        $tinvayApproved = (clone $tinvayQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $tinvayPending = (clone $tinvayQuery)->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $tinvaySaleAmount = (float) (clone $tinvayQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');
        $tinvayClicks = (clone $clickQuery)->where(function ($q) {
            $q->where('campaign_slug', 'ilike', '%tinvay%')->orWhere('partner', 'accesstrade');
        })->count();

        return $this->jsonWithCors([
            'success' => true,
            'stats' => [
                'total_clicks' => $totalClicks,
                'total_conversions' => $totalConversions,
                'approved' => $approvedCount,
                'pending' => $pendingCount,
                'rejected' => $rejectedCount,
                'approval_rate' => $approvalRate,
                'total_sale_amount' => $totalSaleAmount,
                'total_sale_amount_formatted' => number_format($totalSaleAmount, 0, ',', '.') . ' đ',
                'campaigns_breakdown' => [
                    'vpbank' => [
                        'clicks' => $vpbankClicks,
                        'total' => $vpbankTotal,
                        'approved' => $vpbankApproved,
                        'pending' => $vpbankPending,
                        'sale_amount' => $vpbankSaleAmount,
                        'sale_amount_formatted' => number_format($vpbankSaleAmount, 0, ',', '.') . ' đ',
                    ],
                    'shb' => [
                        'clicks' => $shbClicks,
                        'total' => $shbTotal,
                        'approved' => $shbApproved,
                        'pending' => $shbPending,
                        'sale_amount' => $shbSaleAmount,
                        'sale_amount_formatted' => number_format($shbSaleAmount, 0, ',', '.') . ' đ',
                    ],
                    'tinvay' => [
                        'clicks' => $tinvayClicks,
                        'total' => $tinvayTotal,
                        'approved' => $tinvayApproved,
                        'pending' => $tinvayPending,
                        'sale_amount' => $tinvaySaleAmount,
                        'sale_amount_formatted' => number_format($tinvaySaleAmount, 0, ',', '.') . ' đ',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get Realtime Notifications (Unified with CRM System Notifications Table, Deduplicated)
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Strictly fetch notifications addressed to THIS user
        $dbNotifs = DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(80)
            ->get();

        $notifications = [];
        $seenFingerprints = [];

        $userCodeMap = User::all(['id', 'name', 'employee_code'])->filter(fn($u) => filled($u->employee_code))->keyBy('employee_code');

        foreach ($dbNotifs as $n) {
            $data = json_decode($n->data, true) ?: [];
            $title = (string) ($data['title'] ?? '');
            $body = (string) ($data['body'] ?? '');
            $searchContext = strtolower($title . ' ' . $body . ' ' . json_encode($data));

            // EXCLUDE generic CRM internal notifications (ticket assignments, column preferences, system tasks)
            $isAffiliateRelevant = str_contains($searchContext, 'affiliate') ||
                                   str_contains($searchContext, 'vpbank') ||
                                   str_contains($searchContext, 'tinvay') ||
                                   str_contains($searchContext, 'shb') ||
                                   str_contains($searchContext, 'chuyển đổi') ||
                                   str_contains($searchContext, 'hoa hồng') ||
                                   str_contains($searchContext, 'giải ngân') ||
                                   str_contains($searchContext, 'thẩm định') ||
                                   str_contains($searchContext, 'lead') ||
                                   str_contains($searchContext, 'hồ sơ vay') ||
                                   str_contains($searchContext, 'đơn mới') ||
                                   str_contains($searchContext, 'chiến dịch') ||
                                   str_contains($searchContext, 'vietcredit') ||
                                   str_contains($searchContext, 'isclix') ||
                                   str_contains($searchContext, 'accesstrade') ||
                                   str_contains($searchContext, 'hyperlead') ||
                                   isset($data['conversion_id']) ||
                                   isset($data['campaign_name']) ||
                                   isset($data['aff_sub1']) ||
                                   (isset($data['actions']) && str_contains(json_encode($data['actions']), 'openAffiliate'));

            if (! $isAffiliateRelevant) {
                continue; // Skip unrelated CRM notifications
            }

            $statusLower = strtolower($title . ' ' . $body . ' ' . ($data['status'] ?? ''));

            $isApproved = str_contains($statusLower, 'approved') || str_contains($statusLower, 'thành công') || str_contains($statusLower, 'disbursed') || str_contains($statusLower, 'giải ngân') || str_contains($statusLower, 'completed') || str_contains($statusLower, 'paid');
            $isRejected = str_contains($statusLower, 'rejected') || str_contains($statusLower, 'từ chối') || str_contains($statusLower, 'failed') || str_contains($statusLower, 'thất bại') || str_contains($statusLower, 'cancelled') || str_contains($statusLower, 'hủy') || str_contains($statusLower, 'huỷ');

            // 1. Tiêu đề chuẩn hóa kèm icon cảm xúc sinh động
            if ($isApproved) {
                $finalTitle = '🎉 Chúc mừng, bạn có hồ sơ mới giải ngân';
                $icon = 'success';
                $type = 'approved';
            } elseif ($isRejected) {
                $finalTitle = '❌ Rất tiếc, bạn có hồ sơ thất bại';
                $icon = 'danger';
                $type = 'rejected';
            } else {
                $finalTitle = '📋 Cập nhật hồ sơ';
                $icon = 'warning';
                $type = 'lead';
            }

            // Extract campaign label
            $campLabel = match(true) {
                str_contains(strtolower($title . ' ' . $body), 'vpbank') => 'VPBank UPL',
                str_contains(strtolower($title . ' ' . $body), 'tinvay') => 'TinVay · VietCredit',
                str_contains(strtolower($title . ' ' . $body), 'shb') => 'SHB Finance',
                default => ($data['campaign_name'] ?? ($data['offer_id'] ?? 'Chiến dịch tiếp thị')),
            };

            // 2. Nội dung chuẩn hóa theo đúng cấu trúc: Dự án, Mã giao dịch/CaseID, Trạng thái, Số tiền duyệt (ở TRÊN), User (ở DƯỚI)
            if (str_contains($body, '🏢 Dự án:') || str_contains($body, 'Dự án:')) {
                // If body is already formatted, ensure icons and order are clean
                $finalBody = $body;
            } else {
                // Parse old body format
                $caseId = '-';
                if (preg_match('/Mã giao dịch:\s*([^\s·\n]+)/u', $body, $m) && $m[1] !== '-') {
                    $caseId = $m[1];
                } elseif (preg_match('/Mã chuyển đổi:\s*([^\s·\n]+)/u', $body, $m)) {
                    $caseId = $m[1];
                }

                $statusVal = 'Mới ghi nhận';
                if (preg_match('/Trạng thái:\s*([^·\n]+)/u', $body, $m)) {
                    $statusVal = trim($m[1]);
                }

                $userVal = 'Hệ thống';
                if (preg_match('/NVKD:\s*([^·\n]+)/u', $body, $m)) {
                    $userVal = trim($m[1]);
                } elseif (preg_match('/Mã (?:nhân viên|NV):\s*([^·\n]+)/u', $body, $m)) {
                    $code = trim($m[1]);
                    $userVal = isset($userCodeMap[$code]) ? "{$userCodeMap[$code]->name} ({$code})" : $code;
                }

                $bodyLines = [
                    "🏢 Dự án: {$campLabel}",
                    "🔖 Mã giao dịch/CaseID: {$caseId}",
                    "📊 Trạng thái: {$statusVal}",
                ];

                if ($isApproved && isset($data['sale_amount']) && (float)$data['sale_amount'] > 0) {
                    $bodyLines[] = "💰 Số tiền duyệt: " . number_format((float)$data['sale_amount'], 0, ',', '.') . " đ";
                } elseif ($isApproved && preg_match('/(?:Doanh số|Số tiền|giải ngân):\s*([0-9\.,]+)/iu', $body, $m)) {
                    $bodyLines[] = "💰 Số tiền duyệt: " . trim($m[1]) . " đ";
                }

                $bodyLines[] = "👤 User: {$userVal}";

                $finalBody = implode("\n", $bodyLines);
            }

            // Deduplication fingerprint: title + body + rounded minute
            $timeSlot = $n->created_at ? Carbon::parse($n->created_at)->format('Y-m-d H:i') : '';
            $fingerprint = md5($finalTitle . '|' . $finalBody) . '_' . $timeSlot;

            if (isset($seenFingerprints[$fingerprint])) {
                continue; // Skip duplicate notification record
            }
            $seenFingerprints[$fingerprint] = true;

            $timeStr = $n->created_at ? Carbon::parse($n->created_at)->diffForHumans() : 'Vừa xong';
            $exactTime = $n->created_at ? Carbon::parse($n->created_at)->format('H:i d/m/Y') : '';
            $notifications[] = [
                'id' => (string) $n->id,
                'type' => $type,
                'icon' => $icon,
                'title' => $finalTitle,
                'body' => $finalBody,
                'campaign' => $campLabel,
                'time_ago' => $timeStr,
                'exact_time' => $exactTime,
                'created_at' => $n->created_at ? Carbon::parse($n->created_at)->toISOString() : now()->toISOString(),
                'unread' => is_null($n->read_at),
            ];
        }

        $unreadCount = count(array_filter($notifications, fn($n) => !empty($n['unread'])));

        return $this->jsonWithCors([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark Single Notification as Read
     */
    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        DB::table('notifications')->where('id', $id)->update(['read_at' => now()]);

        return $this->jsonWithCors(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
    }

    /**
     * Mark All Notifications as Read
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->jsonWithCors(['success' => true, 'message' => 'Đã đánh dấu tất cả đã đọc']);
    }

    /**
     * Get Accessible Hierarchy Sub-Codes for User (CRM Role & Permission Mapping)
     * Returns null if Admin (Full Access).
     * Returns array of codes & user IDs for ZD, AM, Team Leader, and Staff.
     */
    private function getAccessibleHierarchy(User $user): ?array
    {
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';

        if (in_array($roleName, ['Admin', 'Super Admin', 'Sales Admin', 'Director', 'Board'])) {
            return null; // Full Access
        }

        $subUserQuery = User::query();

        if (str_contains($roleName, 'ZD')) {
            $subUserQuery->where(function ($q) use ($user) {
                $q->where('zd_id', $user->id)
                  ->orWhere('id', $user->id);
            });
        } elseif (str_contains($roleName, 'AM')) {
            $subUserQuery->where(function ($q) use ($user) {
                $q->where('am_id', $user->id)
                  ->orWhere('id', $user->id);
            });
        } elseif (str_contains($roleName, 'Team Leader') || str_contains($roleName, 'Leader')) {
            $subUserQuery->where(function ($q) use ($user) {
                $q->where('team_leader_id', $user->id)
                  ->orWhere('id', $user->id);
                if ($user->team_id) {
                    $q->orWhere('team_id', $user->team_id);
                }
            });
        } else {
            // Direct Sale / CTV / Telesale / Staff
            $subUserQuery->where('id', $user->id);
        }

        $subUsers = $subUserQuery->get(['id', 'employee_code', 'username', 'uid']);
        $codes = [];
        $userIds = [];

        foreach ($subUsers as $u) {
            $userIds[] = $u->id;
            if ($u->employee_code) $codes[] = $u->employee_code;
            if ($u->username) $codes[] = $u->username;
            if ($u->uid) $codes[] = $u->uid;
            $codes[] = 'RD' . str_pad((string)$u->id, 6, '0', STR_PAD_LEFT);
        }

        return [
            'user_ids' => array_unique($userIds),
            'codes' => array_values(array_unique(array_filter($codes))),
            'role_scope' => $roleName,
            'members_count' => count($subUsers),
        ];
    }

    /**
     * Format Human-Readable Role Title
     */
    private function formatRoleTitle(string $roleName): string
    {
        return match($roleName) {
            'Admin', 'Super Admin' => 'Quản Trị Viên (Admin)',
            'Sales Admin' => 'Quản Trị Kinh Doanh (Sales Admin)',
            'ZD' => 'Giám Đốc Vùng (ZD)',
            'AM' => 'Quản Lý Khu Vực (AM)',
            'Team Leader' => 'Trưởng Nhóm (Team Leader)',
            'Direct Sale' => 'Chuyên Viên Kinh Doanh',
            'CTV' => 'Cộng Tác Viên (CTV)',
            'Courier Manager' => 'Quản Lý Giao Nhận',
            'Courier' => 'Nhân Viên Giao Nhận',
            default => $roleName,
        };
    }

    /**
     * Get Members / Publishers List (Full Hierarchy, Exact Spatie Roles, Teams & Pagination)
     */
    public function getMembers(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $hierarchy = $this->getAccessibleHierarchy($user);
        $query = User::query()->with(['team', 'teamLeader', 'roles'])->orderBy('id', 'desc');

        if ($hierarchy !== null) {
            $userIds = $hierarchy['user_ids'];
            $query->where(function ($q) use ($userIds, $user) {
                $q->whereIn('id', $userIds)
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere('team_leader_id', $user->id);
                if ($user->team_id) {
                    $q->orWhere('team_id', $user->team_id);
                }
            });
        }

        // Filter: Search Keyword
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('employee_code', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }

        // Filter: Role (Exact Spatie Role Name)
        $roleFilter = trim((string) $request->input('role', ''));
        if ($roleFilter !== '' && $roleFilter !== 'all') {
            $query->whereHas('roles', fn ($rq) => $rq->where('name', $roleFilter));
        }

        // Filter: Team ID
        $teamFilter = trim((string) $request->input('team_id', ''));
        if ($teamFilter !== '' && $teamFilter !== 'all') {
            $query->where('team_id', (int) $teamFilter);
        }

        // Filter: Status
        $statusFilter = trim((string) $request->input('status', ''));
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            if ($statusFilter === 'active') {
                $query->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED]);
            } elseif ($statusFilter === 'inactive') {
                $query->whereIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED]);
            }
        }

        $perPageInput = $request->input('per_page', 50);
        $perPage = ($perPageInput === 'all' || (int)$perPageInput === -1) ? 500 : min(500, max(5, (int) $perPageInput));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function (User $member) {
            $code = $member->employee_code ?: ($member->username ?: ('RD' . str_pad((string)$member->id, 6, '0', STR_PAD_LEFT)));
            
            // Exact Spatie Roles
            $roles = $member->getRoleNames()->toArray();
            $exactRole = !empty($roles) ? implode(', ', $roles) : ($member->position ?: 'Direct Sale');
            
            // Team & Leader
            $teamName = $member->team?->name ?? ($member->branch_name ?: '-');
            $leaderName = $member->teamLeader?->name ?: '-';

            // Live click & conversion stats for this member
            $clicksCount = \App\Models\AffiliateClick::where('employee_code', $code)->orWhere('user_id', $member->id)->count();
            $convsCount = AffiliateConversion::where('aff_sub1', $code)->orWhere('created_by_id', $member->id)->count();
            $approvedCount = AffiliateConversion::where(function($q) use ($code, $member) {
                $q->where('aff_sub1', $code)->orWhere('created_by_id', $member->id);
            })->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();

            $isActive = ! in_array($member->employment_status, ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED], true);

            return [
                'id' => $member->id,
                'name' => $member->name,
                'employee_code' => $code,
                'email' => $member->email,
                'phone' => $member->phone ?? '-',
                'role' => $exactRole,
                'roles_array' => $roles,
                'team_id' => $member->team_id,
                'team_name' => $teamName,
                'leader_name' => $leaderName,
                'branch_name' => $member->branch_name ?? '-',
                'is_active' => $isActive,
                'status_label' => $isActive ? 'Hoạt động' : 'Tạm khóa',
                'bank_name' => $member->bank_name ?? '-',
                'bank_account_number' => $member->bank_account_number ?? '-',
                'bank_account_name' => $member->bank_account_name ?? '-',
                'identity_number' => $member->identity_number ?? '-',
                'clicks_count' => $clicksCount,
                'conversions_count' => $convsCount,
                'approved_count' => $approvedCount,
                'created_at' => $member->created_at ? $member->created_at->format('d/m/Y H:i') : '-',
            ];
        });

        // Get Available Roles, Teams and Banks for Dropdown Filters
        $availableRoles = \Spatie\Permission\Models\Role::orderBy('id')->pluck('name')->toArray();
        $availableTeams = DB::table('crm_teams')->select('id', 'name', 'code')->orderBy('id')->get();
        $availableBanks = class_exists(\App\Support\VietnamBankCatalog::class) ? \App\Support\VietnamBankCatalog::banks() : [];

        return $this->jsonWithCors([
            'success' => true,
            'data' => $items,
            'meta' => [
                'available_roles' => $availableRoles,
                'available_teams' => $availableTeams,
                'available_banks' => $availableBanks,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
            ],
        ]);
    }

    /**
     * Get Vietnam Banks Catalog (VietQR)
     */
    public function getBanks(Request $request): JsonResponse
    {
        $banks = class_exists(\App\Support\VietnamBankCatalog::class) ? \App\Support\VietnamBankCatalog::banks() : [];
        return $this->jsonWithCors([
            'success' => true,
            'data' => $banks,
        ]);
    }

    /**
     * Create New Affiliate Publisher / Member (Auto CRM Employee Code RD26xxxx)
     */
    public function createMember(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng đăng nhập.'], 401);
        }

        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $phone = trim((string) $request->input('phone', ''));
        $roleName = trim((string) $request->input('role', 'Affiliate Publisher'));
        $teamId = $request->filled('team_id') ? (int) $request->input('team_id') : $user->team_id;
        $bankName = trim((string) $request->input('bank_name', ''));
        $bankAccNum = trim((string) $request->input('bank_account_number', ''));
        $bankAccName = trim((string) $request->input('bank_account_name', ''));
        $idNumber = trim((string) $request->input('identity_number', ''));

        if ($name === '') {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng nhập Họ và tên thành viên.'], 422);
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng nhập định dạng Email hợp lệ.'], 422);
        }
        if (strlen($password) < 6) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Mật khẩu phải có tối thiểu 6 ký tự.'], 422);
        }

        // Check unique email
        if (User::where('email', $email)->exists()) {
            return $this->jsonWithCors(['success' => false, 'message' => "Email '{$email}' đã tồn tại trong hệ thống."], 422);
        }

        // Employee code is automatically generated by CRM User::booted creating hook (RD26xxxx sequence)
        $newMember = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone ?: null,
            'identity_number' => $idNumber ?: null,
            'bank_name' => $bankName ?: null,
            'bank_account_number' => $bankAccNum ?: null,
            'bank_account_name' => $bankAccName ? strtoupper($bankAccName) : null,
            'position' => $roleName,
            'department' => 'Affiliate Network',
            'employment_status' => 'active',
            'team_id' => $teamId,
            'team_leader_id' => $user->hasRole('Team Leader') ? $user->id : $user->team_leader_id,
            'created_by_id' => $user->id,
            'hire_date' => now()->toDateString(),
        ]);
        $newMember->save();

        // Refresh to get auto-generated employee_code from CRM
        $newMember->refresh();

        try {
            $newMember->assignRole($roleName ?: 'Affiliate Publisher');
        } catch (\Throwable) {
            try {
                $newMember->assignRole('Affiliate Publisher');
            } catch (\Throwable) {}
        }

        return $this->jsonWithCors([
            'success' => true,
            'message' => "Đã tạo thành công tài khoản {$name} (Mã NV/CTV: {$newMember->employee_code}, Vai trò: {$roleName})!",
            'member' => [
                'id' => $newMember->id,
                'name' => $newMember->name,
                'employee_code' => $newMember->employee_code,
                'email' => $newMember->email,
                'phone' => $newMember->phone,
                'role' => $roleName,
            ],
        ], 201);
    }

    /**
     * Toggle Member Active Status
     */
    public function toggleMemberStatus(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $member = User::find($id);
        if (! $member) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Thành viên không tồn tại.'], 404);
        }

        $isCurrentlyActive = ! in_array($member->employment_status, ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED], true);
        $newStatus = $isCurrentlyActive ? 'inactive' : 'active';
        $member->employment_status = $newStatus;
        $member->save();

        return $this->jsonWithCors([
            'success' => true,
            'is_active' => $newStatus === 'active',
            'message' => $newStatus === 'active' ? "Đã mở khóa tài khoản {$member->name}" : "Đã tạm khóa tài khoản {$member->name}",
        ]);
    }

    /**
     * Get Member Full Detail
     */
    public function getMemberDetail(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $member = User::with(['roles', 'team', 'teamLeader'])->find($id);
        if (! $member) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không tìm thấy thành viên'], 404);
        }

        $code = $member->employee_code ?: ($member->username ?: ($member->uid ?: ('RD' . str_pad((string)$member->id, 6, '0', STR_PAD_LEFT))));
        $roles = $member->roles->pluck('name')->toArray();
        $exactRole = $roles[0] ?? ($member->position ?: 'Thành viên');
        $teamName = $member->team ? $member->team->name : ($member->department ?: '-');
        $leaderName = $member->teamLeader ? $member->teamLeader->name : '-';
        $isActive = ! in_array($member->employment_status, ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED], true);

        // Stats
        $clicksCount = \App\Models\AffiliateClick::where('employee_code', $code)->orWhere('user_id', $member->id)->count();
        $conversionsCount = AffiliateConversion::where('aff_sub1', $code)->orWhere('created_by_id', $member->id)->count();
        $approvedCount = AffiliateConversion::where(function($q) use ($code, $member) {
            $q->where('aff_sub1', $code)->orWhere('created_by_id', $member->id);
        })->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $rejectedCount = AffiliateConversion::where(function($q) use ($code, $member) {
            $q->where('aff_sub1', $code)->orWhere('created_by_id', $member->id);
        })->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'failed', 'cancelled', 'trash'])->count();
        $pendingCount = AffiliateConversion::where(function($q) use ($code, $member) {
            $q->where('aff_sub1', $code)->orWhere('created_by_id', $member->id);
        })->whereIn(DB::raw('LOWER(conversion_status)'), ['pending', 'processing', 'new'])->count();

        return $this->jsonWithCors([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'employee_code' => $code,
                'uid' => $member->uid ?? '-',
                'email' => $member->email,
                'phone' => $member->phone ?? '-',
                'identity_number' => $member->identity_number ?? '-',
                'role' => $exactRole,
                'roles_array' => $roles,
                'team_id' => $member->team_id,
                'team_name' => $teamName,
                'leader_name' => $leaderName,
                'branch_name' => $member->branch_name ?? '-',
                'employment_status' => $member->employment_status ?? 'active',
                'is_active' => $isActive,
                'bank_name' => $member->bank_name ?? '-',
                'bank_account_number' => $member->bank_account_number ?? '-',
                'bank_account_name' => $member->bank_account_name ?? '-',
                'hire_date' => $member->hire_date ? (is_string($member->hire_date) ? $member->hire_date : $member->hire_date->format('d/m/Y')) : '-',
                'created_at' => $member->created_at ? $member->created_at->format('d/m/Y H:i') : '-',
                'stats' => [
                    'clicks' => $clicksCount,
                    'conversions' => $conversionsCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                    'pending' => $pendingCount,
                ],
            ],
        ]);
    }

    /**
     * Reset / Change Member Password
     */
    public function resetMemberPassword(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $member = User::find($id);
        if (! $member) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không tìm thấy thành viên'], 404);
        }

        $newPassword = (string) $request->input('new_password', '');
        if (strlen($newPassword) < 6) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.'], 422);
        }

        $member->password = Hash::make($newPassword);
        $member->save();

        return $this->jsonWithCors([
            'success' => true,
            'message' => "Đã đổi mật khẩu thành công cho tài khoản {$member->name} ({$member->employee_code})!",
        ]);
    }

    /**
     * Get Current Logged In User Profile Details
     */
    public function getMyProfile(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        return $this->getMemberDetail($request, $user->id);
    }

    /**
     * Change Password for Current Logged In User
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $currentPassword = (string) $request->input('current_password', '');
        $newPassword = (string) $request->input('new_password', '');

        if ($currentPassword !== '' && ! Hash::check($currentPassword, $user->password)) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác. Vui lòng kiểm tra lại.',
            ], 422);
        }

        if (strlen($newPassword) < 6) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Mật khẩu mới phải có tối thiểu 6 ký tự.',
            ], 422);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return $this->jsonWithCors([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công! Vui lòng sử dụng mật khẩu mới cho các lần đăng nhập tiếp theo.',
        ]);
    }

    /**
     * Simple HMAC Token Generator for Portal Session
     */
    private function generateToken(User $user): string
    {
        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'code' => $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT)))),
            'time' => time(),
        ];
        $json = json_encode($payload);
        $sig = hash_hmac('sha256', $json, self::TOKEN_SECRET);

        return base64_encode($json) . '.' . $sig;
    }

    /**
     * Authenticate Request from Bearer Token or Cookie
     */
    private function authenticateRequest(Request $request): ?User
    {
        $token = $request->bearerToken() ?: $request->header('X-Affiliate-Token');
        if (! $token && $request->hasCookie('aff_token')) {
            $token = $request->cookie('aff_token');
        }

        if (! $token || ! is_string($token) || ! str_contains($token, '.')) {
            return null;
        }

        try {
            [$encodedJson, $receivedSig] = explode('.', $token, 2);
            $decodedJson = base64_decode($encodedJson, true);
            if (! $decodedJson) {
                return null;
            }

            $expectedSig = hash_hmac('sha256', $decodedJson, self::TOKEN_SECRET);
            if (! hash_equals($expectedSig, $receivedSig)) {
                return null;
            }

            $payload = json_decode($decodedJson, true);
            if (! isset($payload['id'])) {
                return null;
            }

            return User::find($payload['id']);
        } catch (\Throwable) {
            return null;
        }
    }
}

