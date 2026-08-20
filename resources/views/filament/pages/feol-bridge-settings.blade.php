<x-filament-panels::page>
    <form wire:submit.prevent="save" class="crm-feol-config">
        <section class="crm-feol-config-card">
            <div class="crm-feol-config-head">
                <div>
                    <h2>Cấu hình kết nối FEOL Bridge</h2>
                    <p>Đổi link CRM đối tác, tài khoản, chiến dịch và tham số quét mà không cần SSH vào VPS.</p>
                </div>
                <span class="crm-feol-config-status {{ $serviceStatus === 'active' ? 'is-active' : '' }}">
                    {{ $serviceStatus === 'active' ? 'Đang chạy' : $serviceStatus }}
                </span>
            </div>

            <div class="crm-feol-config-grid">
                <label class="is-wide">
                    <span>CRM nội bộ nhận cập nhật</span>
                    <input type="url" wire:model.defer="config.CRM_UAT_URL" placeholder="https://apps2.3rdvn.io.vn">
                    @error('config.CRM_UAT_URL') <small>{{ $message }}</small> @enderror
                </label>

                <label class="is-wide">
                    <span>Auth URL CRM đối tác</span>
                    <input type="url" wire:model.defer="config.PARTNER_AUTH_URL" placeholder="https://backend-ws.saigonbpo.vn/os_ws_auth">
                    @error('config.PARTNER_AUTH_URL') <small>{{ $message }}</small> @enderror
                </label>

                <label class="is-wide">
                    <span>Data URL CRM đối tác</span>
                    <input type="url" wire:model.defer="config.PARTNER_DATA_URL" placeholder="https://backend-ws.saigonbpo.vn/os_ws_lio_and_fe">
                    @error('config.PARTNER_DATA_URL') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Tài khoản đối tác</span>
                    <input type="text" wire:model.defer="config.PARTNER_USERNAME" autocomplete="off">
                    @error('config.PARTNER_USERNAME') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Mật khẩu đối tác mới</span>
                    <input type="password" wire:model.defer="partner_password" autocomplete="new-password" placeholder="Bỏ trống nếu không đổi">
                    @error('partner_password') <small>{{ $message }}</small> @enderror
                </label>

                <label class="is-wide">
                    <span>Chiến dịch cần lọc</span>
                    <input type="text" wire:model.defer="config.PARTNER_CAMPAIGN" placeholder="FE - Cash Loan - Deeplink">
                    @error('config.PARTNER_CAMPAIGN') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Số dòng mỗi trang</span>
                    <input type="number" min="50" max="500" wire:model.defer="config.PARTNER_PAGE_SIZE">
                    @error('config.PARTNER_PAGE_SIZE') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Số trang tối đa</span>
                    <input type="number" min="1" max="10" wire:model.defer="config.PARTNER_MAX_PAGES">
                    @error('config.PARTNER_MAX_PAGES') <small>{{ $message }}</small> @enderror
                </label>

                <label class="is-wide">
                    <span>Token CRM nội bộ mới</span>
                    <input type="password" wire:model.defer="crm_feol_token" autocomplete="new-password" placeholder="Bỏ trống nếu không đổi">
                    @error('crm_feol_token') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="crm-feol-config-note">
                Mật khẩu/token hiện tại được ẩn. Nếu nhập mật khẩu/token mới, hệ thống sẽ ghi vào env và restart bridge ngay.
            </div>

            <div class="crm-feol-config-actions">
                <button type="button" wire:click="loadConfig" wire:loading.attr="disabled">Tải lại</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Lưu & restart Node-RED</span>
                    <span wire:loading wire:target="save">Đang lưu...</span>
                </button>
            </div>
        </section>
    </form>

    <style>
        .crm-feol-config { display: grid; place-items: start center; }
        .crm-feol-config-card { width: min(980px, 100%); padding: 22px; border: 1px solid #dbe6f2; border-radius: 18px; background: #fff; box-shadow: 0 18px 44px rgba(15,23,42,.07); }
        .crm-feol-config-head { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; margin-bottom: 18px; }
        .crm-feol-config-head h2 { margin: 0; color: #0f172a; font-size: 1.2rem; font-weight: 850; }
        .crm-feol-config-head p { margin: 6px 0 0; color: #64748b; font-size: .88rem; }
        .crm-feol-config-status { border-radius: 999px; padding: 7px 11px; background: #fff7ed; color: #9a3412; font-weight: 800; font-size: .8rem; white-space: nowrap; }
        .crm-feol-config-status.is-active { background: #dcfce7; color: #166534; }
        .crm-feol-config-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .crm-feol-config-grid label.is-wide { grid-column: 1 / -1; }
        .crm-feol-config-grid span { display: block; margin-bottom: 7px; color: #334155; font-size: .83rem; font-weight: 760; }
        .crm-feol-config-grid input { width: 100%; height: 44px; border: 1px solid #d8e2ee; border-radius: 12px; padding: 0 13px; color: #0f172a; background: #fff; outline: 0; }
        .crm-feol-config-grid input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,.1); }
        .crm-feol-config-grid small { display: block; margin-top: 6px; color: #dc2626; font-weight: 700; font-size: .78rem; }
        .crm-feol-config-note { margin-top: 15px; padding: 12px 13px; border-radius: 12px; background: #eff6ff; color: #1e3a8a; font-size: .85rem; font-weight: 650; }
        .crm-feol-config-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
        .crm-feol-config-actions button { height: 42px; border: 0; border-radius: 11px; padding: 0 15px; font-weight: 800; cursor: pointer; }
        .crm-feol-config-actions button:first-child { background: #e2e8f0; color: #334155; }
        .crm-feol-config-actions button:last-child { background: #2563eb; color: #fff; }
        .crm-feol-config-actions button[disabled] { opacity: .65; cursor: wait; }
        @media (max-width: 720px) { .crm-feol-config-card { padding: 16px; } .crm-feol-config-head { flex-direction: column; } .crm-feol-config-grid { grid-template-columns: 1fr; } }
    </style>
</x-filament-panels::page>
