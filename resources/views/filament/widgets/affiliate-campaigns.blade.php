<x-filament-widgets::widget>
    <section class="affiliate-campaigns" x-data="{ open: null }">
        <header class="affiliate-campaigns__heading">
            <div><h2>Chiến dịch đang triển khai</h2><p>Chọn chiến dịch để xem thông tin và lấy link giới thiệu.</p></div>
            <span>{{ $campaigns->count() }} chiến dịch</span>
        </header>
        <div class="affiliate-campaigns__grid">
            @forelse ($campaigns as $campaign)
                @php($ownedLink = 'https://3rdvn.io.vn/affiliate/'.$campaign->slug.'?ref='.rawurlencode($employeeCode))
                <article class="affiliate-card">
                    <button type="button" class="affiliate-card__body" @click="open = open === {{ $campaign->id }} ? null : {{ $campaign->id }}">
                        <div class="affiliate-card__logo">@if($campaign->logo_url)<img src="{{ $campaign->logo_url }}" alt="Logo {{ $campaign->name }}">@else<span>{{ mb_substr($campaign->name, 0, 2) }}</span>@endif</div>
                        <div class="affiliate-card__copy"><span class="affiliate-card__status">Đang mở</span><h3>{{ $campaign->name }}</h3><p>{{ $campaign->summary }}</p></div>
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 5 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="affiliate-card__detail" x-show="open === {{ $campaign->id }}" x-collapse>
                        @if($campaign->details)<p>{{ $campaign->details }}</p>@endif
                        <div class="affiliate-card__link">{{ $ownedLink }}</div>
                        <div class="affiliate-card__actions">
                            <button type="button" @click="navigator.clipboard.writeText(@js($ownedLink)); new FilamentNotification().title('Đã sao chép link').success().send()">Sao chép link</button>
                            <a href="{{ $ownedLink }}" target="_blank" rel="noopener">Mở link</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="affiliate-campaigns__empty">Chưa có chiến dịch đang triển khai.</div>
            @endforelse
        </div>
    </section>
    <style>
        .affiliate-campaigns{display:grid;gap:18px}.affiliate-campaigns__heading{display:flex;align-items:end;justify-content:space-between;gap:16px}.affiliate-campaigns__heading h2{font-size:22px;font-weight:750;color:#102a43}.affiliate-campaigns__heading p{margin-top:4px;color:#64748b}.affiliate-campaigns__heading>span{white-space:nowrap;border-radius:999px;background:#eaf2f8;padding:7px 12px;color:#173b57;font-size:13px;font-weight:700}.affiliate-campaigns__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}.affiliate-card{overflow:hidden;border:1px solid #d7e1ea;border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(15,42,67,.06)}.affiliate-card__body{display:grid;width:100%;grid-template-columns:68px 1fr 22px;align-items:center;gap:16px;padding:20px;text-align:left}.affiliate-card__logo{display:grid;width:68px;height:68px;place-items:center;overflow:hidden;border:1px solid #dce6ee;border-radius:12px;background:#f8fafc;color:#153b57;font-weight:800}.affiliate-card__logo img{width:100%;height:100%;object-fit:contain;padding:8px}.affiliate-card__copy{min-width:0}.affiliate-card__status{display:inline-flex;margin-bottom:7px;border-radius:999px;background:#e8f7ee;padding:4px 8px;color:#177342;font-size:11px;font-weight:800;text-transform:uppercase}.affiliate-card__copy h3{font-size:18px;font-weight:750;color:#102a43}.affiliate-card__copy p{margin-top:4px;overflow:hidden;color:#64748b;font-size:14px;text-overflow:ellipsis;white-space:nowrap}.affiliate-card__body>svg{width:20px;color:#7890a5}.affiliate-card__detail{border-top:1px solid #e5edf3;padding:18px 20px;background:#f8fbfd}.affiliate-card__detail>p{margin-bottom:14px;color:#52677a;font-size:14px}.affiliate-card__link{overflow:hidden;border:1px solid #d8e2ea;border-radius:8px;background:#fff;padding:10px 12px;color:#334e68;font-family:ui-monospace,monospace;font-size:12px;white-space:nowrap;text-overflow:ellipsis}.affiliate-card__actions{display:flex;gap:10px;margin-top:12px}.affiliate-card__actions button,.affiliate-card__actions a{display:inline-flex;min-height:40px;align-items:center;justify-content:center;border-radius:8px;padding:0 15px;font-size:14px;font-weight:700}.affiliate-card__actions button{background:#123852;color:#fff}.affiliate-card__actions a{border:1px solid #b9cad8;color:#173b57}.affiliate-campaigns__empty{grid-column:1/-1;border:1px dashed #cbd5e1;border-radius:12px;padding:28px;text-align:center;color:#64748b}@media(max-width:640px){.affiliate-campaigns__heading{align-items:start}.affiliate-campaigns__heading p{display:none}.affiliate-campaigns__grid{grid-template-columns:1fr}.affiliate-card__body{grid-template-columns:56px 1fr 20px;padding:16px}.affiliate-card__logo{width:56px;height:56px}.affiliate-card__actions>*{flex:1}}
    </style>
</x-filament-widgets::widget>
