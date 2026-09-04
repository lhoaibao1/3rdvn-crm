<?php

namespace App\Support;

use App\Models\Application;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

class LosApplicationPresenter
{
    public static function make(Application $application): array
    {
        $payload = (array) ($application->payload ?? []);
        $fields = (array) ($payload['fields'] ?? []);
        $legacy = (array) ($payload['module_fields'] ?? []);
        $merged = array_merge($legacy, $fields);
        $review = (array) ($payload['review'] ?? []);
        $workflow = (array) ($payload['workflow'] ?? []);
        $documents = (array) ($payload['documents'] ?? []);

        // ─── FEOL Integration Data & Raw Payload Extraction ───
        $feolIntegration = null;
        try {
            if ($application->relationLoaded('feolIntegration')) {
                $feolIntegration = $application->getRelation('feolIntegration');
            } else {
                $feolIntegration = $application->feolIntegration;
            }
        } catch (Throwable) {
            $feolIntegration = null;
        }

        $feolRaw = (array) ($feolIntegration?->raw_payload ?? []);
        $feolHist = (array) ($feolRaw['import']['history'][0] ?? []);
        $feolBridge = (array) ($feolRaw['_bridge'] ?? []);

        $projectSlug = (string) (
            $application->relationLoaded('salesProject')
                ? $application->getRelation('salesProject')?->slug
                : ($application->salesProject?->slug ?? null)
        );
        $projectName = (string) (
            $application->relationLoaded('salesProject')
                ? ($application->getRelation('salesProject')?->name ?? 'Dự án')
                : ($application->salesProject?->name ?? 'Dự án')
        );

        $applicantName = self::cleanString(self::firstFilled([
            $application->applicant_name,
            $merged['customer_name'] ?? null,
            $merged['applicant_name'] ?? null,
            $merged['lead_name'] ?? null,
            $feolRaw['customer_name'] ?? null,
            $feolHist['ten_khach_hang'] ?? null,
        ]));

        $rawIdentity = self::firstFilled([
            $application->identity_number,
            $merged['identity_number'] ?? null,
            $merged['cccd'] ?? null,
            $merged['cmnd'] ?? null,
            $feolRaw['customer_id_no'] ?? null,
            $feolHist['cccd'] ?? null,
        ]);

        $rawPhone = self::firstFilled([
            $application->phone,
            $merged['phone'] ?? null,
            $feolRaw['customer_mobile'] ?? null,
            $feolHist['so_dien_thoai'] ?? null,
        ]);

        // 🔒 MÃ HOÁ BẢO MẬT CCCD VÀ SỐ ĐIỆN THOẠI
        $maskedIdentity = self::maskIdentity($rawIdentity);
        $maskedPhone = self::maskPhone($rawPhone);

        // ─── SẢN PHẨM & MÃ SCHEME CHI TIẾT ───
        $schemeCode = self::cleanString(self::firstFilled([
            $merged['scheme_code'] ?? null,
            $merged['scheme'] ?? null,
            $feolRaw['app_type'] ?? null,
            $feolHist['app_type'] ?? null,
        ]));

        $schemeName = self::cleanString(self::firstFilled([
            $merged['scheme_name'] ?? null,
            $merged['scheme_product_line'] ?? null,
            $merged['scheme_product'] ?? null,
            $merged['scheme_code'] ?? null,
            $feolBridge['campaign'] ?? null,
            $feolHist['chien_dich'] ?? null,
        ]));

        $productName = self::cleanString(self::firstFilled([
            $merged['scheme_product'] ?? null,
            $merged['product'] ?? null,
            $merged['product_name'] ?? null,
            $review['product'] ?? null,
            $merged['scheme_product_type'] ?? null,
            $feolRaw['app_type'] ?? null,
            $feolHist['app_type'] ?? null,
            $projectName,
        ]));

        $schemeOrProduct = $schemeName !== '-' 
            ? $schemeName 
            : ($productName !== '-' ? $productName : ($schemeCode !== '-' ? ('Scheme ' . $schemeCode) : 'Gói tiêu chuẩn'));

        // ─── 1. KHOẢN VAY ĐỀ XUẤT (KHÁCH HÀNG ĐĂNG KÝ) ───
        $isFEOL = $feolIntegration !== null || $projectSlug === 'fe-deeplink';

        $requestedSources = [
            $application->loan_amount,
            $merged['loan_amount'] ?? null,
            $merged['amount'] ?? null,
            $merged['requested_amount'] ?? null,
            $merged['so_tien_vay'] ?? null,
            $merged['loan_limit'] ?? null,
            $feolRaw['offer_amt'] ?? null,
            $feolHist['offer_amt'] ?? null,
            $feolRaw['loan_amt'] ?? null,
            $application->lead?->payload['amount'] ?? null,
        ];
        // FE: review.approved_amount thực chất = Offer Amt từ đối tác → dùng làm đề xuất
        if ($isFEOL) {
            $requestedSources[] = $review['approved_amount'] ?? null;
        }
        $requestedLoanAmount = self::money($requestedSources);

        // ─── 2. SỐ TIỀN GIẢI NGÂN / PHÊ DUYỆT (CHỈ TÍNH KHI ĐÃ GIẢI NGÂN HOẶC PHÊ DUYỆT) ───
        $rawStatus = mb_strtolower((string) $application->status);
        // eligible = chỉ đủ điều kiện sơ bộ, CHƯA phê duyệt/giải ngân -> KHÔNG tính
        $isDisbursed = str_contains($rawStatus, 'disburs');
        $isAppApproved = $isDisbursed || str_contains($rawStatus, 'approved') || $rawStatus === 'completed';



        if ($isAppApproved) {
            if ($isFEOL) {
                // FE: CHỈ lấy số tiền giải ngân thực tế từ đối tác (disbursed_amt)
                // KHÔNG fallback về review.approved_amount vì đó là phê duyệt, không phải giải ngân
                $approvedLoanAmount = self::money([
                    $feolRaw['disbursed_amt'] ?? null,
                    $feolHist['disbursed_amt'] ?? null,
                ]);
            } else {
                // CRM / Lotte / Non-FE: Lấy số tiền phê duyệt từ review
                $approvedLoanAmount = self::money([
                    $review['approved_amount'] ?? null,
                    $review['pre_approved_amount'] ?? null,
                    $review['final_amount'] ?? null,
                    $feolRaw['amt_approved'] ?? null,
                    $feolRaw['disbursed_amt'] ?? null,
                    $feolHist['disbursed_amt'] ?? null,
                    $merged['approved_loan_amount'] ?? null,
                    $merged['approved_amount'] ?? null,
                    ($isDisbursed ? $application->loan_amount : null),
                    ($isDisbursed ? ($merged['loan_amount'] ?? null) : null),
                ]);
            }
        } else {
            // Chưa được phê duyệt / giải ngân -> Tuyệt đối không có số tiền
            $approvedLoanAmount = null;
        }

        $creatorUser = null;
        if ($application->relationLoaded('createdBy') && $application->getRelation('createdBy')) {
            $creatorUser = $application->getRelation('createdBy');
        } elseif ($application->relationLoaded('assignedSale') && $application->getRelation('assignedSale')) {
            $creatorUser = $application->getRelation('assignedSale');
        }

        $rawCreatorCode = self::firstFilled([
            $feolRaw['sale_code'] ?? null,
            $feolRaw['username'] ?? null,
            $feolHist['nhan_vien'] ?? null,
            $feolHist['ma_gioi_thieu'] ?? null,
            $feolHist['pic'] ?? null,
            $merged['source_employee_code'] ?? null,
            $merged['referral_code'] ?? null,
        ]);

        $resolvedNameFromCode = self::getUserNameByCode($rawCreatorCode);

        $creator = self::cleanString(self::firstFilled([
            $creatorUser ? ($creatorUser->name . ' (' . ($creatorUser->employee_code ?: $creatorUser->uid) . ')') : null,
            $resolvedNameFromCode,
            $rawCreatorCode,
            'NVKD',
        ]));

        // Exact CRM Status Mapping (preserves English terms exactly as in CRM)
        $statusLabel = self::cleanString(self::crmStatusLabel($application->status, $projectSlug));
        $statusTone = self::crmStatusTone($application->status, $projectSlug);

        $dob = self::cleanString((string) ($merged['date_of_birth'] ?? ($merged['dob'] ?? '-')));

        // ─── TAB 1: THÔNG TIN ĐỊNH DANH & KHÁCH HÀNG ───
        $customerFields = [
            self::field('Họ và tên khách hàng', $applicantName),
            self::field('Số CCCD / CMND (Đã mã hóa)', $maskedIdentity),
            self::field('Số điện thoại (Đã mã hóa)', $maskedPhone),
            self::field('Ngày tháng năm sinh', $dob),
        ];

        // Add Scheme & Product in Customer Tab
        if ($schemeCode !== '-' || $schemeName !== '-' || $productName !== '-') {
            $customerFields[] = self::field('Sản phẩm / Gói vay', $productName !== '-' ? $productName : $schemeOrProduct);
            if ($schemeCode !== '-') {
                $customerFields[] = self::field('Mã Scheme / App Type', $schemeCode);
            }
            if ($schemeName !== '-' && $schemeName !== $productName) {
                $customerFields[] = self::field('Tên gói Scheme / Chiến dịch', $schemeName);
            }
            if (filled($merged['scheme_loan_period'] ?? null)) {
                $customerFields[] = self::field('Kỳ hạn vay Scheme', self::cleanString((string) $merged['scheme_loan_period']));
            }
            if (filled($merged['scheme_interest_rate'] ?? null)) {
                $customerFields[] = self::field('Lãi suất Scheme', self::cleanString((string) $merged['scheme_interest_rate'] . '%'));
            }
        }

        if (filled($feolRaw['cust_cate'] ?? null)) {
            $customerFields[] = self::field('Phân khúc khách hàng (Cust Cate)', self::cleanString((string) $feolRaw['cust_cate']));
        }
        if (filled($feolRaw['landing_referral_code'] ?? ($merged['referral_code'] ?? null))) {
            $customerFields[] = self::field('Mã giới thiệu (Referral Code)', self::cleanString((string) ($feolRaw['landing_referral_code'] ?? $merged['referral_code'])));
        }

        if (filled($merged['gender'] ?? null)) {
            $customerFields[] = self::field('Giới tính', self::cleanString((string) $merged['gender']));
        }
        if (filled($merged['identity_issued_date'] ?? null)) {
            $customerFields[] = self::field('Ngày cấp CCCD', self::cleanString((string) $merged['identity_issued_date']));
        }
        if (filled($merged['identity_issued_place'] ?? null)) {
            $customerFields[] = self::field('Nơi cấp CCCD', self::cleanString((string) $merged['identity_issued_place']));
        }
        if (filled($merged['identity_expiry_date'] ?? null)) {
            $customerFields[] = self::field('Ngày hết hạn CCCD', self::cleanString((string) $merged['identity_expiry_date']));
        }
        if (filled($merged['marital_status'] ?? null)) {
            $customerFields[] = self::field('Tình trạng hôn nhân', self::cleanString((string) $merged['marital_status']));
        }
        if (filled($merged['education'] ?? null)) {
            $customerFields[] = self::field('Trình độ học vấn', self::cleanString((string) $merged['education']));
        }
        
        $currentAddress = self::formatAddress($merged, 'current');
        if ($currentAddress !== '-') {
            $customerFields[] = self::field('Địa chỉ hiện tại', $currentAddress, null, true);
        }

        $permanentAddress = self::formatAddress($merged, 'permanent');
        if ($permanentAddress !== '-') {
            $customerFields[] = self::field('Địa chỉ thường trú', $permanentAddress, null, true);
        }

        if ($requestedLoanAmount) {
            $customerFields[] = self::field('Khoản vay đề xuất (Nhu cầu)', self::moneyLabel($requestedLoanAmount), 'primary');
        }
        if ($approvedLoanAmount) {
            $customerFields[] = self::field('Khoản vay được phê duyệt', self::moneyLabel($approvedLoanAmount), 'success');
        }

        if (filled($merged['bank_account_name'] ?? null)) {
            $customerFields[] = self::field('Tên chủ tài khoản', self::cleanString((string) $merged['bank_account_name']));
        }
        if (filled($merged['bank_account_number'] ?? null)) {
            $customerFields[] = self::field('Số tài khoản ngân hàng', self::cleanString((string) $merged['bank_account_number']));
        }
        if (filled($merged['bank_name'] ?? null)) {
            $customerFields[] = self::field('Ngân hàng thụ hưởng', self::cleanString((string) $merged['bank_name']));
        }
        if (filled($merged['employer_name'] ?? null)) {
            $customerFields[] = self::field('Đơn vị công tác / Công ty', self::cleanString((string) $merged['employer_name']));
        }
        if (filled($merged['employer_tax_code'] ?? null)) {
            $customerFields[] = self::field('Mã số thuế công ty', self::cleanString((string) $merged['employer_tax_code']));
        }
        if (filled($merged['contract_type'] ?? null)) {
            $customerFields[] = self::field('Loại hợp đồng lao động', self::cleanString((string) $merged['contract_type']));
        }
        if (filled($merged['monthly_income'] ?? ($merged['income'] ?? null))) {
            $incomeVal = self::money([$merged['monthly_income'] ?? $merged['income']]);
            $customerFields[] = self::field('Thu nhập hàng tháng', self::moneyLabel($incomeVal));
        }

        // ─── TAB 2: HỒ SƠ CHỨNG TỪ & TÀI LIỆU (DOCUMENTS) ───
        $documentList = [];
        foreach ($documents as $docKey => $docVal) {
            if (!filled($docVal)) continue;
            
            $docLabel = match($docKey) {
                'consent_6088' => 'Đơn đồng thuận xử lý dữ liệu (Consent 6088)',
                'application_form' => 'Đơn đề nghị vay vốn',
                'id_front' => 'CCCD / CMND Mặt trước',
                'id_back' => 'CCCD / CMND Mặt sau',
                'income_proof' => 'Chứng minh thu nhập / Sao kê',
                'residence_proof' => 'Chứng minh cư trú / Hộ khẩu',
                default => str($docKey)->replace('_', ' ')->title()->toString(),
            };

            $docPath = self::cleanString((string) $docVal);
            $docUrl = str_starts_with($docPath, 'http') ? $docPath : asset('storage/' . ltrim($docPath, '/'));
            $isImage = preg_match('/\.(jpeg|jpg|png|webp|gif)$/i', $docPath);

            $documentList[] = [
                'key' => self::cleanString((string)$docKey),
                'label' => self::cleanString($docLabel),
                'path' => $docPath,
                'url' => $docUrl,
                'is_image' => (bool)$isImage,
            ];
        }

        // ─── TAB 3: THẨM ĐỊNH & PHÊ DUYỆT (UNDERWRITING) ───
        $reviewFields = [
            self::field('Mã hồ sơ', self::cleanString((string) $application->application_code)),
            self::field('Dự án', $projectName),
            self::field('Trạng thái CRM', $statusLabel, $statusTone),
            self::field('Sản phẩm / Mã Scheme', $schemeOrProduct),
        ];

        // FE Deeplink specific statuses in Tab 3
        if ($feolIntegration || $projectSlug === 'fe-deeplink') {
            if (filled($feolIntegration?->main_status ?: ($feolHist['trang_thai_chinh'] ?? null))) {
                $reviewFields[] = self::field('Trạng thái chính FEOL', self::cleanString((string) ($feolIntegration?->main_status ?: $feolHist['trang_thai_chinh'])));
            }
            if (filled($feolIntegration?->sub_status ?: ($feolHist['trang_thai_phu'] ?? null))) {
                $reviewFields[] = self::field('Trạng thái phụ FEOL', self::cleanString((string) ($feolIntegration?->sub_status ?: $feolHist['trang_thai_phu'])));
            }
            if (filled($feolIntegration?->partner_app_id ?: ($feolRaw['app_id'] ?? ($feolHist['app_id'] ?? null)))) {
                $reviewFields[] = self::field('Mã App đối tác FE', self::cleanString((string) ($feolIntegration?->partner_app_id ?: ($feolRaw['app_id'] ?? $feolHist['app_id']))));
            }
        }

        if (filled($merged['scheme_description'] ?? null)) {
            $reviewFields[] = self::field('Mô tả gói Scheme', self::cleanString((string) $merged['scheme_description']), null, true);
        }

        $rejectionReason = $application->note 
            ?: ($review['review_note'] 
            ?? ($workflow['last_transition']['note'] 
            ?? ($feolRaw['remark'] 
            ?? ($feolRaw['action_code'] 
            ?? ($feolHist['ghi_chu'] ?? null)))));

        if (filled($rejectionReason)) {
            $reviewFields[] = self::field('Lý do phản hồi / Ghi chú thẩm định', self::cleanString((string) $rejectionReason), $statusTone, true);
        }

        if (filled($review['decision'] ?? null)) {
            $reviewFields[] = self::field('Kết quả kiểm tra', self::cleanString((string) $review['decision']), self::decisionTone($review['decision']));
        }
        if (filled($review['reviewed_at'] ?? null)) {
            $reviewFields[] = self::field('Thời gian kiểm tra', self::payloadDate($review['reviewed_at']));
        }
        if (filled($review['otp'] ?? null)) {
            $reviewFields[] = self::field('Mã OTP xác thực', self::cleanString((string) $review['otp']));
        }

        // Strictly Separate Requested vs Approved vs Disbursed vs Topup
        $reviewFields[] = self::field('Khoản vay đề xuất (Nhu cầu)', self::moneyLabel($requestedLoanAmount), $requestedLoanAmount ? 'primary' : null);
        $reviewFields[] = self::field('Khoản vay được phê duyệt', self::moneyLabel($approvedLoanAmount), $approvedLoanAmount ? 'success' : null);

        if (filled($feolRaw['disbursed_amt'] ?? ($feolHist['disbursed_amt'] ?? null))) {
            $disbursedAmtVal = self::money([$feolRaw['disbursed_amt'] ?? $feolHist['disbursed_amt']]);
            $reviewFields[] = self::field('Số tiền giải ngân thực tế', self::moneyLabel($disbursedAmtVal), 'success');
        }
        if (filled($feolRaw['topup_amt'] ?? ($feolHist['topup_amt'] ?? null))) {
            $topupAmtVal = self::money([$feolRaw['topup_amt'] ?? $feolHist['topup_amt']]);
            $reviewFields[] = self::field('Số tiền Topup', self::moneyLabel($topupAmtVal));
        }
        if (filled($feolRaw['insurance_amt'] ?? ($feolHist['insurance_amt'] ?? null))) {
            $insAmtVal = self::money([$feolRaw['insurance_amt'] ?? $feolHist['insurance_amt']]);
            $reviewFields[] = self::field('Phí bảo hiểm', self::moneyLabel($insAmtVal));
        }
        if (filled($feolRaw['fee_amt'] ?? ($feolHist['fee_amt'] ?? null))) {
            $feeAmtVal = self::money([$feolRaw['fee_amt'] ?? $feolHist['fee_amt']]);
            $reviewFields[] = self::field('Phí dịch vụ / Xử lý', self::moneyLabel($feeAmtVal));
        }
        if (filled($feolRaw['disbursed_date'] ?? ($feolHist['disbursed_date'] ?? null))) {
            $reviewFields[] = self::field('Ngày giải ngân', self::cleanString((string) ($feolRaw['disbursed_date'] ?? $feolHist['disbursed_date'])));
        }

        if (filled($review['pre_approved_months'] ?? null) || filled($merged['scheme_loan_period'] ?? null)) {
            $period = !empty($review['pre_approved_months']) ? trim((string)$review['pre_approved_months']) . ' tháng' : (string)($merged['scheme_loan_period'] ?? '-');
            $reviewFields[] = self::field('Thời hạn vay', self::cleanString($period));
        }
        if (filled($review['pre_approved_interest_rate'] ?? null) || filled($merged['scheme_interest_rate'] ?? null)) {
            $rate = !empty($review['pre_approved_interest_rate']) ? self::percentage([$review['pre_approved_interest_rate']]) : (isset($merged['scheme_interest_rate']) ? ((string)$merged['scheme_interest_rate'] . '%') : '-');
            $reviewFields[] = self::field('Lãi suất áp dụng', self::cleanString($rate));
        }

        // ─── TAB 4: FEOL DEEPLINK & TÍCH HỢP ĐỐI TÁC (ĐẦY ĐỦ TẤT CẢ CÁC TRƯỜNG FE) ───
        $feolFields = [];
        if ($feolIntegration || $projectSlug === 'fe-deeplink' || str_contains((string)$projectSlug, 'feol')) {
            $syncStateVal = is_object($feolIntegration?->sync_state) ? ($feolIntegration->sync_state->value ?? (string)$feolIntegration->sync_state) : (string)($feolIntegration?->sync_state ?? '-');
            $submitStateVal = is_object($feolIntegration?->submit_state) ? ($feolIntegration->submit_state->value ?? (string)$feolIntegration->submit_state) : (string)($feolIntegration?->submit_state ?? '-');

            $syncStateLabel = match ($syncStateVal) {
                'idle' => 'Chờ kích hoạt',
                'pending' => 'Chờ đồng bộ',
                'processing' => 'Đang đồng bộ',
                'synced' => 'Đã đồng bộ',
                'failed' => 'Đồng bộ lỗi',
                'terminal' => 'Hoàn tất',
                default => filled($syncStateVal) ? (string)$syncStateVal : '-',
            };

            $submitStateLabel = match ($submitStateVal) {
                'awaiting_customer' => 'Chờ khách hàng nhập',
                'queued' => 'Chờ gửi đối tác',
                'processing' => 'Đang gửi đối tác',
                'submitted' => 'Đã gửi đối tác',
                'failed' => 'Gửi đối tác lỗi',
                default => filled($submitStateVal) ? (string)$submitStateVal : '-',
            };

            $feLeadId = self::cleanString((string) ($feolIntegration?->partner_lead_id ?: ($feolRaw['id'] ?? ($feolHist['lead_id'] ?? '-'))));
            $feAppId = self::cleanString((string) ($feolIntegration?->partner_app_id ?: ($feolRaw['app_id'] ?? ($feolHist['app_id'] ?? '-'))));
            $feReqId = self::cleanString((string) ($feolIntegration?->partner_request_id ?? '-'));

            $feolFields = [
                self::field('Mã Lead FE Credit (Partner Lead ID)', $feLeadId),
                self::field('Mã App FE Credit (Partner App ID)', $feAppId),
                self::field('Mã Request ID đối tác', $feReqId),
                self::field('Trạng thái chính FEOL (Main Status)', self::cleanString((string) ($feolIntegration?->main_status ?: ($feolHist['trang_thai_chinh'] ?? '-')))),
                self::field('Trạng thái phụ FEOL (Sub Status)', self::cleanString((string) ($feolIntegration?->sub_status ?: ($feolHist['trang_thai_phu'] ?? '-')))),
                self::field('Trạng thái đồng bộ (Sync State)', $syncStateLabel),
                self::field('Trạng thái gửi FEOL (Submit State)', $submitStateLabel),
                self::field('Số lần thử gửi (Submit Attempts)', (string) ($feolIntegration?->submit_attempts ?? '0')),
                self::field('Lần thử gửi gần nhất', self::dateTime($feolIntegration?->partner_last_attempt_at)),
                self::field('Thời gian gửi đối tác thành công', self::dateTime($feolIntegration?->partner_submitted_at)),
                self::field('Thời gian đồng bộ gần nhất', self::dateTime($feolIntegration?->last_synced_at ?: ($feolHist['thoi_gian_cap_nhat'] ?? null))),
            ];

            if (filled($feolRaw['changed_date'] ?? null)) {
                $feolFields[] = self::field('Thời gian thay đổi trạng thái FE', self::cleanString((string) $feolRaw['changed_date']));
            }
            if (filled($feolRaw['created_date'] ?? null)) {
                $feolFields[] = self::field('Thời gian tạo hồ sơ FE', self::cleanString((string) $feolRaw['created_date']));
            }

            if (filled($feolRaw['app_type'] ?? ($feolHist['app_type'] ?? null))) {
                $feolFields[] = self::field('Loại khoản vay FE (App Type)', self::cleanString((string) ($feolRaw['app_type'] ?? $feolHist['app_type'])));
            }
            if (filled($feolRaw['cust_cate'] ?? null)) {
                $feolFields[] = self::field('Phân khúc khách hàng (Cust Cate)', self::cleanString((string) $feolRaw['cust_cate']));
            }

            if (filled($feolBridge['campaign'] ?? ($feolHist['chien_dich'] ?? ($merged['source_campaign'] ?? null)))) {
                $feolFields[] = self::field('Chiến dịch FEOL (Campaign)', self::cleanString((string) ($feolBridge['campaign'] ?? ($feolHist['chien_dich'] ?? $merged['source_campaign']))));
            }
            if (filled($feolRaw['sale_code'] ?? ($feolHist['nhan_vien'] ?? ($merged['source_employee_code'] ?? null)))) {
                $feolFields[] = self::field('Mã NV giới thiệu (Sale Code)', self::cleanString((string) ($feolRaw['sale_code'] ?? ($feolHist['nhan_vien'] ?? $merged['source_employee_code']))));
            }
            if (filled($feolRaw['teamlead'] ?? ($feolHist['quan_ly'] ?? ($merged['source_manager_code'] ?? null)))) {
                $feolFields[] = self::field('Quản lý trực tiếp (Team Leader)', self::cleanString((string) ($feolRaw['teamlead'] ?? ($feolHist['quan_ly'] ?? $merged['source_manager_code']))));
            }
            if (filled($feolRaw['username'] ?? ($feolHist['pic'] ?? null))) {
                $feolFields[] = self::field('Người xử lý PIC (FE Username)', self::cleanString((string) ($feolRaw['username'] ?? $feolHist['pic'])));
            }

            if (filled($feolRaw['action_code'] ?? null)) {
                $feolFields[] = self::field('Mã hành động FE (Action Code)', self::cleanString((string) $feolRaw['action_code']));
            }
            if (filled($feolRaw['remark'] ?? ($feolHist['ghi_chu'] ?? null))) {
                $feolFields[] = self::field('Ghi chú chi tiết FE (Remark)', self::cleanString((string) ($feolRaw['remark'] ?? $feolHist['ghi_chu'])), null, true);
            }

            $deeplinkVal = $feolIntegration?->deeplink_url ?: ($feolRaw['deeplink'] ?? null);
            if (filled($deeplinkVal)) {
                $feolFields[] = self::field('Link Deeplink đăng ký FE Credit', (string) $deeplinkVal, null, true);
            }
            if (filled($feolIntegration?->b1_url)) {
                $feolFields[] = self::field('Link B1 FE Credit', (string) $feolIntegration->b1_url, null, true);
            }

            if (filled($feolIntegration?->public_token)) {
                $feolFields[] = self::field('Public Token', (string) $feolIntegration->public_token);
            }
            if (filled($feolIntegration?->version)) {
                $feolFields[] = self::field('Phiên bản tích hợp (Version)', (string) $feolIntegration->version);
            }
            if (filled($feolRaw['import']['source_file'] ?? null)) {
                $feolFields[] = self::field('File nguồn Import', (string) $feolRaw['import']['source_file']);
            }

            if (filled($feolIntegration?->submit_last_error)) {
                $feolFields[] = self::field('Lỗi gửi đối tác (Submit Error)', (string) $feolIntegration->submit_last_error, 'danger', true);
            }
            if (filled($feolIntegration?->last_error)) {
                $feolFields[] = self::field('Lỗi đồng bộ (Sync Error)', (string) $feolIntegration->last_error, 'danger', true);
            }

            if (!empty($feolIntegration?->partner_submit_response)) {
                $respStr = json_encode($feolIntegration->partner_submit_response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $feolFields[] = self::field('Kết quả trả về từ Gateway FE', (string) $respStr, null, true);
            }
        }

        // ─── TAB 5: NHÂN SỰ & LUỒNG XỬ LÝ ───
        $saleCode = $application->relationLoaded('assignedSale') && $application->getRelation('assignedSale')
            ? ($application->getRelation('assignedSale')->name . ' · ' . ($application->getRelation('assignedSale')->employee_code ?: $application->getRelation('assignedSale')->uid))
            : ($creator !== '-' ? $creator : '-');
        $creatorName = $application->relationLoaded('createdBy') && $application->getRelation('createdBy')
            ? ($application->getRelation('createdBy')->name . ' · ' . ($application->getRelation('createdBy')->employee_code ?: $application->getRelation('createdBy')->uid))
            : '-';
        $teamName = $application->relationLoaded('team') && $application->getRelation('team')
            ? ($application->getRelation('team')->name ?? '-')
            : '-';
        $leaderName = $application->relationLoaded('teamLeader') && $application->getRelation('teamLeader')
            ? ($application->getRelation('teamLeader')->name ?? '-')
            : (self::cleanString((string)($feolRaw['teamlead'] ?? ($feolHist['quan_ly'] ?? '-'))));

        $personnelFields = [
            self::field('Chuyên viên kinh doanh (NVKD)', self::cleanString($saleCode)),
            self::field('Người tạo hồ sơ', self::cleanString($creatorName)),
            self::field('Team kinh doanh', self::cleanString($teamName)),
            self::field('Trưởng nhóm (Team Leader)', self::cleanString($leaderName)),
            self::field('Nguồn khởi tạo', self::cleanString((string) ($workflow['source'] ?? ($feolBridge['campaign'] ?? 'Hệ thống CRM LOS')))),
            self::field('Thời gian tạo hồ sơ', self::dateTime($application->created_at)),
            self::field('Thời gian cập nhật gần nhất', self::dateTime($application->updated_at)),
        ];

        // ─── TAB 6: LỊCH SỬ XỬ LÝ & TIMELINE NHẬT KÝ CRM ───
        $timelineEvents = [];

        if (!empty($workflow['last_transition'])) {
            $trans = $workflow['last_transition'];
            $fromLabel = self::cleanString(self::crmStatusLabel($trans['from'] ?? null, $projectSlug));
            $toLabel = self::cleanString(self::crmStatusLabel($trans['to'] ?? null, $projectSlug));
            $timelineEvents[] = [
                'title' => "Chuyển bước: {$fromLabel} → {$toLabel}",
                'actor' => 'Người xử lý ID: ' . ($trans['actor_id'] ?? '-'),
                'time' => self::payloadDate($trans['at'] ?? null),
                'note' => self::cleanString((string) ($trans['note'] ?? 'Cập nhật trạng thái')),
                'tone' => self::crmStatusTone($trans['to'] ?? null, $projectSlug),
            ];
        }

        if (!empty($workflow['last_otp_update'])) {
            $otpUp = $workflow['last_otp_update'];
            $timelineEvents[] = [
                'title' => 'Cập nhật OTP xác thực',
                'actor' => 'Người thao tác ID: ' . ($otpUp['actor_id'] ?? '-'),
                'time' => self::payloadDate($otpUp['at'] ?? null),
                'note' => 'Đã nhập mã OTP khách hàng',
                'tone' => 'primary',
            ];
        }

        if ($application->relationLoaded('changeLogs')) {
            foreach ($application->getRelation('changeLogs') as $log) {
                $actorName = $log->actor ? ($log->actor->name . ' (' . ($log->actor->employee_code ?: $log->actor->uid) . ')') : 'Hệ thống';
                $changesSummary = [];
                if (is_array($log->changes)) {
                    foreach ($log->changes as $fieldKey => $change) {
                        if ($fieldKey === 'payload') continue;
                        $oldVal = is_array($change['old'] ?? null) ? json_encode($change['old']) : ($change['old'] ?? '-');
                        $newVal = is_array($change['new'] ?? null) ? json_encode($change['new']) : ($change['new'] ?? '-');
                        if ($fieldKey === 'status') {
                            $oldVal = self::crmStatusLabel($oldVal, $projectSlug);
                            $newVal = self::crmStatusLabel($newVal, $projectSlug);
                        }
                        $changesSummary[] = "{$fieldKey}: {$oldVal} → {$newVal}";
                    }
                }

                $timelineEvents[] = [
                    'title' => 'Hành động: ' . strtoupper((string)$log->action),
                    'actor' => self::cleanString($actorName),
                    'time' => self::dateTime($log->created_at),
                    'note' => self::cleanString(!empty($changesSummary) ? implode('; ', $changesSummary) : 'Cập nhật dữ liệu'),
                    'tone' => $log->action === 'created' ? 'success' : 'primary',
                ];
            }
        }

        if (empty($timelineEvents)) {
            $timelineEvents[] = [
                'title' => 'Khởi tạo hồ sơ',
                'actor' => self::cleanString($creatorName !== '-' ? $creatorName : $creator),
                'time' => self::dateTime($application->created_at),
                'note' => 'Hồ sơ được tạo trên hệ thống',
                'tone' => 'primary',
            ];
        }

        $tabs = [
            [
                'id' => 'tab-customer',
                'title' => 'Thông tin định danh',
                'icon' => 'user',
                'fields' => $customerFields,
            ],
            [
                'id' => 'tab-documents',
                'title' => 'Chứng từ & Tài liệu (' . count($documentList) . ')',
                'icon' => 'files',
                'documents' => $documentList,
            ],
            [
                'id' => 'tab-underwriting',
                'title' => 'Thẩm định & Phê duyệt',
                'icon' => 'shield',
                'fields' => $reviewFields,
            ],
        ];

        if (!empty($feolFields)) {
            $tabs[] = [
                'id' => 'tab-feol',
                'title' => 'Tích hợp Đối tác / FEOL (' . count($feolFields) . ')',
                'icon' => 'link',
                'fields' => $feolFields,
            ];
        }

        $tabs[] = [
            'id' => 'tab-personnel',
            'title' => 'Nhân sự & Phân công',
            'icon' => 'team',
            'fields' => $personnelFields,
        ];

        $tabs[] = [
            'id' => 'tab-history',
            'title' => 'Lịch sử xử lý (' . count($timelineEvents) . ')',
            'icon' => 'clock',
            'timeline' => $timelineEvents,
        ];

        $allFields = array_merge($reviewFields, $customerFields, $feolFields, $personnelFields);

        $appCode = self::cleanString((string) $application->application_code);
        if ($appCode === '-' || $appCode === '') {
            $appCode = 'APL-' . str_pad((string)$application->getKey(), 6, '0', STR_PAD_LEFT);
        }

        return [
            'id' => $application->getKey(),
            'application_code' => $appCode,
            'project' => self::cleanString($projectName),
            'applicant_name' => $applicantName,
            'identity_number' => $maskedIdentity,
            'phone_number' => $maskedPhone,
            'dob' => $dob,
            'product' => $productName,
            'scheme' => $schemeCode,
            'scheme_name' => $schemeName,
            'scheme_or_product' => $schemeOrProduct,
            'requested_loan_amount' => $requestedLoanAmount,
            'approved_loan_amount' => $approvedLoanAmount,
            'requested_loan_amount_label' => self::moneyLabel($requestedLoanAmount),
            'approved_loan_amount_label' => self::moneyLabel($approvedLoanAmount),
            'creator' => $creator,
            'created_at' => self::dateTime($application->created_at),
            'updated_at' => self::dateTime($application->updated_at),
            'updated_timestamp' => $application->updated_at?->getTimestamp() ?? 0,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'tabs' => $tabs,
            'documents' => $documentList,
            'timeline' => $timelineEvents,
            'application_fields' => $allFields,
        ];
    }

    public static function cleanString(?string $value): string
    {
        if ($value === null || $value === '') return '-';
        $clean = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        return filled($clean) ? trim($clean) : '-';
    }

    public static function maskIdentity(?string $value): string
    {
        $raw = self::cleanString($value);
        if ($raw === '' || $raw === '-') return '-';
        
        $digits = preg_replace('/[^0-9A-Za-z]/', '', $raw) ?: '';
        $len = strlen($digits);
        if ($len <= 4) return str_repeat('*', max(1, $len));
        if ($len === 9) { // 9-digit CMND: 095***310
            return substr($digits, 0, 3) . '***' . substr($digits, -3);
        }
        if ($len >= 12) { // 12-digit CCCD: 0950****9310
            return substr($digits, 0, 4) . '****' . substr($digits, -4);
        }
        return substr($digits, 0, 2) . str_repeat('*', max(2, $len - 4)) . substr($digits, -2);
    }

    public static function maskPhone(?string $value): string
    {
        $raw = self::cleanString($value);
        if ($raw === '' || $raw === '-') return '-';

        $digits = preg_replace('/[^0-9]/', '', $raw) ?: '';
        $len = strlen($digits);
        if ($len < 7) return str_repeat('*', max(1, $len));
        return substr($digits, 0, 4) . '***' . substr($digits, -3);
    }

    private static function formatAddress(array $data, string $prefix): string
    {
        $line = self::cleanString($data[$prefix . '_address_line'] ?? null);
        $ward = self::cleanString($data[$prefix . '_ward_name'] ?? null);
        $dist = self::cleanString($data[$prefix . '_district_name'] ?? null);
        $prov = self::cleanString($data[$prefix . '_province_name'] ?? null);

        $parts = array_filter([$line, $ward, $dist, $prov], fn($p) => filled($p) && $p !== '-');
        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    private static function field(string $label, string $value, ?string $tone = null, bool $wide = false): array
    {
        $cleanLabel = self::cleanString($label);
        $cleanValue = self::cleanString($value);
        return [
            'label' => $cleanLabel,
            'value' => $cleanValue,
            'tone' => $tone,
            'wide' => $wide,
        ];
    }

    private static function firstFilled(array $values): string
    {
        $value = collect($values)->first(fn (mixed $candidate): bool => filled($candidate));
        return filled($value) ? trim((string) $value) : '-';
    }

    private static function money(array $values): ?int
    {
        $value = collect($values)->first(fn (mixed $candidate): bool => filled($candidate));
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }
        $str = (string) $value;
        if (str_contains($str, '.') && str_ends_with($str, '.00')) {
            $str = substr($str, 0, -3);
        }
        $digits = preg_replace('/[^0-9-]+/', '', $str) ?: '';
        return $digits !== '' ? (int) $digits : null;
    }

    private static function moneyLabel(?int $value): string
    {
        return is_null($value) ? '-' : number_format($value, 0, ',', '.').' VNĐ';
    }

    private static function percentage(array $values): string
    {
        $value = collect($values)->first(fn (mixed $candidate): bool => filled($candidate));
        if (! filled($value)) return '-';
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    }

    private static function payloadDate(mixed $value): string
    {
        if (! filled($value)) return '-';
        try {
            return Carbon::parse($value)->format('H:i d/m/Y');
        } catch (Throwable) {
            return self::cleanString((string) $value);
        }
    }

    private static function dateTime(mixed $value): string
    {
        if (! filled($value)) return '-';
        if ($value instanceof CarbonInterface) {
            return $value->format('H:i d/m/Y');
        }
        try {
            return Carbon::parse($value)->format('H:i d/m/Y');
        } catch (Throwable) {
            return self::cleanString((string) $value);
        }
    }

    public static function crmStatusLabel(?string $status, ?string $projectSlug = null): string
    {
        $statusKey = trim((string) $status);
        if ($statusKey === '') return 'Mới ghi nhận';

        // EXACT labels from CRM Workflows (preserving English terms as in CRM UI)
        $map = [
            // Lotte Finance Workflow (Exact CRM Filament Schema)
            'lotte_pre_check' => 'Kiểm tra đầu vào',
            'lotte_initiation' => 'Nhập liệu',
            'lotte_sale_completion' => 'Chờ Sale bổ sung thông tin',
            'lotte_returned_to_sale' => 'Trả về Sale',
            'lotte_uw_call' => 'UW Call',
            'lotte_uw_approval' => 'UW Approval',
            'lotte_uw_rejected' => 'UW Rej',
            'lotte_uw_field' => 'UW Field',
            'lotte_op' => 'OP',
            'lotte_esign' => 'eSign',
            'lotte_post_approval' => 'Post Approval',
            'lotte_disbursed' => 'Đã giải ngân',
            'lotte_rejected' => 'Không Pass',

            // Direct workflow values
            'uw_call' => 'UW Call',
            'uw_approval' => 'UW Approval',
            'uw_rejected' => 'UW Rej',
            'uw_field' => 'UW Field',
            'op' => 'OP',
            'esign' => 'eSign',
            'post_approval' => 'Post Approval',
            'disbursed' => 'Đã giải ngân',

            // ACL Mix Workflow
            'pending_initial_review' => 'Kiểm tra đầu vào',
            'otp_required' => 'Xác thực OTP',
            'customer_capp' => 'Khách hàng thao tác CAPP',
            'ineligible' => 'Không đạt',
            'sale_completion' => 'Hoàn thiện hồ sơ',
            'call_recording' => 'Ghi âm',
            'underwriting' => 'Underwriting',
            'returned_to_sale' => 'Trả về Sale',
            'awaiting_contract' => 'Chờ hợp đồng',
            'completed' => 'Hoàn thành',
            'rejected' => 'Từ chối',

            // FE Deeplink & Other Workflows
            'pl_disbursed' => 'PL Disbursed',
            'drop_off' => 'Drop Off',
            'pending_disbursement' => 'Chờ giải ngân',
            'eligible' => 'Eligible',
            'cancellation' => 'Hủy hồ sơ',
            'hard_reject' => 'Hard Reject',
            'draft' => 'Bản nháp',
            'pending_check' => 'Chờ kiểm tra',
            'customer_registration' => 'Khách tự đăng ký',
            'submitted' => 'Đã gửi hồ sơ',
            'partner_processing' => 'Đối tác đang xử lý',
            'approved' => 'Phê duyệt',
            'processing' => 'Đang xử lý',
            'pending' => 'Chờ thẩm định',
        ];

        $clean = strtolower($statusKey);
        if (isset($map[$statusKey])) return $map[$statusKey];
        if (isset($map[$clean])) return $map[$clean];

        if (str_starts_with($clean, 'lotte_')) {
            $sub = substr($clean, 6);
            if (isset($map[$sub])) return $map[$sub];
            if (isset($map['lotte_' . $sub])) return $map['lotte_' . $sub];
        }

        return $statusKey;
    }

    public static function crmStatusTone(?string $status, ?string $projectSlug = null): string
    {
        $s = strtolower(trim((string) $status));
        return match ($s) {
            'approved', 'completed', 'disbursed', 'post_approval', 'uw_approval', 'lotte_disbursed', 'lotte_post_approval', 'lotte_uw_approval', 'pl_disbursed' => 'success',
            'rejected', 'ineligible', 'cancelled', 'uw_rejected', 'failed', 'lotte_rejected', 'lotte_uw_rejected', 'hard_reject', 'cancellation' => 'danger',
            'eligible', 'pending_initial_review', 'otp_required', 'customer_capp', 'sale_completion', 'call_recording', 'underwriting', 'returned_to_sale', 'awaiting_contract', 'lotte_initiation', 'lotte_pre_check', 'lotte_sale_completion', 'lotte_returned_to_sale', 'lotte_uw_call', 'lotte_uw_field', 'lotte_op', 'lotte_esign', 'uw_call', 'uw_field', 'op', 'esign', 'draft', 'pending_check', 'customer_registration', 'submitted', 'partner_processing', 'processing', 'pending', 'pending_disbursement', 'drop_off' => 'warning',
            default => 'primary',
        };
    }

    public static function decisionTone(?string $decision): string
    {
        $normalized = strtolower(trim((string) $decision));

        return match (true) {
            str_contains($normalized, 'pass') || str_contains($normalized, 'approved') || str_contains($normalized, 'chấp thuận') || str_contains($normalized, 'thành công') => 'success',
            str_contains($normalized, 'fail') || str_contains($normalized, 'reject') || str_contains($normalized, 'từ chối') || str_contains($normalized, 'hủy') => 'danger',
            str_contains($normalized, 'pending') || str_contains($normalized, 'review') || str_contains($normalized, 'chờ') => 'warning',
            default => 'neutral',
        };
    }

    protected static ?array $userMapCache = null;

    public static function getUserNameByCode(?string $code): ?string
    {
        if (empty($code) || $code === '-') return null;
        if (self::$userMapCache === null) {
            try {
                self::$userMapCache = [];
                $users = \App\Models\User::all(['id', 'name', 'employee_code', 'uid', 'username']);
                foreach ($users as $u) {
                    $displayName = $u->name . ($u->employee_code ? " ({$u->employee_code})" : '');
                    if ($u->employee_code) self::$userMapCache[strtoupper($u->employee_code)] = $displayName;
                    if ($u->uid) self::$userMapCache[strtoupper($u->uid)] = $displayName;
                    if ($u->username) self::$userMapCache[strtoupper($u->username)] = $displayName;
                    self::$userMapCache[(string)$u->id] = $displayName;
                }
            } catch (\Throwable $e) {
                self::$userMapCache = [];
            }
        }
        return self::$userMapCache[strtoupper(trim($code))] ?? null;
    }
}
