@php
    $integration = $this->record->feolIntegration;
    $eligible = $this->isEligible();
    $ineligible = $this->isIneligible();
    $status = $integration?->sub_status?->label() ?? 'Đang kiểm tra';
    $deeplink = $this->deeplink();
@endphp

<x-filament-panels::page>
    <style>
        .feol-check-card{max-width:640px;margin:2rem auto;text-align:center;padding:2.5rem 2rem;border:1px solid #dbe4f0;border-radius:20px;background:#fff;box-shadow:0 18px 50px rgba(15,23,42,.08)}
        .feol-check-scene{position:relative;width:180px;height:150px;margin:0 auto 1.25rem}.feol-check-paper{position:absolute;width:88px;height:112px;left:46px;top:14px;border:2px solid #cbd5e1;border-radius:14px;background:#fff;box-shadow:0 20px 35px rgba(15,23,42,.10);overflow:hidden}.feol-check-paper:before{content:'';position:absolute;inset:22px 16px auto;height:7px;border-radius:9px;background:#bfdbfe;box-shadow:0 17px #dbeafe,0 34px #dbeafe}.feol-check-scan{position:absolute;inset:auto 0 100%;height:40px;background:linear-gradient(transparent,rgba(59,130,246,.22),transparent);animation:feol-scan 2.4s ease-in-out infinite}.feol-check-badge{position:absolute;right:-8px;bottom:-8px;width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:#fff;border:1px solid #dbeafe;color:#2563eb;font-weight:900;box-shadow:0 8px 18px rgba(37,99,235,.16);animation:feol-pulse 1.1s ease-in-out infinite}.feol-check-result{display:grid;place-items:center;width:88px;height:88px;margin:0 auto 1.25rem;border-radius:50%;font-size:54px;font-weight:900}.feol-check-result.success{color:#15803d;background:#ecfdf5;border:2px solid #86efac}.feol-check-result.failed{color:#c2410c;background:#fff7ed;border:2px solid #fdba74}.feol-check-title{font-size:1.45rem;font-weight:800;color:#172033}.feol-check-copy{margin-top:1.5rem}.feol-check-note{margin-top:.65rem;color:#64748b;font-size:.9rem}@keyframes feol-scan{0%{transform:translateY(0);opacity:0}20%{opacity:1}100%{transform:translateY(160px);opacity:0}}@keyframes feol-pulse{50%{transform:scale(1.12);opacity:.65}}
    </style>
    <div class="feol-check-card" @if (! $eligible && ! $ineligible) wire:poll.2s="refreshResult" @endif>
        @if ($eligible)
            <div class="feol-check-result success">✓</div>
            <div class="feol-check-title">Chúc mừng! Hồ sơ Khách hàng thoả mãn điều kiện đăng ký hồ sơ vay.</div>
            <p class="feol-check-note">Mã hồ sơ: {{ $this->record->application_code }} · ID đối tác: {{ $integration?->partner_lead_id ?: '-' }} · Trạng thái: {{ $status }}</p>
            @if ($deeplink)
                <div class="feol-check-copy">
                    <x-filament::button icon="heroicon-o-clipboard-document" x-on:click="navigator.clipboard.writeText(@js($deeplink)); new FilamentNotification().title('Đã sao chép Deeplink').success().send()">Copy Deeplink</x-filament::button>
                </div>
            @else
                <p class="feol-check-note">Đang nhận deeplink từ đối tác. Trang sẽ tự cập nhật.</p>
            @endif
        @elseif ($ineligible)
            <div class="feol-check-result failed">☹</div>
            <div class="feol-check-title">Rất tiếc hồ sơ của Khách hàng không thoả điều kiện.</div>
            <p class="feol-check-note">Mã hồ sơ: {{ $this->record->application_code }} · ID đối tác: {{ $integration?->partner_lead_id ?: '-' }} · Trạng thái: {{ $status }}</p>
        @else
            <div class="feol-check-scene" aria-label="Đang kiểm tra hồ sơ"><div class="feol-check-paper"><div class="feol-check-scan"></div><div class="feol-check-badge">…</div></div></div>
            <div class="feol-check-title">Đang kiểm tra điều kiện hồ sơ</div>
            <p class="feol-check-note">Hồ sơ đã lưu CRM và đang chờ phản hồi FEOL. Kết quả sẽ tự hiện tại đây.</p>
        @endif
    </div>
</x-filament-panels::page>
