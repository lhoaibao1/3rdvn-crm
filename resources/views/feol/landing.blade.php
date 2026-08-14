<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Đăng ký khoản vay - FE CREDIT</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f7f8fb;color:#101828;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.page{min-height:100vh;padding:34px 20px 48px}.wrap{width:min(100%,760px);margin:auto}.page-heading{margin:0 0 22px;font-size:28px;font-weight:800;line-height:1.2}.brand{display:flex;justify-content:center;padding:4px 16px 20px}.brand img{display:block;width:min(100%,200px);height:auto}.card{overflow:hidden;background:#fff;border:1px solid #d9e1ec;border-radius:8px}.section-head{padding:20px 22px 15px;border-bottom:1px solid #e5e7eb}.section-head h2{margin:0 0 5px;font-size:17px;line-height:1.35}.section-head p{margin:0;color:#667085;font-size:14px;line-height:1.5}.form-content{padding:22px}.grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:17px 20px}.field.full{grid-column:1/-1}label{display:block;margin:0 0 7px;font-size:14px;font-weight:650}.req{color:#e02727}input{width:100%;height:40px;border:1px solid #d0d5dd;border-radius:7px;padding:0 11px;background:#fff;color:#101828;font-size:14px;outline:0;transition:border-color .15s,box-shadow .15s}input:focus{border-color:#2563eb;box-shadow:0 0 0 3px #2563eb1f}input[readonly]{background:#f8fafc;color:#475467}.error{margin-top:5px;color:#c92222;font-size:12px}.consent{position:relative;display:flex;gap:10px;align-items:flex-start;margin:0;font-size:13px;line-height:1.5;cursor:pointer}.consent input{position:absolute;width:1px;height:1px;opacity:0}.consent-mark{display:grid;place-items:center;width:20px;height:20px;margin-top:1px;flex:none;border:2px solid #98a2b3;border-radius:3px;background:#fff;color:#fff;font-size:14px;font-weight:900;line-height:1}.consent input:checked+.consent-mark{border-color:#2563eb;background:#2563eb}.consent input:checked+.consent-mark:after{content:"✓"}.consent input:focus-visible+.consent-mark{outline:3px solid #2563eb33;outline-offset:2px}.actions{display:flex;justify-content:flex-start;padding:18px 22px;border-top:1px solid #e5e7eb;background:#fff}.submit{min-width:170px;height:40px;border:0;border-radius:7px;padding:0 20px;background:#2563eb;color:#fff;font-size:14px;font-weight:750;cursor:pointer}.submit:hover{background:#1d4ed8}.submit:disabled{opacity:.65;cursor:wait}.notice{margin:22px;padding:14px;border:1px solid #b8e3c7;border-radius:7px;background:#edf8f1;color:#17633a}.foot{text-align:center;color:#8290a6;font-size:12px;margin-top:18px}@media(max-width:767px){.page{padding:20px 12px 32px}.page-heading{font-size:23px;margin-bottom:16px}.brand{padding-bottom:16px}.brand img{width:min(100%,175px)}.section-head,.form-content,.actions{padding-left:16px;padding-right:16px}.grid{grid-template-columns:1fr;gap:16px}.field.full{grid-column:auto}.submit{width:100%}}
    </style>
</head>
<body>
<main class="page"><div class="wrap">
    <h1 class="page-heading">Đăng ký khoản vay</h1>
    <header class="brand" aria-label="FE CREDIT"><img src="{{ asset('images/fe-credit.svg') }}" alt="FE CREDIT"></header>
    <section class="card">
        @if($submitted)
            <div class="notice">Hồ sơ đã được tiếp nhận. Chúng tôi sẽ liên hệ với Quý khách trong thời gian sớm nhất.</div>
        @else
        <form method="post" action="{{ $submitUrl }}" id="feol-form">
            @csrf
            <div class="section-head"><h2>Thông tin đăng ký</h2><p>Nhập đầy đủ thông tin theo biểu mẫu FE CREDIT. Hồ sơ được lưu CRM trước khi gửi đối tác.</p></div>
            <div class="form-content"><div class="grid">
                <div class="field full"><label>Họ và tên <span class="req">*</span></label><input name="applicant_name" value="{{ old('applicant_name', $application?->applicant_name) }}" required maxlength="255">@error('applicant_name')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Số điện thoại <span class="req">*</span></label><input name="phone" value="{{ old('phone', $application?->phone) }}" required inputmode="numeric" maxlength="10">@error('phone')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Số CCCD <span class="req">*</span></label><input name="identity_number" value="{{ old('identity_number', $application?->identity_number) }}" required inputmode="numeric" maxlength="12">@error('identity_number')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Ngày tháng năm sinh <span class="req">*</span></label><input name="date_of_birth" type="text" inputmode="numeric" autocomplete="bday" maxlength="10" placeholder="dd/mm/yyyy" data-date-mask value="{{ old('date_of_birth', filled(data_get($application?->payload, 'fields.date_of_birth')) ? \Carbon\CarbonImmutable::parse(data_get($application?->payload, 'fields.date_of_birth'))->format('d/m/Y') : '') }}" required>@error('date_of_birth')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Địa chỉ Email <span class="req">*</span></label><input name="email" type="email" value="{{ old('email', data_get($application?->payload, 'fields.email')) }}" required>@error('email')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Số tiền vay <span class="req">*</span></label><input name="loan_amount" type="text" inputmode="numeric" maxlength="13" data-money-mask value="{{ old('loan_amount', data_get($application?->payload, 'fields.loan_amount')) }}" required>@error('loan_amount')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Thời hạn vay (tháng) <span class="req">*</span></label><input name="loan_term_months" type="number" min="1" max="120" value="{{ old('loan_term_months', data_get($application?->payload, 'fields.loan_term_months')) }}" required>@error('loan_term_months')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Mã giới thiệu</label><input value="{{ $referralCode }}" readonly></div>
                <div class="field"><label>Nhân viên phụ trách</label><input value="{{ $employeeName }}" readonly></div>
                <div class="field full"><label class="consent"><input type="checkbox" name="customer_consent" value="1" required @checked(old('customer_consent'))><span class="consent-mark" aria-hidden="true"></span><span>{{ $consentText }}</span></label>@error('customer_consent')<div class="error">{{ $message }}</div>@enderror</div>
            </div></div>
            <div class="actions"><button class="submit" id="submit-button" type="submit">Tạo khách hàng</button></div>
        </form>
        @endif
    </section>
    <div class="foot">Thông tin được truyền qua kết nối bảo mật · 3RD-VN</div>
</div></main>
<script>
    const dateInput = document.querySelector('[data-date-mask]');
    const formatDate = (value) => {
        const digits = value.replace(/\D/g, '').slice(0, 8);

        if (digits.length <= 2) return digits;
        if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;

        return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
    };

    dateInput?.addEventListener('input', (event) => {
        event.currentTarget.value = formatDate(event.currentTarget.value);
    });

    const moneyInput = document.querySelector('[data-money-mask]');
    const formatMoney = (value) => value
        .replace(/\D/g, '')
        .slice(0, 10)
        .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    if (moneyInput) {
        moneyInput.value = formatMoney(moneyInput.value);
        moneyInput.addEventListener('input', (event) => {
            event.currentTarget.value = formatMoney(event.currentTarget.value);
        });
    }

    document.getElementById('feol-form')?.addEventListener('submit',()=>{const b=document.getElementById('submit-button');b.disabled=true;b.textContent='Đang tạo khách hàng...'});
</script>
</body>
</html>
