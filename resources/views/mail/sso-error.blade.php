<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Không thể mở Mail</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #172033; background: #f5f7fb; font: 15px/1.5 Inter, system-ui, sans-serif; }
        main { width: min(420px, 100%); padding: 28px; text-align: center; background: #fff; border: 1px solid #e3e8f0; border-radius: 8px; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        p { margin: 0 0 20px; color: #64748b; }
        a { display: inline-block; padding: 10px 16px; color: #fff; text-decoration: none; background: #1565d8; border-radius: 6px; }
    </style>
</head>
<body>
<main>
    <h1>Chưa thể mở hộp thư</h1>
    <p>Vui lòng thử lại. Nếu lỗi vẫn còn, liên hệ quản trị viên.</p>
    <a href="{{ route('mail.sso') }}">Thử lại</a>
</main>
</body>
</html>
