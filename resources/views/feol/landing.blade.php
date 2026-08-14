<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Đăng ký vay FE CREDIT</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#172033;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.page{min-height:100vh;padding:28px 16px 48px;background:linear-gradient(160deg,#edf4ff 0,#f7f9fc 42%,#fff6ed 100%)}.wrap{width:min(100%,620px);margin:auto}.brand{text-align:center;margin:4px 0 20px}.brand-mark{display:inline-grid;place-items:center;width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#1456b8,#2e6ff2);color:#fff;font-size:30px;font-weight:900;box-shadow:0 12px 28px #174ea633}.brand h1{font-size:24px;margin:12px 0 4px}.brand p{margin:0;color:#68758b}.card{background:#fff;border:1px solid #dfe6f0;border-radius:20px;padding:28px;box-shadow:0 18px 50px #20345114}.grid{display:grid;grid-template-columns:1fr 1fr;gap:17px}.field.full{grid-column:1/-1}label{display:block;font-size:14px;font-weight:700;margin:0 0 7px}.req{color:#e02727}input{width:100%;height:46px;border:1px solid #cfd8e6;border-radius:10px;padding:0 13px;font-size:15px;color:#172033;outline:0;background:#fff}input:focus{border-color:#2e6ff2;box-shadow:0 0 0 3px #2e6ff21a}.error{font-size:12px;color:#c92222;margin-top:5px}.consent{display:flex;gap:10px;align-items:flex-start;padding:14px;border:1px solid #dfe6f0;border-radius:12px;background:#f8fafc;font-size:13px;line-height:1.5}.consent input{width:19px;height:19px;margin-top:1px;flex:none}.submit{width:100%;height:50px;border:0;border-radius:11px;background:linear-gradient(90deg,#1556b7,#2f6ff0);color:#fff;font-weight:800;font-size:16px;cursor:pointer}.submit:disabled{opacity:.65;cursor:wait}.notice{padding:14px;border-radius:12px;background:#edf8f1;border:1px solid #b8e3c7;color:#17633a;margin-bottom:18px}.foot{text-align:center;color:#8290a6;font-size:12px;margin-top:18px}@media(max-width:620px){.page{padding:18px 12px 32px}.card{padding:20px 16px;border-radius:16px}.grid{grid-template-columns:1fr}.field.full{grid-column:auto}.brand h1{font-size:21px}}
    </style>
</head>
<body>
<main class="page"><div class="wrap">
    <header class="brand"><div class="brand-mark">FE</div><h1>Đăng ký vay FE CREDIT</h1><p>Hoàn tất thông tin để tiếp tục hồ sơ</p></header>
    <section class="card">
        @if($submitted)
            <div class="notice">Hồ sơ đã được tiếp nhận. Chúng tôi sẽ liên hệ với Quý khách trong thời gian sớm nhất.</div>
        @else
        <form method="post" action="{{ $submitUrl }}" id="feol-form">
            @csrf
            <div class="grid">
                <div class="field full"><label>Họ và tên <span class="req">*</span></label><input name="applicant_name" value="{{ old('applicant_name', $application?->applicant_name) }}" required maxlength="255">@error('applicant_name')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Số điện thoại <span class="req">*</span></label><input name="phone" value="{{ old('phone', $application?->phone) }}" required inputmode="numeric" maxlength="10">@error('phone')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Số CCCD <span class="req">*</span></label><input name="identity_number" value="{{ old('identity_number', $application?->identity_number) }}" required inputmode="numeric" maxlength="12">@error('identity_number')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Ngày tháng năm sinh <span class="req">*</span></label><input name="date_of_birth" type="text" placeholder="dd/mm/yyyy" value="{{ old('date_of_birth', filled(data_get($application?->payload, 'fields.date_of_birth')) ? \Carbon\CarbonImmutable::parse(data_get($application?->payload, 'fields.date_of_birth'))->format('d/m/Y') : '') }}" required>@error('date_of_birth')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Địa chỉ Email <span class="req">*</span></label><input name="email" type="email" value="{{ old('email', data_get($application?->payload, 'fields.email')) }}" required>@error('email')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Số tiền vay <span class="req">*</span></label><input name="loan_amount" type="number" min="1000000" max="1000000000" value="{{ old('loan_amount', data_get($application?->payload, 'fields.loan_amount')) }}" required>@error('loan_amount')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Thời hạn vay (tháng) <span class="req">*</span></label><input name="loan_term_months" type="number" min="1" max="120" value="{{ old('loan_term_months', data_get($application?->payload, 'fields.loan_term_months')) }}" required>@error('loan_term_months')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label>Mã giới thiệu</label><input value="{{ $referralCode }}" readonly></div>
                <div class="field"><label>Mã nhân viên</label><input value="{{ data_get($application?->payload, 'fields.salesman_code', config('services.feol_bridge.landing_sale_code')) }}" readonly></div>
                <div class="field full"><label class="consent"><input type="checkbox" name="customer_consent" value="1" required @checked(old('customer_consent'))><span>Tôi đồng ý cung cấp dữ liệu cá nhân đầy đủ, chính xác và cho phép chuyển dữ liệu phục vụ thẩm định, xét duyệt hồ sơ cấp tín dụng.</span></label>@error('customer_consent')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field full"><button class="submit" id="submit-button" type="submit">Gửi đăng ký</button></div>
            </div>
        </form>
        @endif
    </section>
    <div class="foot">Thông tin được truyền qua kết nối bảo mật · 3RD-VN</div>
</div></main>
<script>document.getElementById('feol-form')?.addEventListener('submit',()=>{const b=document.getElementById('submit-button');b.disabled=true;b.textContent='Đang lưu hồ sơ...'});</script>
</body>
</html>
