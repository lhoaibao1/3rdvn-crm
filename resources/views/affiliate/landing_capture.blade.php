<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $ogTitle }}</title>
    <meta name="description" content="{{ $ogDescription }}">

    <!-- OpenGraph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:site_name" content="3RD Vietnam Fintech">

    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #00b04f;
            --primary-dark: #008f3f;
            --primary-glow: rgba(0, 176, 79, 0.25);
            --bg-dark: #090e17;
            --card-bg: rgba(15, 23, 42, 0.92);
            --card-border: rgba(255, 255, 255, 0.12);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(30, 41, 59, 0.75);
            --input-border: rgba(255, 255, 255, 0.15);
            --radius-lg: 24px;
            --radius-md: 14px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
        
        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px 14px 28px;
            position: relative;
            overflow-x: hidden;
        }

        .bg-glow {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 176, 79, 0.14) 0%, rgba(15, 23, 42, 0) 70%);
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            pointer-events: none;
        }

        .landing-card {
            position: relative;
            z-index: 1;
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 32px 24px 28px;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 35px var(--primary-glow);
            animation: cardFadeIn 0.35s ease-out;
        }

        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .top-badge-area {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .badge-secure {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(0, 176, 79, 0.12);
            border: 1px solid rgba(0, 176, 79, 0.35);
            color: #4ade80;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .hero-title-area {
            text-align: center;
            margin-bottom: 22px;
        }

        .hero-title {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .hero-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 6px;
            letter-spacing: 0.2px;
        }

        .form-label span.req {
            color: #ef4444;
            margin-left: 2px;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #64748b;
            display: flex;
            align-items: center;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 48px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-md);
            color: #ffffff;
            font-size: 14.5px;
            font-weight: 600;
            padding: 0 14px 0 42px;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control:focus {
            background: rgba(30, 41, 59, 0.95);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 176, 79, 0.25);
        }

        .form-control::placeholder {
            color: #64748b;
            font-weight: 500;
            font-size: 13.5px;
        }

        .form-control.uppercase {
            text-transform: uppercase;
        }

        .error-msg {
            font-size: 11.5px;
            color: #f87171;
            margin-top: 4px;
            display: none;
            font-weight: 600;
        }

        .error-msg.show {
            display: block;
        }

        /* Consent Box */
        .consent-box {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin: 18px 0 16px;
            text-align: left;
        }

        .consent-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            font-size: 12px;
            color: #cbd5e1;
            line-height: 1.55;
        }

        .consent-label input[type="checkbox"] {
            margin-top: 3px;
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
            flex-shrink: 0;
        }

        .consent-label strong {
            color: #ffffff;
        }

        .campaign-note {
            margin: 0 0 16px;
            padding: 13px 14px;
            border: 1px solid rgba(251, 191, 36, .4);
            border-radius: var(--radius-md);
            background: rgba(120, 53, 15, .22);
            color: #fde68a;
            font-size: 12.5px;
            line-height: 1.55;
        }
        .campaign-note strong { color: #fff; }

        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #00b04f 0%, #008f3f 100%);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            box-shadow: 0 6px 20px rgba(0, 176, 79, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s ease;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(0, 176, 79, 0.55);
            background: linear-gradient(135deg, #02c75a 0%, #00a147 100%);
        }

        .btn-submit:active:not(:disabled) {
            transform: translateY(1px);
        }

        .btn-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .transition-view {
            display: none;
            text-align: center;
            padding: 24px 8px;
        }

        .spinner-ring {
            width: 44px;
            height: 44px;
            border: 3.5px solid rgba(0, 176, 79, 0.2);
            border-top-color: #00b04f;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .trans-title {
            font-size: 17px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .trans-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .card-footer {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 11.5px;
            color: #64748b;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <div class="landing-card">
        <div class="top-badge-area">
            <div class="badge-secure">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                Cổng Đăng Ký Trực Tuyến &middot; Bảo Mật SSL
            </div>
        </div>

        <!-- Form Section -->
        <div id="formSection">
            <div class="hero-title-area">
                <h1 class="hero-title">Đăng Ký Hồ Sơ {{ $campaignName }}</h1>
                <p class="hero-subtitle">Vui lòng điền thông tin để kết nối hồ sơ xét duyệt tự động</p>
            </div>

            <form id="leadCaptureForm" action="{{ url()->current() }}/submit" method="POST" onsubmit="submitLeadForm(event)">
                @csrf
                @if(str_contains(strtolower($campaign->slug . $campaign->name), 'shinhan'))
                    <div class="campaign-note">
                        Khi thao tác trên ứng dụng iShinhan, khách hàng phải nhập mã giới thiệu <strong>ACT23</strong> để hồ sơ được ghi nhận. <strong>Shinhan Finance không thu bất kỳ khoản phí nào của khách hàng.</strong>
                    </div>
                @endif
                <!-- 1. Họ và tên -->
                <div class="form-group">
                    <label class="form-label" for="customerName">Họ và tên khách hàng <span class="req">*</span></label>
                    <div class="input-wrap">
                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <input type="text" id="customerName" name="full_name" class="form-control uppercase" required placeholder="NGUYỄN VĂN A" autocomplete="name" oninput="validateName(this)">
                    </div>
                    <div class="error-msg" id="nameError">Vui lòng nhập đầy đủ họ và tên</div>
                </div>

                <!-- 2. Số điện thoại -->
                <div class="form-group">
                    <label class="form-label" for="customerPhone">Số điện thoại đăng ký <span class="req">*</span></label>
                    <div class="input-wrap">
                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <input type="tel" id="customerPhone" name="phone" class="form-control" required placeholder="0901234567" maxlength="10" autocomplete="tel" oninput="validatePhone(this)">
                    </div>
                    <div class="error-msg" id="phoneError">Số điện thoại phải gồm 10 chữ số hợp lệ (03x, 05x, 07x, 08x, 09x)</div>
                </div>

                <!-- 3. Số CCCD / CMND -->
                <div class="form-group">
                    <label class="form-label" for="customerIdNumber">Số CCCD / CMND <span class="req">*</span></label>
                    <div class="input-wrap">
                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                        <input type="text" id="customerIdNumber" name="identity_number" class="form-control" required placeholder="Nhập 12 số CCCD gắn chip" maxlength="12" oninput="validateIdNumber(this)">
                    </div>
                    <div class="error-msg" id="idError">Vui lòng nhập đúng 12 chữ số CCCD (hoặc 9 số CMND)</div>
                </div>

                <!-- 4. Ngày tháng năm sinh -->
                <div class="form-group">
                    <label class="form-label" for="customerDob">Ngày tháng năm sinh <span class="req">*</span></label>
                    <div class="input-wrap">
                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        </div>
                        <input type="date" id="customerDob" name="date_of_birth" class="form-control" required max="{{ date('Y-m-d', strtotime('-18 years')) }}">
                    </div>
                    <div class="error-msg" id="dobError">Khách hàng phải đủ từ 18 tuổi trở lên</div>
                </div>

                <!-- Legal Consent Box -->
                <div class="consent-box">
                    <label class="consent-label" for="consentCheck">
                        <input type="checkbox" id="consentCheck" name="consent" checked required>
                        <span>
                            @if(str_contains(strtolower($campaign->slug . $campaign->name), 'shb'))
                                Tôi đồng ý cho <strong>Công ty TNHH 3RD Vietnam</strong> chuyển giao thông tin cá nhân của tôi cho <strong>Công ty Tài chính TNHH MTV Ngân hàng TMCP Sài Gòn - Hà Nội (SHB Finance)</strong> xử lý dữ liệu nhằm mục đích cung cấp sản phẩm vay.
                            @elseif(str_contains(strtolower($campaign->slug . $campaign->name), 'vpbank'))
                                Tôi đồng ý cho <strong>Công ty TNHH 3RD Vietnam</strong> chuyển giao thông tin cá nhân của tôi cho <strong>Ngân hàng TMCP Việt Nam Thịnh Vượng (VPBank)</strong> xử lý dữ liệu nhằm mục đích cung cấp sản phẩm vay.
                            @elseif(str_contains(strtolower($campaign->slug . $campaign->name), 'tinvay'))
                                Tôi đồng ý cho <strong>Công ty TNHH 3RD Vietnam</strong> chuyển giao thông tin cá nhân của tôi cho <strong>Công ty Tài chính Cổ phần Tín Việt (VietCredit - Tin Vay)</strong> xử lý dữ liệu nhằm mục đích cung cấp sản phẩm vay.
                            @elseif(str_contains(strtolower($campaign->slug . $campaign->name), 'shinhan'))
                                Tôi đồng ý cho <strong>Công ty TNHH 3RD Vietnam</strong> chuyển giao thông tin cá nhân của tôi cho <strong>Công ty Tài chính TNHH Một thành viên Shinhan Việt Nam (Shinhan Finance)</strong> xử lý dữ liệu nhằm mục đích cung cấp sản phẩm vay.
                            @else
                                Tôi đồng ý cho <strong>Công ty TNHH 3RD Vietnam</strong> chuyển giao thông tin cá nhân của tôi cho đơn vị tài chính đối tác <strong>{{ $campaignName }}</strong> xử lý dữ liệu nhằm mục đích cung cấp sản phẩm vay.
                            @endif
                        </span>
                    </label>
                </div>

                <input type="hidden" name="ref" value="{{ $employeeCode }}">
                <input type="hidden" name="utm_source" value="{{ request('utm_source', 'affiliate') }}">
                <input type="hidden" name="utm_medium" value="{{ request('utm_medium', 'cpa') }}">
                <input type="hidden" name="utm_campaign" value="{{ request('utm_campaign', $campaign->slug) }}">
                <input type="hidden" id="customerPlatform" name="platform" value="{{ preg_match('/iPhone|iPad|iPod/i', (string) request()->userAgent()) ? 'ios' : 'android' }}">

                <button type="submit" class="btn-submit" id="btnSubmit">
                    <span>Tiếp Tục Đăng Ký Khoản Vay</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>
        </div>

        <!-- Transition / Loading View -->
        <div id="transitionSection" class="transition-view">
            <div class="spinner-ring"></div>
            <h2 class="trans-title">Đang Kết Nối Hồ Sơ...</h2>
            <p class="trans-desc">Hệ thống 3RD VN đã lưu thông tin và đang chuyển tiếp bạn đến cổng duyệt chính thức của <strong>{{ $campaignName }}</strong>.</p>
        </div>

        <!-- Footer -->
        <div class="card-footer">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Bảo mật thông tin khách hàng &middot; 3RD Vietnam Co., Ltd.
        </div>
    </div>

    <script>
        function validateName(input) {
            input.value = input.value.toUpperCase();
            var val = input.value.trim();
            var err = document.getElementById('nameError');
            if (val.length < 3) {
                err.classList.add('show');
            } else {
                err.classList.remove('show');
            }
        }

        function validatePhone(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
            var val = input.value;
            var err = document.getElementById('phoneError');
            var phoneRegex = /^(03|05|07|08|09)[0-9]{8}$/;
            if (val.length === 10 && phoneRegex.test(val)) {
                err.classList.remove('show');
            } else if (val.length >= 10) {
                err.classList.add('show');
            }
        }

        function validateIdNumber(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
            var val = input.value;
            var err = document.getElementById('idError');
            if (val.length === 12 || val.length === 9) {
                err.classList.remove('show');
            } else if (val.length >= 9) {
                err.classList.add('show');
            }
        }

        async function submitLeadForm(e) {
            e.preventDefault();
            
            var nameInput = document.getElementById('customerName');
            var phoneInput = document.getElementById('customerPhone');
            var idInput = document.getElementById('customerIdNumber');
            var dobInput = document.getElementById('customerDob');
            var consentInput = document.getElementById('consentCheck');
            var btn = document.getElementById('btnSubmit');

            var name = nameInput.value.trim();
            var phone = phoneInput.value.trim();
            var identityNumber = idInput.value.trim();
            var dob = dobInput.value.trim();

            if (!name || name.length < 3) {
                document.getElementById('nameError').classList.add('show');
                nameInput.focus();
                return;
            }

            var phoneRegex = /^(03|05|07|08|09)[0-9]{8}$/;
            if (!phone || !phoneRegex.test(phone)) {
                document.getElementById('phoneError').classList.add('show');
                phoneInput.focus();
                return;
            }

            if (!identityNumber || (identityNumber.length !== 12 && identityNumber.length !== 9)) {
                document.getElementById('idError').classList.add('show');
                idInput.focus();
                return;
            }

            if (!dob) {
                document.getElementById('dobError').classList.add('show');
                dobInput.focus();
                return;
            }

            if (!consentInput.checked) {
                alert('Vui lòng tích đồng ý điều khoản chuyển giao dữ liệu để tiếp tục.');
                consentInput.focus();
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation: spin 0.8s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <span>Đang xử lý hồ sơ...</span>
            `;

            var payload = {
                full_name: name,
                name: name,
                phone: phone,
                identity_number: identityNumber,
                date_of_birth: dob,
                ref: '{{ $employeeCode }}',
                utm_source: '{{ request("utm_source", "affiliate") }}',
                utm_medium: '{{ request("utm_medium", "cpa") }}',
                utm_campaign: '{{ request("utm_campaign", $campaign->slug) }}',
                platform: /iPhone|iPad|iPod/i.test(navigator.userAgent) ? 'ios' : 'android',
                _token: '{{ csrf_token() }}'
            };

            // Relative URL to stay on current host without CORS issues
            var submitPath = window.location.pathname.replace(/\/+$/, '') + '/submit';

            try {
                var response = await fetch(submitPath, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                var data = await response.json();

                if (data.success && data.redirect_url) {
                    document.getElementById('formSection').style.display = 'none';
                    document.getElementById('transitionSection').style.display = 'block';

                    setTimeout(function() {
                        window.location.replace(data.redirect_url);
                    }, 300);
                } else {
                    alert(data.message || 'Có lỗi xảy ra, vui lòng kiểm tra lại thông tin.');
                    btn.disabled = false;
                    btn.innerHTML = '<span>Tiếp Tục Đăng Ký Khoản Vay</span>';
                }
            } catch (err) {
                // If fetch fails, submit form natively
                document.getElementById('leadCaptureForm').submit();
            }
        }
    </script>
</body>
</html>
