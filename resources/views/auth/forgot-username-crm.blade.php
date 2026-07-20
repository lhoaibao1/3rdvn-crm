<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quên tên đăng nhập - 3RDVN CRM</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #0f172a; }
        body { min-height: 100dvh; display: grid; place-items: center; padding: 24px; background: linear-gradient(135deg, #f6f9ff, #edf5ff); }
        main { width: min(430px, 100%); padding: 30px; border-radius: 24px; background: rgba(255,255,255,.92); border: 1px solid #e2e8f0; box-shadow: 0 24px 70px rgba(15,23,42,.13); }
        h1 { margin: 0 0 22px; font-size: 2rem; line-height: 1.08; letter-spacing: 0; }
        form { display: grid; gap: 14px; }
        label span { display: block; margin-bottom: 8px; color: #334155; font-weight: 760; font-size: .88rem; }
        input { width: 100%; height: 52px; border: 1px solid #d8e2ee; border-radius: 15px; padding: 0 15px; font-size: 1rem; outline: none; }
        input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,.11); }
        button { height: 52px; border: 0; border-radius: 15px; background: linear-gradient(135deg, #2563eb, #0891b2); color: #fff; font-weight: 850; cursor: pointer; }
        a { width: fit-content; color: #2563eb; font-weight: 760; text-decoration: none; }
        .status { margin-bottom: 16px; padding: 12px 14px; border-radius: 14px; background: #eff6ff; color: #1d4ed8; font-weight: 720; }
        .error { margin-top: 8px; color: #dc2626; font-size: .88rem; font-weight: 680; }
    </style>
</head>
<body>
    <main>
        <h1>Quên tên đăng nhập</h1>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <form method="post" action="{{ route('crm.username.lookup') }}">
            @csrf
            <label>
                <span>CCCD / SĐT / Email</span>
                <input name="identifier" value="{{ old('identifier') }}" autofocus>
                @error('identifier')
                    <div class="error">{{ $message }}</div>
                @enderror
            </label>
            <button type="submit">Kiểm tra</button>
            <a href="{{ url('/authen/login') }}">Quay lại đăng nhập</a>
        </form>
    </main>
</body>
</html>
