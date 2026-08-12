@php
    $settings = \App\Models\UiSetting::current();
    $favicon = $settings->favicon_path ? asset('storage/'.$settings->favicon_path) : asset('favicon.ico');
@endphp
<!doctype html>
<html lang="vi" data-crm-login-page>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập LOS · 3RDVN</title>
    <link rel="icon" href="{{ $favicon }}">
    <link rel="stylesheet" href="{{ asset('fonts/filament/filament/inter/index.css') }}">
</head>
<body>
    <x-auth.crm-login-shell
        title="Đăng nhập LOS"
        subtitle="Truy vấn hồ sơ"
        workspace="LOS Workspace"
        story-kicker="Truy vấn hồ sơ"
        story-title="Tìm đúng hồ sơ."
        story-accent="Xem đủ thông tin."
        story-description="Tìm kiếm và đối chiếu thông tin Application theo từng dự án trong một không gian bảo mật, rõ ràng và thống nhất."
        :flow="[
            ['label' => 'Tìm kiếm'],
            ['label' => 'Đối chiếu'],
            ['label' => 'Xem hồ sơ'],
            ['label' => 'Theo dõi'],
        ]"
    >
        @if (session('status'))
            <div class="crm-login-status" role="status">{{ session('status') }}</div>
        @endif

        <form
            id="los-login-form"
            class="crm-login-form"
            method="POST"
            action="{{ route('los.login.store') }}"
        >
            @csrf

            <div class="crm-login-field">
                <label class="crm-login-label" for="los-login-identifier">User / UID</label>
                <div @class(['crm-login-control', 'is-invalid' => $errors->has('identifier')])>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <input
                        id="los-login-identifier"
                        type="text"
                        name="identifier"
                        value="{{ old('identifier') }}"
                        autocomplete="username"
                        placeholder="Nhập User hoặc UID"
                        aria-invalid="{{ $errors->has('identifier') ? 'true' : 'false' }}"
                        @error('identifier') aria-describedby="los-login-identifier-error" @enderror
                        required
                        autofocus
                    >
                </div>
                @error('identifier')
                    <div id="los-login-identifier-error" class="crm-login-error" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="crm-login-field">
                <label class="crm-login-label" for="los-login-password">Mật khẩu</label>
                <div @class(['crm-login-control', 'is-invalid' => $errors->has('password')])>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7 10V7a5 5 0 0 1 10 0v3M6.5 10h11A1.5 1.5 0 0 1 19 11.5v7A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-7A1.5 1.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <input
                        id="los-login-password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Nhập mật khẩu"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        @error('password') aria-describedby="los-login-password-error" @enderror
                        required
                    >
                    <button
                        class="crm-login-password-toggle"
                        type="button"
                        data-los-password-toggle
                        aria-label="Hiện mật khẩu"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7"/>
                            <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.7"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <div id="los-login-password-error" class="crm-login-error" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="crm-login-options">
                <label class="crm-login-remember">
                    <input type="hidden" name="remember" value="0">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Ghi nhớ đăng nhập</span>
                </label>
            </div>

            <button class="crm-login-submit" type="submit" data-los-login-submit>
                <span data-los-submit-label>Đăng nhập</span>
                <svg data-los-submit-arrow viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="crm-login-spinner" data-los-submit-spinner hidden aria-hidden="true"></span>
            </button>
        </form>

        <p class="crm-login-secondary">Sử dụng tài khoản CRM được cấp để truy vấn hồ sơ.</p>
    </x-auth.crm-login-shell>

    <script>
        (() => {
            const password = document.getElementById('los-login-password');
            const passwordToggle = document.querySelector('[data-los-password-toggle]');

            passwordToggle?.addEventListener('click', () => {
                const willShow = password?.type === 'password';

                if (password) {
                    password.type = willShow ? 'text' : 'password';
                    password.focus({ preventScroll: true });
                }

                passwordToggle.setAttribute('aria-label', willShow ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            });

            const form = document.getElementById('los-login-form');
            const submit = form?.querySelector('[data-los-login-submit]');
            const submitLabel = form?.querySelector('[data-los-submit-label]');
            const submitArrow = form?.querySelector('[data-los-submit-arrow]');
            const submitSpinner = form?.querySelector('[data-los-submit-spinner]');
            let pending = false;

            form?.addEventListener('submit', () => {
                if (! form.checkValidity()) {
                    return;
                }

                if (pending) {
                    return;
                }

                pending = true;
                if (submit) submit.disabled = true;
                if (submitLabel) submitLabel.textContent = 'Đang xác thực...';
                if (submitArrow) submitArrow.hidden = true;
                if (submitSpinner) submitSpinner.hidden = false;
            });
        })();
    </script>
</body>
</html>
