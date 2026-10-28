<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>SHBFinance &middot; Đăng ký vay tiêu dùng</title>
    <meta name="description" content="Đăng ký vay tiêu dùng trực tuyến SHBFinance">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        
        body {
            background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 40%, #fed7aa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px 12px 28px;
            color: #1e293b;
        }

        .widget-card {
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 20px 40px -10px rgba(234, 88, 12, 0.12), 0 1px 3px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 440px;
            padding: 26px 24px 22px;
            position: relative;
        }

        /* Top Loan Amount Section */
        .header-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 19px;
            font-weight: 700;
            color: #1e293b;
        }
        .amount-display {
            font-size: 22px;
            font-weight: 800;
            color: #00b14f;
            letter-spacing: -0.02em;
        }

        /* Slider */
        .slider-wrap {
            position: relative;
            margin-bottom: 8px;
        }
        .custom-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 10px;
            border-radius: 9999px;
            background: #e2e8f0;
            outline: none;
            cursor: pointer;
        }
        .custom-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #ffffff;
            border: 4px solid #00b14f;
            box-shadow: 0 2px 6px rgba(0, 177, 79, 0.35);
            cursor: pointer;
            transition: transform 0.1s ease;
        }
        .custom-slider::-webkit-slider-thumb:hover {
            transform: scale(1.1);
        }
        .custom-slider::-moz-range-thumb {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #ffffff;
            border: 4px solid #00b14f;
            box-shadow: 0 2px 6px rgba(0, 177, 79, 0.35);
            cursor: pointer;
        }
        .slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin-top: 4px;
            margin-bottom: 18px;
        }

        /* Term Row */
        .term-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .term-label {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        .term-select {
            padding: 8px 34px 8px 16px;
            background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 12px center;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
        }
        .term-select:focus {
            border-color: #f97316;
        }

        /* Highlight Estimation Box */
        .estimate-box {
            background: #fef3c7;
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 18px;
            border: 1px solid #fde68a;
        }
        .estimate-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .estimate-label {
            font-size: 13px;
            font-weight: 600;
            color: #78350f;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .estimate-amount {
            font-size: 16px;
            font-weight: 800;
            color: #ea580c;
        }
        .estimate-sub {
            text-align: right;
            font-size: 11px;
            color: #92400e;
            font-weight: 500;
        }

        /* Form section */
        .form-prompt {
            text-align: center;
            font-size: 13px;
            color: #475569;
            margin-bottom: 14px;
            font-weight: 500;
        }

        .inputs-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 18px;
        }
        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .form-input, .form-select {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 14px;
            font-size: 14px;
            color: #1e293b;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        .form-input:focus, .form-select:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
        }
        .form-select {
            background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 12px center;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
            color: #1e293b;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        /* Submit Button */
        .submit-cta {
            width: 100%;
            height: 52px;
            background: linear-gradient(135deg, #ff8a00 0%, #ff6b00 100%);
            border: none;
            border-radius: 9999px;
            color: #ffffff;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 20px -4px rgba(255, 107, 0, 0.45);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            margin-bottom: 14px;
        }
        .submit-cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px -4px rgba(255, 107, 0, 0.55);
        }
        .submit-cta:active {
            transform: translateY(0);
        }

        /* Disclaimer */
        .disclaimer {
            font-size: 10.5px;
            line-height: 1.45;
            color: #334155;
            text-align: center;
            font-style: italic;
        }
        .disclaimer a {
            color: #ea580c;
            text-decoration: none;
            font-weight: 600;
        }
        .disclaimer a:hover {
            text-decoration: underline;
        }

        .advisor-pill {
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="widget-card">
    <!-- 1. Header & Slider -->
    <div class="header-row">
        <div class="header-title">Tôi muốn vay</div>
        <div class="amount-display" id="amountDisplay">10.000.000 đ</div>
    </div>

    <div class="slider-wrap">
        <input 
            type="range" 
            class="custom-slider" 
            id="loanSlider" 
            min="5000000" 
            max="100000000" 
            step="1000000" 
            value="10000000"
        >
    </div>
    <div class="slider-labels">
        <span>5 Triệu</span>
        <span>50 Triệu</span>
        <span>100 Triệu</span>
    </div>

    <!-- 2. Term -->
    <div class="term-row">
        <div class="term-label">Thời hạn vay</div>
        <select class="term-select" id="termSelect" name="loan_term">
            <option value="6">6 tháng</option>
            <option value="9">9 tháng</option>
            <option value="12">12 tháng</option>
            <option value="15">15 tháng</option>
            <option value="18">18 tháng</option>
            <option value="24">24 tháng</option>
            <option value="27">27 tháng</option>
            <option value="30">30 tháng</option>
            <option value="33">33 tháng</option>
            <option value="36" selected>36 tháng</option>
        </select>
    </div>

    <!-- 3. Estimation Box -->
    <div class="estimate-box">
        <div class="estimate-top">
            <div class="estimate-label">
                <span>💰</span> Tiền trả mỗi tháng
            </div>
            <div class="estimate-amount" id="monthlyPayment">466.000 đ</div>
        </div>
        <div class="estimate-sub" id="dailyPayment">Tương đương chỉ 16.000đ/ngày</div>
    </div>

    <!-- 4. Form Subtitle -->
    <div class="form-prompt">
        Bạn vui lòng điền các thông tin bên dưới để kiểm tra
    </div>

    <!-- 5. Form Inputs -->
    <form method="POST" action="{{ $submitUrl }}" id="loanForm">
        @csrf
        <input type="hidden" name="ref" value="{{ $employeeCode }}">
        <input type="hidden" name="loan_amount" id="hiddenLoanAmount" value="10000000">
        <input type="hidden" name="loan_term" id="hiddenLoanTerm" value="36">

        <div class="inputs-grid">
            <input 
                type="text" 
                class="form-input" 
                name="applicant_name" 
                id="applicant_name" 
                placeholder="Họ và tên" 
                required 
                autocomplete="name"
                value="{{ old('applicant_name') }}"
            >

            <div class="row-2">
                <input 
                    type="text" 
                    class="form-input" 
                    name="dob" 
                    id="dob" 
                    placeholder="Ngày sinh" 
                    maxlength="10"
                    inputmode="numeric"
                >
                <select class="form-select" name="job" id="job">
                    <option value="" disabled selected>Nghề nghiệp</option>
                    <option value="CongChucNhaNuoc">Công chức nhà nước</option>
                    <option value="CanBoDoanhNghiepTu">Cán bộ doanh nghiệp tư</option>
                    <option value="LaoDongTuDo">Lao động tự do</option>
                    <option value="TuDoanhKhongDKKD">Tự doanh (không có ĐKKD)</option>
                    <option value="HoKinhDoanhCoDKKD">Hộ kinh doanh/doanh nghiệp (có ĐKKD)</option>
                    <option value="HuuTri">Hưu trí</option>
                    <option value="CongNhan">Công nhân</option>
                    <option value="SinhVien">Sinh viên</option>
                    <option value="NoiTro">Nội trợ</option>
                    <option value="Khac">Khác (Mô tả chi tiết)</option>
                </select>
            </div>

            <input 
                type="tel" 
                class="form-input" 
                name="phone" 
                id="phone" 
                placeholder="Nhập số điện thoại" 
                inputmode="numeric" 
                maxlength="10" 
                required 
                autocomplete="tel"
                value="{{ old('phone') }}"
            >

            <input 
                type="tel" 
                class="form-input" 
                name="identity_number" 
                id="identity_number" 
                placeholder="Nhập số CCCD/CC" 
                inputmode="numeric" 
                maxlength="12" 
                required
                value="{{ old('identity_number') }}"
            >
        </div>

        <!-- 6. Submit Button -->
        <button type="submit" class="submit-cta">
            Xem gói vay
        </button>

        <!-- 7. Legal Disclaimer -->
        <p class="disclaimer">
            Bằng việc click vào "Xem gói vay", Tôi đồng ý với <a href="javascript:void(0)">Điều khoản điều kiện sử dụng dịch vụ Online Banking</a> và <a href="javascript:void(0)">Bảo vệ dữ liệu cá nhân và bảo mật thông tin</a> của SHBFinance và đồng ý nhận cuộc gọi giới thiệu sản phẩm, dịch vụ của SHBFinance từ 08h đến 20h
        </p>
    </form>

    <div class="advisor-pill">
        Mã chuyên viên: {{ $salesUser->name }} &middot; {{ $employeeCode }}
    </div>
</div>

<script>
    const slider = document.getElementById('loanSlider');
    const amountDisplay = document.getElementById('amountDisplay');
    const hiddenLoanAmount = document.getElementById('hiddenLoanAmount');
    const hiddenLoanTerm = document.getElementById('hiddenLoanTerm');
    const termSelect = document.getElementById('termSelect');
    const monthlyPayment = document.getElementById('monthlyPayment');
    const dailyPayment = document.getElementById('dailyPayment');

    function updateTrack() {
        const val = Number(slider.value);
        const min = Number(slider.min);
        const max = Number(slider.max);
        const percentage = ((val - min) / (max - min)) * 100;
        slider.style.background = `linear-gradient(to right, #00b14f 0%, #00b14f ${percentage}%, #e2e8f0 ${percentage}%, #e2e8f0 100%)`;
    }

    // Exact SHB Finance Consumer Loan Annuity Formula
    function calculate() {
        const amount = Number(slider.value);
        const months = Number(termSelect.value);
        
        amountDisplay.textContent = new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
        hiddenLoanAmount.value = amount;
        hiddenLoanTerm.value = months;

        const r = 0.031505; // Exact monthly rate (~3.15% per month)
        const factor = Math.pow(1 + r, months);
        const rawMonthly = (amount * (r * factor)) / (factor - 1);
        
        const totalPerMonth = Math.round(rawMonthly / 1000) * 1000;
        const perDay = Math.round((totalPerMonth / 30) / 1000) * 1000;

        monthlyPayment.textContent = new Intl.NumberFormat('vi-VN').format(totalPerMonth) + ' đ';
        dailyPayment.textContent = 'Tương đương chỉ ' + new Intl.NumberFormat('vi-VN').format(perDay) + 'đ/ngày';

        updateTrack();
    }

    slider.addEventListener('input', calculate);
    termSelect.addEventListener('change', calculate);

    // Auto-mask for Birthday DD/MM/YYYY
    const dobInput = document.getElementById('dob');
    dobInput.addEventListener('input', function(e) {
        let v = this.value.replace(/\D/g, '');
        if (v.length > 2 && v.length <= 4) {
            v = v.slice(0, 2) + '/' + v.slice(2);
        } else if (v.length > 4) {
            v = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4, 8);
        }
        this.value = v;
    });

    calculate();
</script>

</body>
</html>
