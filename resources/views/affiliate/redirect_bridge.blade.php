<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ $ogTitle }} | 3RD Vietnam</title>
    <meta name="description" content="{{ $ogDescription }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    
    <!-- OpenGraph / Facebook / Zalo Preview -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="3RD Vietnam Finance">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $ogTitle }}">
    
    <!-- Twitter Preview -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@3rdvn">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <!-- Canonical -->
    <link rel="canonical" href="{{ $currentUrl }}">
    <link rel="icon" type="image/png" href="/favicon.png">

    @if(!$isBot)
    <!-- Automatic Meta Refresh Redirect for Human Visitors -->
    <meta http-equiv="refresh" content="1;url={{ $targetUrl }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        
        body {
            background: #090e17;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
            overflow-x: hidden;
            position: relative;
        }

        .bg-glow {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, rgba(15, 23, 42, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            pointer-events: none;
        }

        .bridge-card {
            position: relative;
            z-index: 1;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 36px 28px 30px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(37, 99, 235, 0.1);
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .badge-sec {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 22px;
        }

        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .brand-avatar {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .brand-avatar img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .transfer-arrow {
            color: #3b82f6;
            animation: pulseArrow 1.5s infinite;
        }

        @keyframes pulseArrow {
            0%, 100% { transform: translateX(0); opacity: 0.6; }
            50% { transform: translateX(5px); opacity: 1; }
        }

        h1 {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1.35;
        }

        p.desc {
            font-size: 13.5px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .progress-bar-wrap {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 24px;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, #3b82f6, #60a5fa, #93c5fd);
            border-radius: 999px;
            animation: loadingProgress 1.2s infinite ease-in-out;
        }

        @keyframes loadingProgress {
            0% { transform: translateX(-100%); width: 30%; }
            50% { width: 60%; }
            100% { transform: translateX(400%); width: 30%; }
        }

        .btn-direct {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 20px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            border-radius: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-direct:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .footer-note {
            margin-top: 18px;
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="bridge-card">
        <div class="badge-sec">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
            Xác thực liên kết an toàn
        </div>

        <div class="logo-wrap">
            <div class="brand-avatar" title="3RD Vietnam">
                <img src="/images/logo-3rdvn.jpg" alt="3RD Vietnam" onerror="this.src='/favicon.png'">
            </div>
            <div class="transfer-arrow">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </div>
            <div class="brand-avatar" title="{{ $campaignName }}">
                <img src="{{ $ogImage }}" alt="{{ $campaignName }}">
            </div>
        </div>

        <h1>Đang chuyển tiếp bảo mật...</h1>
        <p class="desc">Hệ thống 3RD VN đang kết nối bạn đến cổng đăng ký chính thức <strong>{{ $campaignName }}</strong>.</p>

        <div class="progress-bar-wrap">
            <div class="progress-bar-fill"></div>
        </div>

        <a href="{{ $targetUrl }}" class="btn-direct">
            Tiếp Tục Đến Cổng Đăng Ký
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>

        <div class="footer-note">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Đường truyền mã hóa SSL 256-bit &middot; 3RD Vietnam
        </div>
    </div>

    @if(!$isBot)
    <script>
        // Real human user instant redirect
        (function() {
            var target = @json($targetUrl);
            setTimeout(function() {
                try {
                    window.location.replace(target);
                } catch(e) {
                    window.location.href = target;
                }
            }, 300);
        })();
    </script>
    @endif
</body>
</html>
