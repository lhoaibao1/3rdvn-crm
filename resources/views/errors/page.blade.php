@php
    $status = (int) ($status ?? 500);
    $catalog = [
        400 => ['Yêu cầu không hợp lệ', 'Dữ liệu gửi lên chưa đúng định dạng. Vui lòng kiểm tra và thử lại.', 'Kiểm tra dữ liệu', 'warning'],
        401 => ['Cần đăng nhập', 'Phiên truy cập chưa được xác thực. Vui lòng đăng nhập để tiếp tục.', 'Đăng nhập lại', 'warning'],
        402 => ['Yêu cầu thanh toán', 'Tác vụ này cần hoàn tất điều kiện thanh toán trước khi tiếp tục.', 'Kiểm tra thanh toán', 'warning'],
        403 => ['Không có quyền truy cập', 'Tài khoản của bạn không được phép mở nội dung này.', 'Liên hệ quản trị viên', 'danger'],
        404 => ['Không tìm thấy trang', 'Trang bạn cần có thể đã được di chuyển, đổi tên hoặc không còn tồn tại.', 'Kiểm tra đường dẫn', 'info'],
        405 => ['Phương thức không được hỗ trợ', 'Thao tác này không phù hợp với địa chỉ đang truy cập.', 'Thử lại thao tác', 'warning'],
        408 => ['Yêu cầu hết thời gian', 'Kết nối mất quá nhiều thời gian. Vui lòng gửi lại yêu cầu.', 'Thử lại', 'warning'],
        409 => ['Dữ liệu đang xung đột', 'Thông tin đã thay đổi trong lúc xử lý. Vui lòng tải lại trước khi tiếp tục.', 'Tải dữ liệu mới', 'warning'],
        410 => ['Nội dung không còn tồn tại', 'Tài nguyên này đã được gỡ khỏi hệ thống và không thể truy cập lại.', 'Về trang chủ', 'info'],
        419 => ['Phiên làm việc đã hết hạn', 'Để bảo vệ tài khoản, vui lòng đăng nhập lại rồi tiếp tục công việc.', 'Đăng nhập lại', 'warning'],
        422 => ['Dữ liệu chưa hợp lệ', 'Một số thông tin chưa đạt yêu cầu. Vui lòng kiểm tra các trường đã nhập.', 'Kiểm tra biểu mẫu', 'warning'],
        429 => ['Thao tác quá nhanh', 'Hệ thống đang nhận nhiều yêu cầu. Vui lòng chờ một lát rồi thử lại.', 'Chờ và thử lại', 'warning'],
        500 => ['Hệ thống gặp sự cố', 'CRM chưa thể hoàn tất yêu cầu. Đội vận hành đã có thể tra cứu sự cố này.', 'Thử lại sau', 'danger'],
        501 => ['Chức năng chưa sẵn sàng', 'Tính năng này chưa được hỗ trợ trong phiên bản hiện tại.', 'Về trang chủ', 'info'],
        502 => ['Dịch vụ trung gian không phản hồi', 'CRM chưa nhận được phản hồi hợp lệ từ dịch vụ liên kết.', 'Thử lại sau', 'danger'],
        503 => ['Hệ thống đang bảo trì', 'Dịch vụ tạm thời gián đoạn để nâng cấp. Vui lòng quay lại sau ít phút.', 'Quay lại sau', 'info'],
        504 => ['Dịch vụ phản hồi quá chậm', 'Hệ thống liên kết chưa phản hồi kịp thời. Vui lòng thử lại sau.', 'Thử lại sau', 'warning'],
    ];
    [$title, $description, $hint, $tone] = $catalog[$status] ?? ($status >= 500
        ? ['Hệ thống gặp sự cố', 'CRM chưa thể hoàn tất yêu cầu này. Vui lòng thử lại sau.', 'Thử lại sau', 'danger']
        : ['Không thể mở nội dung', 'Yêu cầu chưa thể được xử lý.', 'Kiểm tra lại', 'warning']);
    $isSessionError = in_array($status, [401, 419], true);
    $requestCode = strtoupper(substr(hash('sha256', request()->path().'|'.now()->format('YmdHi')), 0, 10));
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $status }} · {{ $title }} · 3RD-VN CRM</title>
    <style>
        :root{color-scheme:light;--ink:#111827;--muted:#64748b;--line:#dce5f1;--blue:#2563eb;--blue-soft:#eff6ff;--danger:#e5484d;--warning:#d97706;--info:#2563eb}
        *{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ink)}
        body{min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 12% 8%,#dff4ff 0,transparent 28%),radial-gradient(circle at 90% 90%,#e9e7ff 0,transparent 31%),#f4f7fb}
        .shell{width:min(100%,1040px);overflow:hidden;border:1px solid #d8e1ee;border-radius:28px;background:rgba(255,255,255,.96);box-shadow:0 28px 80px rgba(31,55,91,.16)}
        .bar{height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 28px;border-bottom:1px solid var(--line)}
        .brand{display:flex;align-items:center;gap:12px;font-weight:800;letter-spacing:.02em}.mark{display:grid;place-items:center;width:38px;height:38px;border-radius:12px;color:#fff;background:linear-gradient(145deg,#1e40af,#2f6cf4);font-size:22px}
        .system{display:flex;align-items:center;gap:9px;color:#52647d;font-size:13px;font-weight:700}.dot{width:9px;height:9px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 5px #dcfce7}
        .content{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(280px,.9fr);min-height:510px}
        .copy{padding:64px 64px 58px}.eyebrow{display:inline-flex;align-items:center;gap:9px;padding:8px 12px;border-radius:999px;background:var(--blue-soft);color:#1d4ed8;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        h1{margin:25px 0 15px;font-size:clamp(34px,5vw,58px);line-height:1.02;letter-spacing:-.045em}.desc{max-width:580px;margin:0;color:var(--muted);font-size:18px;line-height:1.65}
        .actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:34px}.btn{min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:0 19px;border:1px solid #cbd6e5;border-radius:13px;color:#26364d;background:#fff;text-decoration:none;font-weight:750;cursor:pointer;font:inherit}.btn.primary{border-color:var(--blue);background:var(--blue);color:#fff}.btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(30,64,175,.12)}
        .meta{display:flex;flex-wrap:wrap;gap:18px;margin-top:38px;padding-top:24px;border-top:1px solid var(--line);color:#718198;font:600 12px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace}.meta strong{color:#334155}
        .visual{position:relative;display:grid;place-items:center;min-height:510px;overflow:hidden;border-left:1px solid var(--line);background:linear-gradient(145deg,#f8fbff,#edf3fb)}
        .grid{position:absolute;inset:0;opacity:.55;background-image:linear-gradient(#cdd9e8 1px,transparent 1px),linear-gradient(90deg,#cdd9e8 1px,transparent 1px);background-size:32px 32px;mask-image:linear-gradient(to bottom,transparent,#000 20%,#000 80%,transparent)}
        .core{position:relative;width:250px;height:250px;display:grid;place-items:center}.orbit{position:absolute;inset:0;border:1px solid #b8c8df;border-radius:50%;animation:spin 12s linear infinite}.orbit:before,.orbit:after{content:"";position:absolute;width:13px;height:13px;border-radius:50%;background:var(--tone,var(--blue));box-shadow:0 0 24px var(--tone,var(--blue))}.orbit:before{top:20px;left:42px}.orbit:after{right:14px;bottom:58px}.orbit.two{inset:28px;animation-direction:reverse;animation-duration:8s;border-style:dashed}
        .code{position:relative;display:grid;place-items:center;width:150px;height:150px;border-radius:38px;background:#fff;border:1px solid #cfdaea;box-shadow:0 22px 50px rgba(43,69,105,.18);font-size:52px;font-weight:850;letter-spacing:-.06em;color:var(--tone,var(--blue))}.code:after{content:"";position:absolute;inset:-13px;border:1px solid color-mix(in srgb,var(--tone,var(--blue)) 28%,transparent);border-radius:48px}
        .visual-label{position:absolute;bottom:55px;text-align:center;color:#60728b;font-size:13px;font-weight:700}.visual-label b{display:block;margin-bottom:5px;color:#26364d;font-size:14px}.tone-danger{--tone:var(--danger)}.tone-warning{--tone:var(--warning)}.tone-info{--tone:var(--info)}
        @keyframes spin{to{transform:rotate(360deg)}}
        @media(max-width:760px){body{padding:14px}.shell{border-radius:22px}.bar{height:58px;padding:0 18px}.system span:last-child{display:none}.content{display:flex;flex-direction:column-reverse;min-height:auto}.visual{min-height:250px;border-left:0;border-bottom:1px solid var(--line)}.core{width:178px;height:178px}.orbit.two{inset:22px}.code{width:106px;height:106px;border-radius:29px;font-size:40px}.visual-label{bottom:18px}.copy{padding:32px 24px 28px}h1{margin-top:18px}.desc{font-size:16px}.actions{margin-top:25px}.btn{flex:1 1 145px}.meta{margin-top:28px}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important}}
    </style>
</head>
<body>
<main class="shell tone-{{ $tone }}">
    <header class="bar">
        <div class="brand"><span class="mark">3</span><span>3RD-VN CRM</span></div>
        <div class="system"><span class="dot"></span><span>TRUNG TÂM HỖ TRỢ HỆ THỐNG</span></div>
    </header>
    <section class="content">
        <div class="copy">
            <span class="eyebrow">Mã phản hồi {{ $status }}</span>
            <h1>{{ $title }}</h1>
            <p class="desc">{{ $description }}</p>
            <div class="actions">
                @if($isSessionError)
                    <a class="btn primary" href="{{ url('/authen/login') }}">Đăng nhập lại <span aria-hidden="true">→</span></a>
                @else
                    <button class="btn primary" type="button" onclick="window.location.reload()">Thử lại <span aria-hidden="true">↻</span></button>
                @endif
                <button class="btn" type="button" onclick="history.length > 1 ? history.back() : window.location.assign('{{ url('/') }}')">Quay lại</button>
                <a class="btn" href="{{ url('/') }}">Trang chủ</a>
            </div>
            <div class="meta">
                <span>Gợi ý: <strong>{{ $hint }}</strong></span>
                <span>Mã tra cứu: <strong>{{ $requestCode }}</strong></span>
            </div>
        </div>
        <div class="visual" aria-hidden="true">
            <div class="grid"></div>
            <div class="core"><div class="orbit"></div><div class="orbit two"></div><div class="code">{{ $status }}</div></div>
            <div class="visual-label"><b>CRM RESPONSE CENTER</b>Yêu cầu đã được ghi nhận an toàn</div>
        </div>
    </section>
</main>
</body>
</html>
