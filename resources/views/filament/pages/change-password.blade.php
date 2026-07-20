<x-filament-panels::page>
    <div class="crm-change-password">
        <form wire:submit.prevent="save" class="crm-change-password-card">
            <div class="crm-change-password-head">
                <h2>Thay đổi mật khẩu</h2>
                <p>Cập nhật mật khẩu đăng nhập CRM của tài khoản hiện tại.</p>
            </div>

            <label>
                <span>Mật khẩu hiện tại</span>
                <input type="password" wire:model.defer="current_password" autocomplete="current-password">
                @error('current_password') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Mật khẩu mới</span>
                <input type="password" wire:model.defer="password" autocomplete="new-password">
                @error('password') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Xác nhận mật khẩu mới</span>
                <input type="password" wire:model.defer="password_confirmation" autocomplete="new-password">
                @error('password_confirmation') <small>{{ $message }}</small> @enderror
            </label>

            <div class="crm-change-password-actions">
                <a href="{{ url()->previous() === url()->current() ? url('/') : url()->previous() }}">Quay lại</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save">Lưu mật khẩu</button>
            </div>
        </form>
    </div>

    <style>
        .crm-change-password {
            min-height: calc(100dvh - var(--crm-topbar-height, 72px) - 32px);
            display: grid;
            place-items: start center;
            padding-top: 18px;
        }

        .crm-change-password-card {
            width: min(520px, 100%);
            display: grid;
            gap: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .08);
        }

        .crm-change-password-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 1.28rem;
            line-height: 1.2;
            font-weight: 820;
            letter-spacing: 0;
        }

        .crm-change-password-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: .88rem;
            line-height: 1.45;
        }

        .crm-change-password-card label span {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: .84rem;
            font-weight: 720;
        }

        .crm-change-password-card input {
            width: 100%;
            height: 46px;
            border: 1px solid #d8e2ee;
            border-radius: 12px;
            padding: 0 13px;
            color: #0f172a;
            font-size: .94rem;
            outline: none;
        }

        .crm-change-password-card input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
        }

        .crm-change-password-card small {
            display: block;
            margin-top: 7px;
            color: #dc2626;
            font-size: .8rem;
            font-weight: 650;
        }

        .crm-change-password-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 4px;
        }

        .crm-change-password-actions a {
            color: #64748b;
            font-size: .88rem;
            font-weight: 720;
            text-decoration: none;
        }

        .crm-change-password-actions button {
            height: 42px;
            border: 0;
            border-radius: 11px;
            padding: 0 16px;
            background: #2563eb;
            color: #fff;
            font-size: .88rem;
            font-weight: 760;
            cursor: pointer;
        }

        .crm-change-password-actions button[disabled] {
            opacity: .68;
            cursor: wait;
        }
    </style>
</x-filament-panels::page>
