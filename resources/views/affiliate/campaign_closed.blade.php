<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Dự án tạm dừng</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#070d19;color:#f8fafc;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.card{width:min(520px,100%);padding:36px 28px;border:1px solid #263449;border-radius:24px;background:#101827;box-shadow:0 24px 70px #0008;text-align:center}.icon{width:68px;height:68px;margin:0 auto 20px;display:grid;place-items:center;border-radius:20px;background:#3b2714;color:#fbbf24;font-size:34px}h1{margin:0 0 12px;font-size:28px;line-height:1.2}.message{margin:0;color:#cbd5e1;font-size:16px;line-height:1.65}.brand{margin-top:28px;padding-top:20px;border-top:1px solid #263449;color:#64748b;font-size:13px}.badge{display:inline-flex;margin-bottom:16px;padding:7px 12px;border-radius:999px;background:#3b2714;color:#fbbf24;font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
    </style>
</head>
<body>
<main class="card">
    <div class="icon">⏸</div>
    <div class="badge">{{ $campaign->name }}</div>
    <h1>Dự án tạm dừng</h1>
    <p class="message">{{ $message }}</p>
    <div class="brand">3RD Publisher · Affiliate Network</div>
</main>
</body>
</html>
