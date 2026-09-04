<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateClick;
use App\Models\AffiliateConversion;
use App\Models\User;
use App\Support\AffiliateConversionStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class AffiliatePortalApiController extends Controller
{
    private function jsonWithCors(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Affiliate-Token');
    }

    public function options(): JsonResponse
    {
        return $this->jsonWithCors(['status' => 'OK']);
    }

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

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$identLower])
            ->orWhereRaw('LOWER(COALESCE(employee_code, \'\')) = ?', [$identLower])
            ->orWhereRaw('LOWER(COALESCE(username, \'\')) = ?', [$identLower])
            ->orWhereRaw('LOWER(COALESCE(uid, \'\')) = ?', [$identLower])
            ->orWhere('phone', $identifier)
            ->orWhere('phone', $normalizedPhone)
            ->orWhere('identity_number', $identifier)
            ->first();

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

        $code = $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))));
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
        $hierarchy = $this->getAccessibleHierarchy($user);
        $token = $this->generateToken($user);

        return $this->jsonWithCors([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'employee_code' => $code,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_path' => $user->avatar_path ? (str_starts_with($user->avatar_path, 'http') ? $user->avatar_path : asset('storage/' . $user->avatar_path)) : null,
                'role' => $roleName,
                'role_title' => $this->getRoleTitle($user),
                'team' => $user->team?->name ?: ($user->branch_name ?: '3RD Fintech'),
                'is_admin' => $user->hasRole('Admin') || $user->hasRole('Super Admin'),
                'can_manage_campaigns' => $user->hasRole('Admin') || $user->hasRole('Super Admin') || $user->hasRole('Director') || $user->hasRole('General Manager') || $user->hasRole('Manager'),
                'managed_members' => $hierarchy !== null ? count($hierarchy['codes']) : 'Toàn hệ thống',
            ],
            'message' => 'Đăng nhập thành công',
        ]);
    }

    public function getMe(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $code = $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))));
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
        $hierarchy = $this->getAccessibleHierarchy($user);

        return $this->jsonWithCors([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'employee_code' => $code,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_path' => $user->avatar_path ? (str_starts_with($user->avatar_path, 'http') ? $user->avatar_path : asset('storage/' . $user->avatar_path)) : null,
                'role' => $roleName,
                'role_title' => $this->getRoleTitle($user),
                'team' => $user->team?->name ?: ($user->branch_name ?: '3RD Fintech'),
                'is_admin' => $user->hasRole('Admin') || $user->hasRole('Super Admin'),
                'can_manage_campaigns' => $user->hasRole('Admin') || $user->hasRole('Super Admin') || $user->hasRole('Director') || $user->hasRole('General Manager') || $user->hasRole('Manager'),
                'managed_members' => $hierarchy !== null ? count($hierarchy['codes']) : 'Toàn hệ thống',
            ],
        ]);
    }

    public function getCampaigns(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $userCode = $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))));
        $campaigns = AffiliateCampaign::query()
            ->orderBy('id', 'asc')
            ->get();

        $data = $campaigns->map(function ($camp) use ($userCode) {
            $defaultLogo = match(strtolower($camp->slug ?: '')) {
                'shb-finance', 'shbfinance' => '/static/logo-shb.svg',
                'tinvay-vietcredit', 'tinvay' => '/static/logo-vietcredit.svg',
                'vpbank-upl', 'vpbank' => '/static/logo-vpbank.svg',
                'lotte-finance', 'lotte' => '/static/logo-lotte-finance.svg',
                'shinhan-finance-android', 'shinhan-android', 'shinhan-finance-ios', 'shinhan-ios' => '/static/logo-shinhan.svg',
                default => '/static/logo.jpg',
            };

            $campaignDetails = match(strtolower($camp->slug ?: '')) {
                'shb-finance', 'shbfinance' => [
                    'badge' => 'HOT NHẤT',
                    'category' => 'Vay tiêu dùng tín chấp',
                    'partner_name' => 'SHB Finance',
                    'partner_logo' => '/static/logo-shb.svg',
                    'loan_limit' => '10 - 100 Triệu VNĐ',
                    'tenure' => '6 - 36 Tháng',
                    'disbursement_time' => '12 - 24 Giờ',
                    'target_audience' => 'Khách hàng có thu nhập từ lương hoặc tự doanh (20 - 59 tuổi)',
                    'highlights' => ['Hạn mức đến 100 Triệu VNĐ', 'Lãi suất chỉ từ 1.6%/tháng', 'Không thế chấp tài sản', 'Đăng ký online 100%'],
                    'terms' => 'Quy trình ghi nhận: Khách hàng click link -> Điền form thông tin -> SHB Finance liên hệ tư vấn -> Ký hợp đồng & Giải ngân.',
                    'rejection_reasons' => 'Nợ xấu nhóm 2 trở lên trên CIC, sai thông tin định danh, hủy hồ sơ.',
                ],
                'vpbank-upl', 'vpbank' => [
                    'badge' => 'DUYỆT CAO',
                    'category' => 'Vay tín chấp ngân hàng',
                    'partner_name' => 'VPBank UPL',
                    'partner_logo' => '/static/logo-vpbank.svg',
                    'loan_limit' => '20 - 200 Triệu VNĐ',
                    'tenure' => '12 - 48 Tháng',
                    'disbursement_time' => '24 Giờ',
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
                    'terms' => 'Quy trình ghi nhận: Khách hàng đăng ký online -> Hệ thống thẩm định tự động -> Duyệt hạn mức và giải ngân.',
                    'rejection_reasons' => 'Thông tin CCCD không hợp lệ, nợ xấu nhóm cao.',
                ],
                'lotte-finance', 'lotte' => [
                    'badge' => 'HẠN MỨC CAO',
                    'category' => 'Vay tiêu dùng tín chấp',
                    'partner_name' => 'Lotte Finance',
                    'partner_logo' => '/static/logo-lotte-finance.svg',
                    'loan_limit' => '20 - 600 Triệu VNĐ',
                    'tenure' => '6 - 60 Tháng',
                    'disbursement_time' => 'Theo kết quả thẩm định',
                    'target_audience' => 'Khách hàng từ 21 - 60 tuổi thuộc nhóm Công ty, Công ty TOP, GOV hoặc Bảo hiểm nhân thọ',
                    'highlights' => ['Hạn mức đến 600 Triệu VNĐ', 'Kỳ hạn từ 6 đến 60 tháng', 'Không phụ thuộc CRM', 'Nhân viên Lotte Finance liên hệ hỗ trợ'],
                    'terms' => 'Khách hàng hoàn tất biểu mẫu đăng ký. Hồ sơ được Admin xử lý tại LOS và cập nhật lần lượt: Đang thẩm định/Chờ duyệt, Đã duyệt - chờ giải ngân, Đã giải ngân hoặc Hủy.',
                    'rejection_reasons' => 'Không đáp ứng điều kiện thẩm định hoặc khách hàng hủy hồ sơ.',
                ],
                'shinhan-finance-android', 'shinhan-android' => [
                    'badge' => 'MỚI',
                    'category' => 'Android · Vay online 100%',
                    'partner_name' => 'Shinhan Finance Android',
                    'partner_logo' => '/static/logo-shinhan.svg',
                    'loan_limit' => 'Theo kết quả xét duyệt',
                    'tenure' => 'Theo chính sách SVFC',
                    'disbursement_time' => 'Quy trình tự động trên app',
                    'target_audience' => 'Khách hàng có nhu cầu vay Tài Tốc hoặc Vay Thông Thường trên ứng dụng iShinhan',
                    'highlights' => ['Đăng ký online 100%', 'Tải đúng app theo Android/iOS', 'Nhập mã giới thiệu ACT23', 'Shinhan Finance không thu phí khách hàng'],
                    'terms' => 'Khách hàng điền landing form 3RDVN -> Hệ thống chuyển đến đúng link tải iShinhan theo Android/iOS -> Khách hàng tải và mở ứng dụng iShinhan -> Chọn Vay Tài Tốc hoặc Vay Thông Thường -> Nhập mã giới thiệu ACT23 -> Đăng ký khoản vay và xác thực eKYC bằng CCCD -> Hoàn tất xét duyệt tự động -> Ký hợp đồng trực tuyến trên iShinhan -> Shinhan Finance giải ngân thành công (CPA)',
                    'rejection_reasons' => 'Không nhập mã ACT23, thông tin không hợp lệ, không hoàn tất eKYC/ký hợp đồng hoặc không được giải ngân.',
                    'guide_url' => 'https://drive.google.com/file/d/1ZICJ8Op_WZ1oCstjLuokjmCkpikd2_2h/view?usp=sharing',
                    'publisher_notice' => 'Shinhan Finance không thu bất kỳ khoản phí nào của khách hàng. Publisher thu phí hoặc làm khách hàng hiểu sai sẽ bị hủy đơn và có thể bị dừng tham gia các chiến dịch tài chính.',
                ],
                'shinhan-finance-ios', 'shinhan-ios' => [
                    'badge' => 'MỚI',
                    'category' => 'iOS · Vay online 100%',
                    'partner_name' => 'Shinhan Finance iOS',
                    'partner_logo' => '/static/logo-shinhan.svg',
                    'loan_limit' => 'Theo kết quả xét duyệt',
                    'tenure' => 'Theo chính sách SVFC',
                    'disbursement_time' => 'Quy trình tự động trên app',
                    'target_audience' => 'Khách hàng dùng iPhone/iPad có nhu cầu vay Tài Tốc hoặc Vay Thông Thường trên ứng dụng iShinhan',
                    'highlights' => ['Link tải riêng cho iOS', 'Đăng ký online 100%', 'Nhập mã giới thiệu ACT23', 'Shinhan Finance không thu phí khách hàng'],
                    'terms' => 'Khách hàng điền landing form 3RDVN -> Hệ thống chuyển đến link tải iShinhan dành cho iOS -> Khách hàng tải và mở ứng dụng iShinhan -> Chọn Vay Tài Tốc hoặc Vay Thông Thường -> Nhập mã giới thiệu ACT23 -> Đăng ký khoản vay và xác thực eKYC bằng CCCD -> Hoàn tất xét duyệt tự động -> Ký hợp đồng trực tuyến trên iShinhan -> Shinhan Finance giải ngân thành công (CPA)',
                    'rejection_reasons' => 'Không nhập mã ACT23, thông tin không hợp lệ, không hoàn tất eKYC/ký hợp đồng hoặc không được giải ngân.',
                    'guide_url' => 'https://drive.google.com/file/d/1ZICJ8Op_WZ1oCstjLuokjmCkpikd2_2h/view?usp=sharing',
                    'publisher_notice' => 'Shinhan Finance không thu bất kỳ khoản phí nào của khách hàng. Publisher thu phí hoặc làm khách hàng hiểu sai sẽ bị hủy đơn và có thể bị dừng tham gia các chiến dịch tài chính.',
                ],
                default => [
                    'badge' => 'CHIẾN DỊCH',
                    'category' => 'Tiếp thị liên kết',
                    'partner_name' => $camp->name,
                    'partner_logo' => '/static/logo.jpg',
                    'loan_limit' => 'Theo quy định',
                    'tenure' => 'Linh hoạt',
                    'disbursement_time' => '24 - 48 Giờ',
                    'target_audience' => 'Khách hàng toàn quốc từ 18 - 60 tuổi',
                    'highlights' => ['Đăng ký trực tuyến', 'Thủ tục đơn giản', 'Giải ngân nhanh'],
                    'terms' => 'Quy trình ghi nhận theo chính sách của đối tác.',
                    'rejection_reasons' => 'Thông tin không chính xác hoặc không đáp ứng điều kiện vay.',
                ]
            };

            return [
                'id' => $camp->id,
                'name' => $camp->name,
                'slug' => $camp->slug,
                'description' => $camp->description,
                'commission_rate' => $camp->commission_rate,
                'cookie_duration_days' => $camp->cookie_duration_days,
                'logo_url' => $camp->logo_url ?: $defaultLogo,
                'is_active' => $camp->is_active,
                'is_open' => $camp->isOpen(),
                'opens_at' => $camp->opens_at?->toIso8601String(),
                'closes_at' => $camp->closes_at?->toIso8601String(),
                'closure_message' => $camp->closureReason(),
                'publisher_base_url' => "https://3rdvn.io.vn/affiliate/{$camp->slug}",
                'tracking_url' => "https://3rdvn.io.vn/affiliate/{$camp->slug}?ref={$userCode}",
                'short_tracking_url' => "https://3rdvn.io.vn/aff/{$camp->slug}?ref={$userCode}",
                'affiliate_link' => "https://3rdvn.io.vn/affiliate/{$camp->slug}?ref={$userCode}",
                'meta' => $campaignDetails,
                'details' => $campaignDetails,
            ];
        });

        return $this->jsonWithCors([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getConversions(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $query = $this->buildConversionReportQuery($request, $user)
            ->with(['createdBy'])
            ->orderByRaw('COALESCE(conversion_time, click_time, created_at) DESC')
            ->orderBy('id', 'desc');

        $statsQuery = clone $query;
        $statsQuery->getQuery()->orders = null;
        
        $totalCount = (clone $statsQuery)->count();
        $approvedCount = (clone $statsQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $approvedWaitingQuery = (clone $statsQuery)->where(function ($q) {
            $q->whereRaw('LOWER(conversion_status) = ?', ['approved_waiting_disbursement'])
              ->orWhere(function ($approvedAmountQuery) {
                  $approvedAmountQuery
                      ->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])
                      ->where('sale_amount', '>', 0);
              });
        });
        $approvedWaitingCount = (clone $approvedWaitingQuery)->count();
        $approvedWaitingVolume = (float) ((clone $approvedWaitingQuery)->sum('sale_amount') ?? 0);
        $rejectedCount = (clone $statsQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $pendingCount = max(0, $totalCount - $approvedCount - $approvedWaitingCount - $rejectedCount);
        $approvedVolume = (float) ((clone $statsQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount') ?? 0);
        $approvalRate = $totalCount > 0 ? round(($approvedCount / $totalCount) * 100, 1) : 0;

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $paginator = $query->paginate($perPage);

        $userCodeMap = User::all(['id', 'name', 'employee_code'])->filter(fn($u) => filled($u->employee_code))->keyBy('employee_code');

        $items = collect($paginator->items())->map(function (AffiliateConversion $item) use ($userCodeMap) {
            $recordedAt = $item->conversion_time ?: $item->click_time ?: $item->created_at;
            $tone = AffiliateConversionStatus::tone($item->conversion_status, $item->sale_amount, $item->campaign_name.' '.$item->offer_id.' '.$item->partner);
            $statusLabel = AffiliateConversionStatus::label($item->conversion_status, $item->sale_amount, $item->campaign_name.' '.$item->offer_id.' '.$item->partner);

            $searchMeta = strtolower((string)($item->campaign_name . $item->partner . $item->offer_id . $item->landing_page . $item->conversion_id));
            $campaignLabel = match (true) {
                str_contains($searchMeta, 'vpbank') => 'VPBank UPL',
                str_contains($searchMeta, 'shb') || strtolower((string)$item->partner) === 'hyperlead' => 'SHB Finance',
                str_contains($searchMeta, 'tinvay') || str_contains($searchMeta, 'vietcredit') || str_contains($searchMeta, 'vcredit') => 'Tin Vay',
                default => $item->campaign_name ?: 'SHB Finance',
            };

            $creatorName = $item->createdBy?->name ?: ($userCodeMap[$item->aff_sub1]->name ?? null);
            $rawPayload = (array) ($item->raw_payload ?? []);
            $customerName = $rawPayload['customer_name'] ?? null;
            $customerPhone = $rawPayload['customer_phone'] ?? null;
            $customerIdNumber = $rawPayload['customer_identity_number'] ?? null;
            $customerDob = $rawPayload['customer_dob'] ?? $rawPayload['date_of_birth'] ?? null;
            $provinceName = $rawPayload['province_name'] ?? $rawPayload['province'] ?? null;
            $customerGroupCode = $rawPayload['customer_group'] ?? null;
            $customerGroup = $rawPayload['customer_group_label']
                ?? $rawPayload['product_category']
                ?? $rawPayload['category_name']
                ?? data_get($rawPayload, '_extra.product_category');
            if (! filled($customerGroup) && filled($customerGroupCode)) {
                $customerGroup = match ($customerGroupCode) {
                    'company' => 'Làm Công ty',
                    'top_company' => 'Công ty TOP',
                    'gov' => 'GOV',
                    'life_insurance' => 'Bảo hiểm nhân thọ',
                    default => $customerGroupCode,
                };
            }
            if ($campaignLabel === 'Tin Vay') {
                $customerGroup = 'High';
                $customerGroupCode = 'high';
                $statusLabel = AffiliateConversionStatus::label($item->conversion_status, $item->sale_amount, $campaignLabel);
                $tone = AffiliateConversionStatus::tone($item->conversion_status, $item->sale_amount, $campaignLabel);
            }
            $attributionVerified = $rawPayload['attribution_verified'] ?? null;

            if ($attributionVerified !== false && (!$customerName || !$customerPhone) && is_numeric($item->aff_sub2)) {
                $lead = \App\Models\Lead::find((int) $item->aff_sub2);
                if ($lead) {
                    $customerName = $customerName ?: $lead->lead_name;
                    $customerPhone = $customerPhone ?: $lead->phone;
                    $customerIdNumber = $customerIdNumber ?: (is_array($lead->payload) ? ($lead->payload['identity_number'] ?? null) : null);
                }
            }

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
                'customer_name' => $customerName ?: 'Khách hàng',
                'customer_phone' => $customerPhone ?: '-',
                'customer_identity' => $customerIdNumber ?: '-',
                'customer_dob' => $customerDob ?: '-',
                'province_name' => $provinceName ?: '-',
                'customer_group' => filled($customerGroup) ? $customerGroup : '-',
                'customer_group_code' => $customerGroupCode ?: '-',
                'requested_amount' => (float) ($rawPayload['requested_amount'] ?? 0),
                'requested_amount_formatted' => number_format((float) ($rawPayload['requested_amount'] ?? 0), 0, ',', '.') . ' đ',
                'approved_amount' => (float) ($rawPayload['approved_amount'] ?? $item->sale_amount ?? 0),
                'approved_amount_formatted' => number_format((float) ($rawPayload['approved_amount'] ?? $item->sale_amount ?? 0), 0, ',', '.') . ' đ',
                'loan_term_months' => filled($rawPayload['loan_term_months'] ?? null) ? (int) $rawPayload['loan_term_months'] : null,
                'note' => filled($rawPayload['note'] ?? null) ? trim((string) $rawPayload['note']) : '-',
                'attribution_verified' => $attributionVerified,
                'lead_id' => $item->aff_sub2 ?: '-',
                'aff_sub1' => $item->aff_sub1 ?: '-',
                'aff_sub2' => $item->aff_sub2 ?: '-',
                'click_time' => $item->click_time ? Carbon::parse($item->click_time)->format('H:i d/m/Y') : '-',
                'conversion_time' => $recordedAt ? Carbon::parse($recordedAt)->format('H:i d/m/Y') : '-',
                'created_at' => $item->created_at ? $item->created_at->format('H:i d/m/Y') : '-',
            ];
        });

        return $this->jsonWithCors([
            'success' => true,
            'data' => $items,
            'summary' => [
                'total' => $totalCount,
                'pending' => $pendingCount,
                'approved_waiting_disbursement' => $approvedWaitingCount,
                'approved_waiting_disbursement_volume' => $approvedWaitingVolume,
                'approved_waiting_disbursement_volume_formatted' => number_format($approvedWaitingVolume, 0, ',', '.') . ' đ',
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'approved_volume' => $approvedVolume,
                'approved_volume_formatted' => number_format($approvedVolume, 0, ',', '.') . ' đ',
                'approval_rate' => $approvalRate,
            ],
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function exportConversions(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'affiliate-report-3rdvn-');
        if ($temporaryPath === false) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không thể khởi tạo file Excel.'], 500);
        }

        $writer = new Writer();
        $writer->openToFile($temporaryPath);
        $writer->addRow(Row::fromValues([
            'STT', 'Mã chuyển đổi', 'Mã giao dịch', 'Khách hàng', 'Số điện thoại',
            'Chiến dịch', 'Nhóm khách hàng', 'Mã NVKD (Sub 1)', 'Người tạo',
            'Doanh số vay', 'Trạng thái duyệt', 'Ghi chú', 'Thời gian ghi nhận',
        ]));

        $count = 0;
        $userCodeMap = User::query()
            ->select(['id', 'name', 'employee_code'])
            ->whereNotNull('employee_code')
            ->get()
            ->keyBy('employee_code');

        $this->buildConversionReportQuery($request, $user)
            ->with('createdBy:id,name,employee_code')
            ->orderBy('id')
            ->chunkById(1000, function ($conversions) use ($writer, $userCodeMap, &$count): void {
                foreach ($conversions as $conversion) {
                    $recordedAt = $conversion->conversion_time ?: $conversion->click_time ?: $conversion->created_at;
                    $rawPayload = (array) ($conversion->raw_payload ?? []);
                    $searchMeta = strtolower((string) ($conversion->campaign_name.$conversion->partner.$conversion->offer_id.$conversion->landing_page.$conversion->conversion_id));
                    $campaignLabel = match (true) {
                        str_contains($searchMeta, 'vpbank') => 'VPBank UPL',
                        str_contains($searchMeta, 'shb') || strtolower((string) $conversion->partner) === 'hyperlead' => 'SFinance',
                        str_contains($searchMeta, 'tinvay') || str_contains($searchMeta, 'vietcredit') || str_contains($searchMeta, 'vcredit') => 'Tin Vay',
                        str_contains($searchMeta, 'lotte') => 'Lotte Finance',
                        default => $conversion->campaign_name ?: '-',
                    };
                    $customerGroupCode = $rawPayload['customer_group'] ?? null;
                    $customerGroup = $rawPayload['customer_group_label']
                        ?? $rawPayload['product_category']
                        ?? $rawPayload['category_name']
                        ?? data_get($rawPayload, '_extra.product_category');
                    if (! filled($customerGroup) && filled($customerGroupCode)) {
                        $customerGroup = match ($customerGroupCode) {
                            'company' => 'Làm Công ty',
                            'top_company' => 'Công ty TOP',
                            'gov' => 'GOV',
                            'life_insurance' => 'Bảo hiểm nhân thọ',
                            default => $customerGroupCode,
                        };
                    }
                    if ($campaignLabel === 'Tin Vay') {
                        $customerGroup = 'High';
                    }
                    $creatorName = $conversion->createdBy?->name
                        ?: ($userCodeMap[$conversion->aff_sub1]->name ?? $conversion->aff_sub1 ?? '-');

                    $count++;
                    $writer->addRow(Row::fromValues([
                        $count,
                        $conversion->conversion_id ?: 'CONV-'.$conversion->id,
                        $conversion->transaction_id ?: '-',
                        $rawPayload['customer_name'] ?? 'Khách hàng',
                        $rawPayload['customer_phone'] ?? '-',
                        $campaignLabel,
                        filled($customerGroup) ? $customerGroup : '-',
                        $conversion->aff_sub1 ?: '-',
                        $creatorName,
                        (float) ($conversion->sale_amount ?? 0),
                        AffiliateConversionStatus::label($conversion->conversion_status, $conversion->sale_amount, $campaignLabel),
                        filled($rawPayload['note'] ?? null) ? trim((string) $rawPayload['note']) : '-',
                        $recordedAt ? Carbon::parse($recordedAt)->format('H:i d/m/Y') : '-',
                    ]));
                }
            }, 'id');

        $writer->close();
        $xlsx = file_get_contents($temporaryPath);
        @unlink($temporaryPath);
        if ($xlsx === false) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không thể đọc file Excel đã tạo.'], 500);
        }

        return $this->jsonWithCors([
            'success' => true,
            'filename' => 'bao-cao-'.($request->filled('campaign')
                ? Str::slug((string) $request->input('campaign'))
                : 'tat-ca-chien-dich').'-3rdvn-'.now()->format('Ymd-His').'.xlsx',
            'count' => $count,
            'xlsx_base64' => base64_encode($xlsx),
        ]);
    }

    public function getTraffic(Request $request): JsonResponse
    {
        $admin = $this->authenticateRequest($request);
        if (! $admin) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (! $this->isAffiliateAdmin($admin)) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Bạn không có quyền truy cập Quản lý Traffic.'], 403);
        }

        $validator = Validator::make($request->all(), $this->trafficValidationRules());
        if ($validator->fails()) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Bộ lọc traffic không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $this->buildTrafficQuery($request);
        $statsQuery = clone $query;
        $statsQuery->getQuery()->orders = null;

        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));
        $paginator = $query
            ->with(['user.roles', 'user.team', 'user.teamLeader', 'user.am'])
            ->orderByDesc('clicked_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = collect($paginator->items())
            ->map(fn (AffiliateClick $click): array => $this->formatTrafficClick($click));

        $campaigns = AffiliateClick::query()
            ->select(['campaign_slug', 'campaign_name'])
            ->whereNotNull('campaign_slug')
            ->orderBy('campaign_name')
            ->get()
            ->unique('campaign_slug')
            ->map(fn (AffiliateClick $click): array => [
                'value' => $click->campaign_slug,
                'label' => $click->campaign_name ?: $click->campaign_slug,
            ])
            ->values();

        $employees = AffiliateClick::query()
            ->with('user:id,name,employee_code')
            ->select(['id', 'employee_code', 'user_id'])
            ->whereNotNull('employee_code')
            ->orderBy('employee_code')
            ->get()
            ->unique(fn (AffiliateClick $click): string => strtoupper((string) $click->employee_code))
            ->map(fn (AffiliateClick $click): array => [
                'value' => $click->employee_code,
                'label' => trim(($click->user?->name ?: 'Không rõ tên').' · '.$click->employee_code),
            ])
            ->values();

        return $this->jsonWithCors([
            'success' => true,
            'data' => $items,
            'stats' => [
                'total_clicks' => (clone $statsQuery)->count(),
                'unique_ips' => (clone $statsQuery)->whereNotNull('ip_address')->distinct('ip_address')->count('ip_address'),
                'unique_employees' => (clone $statsQuery)->whereNotNull('employee_code')->distinct('employee_code')->count('employee_code'),
                'mobile_clicks' => $this->applyTrafficDeviceFilter(clone $statsQuery, 'mobile')->count(),
            ],
            'options' => [
                'campaigns' => $campaigns,
                'employees' => $employees,
            ],
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function exportTraffic(Request $request): JsonResponse
    {
        $admin = $this->authenticateRequest($request);
        if (! $admin) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (! $this->isAffiliateAdmin($admin)) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Bạn không có quyền xuất Traffic.'], 403);
        }

        $validator = Validator::make($request->all(), $this->trafficValidationRules());
        if ($validator->fails()) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Bộ lọc traffic không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'traffic-3rdvn-');
        if ($temporaryPath === false) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không thể khởi tạo file Excel.'], 500);
        }

        $writer = new Writer();
        $writer->openToFile($temporaryPath);
        $writer->addRow(Row::fromValues([
            'Traffic ID', 'Click ID', 'Thời gian', 'Chiến dịch',
            'Mã nhân viên', 'Họ tên', 'Vai trò', 'Đội nhóm', 'Team Leader', 'AM',
            'Nguồn', 'Thiết bị', 'Trình duyệt', 'IP', 'Trang giới thiệu', 'User Agent',
        ]));

        $count = 0;
        $this->buildTrafficQuery($request)
            ->with(['user.roles', 'user.team', 'user.teamLeader', 'user.am'])
            ->chunkById(1000, function ($clicks) use ($writer, &$count): void {
                foreach ($clicks as $click) {
                    $row = $this->formatTrafficClick($click);
                    $writer->addRow(Row::fromValues([
                        $row['traffic_id'], $row['click_id'], $row['clicked_at'], $row['campaign_name'],
                        $row['employee_code'], $row['employee_name'], $row['role'], $row['team'], $row['team_leader'], $row['am'],
                        $row['source'], $row['device'], $row['browser'], $row['ip_address'], $row['referer'], $row['user_agent'],
                    ]));
                    $count++;
                }
            }, 'id');

        $writer->close();
        $xlsx = file_get_contents($temporaryPath);
        @unlink($temporaryPath);
        if ($xlsx === false) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không thể đọc file Excel đã tạo.'], 500);
        }

        return $this->jsonWithCors([
            'success' => true,
            'filename' => 'traffic-3rdvn-'.now()->format('Ymd-His').'.xlsx',
            'count' => $count,
            'xlsx_base64' => base64_encode($xlsx),
        ]);
    }

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
        $pendingCount = max(0, $totalConversions - $approvedCount - $rejectedCount);
        $totalSaleAmount = (float) (clone $baseQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');

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
        $vpbankRejected = (clone $vpbankQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $vpbankSaleAmount = (float) (clone $vpbankQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');
        $vpbankRate = $vpbankTotal > 0 ? round(($vpbankApproved / $vpbankTotal) * 100, 1) : 0;
        $vpbankClicks = (clone $clickQuery)->where(function ($q) {
            $q->where('campaign_slug', 'ilike', '%vpbank%')->orWhere('partner', 'isclix');
        })->count();

        $shbQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('campaign_name', 'ilike', '%shb%')->orWhere('partner', 'ilike', '%hyperlead%');
        });
        $shbTotal = (clone $shbQuery)->count();
        $shbApproved = (clone $shbQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $shbPending = (clone $shbQuery)->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $shbRejected = (clone $shbQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $shbSaleAmount = (float) (clone $shbQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');
        $shbRate = $shbTotal > 0 ? round(($shbApproved / $shbTotal) * 100, 1) : 0;
        $shbClicks = (clone $clickQuery)->where(function ($q) {
            $q->where('campaign_slug', 'ilike', '%shb%')->orWhere('partner', 'hyperlead');
        })->count();

        $tinvayQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('campaign_name', 'ilike', '%tinvay%')
              ->orWhere('campaign_name', 'ilike', '%tin vay%')
              ->orWhere('campaign_name', 'ilike', '%vietcredit%')
              ->orWhere('campaign_name', 'ilike', '%vcredit%');
        });
        $tinvayTotal = (clone $tinvayQuery)->count();
        $tinvayApproved = (clone $tinvayQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $tinvayPending = (clone $tinvayQuery)->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $tinvayRejected = (clone $tinvayQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'cancelled', 'failed', 'declined', 'trash'])->count();
        $tinvaySaleAmount = (float) (clone $tinvayQuery)->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->sum('sale_amount');
        $tinvayRate = $tinvayTotal > 0 ? round(($tinvayApproved / $tinvayTotal) * 100, 1) : 0;
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
                        'rejected' => $vpbankRejected,
                        'approval_rate' => $vpbankRate,
                        'sale_amount' => $vpbankSaleAmount,
                        'sale_amount_formatted' => number_format($vpbankSaleAmount, 0, ',', '.') . ' đ',
                    ],
                    'shb' => [
                        'clicks' => $shbClicks,
                        'total' => $shbTotal,
                        'approved' => $shbApproved,
                        'pending' => $shbPending,
                        'rejected' => $shbRejected,
                        'approval_rate' => $shbRate,
                        'sale_amount' => $shbSaleAmount,
                        'sale_amount_formatted' => number_format($shbSaleAmount, 0, ',', '.') . ' đ',
                    ],
                    'tinvay' => [
                        'clicks' => $tinvayClicks,
                        'total' => $tinvayTotal,
                        'approved' => $tinvayApproved,
                        'pending' => $tinvayPending,
                        'rejected' => $tinvayRejected,
                        'approval_rate' => $tinvayRate,
                        'sale_amount' => $tinvaySaleAmount,
                        'sale_amount_formatted' => number_format($tinvaySaleAmount, 0, ',', '.') . ' đ',
                    ],
                ],
            ],
        ]);
    }

    public function getNotifications(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $query = DB::table('notifications')->where('notifiable_id', $user->id);

        $rawNotifs = $query->orderBy('created_at', 'desc')->take(30)->get();
        $unreadCount = (clone $query)->whereNull('read_at')->count();

        $notifications = $rawNotifs->map(function ($n) {
            $data = json_decode($n->data ?? '{}', true) ?: [];
            $title = $data['title'] ?? 'Thông báo hệ thống';
            $body = $data['body'] ?? ($data['message'] ?? '');
            $icon = 'info';
            if (str_contains($title, '🎉') || str_contains($title, 'giải ngân') || str_contains($title, 'thành công')) {
                $icon = 'success';
            } elseif (str_contains($title, '❌') || str_contains($title, 'thất bại') || str_contains($title, 'từ chối')) {
                $icon = 'danger';
            } elseif (str_contains($title, '⏳') || str_contains($title, 'chờ') || str_contains($title, 'cập nhật')) {
                $icon = 'warning';
            }

            $createdAt = Carbon::parse($n->created_at);
            return [
                'id' => $n->id,
                'title' => $title,
                'body' => $body,
                'icon' => $icon,
                'unread' => empty($n->read_at),
                'read_at' => $n->read_at,
                'time_ago' => $createdAt->diffForHumans(),
                'exact_time' => $createdAt->format('H:i d/m/Y'),
                'conversion_id' => $data['conversion_id'] ?? null,
            ];
        });

        return $this->jsonWithCors([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        DB::table('notifications')->where('id', $id)->update(['read_at' => now()]);
        return $this->jsonWithCors(['success' => true]);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        DB::table('notifications')->where('notifiable_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
        return $this->jsonWithCors(['success' => true]);
    }

    public function getBanks(Request $request): JsonResponse
    {
        $banks = [
            ['code' => 'VCB', 'name' => 'Vietcombank - Ngân hàng Ngoại thương Việt Nam'],
            ['code' => 'TCB', 'name' => 'Techcombank - Ngân hàng Kỹ Thương Việt Nam'],
            ['code' => 'MB',  'name' => 'MB Bank - Ngân hàng Quân Đội'],
            ['code' => 'VPB', 'name' => 'VPBank - Ngân hàng Việt Nam Thịnh Vượng'],
            ['code' => 'ACB', 'name' => 'ACB - Ngân hàng Á Châu'],
            ['code' => 'BIDV','name' => 'BIDV - Ngân hàng Đầu tư và Phát triển Việt Nam'],
            ['code' => 'CTG', 'name' => 'VietinBank - Ngân hàng Công Thương Việt Nam'],
            ['code' => 'SHB', 'name' => 'SHB - Ngân hàng Sài Gòn - Hà Nội'],
            ['code' => 'STB', 'name' => 'Sacombank - Ngân hàng Sài Gòn Thương Tín'],
            ['code' => 'TPB', 'name' => 'TPBank - Ngân hàng Tiên Phong'],
            ['code' => 'HDB', 'name' => 'HDBank - Ngân hàng Phát triển TP.HCM'],
            ['code' => 'MSB', 'name' => 'MSB - Ngân hàng Hàng Hải'],
            ['code' => 'VIB', 'name' => 'VIB - Ngân hàng Quốc Tế'],
            ['code' => 'OCB', 'name' => 'OCB - Ngân hàng Phương Đông'],
        ];

        return $this->jsonWithCors(['success' => true, 'data' => $banks]);
    }

    public function getMembers(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $query = User::query()->whereNotIn('employment_status', ['deleted', 'resigned'])->with(['team', 'teamLeader']);
        $hierarchy = $this->getAccessibleHierarchy($user);
        if ($hierarchy !== null) {
            $query->whereIn('id', $hierarchy['user_ids']);
        }

        // Keep dashboard counters independent from the active table filters.
        $statsQuery = clone $query;

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('uid', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = trim((string) $request->input('role'));
            $query->whereHas('roles', fn ($builder) => $builder->where('name', $role));
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', (int) $request->input('team_id'));
        }

        if ($request->filled('status')) {
            $status = trim((string) $request->input('status'));
            if ($status === 'active') {
                $query->where(function ($builder) {
                    $builder->where('employment_status', 'active')->orWhereNull('employment_status');
                });
            } elseif ($status === 'inactive') {
                $query->where('employment_status', 'inactive');
            }
        }

        $convStats = AffiliateConversion::query()
            ->select('aff_sub1', 
                DB::raw('count(*) as conversions'),
                DB::raw('count(case when lower(conversion_status) in (\'success\',\'approved\',\'disbursed\',\'completed\',\'paid\') then 1 end) as approved')
            )
            ->whereNotNull('aff_sub1')
            ->groupBy('aff_sub1')
            ->get()
            ->keyBy('aff_sub1');

        $clickStats = \App\Models\AffiliateClick::query()
            ->select('employee_code', DB::raw('count(*) as clicks'))
            ->whereNotNull('employee_code')
            ->groupBy('employee_code')
            ->get()
            ->keyBy('employee_code');

        $perPage = max(10, min(100, (int) $request->input('per_page', 15)));
        $paginator = $query->orderBy('id', 'asc')->paginate($perPage);
        $members = collect($paginator->items())->map(function ($u) use ($convStats, $clickStats) {
            $code = $u->employee_code ?: ($u->username ?: ('RD' . str_pad((string)$u->id, 6, '0', STR_PAD_LEFT)));
            $roleName = method_exists($u, 'getRoleNames') ? ($u->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
            $isActive = ($u->employment_status === 'active' || empty($u->employment_status));
            
            $cStat = $convStats->get($code);
            $clkStat = $clickStats->get($code);

            return [
                'id' => $u->id,
                'uid' => $u->uid ?: '-',
                'name' => $u->name,
                'employee_code' => $code,
                'email' => $u->email ?: '-',
                'phone' => $u->phone ?: '-',
                'role' => $roleName,
                'role_title' => $this->getRoleTitle($u),
                'team_name' => $u->team?->name ?: ($u->branch_name ?: 'Fintech'),
                'team' => $u->team?->name ?: ($u->branch_name ?: 'Fintech'),
                'leader_name' => $u->teamLeader?->name ?: '-',
                'status' => $isActive ? 'active' : 'inactive',
                'is_active' => $isActive,
                'stats' => [
                    'clicks' => (int) ($clkStat?->clicks ?? 0),
                    'conversions' => (int) ($cStat?->conversions ?? 0),
                    'approved' => (int) ($cStat?->approved ?? 0),
                ],
                'created_at' => $u->created_at?->format('d/m/Y'),
            ];
        });

        return $this->jsonWithCors([
            'success' => true,
            'data' => $members->values(),
            'meta' => [
                'total' => (clone $statsQuery)->count(),
                'active_count' => (clone $statsQuery)
                    ->where(function ($builder) {
                        $builder->where('employment_status', 'active')->orWhereNull('employment_status');
                    })->count(),
                'inactive_count' => (clone $statsQuery)->where('employment_status', 'inactive')->count(),
                'publisher_count' => (clone $statsQuery)
                    ->whereHas('roles', fn ($builder) => $builder->where('name', 'Publisher'))->count(),
                'ctv_count' => (clone $statsQuery)
                    ->whereHas('roles', fn ($builder) => $builder->where('name', 'CTV'))->count(),
                'direct_sale_count' => (clone $statsQuery)
                    ->whereHas('roles', fn ($builder) => $builder->where('name', 'Direct Sale'))->count(),
                'total_clicks' => (int) $clickStats->sum('clicks'),
                'total_conversions' => (int) $convStats->sum('conversions'),
                'per_page' => $paginator->perPage(),
                'available_teams' => \App\Models\CrmTeam::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($team) => ['id' => $team->id, 'name' => $team->name])
                    ->values(),
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function getMemberDetail(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $member = User::with(['team', 'teamLeader'])->find($id);
        if (! $member) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không tìm thấy thành viên'], 404);
        }

        $code = $member->employee_code ?: ($member->username ?: ('RD' . str_pad((string)$member->id, 6, '0', STR_PAD_LEFT)));
        $roleName = method_exists($member, 'getRoleNames') ? ($member->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
        $isActive = ($member->employment_status === 'active' || empty($member->employment_status));

        $totalConversions = AffiliateConversion::where('aff_sub1', $code)->orWhere('created_by_id', $member->id)->count();
        $approvedConversions = AffiliateConversion::where(function($q) use ($code, $member) {
            $q->where('aff_sub1', $code)->orWhere('created_by_id', $member->id);
        })->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid'])->count();
        $totalClicks = \App\Models\AffiliateClick::where('employee_code', $code)->orWhere('user_id', $member->id)->count();

        return $this->jsonWithCors([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'uid' => $member->uid ?: '-',
                'name' => $member->name,
                'employee_code' => $code,
                'email' => $member->email ?: '-',
                'phone' => $member->phone ?: '-',
                'identity_number' => $member->identity_number ?: '-',
                'bank_account_number' => $member->bank_account_number ?: '-',
                'bank_account_name' => $member->bank_account_name ?: '-',
                'bank_name' => $member->bank_name ?: '-',
                'team_id' => $member->team_id,
                'role' => $roleName,
                'role_title' => $this->getRoleTitle($member),
                'team_name' => $member->team?->name ?: 'Fintech',
                'leader_name' => $member->teamLeader?->name ?: '-',
                'status' => $isActive ? 'active' : 'inactive',
                'is_active' => $isActive,
                'stats' => [
                    'clicks' => $totalClicks,
                    'conversions' => $totalConversions,
                    'approved' => $approvedConversions,
                ],
                'created_at' => $member->created_at?->format('d/m/Y H:i'),
            ]
        ]);
    }

    public function toggleMemberStatus(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user || (! $user->hasRole('Admin') && ! $user->hasRole('Super Admin'))) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này'], 403);
        }

        $member = User::find($id);
        if ($member->id === $user->id || $member->id === 1) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không thể khóa tài khoản Quản trị viên tối cao'], 400);
        }

        $newStatus = ($member->employment_status === 'active' || empty($member->employment_status)) ? 'inactive' : 'active';
        $member->employment_status = $newStatus;
        $member->saveQuietly();

        return $this->jsonWithCors([
            'success' => true,
            'message' => $newStatus === 'active' ? 'Đã mở khóa tài khoản thành công' : 'Đã tạm khóa tài khoản thành công',
            'is_active' => $newStatus === 'active',
            'status' => $newStatus,
        ]);
    }

    public function updateMember(Request $request, int $id): JsonResponse
    {
        $admin = $this->authenticateRequest($request);
        if (! $admin || (! $admin->hasRole('Admin') && ! $admin->hasRole('Super Admin'))) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Bạn không có quyền sửa thông tin nhân sự'], 403);
        }

        $member = User::find($id);
        if (! $member) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không tìm thấy thành viên'], 404);
        }

        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Họ tên hoặc email không hợp lệ'], 422);
        }
        if (User::where('email', $email)->where('id', '!=', $member->id)->exists()) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Email đã được sử dụng bởi tài khoản khác'], 422);
        }

        $member->fill([
            'name' => $name,
            'email' => $email,
            'phone' => trim((string) $request->input('phone', '')) ?: null,
            'identity_number' => trim((string) $request->input('identity_number', '')) ?: null,
            'team_id' => $request->filled('team_id') ? (int) $request->input('team_id') : null,
            'bank_name' => trim((string) $request->input('bank_name', '')) ?: null,
            'bank_account_number' => trim((string) $request->input('bank_account_number', '')) ?: null,
            'bank_account_name' => trim((string) $request->input('bank_account_name', '')) ?: null,
        ]);
        if ($request->filled('password')) {
            $member->password = Hash::make((string) $request->input('password'));
        }
        $member->save();

        $role = trim((string) $request->input('role', ''));
        if ($role !== '') {
            try {
                $member->syncRoles([$role]);
            } catch (\Throwable $e) {
                return $this->jsonWithCors(['success' => false, 'message' => 'Vai trò không hợp lệ'], 422);
            }
        }

        return $this->jsonWithCors([
            'success' => true,
            'message' => "Đã cập nhật thông tin {$member->name}",
        ]);
    }

    private function normalizePublisherManagerCode(?string $code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $code));
        if (preg_match('/^RD260(\d{1,3})$/', $normalized, $matches)) {
            return 'RD260'.str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        }

        return $normalized;
    }

    private function publisherManagerQuery()
    {
        return User::query()
            ->whereNotNull('employee_code')
            ->whereNotIn('employment_status', ['inactive', 'deactive', 'resigned', 'deleted'])
            ->where(function ($query): void {
                $query->whereHas('roles', function ($roleQuery): void {
                    $roleQuery->whereIn('name', [
                        'Admin', 'Super Admin', 'Director', 'General Manager', 'Manager',
                        'Sales Admin', 'ZD', 'AM', 'Team Leader', 'Trưởng nhóm', 'Quản lý',
                    ]);
                })->orWhereHas('managedTeam');
            });
    }

    public function getPublicPublisherManagers(Request $request): JsonResponse
    {
        $defaultCode = $this->normalizePublisherManagerCode('RD26003');
        $requestedCode = $this->normalizePublisherManagerCode($request->query('ref'));
        $managers = $this->publisherManagerQuery()
            ->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'team_id'])
            ->map(fn (User $manager): array => [
                'name' => $manager->name,
                'employee_code' => $manager->employee_code,
                'role' => $manager->roles->first()?->name,
            ])
            ->values();

        $selectedCode = $requestedCode !== '' && $managers->contains('employee_code', $requestedCode)
            ? $requestedCode
            : $defaultCode;

        return $this->jsonWithCors([
            'success' => true,
            'data' => $managers,
            'selected_code' => $selectedCode,
            'ref_applied' => $requestedCode !== '' && $selectedCode === $requestedCode,
        ]);
    }

    public function registerPublicPublisher(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'phone' => ['required', 'regex:/^(0|84)[0-9]{9,10}$/', 'unique:users,phone'],
            'identity_number' => ['nullable', 'regex:/^[0-9]{9,12}$/'],
            'manager_code' => ['nullable', 'string', 'max:30'],
            'ref' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_account_name' => ['nullable', 'string', 'max:120'],
        ], [
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ]);

        if ($validator->fails()) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $managerCode = $this->normalizePublisherManagerCode(
            $request->input('ref') ?: $request->input('manager_code') ?: 'RD26003'
        );
        $manager = $this->publisherManagerQuery()
            ->where('employee_code', $managerCode)
            ->with(['roles', 'team', 'managedTeam'])
            ->first();

        if (! $manager) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Quản lý đã chọn không hợp lệ hoặc đang ngừng hoạt động.',
            ], 422);
        }

        try {
            $newMember = DB::transaction(function () use ($request, $manager): User {
                $maxEmployeeSequence = User::query()
                    ->whereNotNull('employee_code')
                    ->lockForUpdate()
                    ->pluck('employee_code')
                    ->reduce(function (int $max, $code): int {
                        return preg_match('/^RD260(\d+)$/i', (string) $code, $matches)
                            ? max($max, (int) $matches[1])
                            : $max;
                    }, 139);
                $maxUidSequence = User::query()
                    ->whereNotNull('uid')
                    ->lockForUpdate()
                    ->pluck('uid')
                    ->reduce(function (int $max, $uid): int {
                        return preg_match('/^NV(\d+)$/i', (string) $uid, $matches)
                            ? max($max, (int) $matches[1])
                            : $max;
                    }, 139);

                $teamLeaderId = $manager->hasRole(['Team Leader', 'Trưởng nhóm']) ? $manager->id : $manager->team_leader_id;
                $amId = $manager->hasRole('AM') ? $manager->id : $manager->am_id;
                $zdId = $manager->hasRole('ZD') ? $manager->id : $manager->zd_id;
                $teamId = $manager->team_id ?: $manager->managedTeam?->id;

                $member = User::create([
                    'name' => trim((string) $request->input('name')),
                    'email' => strtolower(trim((string) $request->input('email'))),
                    'password' => Hash::make((string) $request->input('password')),
                    'phone' => trim((string) $request->input('phone')),
                    'employee_code' => 'RD260'.str_pad((string) ($maxEmployeeSequence + 1), 3, '0', STR_PAD_LEFT),
                    'uid' => 'NV'.str_pad((string) ($maxUidSequence + 1), 4, '0', STR_PAD_LEFT),
                    'team_id' => $teamId,
                    'team_leader_id' => $teamLeaderId,
                    'am_id' => $amId,
                    'zd_id' => $zdId,
                    'created_by_id' => $manager->id,
                    'branch_name' => $manager->branch_name ?: ($manager->team?->name ?: '3RD Fintech'),
                    'employment_status' => 'active',
                    'allowed_apps' => ['affiliate'],
                    'identity_number' => trim((string) $request->input('identity_number')) ?: null,
                    'bank_name' => trim((string) $request->input('bank_name')) ?: null,
                    'bank_account_number' => trim((string) $request->input('bank_account_number')) ?: null,
                    'bank_account_name' => trim((string) $request->input('bank_account_name')) ?: null,
                    'hire_date' => now()->toDateString(),
                ]);
                $member->assignRole('Affiliate Publisher');

                return $member;
            });
        } catch (\Throwable $exception) {
            Log::error('Public publisher registration failed', [
                'email' => $request->input('email'),
                'manager_code' => $managerCode,
                'error' => $exception->getMessage(),
            ]);

            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Chưa thể tạo tài khoản lúc này. Vui lòng thử lại.',
            ], 500);
        }

        $this->notifyAffiliateDefaultCommission($manager, $newMember);

        return $this->jsonWithCors([
            'success' => true,
            'message' => 'Đăng ký CTV thành công.',
            'data' => [
                'name' => $newMember->name,
                'employee_code' => $newMember->employee_code,
                'manager_name' => $manager->name,
                'manager_code' => $manager->employee_code,
            ],
        ], 201);
    }

    public function createMember(Request $request): JsonResponse
    {
        $creator = $this->authenticateRequest($request);
        if (! $creator) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng đăng nhập'], 401);
        }

        $isManager = $creator->hasRole(['Admin', 'Super Admin', 'Director', 'General Manager', 'Manager', 'AM', 'ZD', 'Team Leader', 'Trưởng nhóm', 'Quản lý'])
            || \App\Models\CrmTeam::where('manager_id', $creator->id)->exists()
            || $creator->employee_code === 'RD260001';

        if (! $isManager) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Bạn không có quyền tạo thành viên mới'], 403);
        }

        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $phone = trim((string) $request->input('phone', ''));
        $password = (string) $request->input('password', '');

        if ($name === '') {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng nhập họ và tên thành viên'], 422);
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng nhập địa chỉ email hợp lệ'], 422);
        }

        if (User::where('email', $email)->exists()) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Địa chỉ email này đã tồn tại trên hệ thống'], 422);
        }

        $teamId = null;
        if (($creator->hasRole('Admin') || $creator->hasRole('Super Admin')) && $request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
        } else {
            $teamId = $creator->team_id ?: ($creator->managedTeam?->id ?: (\App\Models\CrmTeam::where('manager_id', $creator->id)->value('id') ?: null));
        }

        $teamLeaderId = null;
        $amId = null;
        $zdId = null;

        if ($creator->hasRole('Team Leader') || $creator->hasRole('Trưởng nhóm')) {
            $teamLeaderId = $creator->id;
            $amId = $creator->am_id;
            $zdId = $creator->zd_id;
        } elseif ($creator->hasRole('AM')) {
            $teamLeaderId = null;
            $amId = $creator->id;
            $zdId = $creator->zd_id;
        } elseif ($creator->hasRole('ZD')) {
            $teamLeaderId = null;
            $amId = null;
            $zdId = $creator->id;
        } else {
            $teamLeaderId = $creator->team_leader_id;
            $amId = $creator->am_id;
            $zdId = $creator->zd_id;
        }

        $createdById = $creator->id;
        $branchName = $creator->branch_name ?: ($creator->team?->name ?: '3RD Fintech');

        $maxNum = 139;
        $codes = User::pluck('employee_code');
        foreach ($codes as $c) {
            if (preg_match('/RD260?(\d+)/i', (string)$c, $m)) {
                $n = (int) $m[1];
                if ($n > $maxNum) $maxNum = $n;
            }
        }
        $employeeCode = 'RD260' . str_pad((string)($maxNum + 1), 3, '0', STR_PAD_LEFT);

        $maxUid = 139;
        $uids = User::pluck('uid');
        foreach ($uids as $u) {
            if (preg_match('/NV(\d+)/i', (string)$u, $m)) {
                $n = (int) $m[1];
                if ($n > $maxUid) $maxUid = $n;
            }
        }
        $uid = 'NV' . str_pad((string)($maxUid + 1), 4, '0', STR_PAD_LEFT);

        $newMember = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password !== '' ? $password : '123456Aa@'),
            'phone' => $phone ?: null,
            'employee_code' => $employeeCode,
            'uid' => $uid,
            'team_id' => $teamId,
            'team_leader_id' => $teamLeaderId,
            'am_id' => $amId,
            'zd_id' => $zdId,
            'created_by_id' => $createdById,
            'branch_name' => $branchName,
            'employment_status' => 'active',
            'allowed_apps' => ['affiliate', 'crm'],
            'identity_number' => trim((string)$request->input('identity_number', '')),
            'bank_name' => trim((string)$request->input('bank_name', '')),
            'bank_account_number' => trim((string)$request->input('bank_account_number', '')),
            'bank_account_name' => trim((string)$request->input('bank_account_name', '')),
            'hire_date' => now()->toDateString(),
        ]);

        $role = trim((string)$request->input('role', 'Affiliate Publisher'));
        try {
            $newMember->assignRole($role !== '' ? $role : 'Affiliate Publisher');
        } catch (\Throwable $e) {}

        $this->notifyAffiliateDefaultCommission($creator, $newMember);

        return $this->jsonWithCors([
            'success' => true,
            'message' => "Tạo thành viên {$newMember->name} ({$newMember->employee_code}) thành công!",
            'data' => [
                'id' => $newMember->id,
                'name' => $newMember->name,
                'employee_code' => $newMember->employee_code,
                'email' => $newMember->email,
                'team_id' => $newMember->team_id,
            ],
        ]);
    }


    private function notifyAffiliateDefaultCommission($manager, $member): void
    {
        try {
            $payload = json_encode([
                'manager_code' => (string) ($manager->employee_code ?? ''),
                'recipient' => [
                    'id' => $member->id,
                    'employee_code' => $member->employee_code,
                    'name' => $member->name,
                    'role' => method_exists($member, 'getRoleNames') ? ($member->getRoleNames()->first() ?: 'Affiliate Publisher') : 'Affiliate Publisher',
                    'team_name' => $member->branch_name ?: ($member->team?->name ?? ''),
                ],
            ], JSON_UNESCAPED_UNICODE);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
                    'content' => $payload,
                    'timeout' => 3,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents('http://127.0.0.1:3070/api/internal/commission-auto-assign', false, $context);
        } catch (\Throwable $exception) {
            Log::warning('Affiliate default commission notify failed', [
                'manager' => $manager->employee_code ?? null,
                'member' => $member->employee_code ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function getMyProfile(Request $request): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $user->loadMissing(['team', 'teamLeader', 'managedTeam']);

        $code = $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))));
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
        $hierarchy = $this->getAccessibleHierarchy($user);

        $teamName = $user->team?->name ?: ($user->managedTeam?->name ?: ($user->branch_name ?: 'Fintech'));
        $leaderName = $user->teamLeader?->name ?: ($user->team?->manager?->name ?: '-');

        $profileData = [
            'id' => $user->id,
            'uid' => $user->uid ?: '-',
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?: '-',
            'employee_code' => $code,
            'role' => $roleName,
            'role_title' => $this->getRoleTitle($user),
            'avatar_path' => $user->avatar_path ? (str_starts_with($user->avatar_path, 'http') ? $user->avatar_path : asset('storage/' . $user->avatar_path)) : null,
            'identity_number' => $user->identity_number ?: '-',
            'hire_date' => $user->hire_date ? Carbon::parse($user->hire_date)->format('d/m/Y') : ($user->created_at?->format('d/m/Y') ?: '-'),
            'created_at' => $user->created_at?->format('d/m/Y') ?: '-',
            'team_id' => $user->team_id ?: ($user->managedTeam?->id ?: null),
            'team_name' => $teamName,
            'leader_name' => $leaderName,
            'branch_name' => $user->branch_name ?: '3RD Fintech',
            'employment_status' => ($user->employment_status === 'active' || empty($user->employment_status)) ? 'Đang làm việc' : 'Tạm khóa',
            'bank_name' => $user->bank_name ?: '-',
            'bank_account_number' => $user->bank_account_number ?: '-',
            'bank_account_name' => $user->bank_account_name ?: '-',
            'is_admin' => $user->hasRole('Admin') || $user->hasRole('Super Admin'),
            'can_manage_campaigns' => $user->hasRole('Admin') || $user->hasRole('Super Admin') || $user->hasRole('Director') || $user->hasRole('General Manager') || $user->hasRole('Manager'),
            'managed_members' => $hierarchy !== null ? count($hierarchy['codes']) : 'Toàn hệ thống',
        ];

        return $this->jsonWithCors([
            'success' => true,
            'data' => $profileData,
            'user' => $profileData,
        ]);
    }

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
                'message' => 'Mật khẩu hiện tại không chính xác.',
            ], 422);
        }

        if (strlen($newPassword) < 6) {
            return $this->jsonWithCors([
                'success' => false,
                'message' => 'Mật khẩu mới phải có tối thiểu 6 ký tự.',
            ], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($newPassword),
            'updated_at' => now(),
        ]);

        return $this->jsonWithCors([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công!',
        ]);
    }

    public function resetMemberPassword(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user || (! $user->hasRole('Admin') && ! $user->hasRole('Super Admin') && ! $user->hasRole('Director') && ! $user->hasRole('General Manager'))) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Bạn không có quyền đổi mật khẩu thành viên'], 403);
        }

        $member = User::find($id);
        if (! $member) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không tìm thấy thành viên'], 404);
        }

        $newPassword = (string) $request->input('new_password', '');
        if (strlen($newPassword) < 6) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'], 422);
        }

        DB::table('users')->where('id', $member->id)->update([
            'password' => Hash::make($newPassword),
            'updated_at' => now(),
        ]);

        return $this->jsonWithCors([
            'success' => true,
            'message' => "Đã cập nhật mật khẩu cho thành viên {$member->name} thành công!",
        ]);
    }

    public function updateCampaignLogo(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user || (! $user->hasRole('Admin') && ! $user->hasRole('Super Admin'))) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Bạn không có quyền thay đổi logo'], 403);
        }

        $campaign = AffiliateCampaign::find($id);
        if (! $campaign) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không tìm thấy chiến dịch'], 404);
        }

        $logo = $request->input('logo') ?: $request->input('logo_url');
        if (! $logo) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Vui lòng cung cấp URL logo hợp lệ'], 422);
        }

        $campaign->logo_url = $logo;
        $campaign->save();

        return $this->jsonWithCors(['success' => true, 'message' => 'Cập nhật logo thành công', 'logo_url' => $campaign->logo_url]);
    }

    public function updateCampaignAvailability(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticateRequest($request);
        if (! $user || (! $user->hasRole('Admin') && ! $user->hasRole('Super Admin'))) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Chỉ Admin được đóng/mở chiến dịch'], 403);
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'closure_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $campaign = AffiliateCampaign::find($id);
        if (! $campaign) {
            return $this->jsonWithCors(['success' => false, 'message' => 'Không tìm thấy chiến dịch'], 404);
        }

        $campaign->update([
            'is_active' => (bool) $validated['is_active'],
            'opens_at' => filled($validated['opens_at'] ?? null) ? $validated['opens_at'] : null,
            'closes_at' => filled($validated['closes_at'] ?? null) ? $validated['closes_at'] : null,
            'closure_message' => trim((string) ($validated['closure_message'] ?? '')) ?: null,
        ]);

        Log::notice('Affiliate campaign availability updated', [
            'campaign_id' => $campaign->id,
            'admin_id' => $user->id,
            'is_active' => $campaign->is_active,
            'opens_at' => $campaign->opens_at?->toIso8601String(),
            'closes_at' => $campaign->closes_at?->toIso8601String(),
        ]);

        return $this->jsonWithCors([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái chiến dịch',
            'campaign' => [
                'id' => $campaign->id,
                'is_open' => $campaign->isOpen(),
                'is_active' => $campaign->is_active,
                'opens_at' => $campaign->opens_at?->toIso8601String(),
                'closes_at' => $campaign->closes_at?->toIso8601String(),
                'closure_message' => $campaign->closureReason(),
            ],
        ]);
    }

    private function buildConversionReportQuery(Request $request, User $user)
    {
        $query = AffiliateConversion::query();
        $hierarchy = $this->getAccessibleHierarchy($user);

        if ($hierarchy !== null) {
            $codes = $hierarchy['codes'];
            $userIds = $hierarchy['user_ids'];
            $query->where(function ($q) use ($codes, $userIds): void {
                if (! empty($codes)) {
                    $q->where(function ($subQuery) use ($codes): void {
                        foreach ($codes as $code) {
                            $subQuery->orWhere('aff_sub1', $code)
                                ->orWhere('aff_sub1', 'like', "{$code}%");
                        }
                    });
                }
                if (! empty($userIds)) {
                    $q->orWhereIn('created_by_id', $userIds);
                }
            });
        }

        $campaign = trim((string) $request->input('campaign', ''));
        if ($campaign !== '' && $campaign !== 'all') {
            if (in_array($campaign, ['vpbank', 'vpbank-upl', 'vpbank3t_vaytinchap'], true)) {
                $query->where(function ($q): void {
                    $q->whereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%vpbank%'])
                        ->orWhereRaw("LOWER(COALESCE(partner, '')) LIKE ?", ['%isclix%']);
                });
            } elseif (in_array($campaign, ['shb', 'shb-finance', 'shbfinance'], true)) {
                $query->where(function ($q): void {
                    $q->whereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%shb%'])
                        ->orWhereRaw("LOWER(COALESCE(partner, '')) LIKE ?", ['%hyperlead%']);
                });
            } elseif (in_array($campaign, ['tinvay', 'tinvay-vietcredit'], true)) {
                $query->where(function ($q): void {
                    $q->whereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%tinvay%'])
                        ->orWhereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%tin vay%'])
                        ->orWhereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%vietcredit%'])
                        ->orWhereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%vcredit%']);
                });
            } elseif (in_array($campaign, ['shinhan-finance-android', 'shinhan-android'], true)) {
                $query->where(function ($q): void {
                    $q->whereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%shinhan finance android%'])
                        ->orWhereIn('offer_id', ['shinhan-finance-android', '6949942463850829113']);
                });
            } elseif (in_array($campaign, ['shinhan-finance-ios', 'shinhan-ios'], true)) {
                $query->where(function ($q): void {
                    $q->whereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%shinhan finance ios%'])
                        ->orWhereIn('offer_id', ['shinhan-finance-ios', '6949939948611548600']);
                });
            } else {
                $query->whereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", ['%'.strtolower($campaign).'%']);
            }
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '' && $status !== 'all') {
            if (in_array($status, ['approved', 'success'], true)) {
                $query->whereIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid']);
            } elseif ($status === 'approved_waiting_disbursement') {
                $query->where(function ($q): void {
                    $q->whereRaw('LOWER(conversion_status) = ?', ['approved_waiting_disbursement'])
                        ->orWhere(function ($approvedAmountQuery): void {
                            $approvedAmountQuery
                                ->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])
                                ->where('sale_amount', '>', 0);
                        });
                });
            } elseif (in_array($status, ['rejected', 'cancelled'], true)) {
                $query->whereIn(DB::raw('LOWER(conversion_status)'), ['rejected', 'cancelled', 'failed', 'declined', 'trash']);
            } elseif ($status === 'pending') {
                $query->where(function ($q): void {
                    $q->whereNotIn(DB::raw('LOWER(conversion_status)'), ['success', 'approved', 'disbursed', 'completed', 'paid', 'rejected', 'cancelled', 'failed', 'declined', 'trash'])
                        ->where(function ($amountQuery): void {
                            $amountQuery->whereNull('sale_amount')->orWhere('sale_amount', '<=', 0);
                        })
                        ->orWhereNull('conversion_status');
                });
            }
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('conversion_id', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('aff_sub1', 'like', "%{$search}%")
                    ->orWhere('aff_sub2', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereRaw(
                'COALESCE(conversion_time, click_time, created_at) >= ?',
                [Carbon::parse($request->input('date_from'))->startOfDay()]
            );
        }
        if ($request->filled('date_to')) {
            $query->whereRaw(
                'COALESCE(conversion_time, click_time, created_at) <= ?',
                [Carbon::parse($request->input('date_to'))->endOfDay()]
            );
        }

        return $query;
    }

    private function trafficValidationRules(): array
    {
        return [
            'campaign' => ['nullable', 'string', 'max:150'],
            'employee_code' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'in:direct,zalo,facebook,google,other'],
            'device' => ['nullable', 'in:mobile,desktop'],
            'q' => ['nullable', 'string', 'max:180'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ];
    }

    private function isAffiliateAdmin(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Super Admin');
    }

    private function buildTrafficQuery(Request $request)
    {
        $query = AffiliateClick::query();

        if ($request->filled('campaign') && $request->input('campaign') !== 'all') {
            $campaign = trim((string) $request->input('campaign'));
            $campaignLike = '%'.strtolower($campaign).'%';
            $query->where(function ($campaignQuery) use ($campaign, $campaignLike): void {
                $campaignQuery->where('campaign_slug', $campaign)
                    ->orWhereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", [$campaignLike]);
            });
        }
        if ($request->filled('employee_code') && $request->input('employee_code') !== 'all') {
            $query->whereRaw('UPPER(employee_code) = ?', [strtoupper(trim((string) $request->input('employee_code')))]);
        }
        if ($request->filled('device')) {
            $this->applyTrafficDeviceFilter($query, (string) $request->input('device'));
        }
        if ($request->filled('source')) {
            $this->applyTrafficSourceFilter($query, (string) $request->input('source'));
        }
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $searchLike = '%'.strtolower($search).'%';
            $numericId = preg_replace('/\D+/', '', $search);
            $query->where(function ($searchQuery) use ($searchLike, $numericId): void {
                $searchQuery->whereRaw("LOWER(COALESCE(campaign_name, '')) LIKE ?", [$searchLike])
                    ->orWhereRaw("LOWER(COALESCE(campaign_slug, '')) LIKE ?", [$searchLike])
                    ->orWhereRaw("LOWER(COALESCE(employee_code, '')) LIKE ?", [$searchLike])
                    ->orWhereRaw("LOWER(COALESCE(ip_address, '')) LIKE ?", [$searchLike])
                    ->orWhereRaw("LOWER(COALESCE(referer, '')) LIKE ?", [$searchLike])
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$searchLike]));
                if ($numericId !== '') {
                    $searchQuery->orWhere('id', (int) $numericId);
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->where('clicked_at', '>=', Carbon::createFromFormat('Y-m-d', (string) $request->input('date_from'))->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('clicked_at', '<=', Carbon::createFromFormat('Y-m-d', (string) $request->input('date_to'))->endOfDay());
        }
        if ($request->filled('time_from')) {
            $query->whereTime('clicked_at', '>=', (string) $request->input('time_from'));
        }
        if ($request->filled('time_to')) {
            $query->whereTime('clicked_at', '<=', (string) $request->input('time_to'));
        }

        return $query;
    }

    private function applyTrafficDeviceFilter($query, string $device)
    {
        $mobilePatterns = ['%mobile%', '%android%', '%iphone%', '%ipad%'];
        if ($device === 'mobile') {
            return $query->where(function ($deviceQuery) use ($mobilePatterns): void {
                foreach ($mobilePatterns as $pattern) {
                    $deviceQuery->orWhereRaw('LOWER(COALESCE(user_agent, \'\')) LIKE ?', [$pattern]);
                }
            });
        }

        return $query->where(function ($deviceQuery) use ($mobilePatterns): void {
            $deviceQuery->whereNull('user_agent');
            foreach ($mobilePatterns as $pattern) {
                $deviceQuery->whereRaw('LOWER(COALESCE(user_agent, \'\')) NOT LIKE ?', [$pattern]);
            }
        });
    }

    private function applyTrafficSourceFilter($query, string $source)
    {
        $ref = "LOWER(COALESCE(referer, ''))";
        if ($source === 'direct') {
            return $query->where(function ($sourceQuery): void {
                $sourceQuery->whereNull('referer')->orWhere('referer', '');
            });
        }
        if ($source === 'zalo') {
            return $query->where(fn ($sourceQuery) => $sourceQuery
                ->whereRaw("{$ref} LIKE ?", ['%zalo%'])
                ->orWhereRaw("{$ref} LIKE ?", ['%zarsrc%']));
        }
        if ($source === 'facebook') {
            return $query->where(fn ($sourceQuery) => $sourceQuery
                ->whereRaw("{$ref} LIKE ?", ['%facebook%'])
                ->orWhereRaw("{$ref} LIKE ?", ['%fbclid%']));
        }
        if ($source === 'google') {
            return $query->where(fn ($sourceQuery) => $sourceQuery
                ->whereRaw("{$ref} LIKE ?", ['%google%'])
                ->orWhereRaw("{$ref} LIKE ?", ['%gclid%']));
        }

        return $query->whereNotNull('referer')
            ->where('referer', '<>', '')
            ->whereRaw("{$ref} NOT LIKE ?", ['%zalo%'])
            ->whereRaw("{$ref} NOT LIKE ?", ['%zarsrc%'])
            ->whereRaw("{$ref} NOT LIKE ?", ['%facebook%'])
            ->whereRaw("{$ref} NOT LIKE ?", ['%fbclid%'])
            ->whereRaw("{$ref} NOT LIKE ?", ['%google%'])
            ->whereRaw("{$ref} NOT LIKE ?", ['%gclid%']);
    }

    private function formatTrafficClick(AffiliateClick $click): array
    {
        $userAgent = (string) ($click->user_agent ?: '');
        $employee = $click->user;
        $role = $employee?->roles?->pluck('name')->implode(', ') ?: '-';

        return [
            'traffic_id' => 'TRF-'.str_pad((string) $click->id, 10, '0', STR_PAD_LEFT),
            'click_id' => $click->id,
            'clicked_at' => $click->clicked_at?->format('H:i:s d/m/Y') ?: '-',
            'clicked_at_iso' => $click->clicked_at?->toIso8601String(),
            'campaign_slug' => $click->campaign_slug,
            'campaign_name' => $click->campaign_name ?: $click->campaign_slug,
            'employee_code' => $click->employee_code ?: '-',
            'employee_name' => $employee?->name ?: 'Không rõ tên',
            'role' => $role,
            'team' => $employee?->team?->name ?: '-',
            'team_leader' => $employee?->teamLeader?->name ?: '-',
            'am' => $employee?->am?->name ?: '-',
            'source' => $this->trafficSourceLabel($click->referer),
            'device' => preg_match('/mobile|android|iphone|ipad/i', $userAgent) ? 'Mobile' : 'Desktop',
            'browser' => $this->trafficBrowserLabel($userAgent),
            'ip_address' => $click->ip_address ?: '-',
            'referer' => $click->referer ?: 'Truy cập trực tiếp',
            'user_agent' => $userAgent ?: '-',
        ];
    }

    private function trafficSourceLabel(?string $referer): string
    {
        if (! filled($referer)) {
            return 'Trực tiếp';
        }
        $value = strtolower($referer);
        if (str_contains($value, 'zalo') || str_contains($value, 'zarsrc')) return 'Zalo';
        if (str_contains($value, 'facebook') || str_contains($value, 'fbclid')) return 'Facebook';
        if (str_contains($value, 'google') || str_contains($value, 'gclid')) return 'Google';
        return parse_url($referer, PHP_URL_HOST) ?: 'Nguồn khác';
    }

    private function trafficBrowserLabel(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($userAgent, 'CriOS') || str_contains($userAgent, 'Chrome/') => 'Google Chrome',
            str_contains($userAgent, 'FxiOS') || str_contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Khác',
        };
    }

    private function authenticateRequest(Request $request): ?User
    {
        $token = $request->bearerToken() ?: $request->header('X-Affiliate-Token');
        if (! $token && $request->hasCookie('aff_token')) {
            $token = $request->cookie('aff_token');
        }
        if (! $token && $request->hasCookie('sso_token')) {
            $token = $request->cookie('sso_token');
        }

        if (! $token || ! is_string($token) || ! str_contains($token, '.')) {
            return null;
        }

        $parts = explode('.', $token);

        if (count($parts) === 3) {
            try {
                [$headerB64, $payloadB64, $sigB64] = $parts;
                
                $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'));
                $payload = json_decode($payloadJson, true);
                if (! $payload) return null;

                $userId = $payload['id'] ?? ($payload['uid'] ?? ($payload['sub'] ?? null));
                if (! $userId) return null;

                $ssoSecrets = array_filter([
                    env('SSO_JWT_SECRET', 'bRokIaKqZvOF7h0VuPI8A3RhD2dblYxYzzt9HQC5iB7AG49t'),
                    'bRokIaKqZvOF7h0VuPI8A3RhD2dblYxYzzt9HQC5iB7AG49t',
                    config('app.key'),
                ]);

                $sigValid = false;
                $sigTrimmed = rtrim($sigB64, '=');

                foreach ($ssoSecrets as $secret) {
                    $expectedRaw = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true);
                    $expectedUrlSafe = rtrim(strtr(base64_encode($expectedRaw), '+/', '-_'), '=');
                    $expectedB64 = rtrim(base64_encode($expectedRaw), '=');
                    $expectedHex = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret);

                    if (hash_equals($expectedUrlSafe, $sigTrimmed) || hash_equals($expectedB64, $sigTrimmed) || hash_equals($expectedHex, $sigB64)) {
                        $sigValid = true;
                        break;
                    }
                }

                if (! $sigValid) return null;

                return User::find($userId);
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (count($parts) === 2) {
            [$payloadB64, $signature] = $parts;
            $secret = config('app.key');
            $expectedSig = hash_hmac('sha256', $payloadB64, $secret);

            if (! hash_equals($expectedSig, $signature)) {
                return null;
            }

            $payload = json_decode(base64_decode($payloadB64), true);
            if (! $payload || empty($payload['id'])) {
                return null;
            }

            return User::find($payload['id']);
        }

        return null;
    }

    private function generateToken(User $user): string
    {
        $code = $user->employee_code ?: ($user->username ?: ($user->uid ?: ('RD' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT))));
        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'code' => $code,
            'time' => time(),
        ];
        $payloadB64 = base64_encode(json_encode($payload));
        $secret = config('app.key');
        $signature = hash_hmac('sha256', $payloadB64, $secret);

        return "{$payloadB64}.{$signature}";
    }

    private function getAccessibleHierarchy(User $user): ?array
    {
        if ($user->hasRole('Admin') || $user->hasRole('Super Admin') || $user->hasRole('Director') || $user->hasRole('General Manager') || $user->hasRole('BOD') || $user->employee_code === 'RD260001') {
            return null;
        }

        $userIds = [$user->id];
        $codes = array_filter([$user->employee_code, $user->username, $user->uid]);

        // Cấp quản lý bao gồm: Team Leader, AM, ZD, Courier Manager, Manager, hoặc người quản lý team trong CrmTeam
        $isManager = $user->hasRole(['Manager', 'Team Leader', 'AM', 'ZD', 'Courier Manager', 'Trưởng nhóm', 'Quản lý'])
            || \App\Models\CrmTeam::where('manager_id', $user->id)->exists()
            || User::where('team_leader_id', $user->id)->orWhere('am_id', $user->id)->orWhere('zd_id', $user->id)->orWhere('created_by_id', $user->id)->exists();

        if ($isManager) {
            $managedTeamIds = \App\Models\CrmTeam::where('manager_id', $user->id)->pluck('id')->toArray();
            $managedTeamIds = array_values(array_filter(array_unique($managedTeamIds)));

            // team_id only identifies the team a user belongs to. It must not grant
            // management access unless that user is the configured CrmTeam manager.
            // Otherwise a Team Leader sharing an AM's team would see every sale of the AM.

            $subordinates = User::query()
                ->where('id', '!=', $user->id)
                ->where(function ($q) use ($user, $managedTeamIds) {
                    $q->where('team_leader_id', $user->id)
                      ->orWhere('am_id', $user->id)
                      ->orWhere('zd_id', $user->id)
                      ->orWhere('courier_manager_id', $user->id)
                      ->orWhere('created_by_id', $user->id);
                    
                    if (!empty($managedTeamIds)) {
                        $q->orWhereIn('team_id', $managedTeamIds);
                    }
                })
                ->get(['id', 'employee_code', 'username', 'uid']);

            foreach ($subordinates as $sub) {
                $userIds[] = $sub->id;
                if ($sub->employee_code) $codes[] = $sub->employee_code;
                if ($sub->username) $codes[] = $sub->username;
                if ($sub->uid) $codes[] = $sub->uid;
            }
        }

        return [
            'user_ids' => array_values(array_unique($userIds)),
            'codes' => array_values(array_unique($codes)),
        ];
    }

    private function getRoleTitle(User $user): string
    {
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Direct Sale') : 'Direct Sale';
        return match ($roleName) {
            'Admin', 'Super Admin' => 'Quản trị viên cấp cao',
            'Director', 'General Manager' => 'Ban Giám Đốc',
            'Manager' => 'Quản lý kinh doanh',
            'Team Leader', 'Trưởng nhóm' => 'Trưởng nhóm kinh doanh',
            'Publisher', 'Cộng tác viên', 'Affiliate Publisher' => 'Cộng tác viên tiếp thị (Publisher)',
            'AM' => 'Quản lý khu vực (AM)',
            'ZD' => 'Giám đốc vùng (ZD)',
            default => 'Chuyên viên tư vấn (Direct Sale)',
        };
    }
}
