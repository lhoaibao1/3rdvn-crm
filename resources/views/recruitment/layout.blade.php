<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="Cổng tuyển dụng chính thức của 3RDVN">
<title>@yield('title') - 3RDVN</title>
@if($settings->favicon_path)<link rel="icon" href="{{ asset('storage/'.$settings->favicon_path) }}">@endif
<style>
:root {
    --brand: {{ $settings->primary_color ?: '#2563eb' }};
    --navy: #0b2038;
    --ink: #132238;
    --muted: #64748b;
    --line: #dce5ef;
    --canvas: #f4f7fb;
    --surface: #ffffff;
    --teal: #0f8b7f;
    --amber: #d97706;
    --danger: #dc2626;
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; color: var(--ink); background: var(--canvas); letter-spacing: 0; }
body { margin: 0; min-height: 100dvh; }
button, input, select, textarea { font: inherit; }
a { color: inherit; }
[hidden] { display: none !important; }

.top { height: 68px; display: flex; align-items: center; border-bottom: 1px solid var(--line); background: #fff; position: sticky; top: 0; z-index: 30; }
.top-inner { width: min(1200px, calc(100% - 32px)); margin: auto; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.brand { display: flex; align-items: center; gap: 11px; font-weight: 800; color: var(--ink); text-decoration: none; }
.brand img { max-width: 150px; max-height: 38px; object-fit: contain; }
.brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 8px; background: var(--brand); color: #fff; }
.top-links { display: flex; align-items: center; gap: 8px; }
.top-note { display: inline-flex; min-height: 38px; align-items: center; padding: 0 14px; border: 1px solid var(--line); border-radius: 7px; color: #334155; font-size: .82rem; font-weight: 700; text-decoration: none; }
.top-note:hover { border-color: var(--brand); color: var(--brand); }

.jobs-page { width: min(1200px, calc(100% - 32px)); margin: 24px auto 46px; }
.recruitment-hero { min-height: clamp(360px, 31vw, 420px); aspect-ratio: 16 / 5; padding: 44px 50px; display: flex; flex-direction: column; justify-content: flex-end; overflow: hidden; background-color: var(--navy); background-image: var(--recruitment-hero-image); background-size: cover; background-position: center 38%; color: #fff; border-radius: 8px; position: relative; isolation: isolate; }
.recruitment-hero::before { content: ""; position: absolute; inset: 0; z-index: -1; background: #071b2dcc; }
.recruitment-hero:not(.has-image)::before { background: var(--navy); }
.hero-copy { max-width: 760px; }
.eyebrow { display: block; color: #73d7cc; font-size: .74rem; font-weight: 850; margin-bottom: 15px; }
.eyebrow.dark { color: var(--brand); margin-bottom: 8px; }
.hero-copy h1 { max-width: 680px; margin: 0; font-size: 2.55rem; line-height: 1.14; letter-spacing: 0; }
.hero-copy p { max-width: 650px; margin: 14px 0 22px; color: #e6eef6; line-height: 1.65; font-size: .96rem; }
.hero-search { width: 100%; padding: 10px; display: grid; grid-template-columns: minmax(230px, 1.4fr) minmax(170px, 1fr) minmax(170px, 1fr) auto; align-items: end; gap: 9px; border: 1px solid #ffffff52; border-radius: 8px; background: #fffffffa; box-shadow: 0 16px 38px #071b2d38; color: var(--ink); }
.hero-search-field { min-width: 0; }
.hero-search-field > span { display: block; margin: 0 0 5px 3px; color: #516176; font-size: .66rem; font-weight: 800; }
.hero-search-field input, .hero-search-field select { width: 100%; min-height: 42px; padding: 0 11px; border: 1px solid #ced9e6; border-radius: 6px; background: #fff; color: var(--ink); outline: 0; }
.hero-search-field input:focus, .hero-search-field select:focus { border-color: var(--brand); box-shadow: 0 0 0 3px #2563eb18; }
.hero-search-actions { display: flex; gap: 7px; }
.hero-search-actions button { min-height: 42px; padding: 0 15px; border: 1px solid transparent; border-radius: 6px; font-size: .78rem; font-weight: 800; cursor: pointer; }
.hero-search-actions button[type="submit"] { background: var(--brand); color: #fff; }
.hero-search-actions button[type="reset"] { border-color: #ced9e6; background: #fff; color: #475569; }
.career-intro { margin-top: 16px; padding: 20px 24px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
.career-intro div { display: flex; flex-direction: column; gap: 4px; padding-left: 13px; border-left: 3px solid var(--teal); }
.career-intro strong { font-size: .82rem; }
.career-intro span { color: var(--muted); font-size: .72rem; line-height: 1.5; }
.hero-cta { display: inline-flex; min-height: 46px; align-items: center; padding: 0 19px; border-radius: 7px; background: var(--brand); color: #fff; font-size: .88rem; font-weight: 800; text-decoration: none; }
.hero-cta:hover { filter: brightness(.94); }
.hero-panel { padding-left: 34px; border-left: 1px solid #ffffff33; display: flex; flex-direction: column; position: relative; z-index: 1; }
.hero-panel-label { color: #a8bfd4; font-size: .8rem; font-weight: 700; }
.hero-panel > strong { margin: 4px 0 -2px; font-size: 4rem; line-height: 1; color: #fff; }
.hero-panel > span:not(.hero-panel-label) { color: #d8e4ef; font-size: .87rem; }
.hero-divider { height: 1px; width: 100%; margin: 24px 0 17px; background: #ffffff2b; }
.hero-panel p { margin: 0; color: #a8bfd4; font-size: .82rem; line-height: 1.7; }
.hero-banner { height: 270px; display: block; position: relative; z-index: 1; overflow: hidden; border: 1px solid #ffffff2b; border-radius: 8px; background: #15334f; text-decoration: none; }
.hero-banner img { width: 100%; height: 100%; display: block; object-fit: cover; }
.hero-banner span { position: absolute; right: 14px; bottom: 14px; left: 14px; padding: 10px 12px; display: flex; flex-direction: column; gap: 3px; border-radius: 6px; background: #0b2038e8; color: #c7d7e6; font-size: .75rem; }
.hero-banner span strong { color: #fff; font-size: .9rem; }

.jobs-section { padding: 62px 0 36px; scroll-margin-top: 76px; }
.jobs-heading { display: flex; justify-content: space-between; align-items: end; gap: 24px; margin-bottom: 24px; }
.jobs-heading h2 { margin: 0; font-size: 1.75rem; }
.jobs-count { margin: 0; color: var(--muted); font-size: .8rem; }
.jobs-count strong { color: var(--brand); font-size: 1rem; }
.job-search { width: min(390px, 100%); display: block; }
.job-search > span { display: block; margin: 0 0 7px; font-size: .74rem; font-weight: 750; color: #475569; }
.job-search input { width: 100%; min-height: 44px; border: 1px solid #cbd7e5; border-radius: 7px; padding: 0 13px; background: #fff; color: var(--ink); outline: 0; }
.job-search input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px #2563eb1c; }
.jobs-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.job-card { min-width: 0; padding: 24px; background: #fff; border: 1px solid var(--line); border-radius: 8px; box-shadow: 0 8px 22px #1d35570a; position: relative; overflow: hidden; }
.job-card.featured { border-top: 3px solid var(--teal); padding-top: 22px; }
.job-card-banner { height: 150px; display: block; margin: -24px -24px 20px; overflow: hidden; border-bottom: 1px solid var(--line); }
.job-card.featured .job-card-banner { margin-top: -22px; }
.job-card-banner img { width: 100%; height: 100%; display: block; object-fit: cover; transition: transform .25s ease; }
.job-card-banner:hover img { transform: scale(1.025); }
.job-card-top { min-height: 25px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.job-code { display: inline-flex; width: fit-content; padding: 4px 8px; border-radius: 5px; background: #edf4ff; color: #1d4ed8; font-size: .7rem; font-weight: 850; }
.job-code.light { margin: 16px 0 14px; background: #ffffff19; color: #bfe5ff; border: 1px solid #ffffff25; }
.featured-label { color: var(--teal); font-size: .72rem; font-weight: 850; }
.job-card h3 { margin: 14px 0 10px; font-size: 1.18rem; line-height: 1.35; }
.job-card h3 a { text-decoration: none; }
.job-card h3 a:hover { color: var(--brand); }
.job-meta { display: flex; flex-wrap: wrap; gap: 7px; }
.job-meta span { padding: 5px 8px; border-radius: 5px; background: #f3f6fa; color: #516176; font-size: .72rem; font-weight: 650; }
.job-card > p { min-height: 44px; margin: 16px 0; color: var(--muted); font-size: .83rem; line-height: 1.65; }
.job-card-bottom { margin-top: 21px; padding-top: 17px; border-top: 1px solid var(--line); display: flex; align-items: end; justify-content: space-between; gap: 15px; }
.job-card-bottom div { min-width: 0; display: flex; flex-direction: column; }
.job-card-bottom small { color: var(--muted); font-size: .67rem; }
.job-card-bottom strong { margin-top: 3px; font-size: .83rem; overflow-wrap: anywhere; }
.job-card-bottom a { flex: 0 0 auto; min-height: 39px; display: inline-flex; align-items: center; padding: 0 13px; border-radius: 7px; background: var(--brand); color: #fff; text-decoration: none; font-size: .76rem; font-weight: 800; }
.deadline { margin-top: 14px; color: var(--amber); font-size: .71rem; font-weight: 700; }
.jobs-empty, .no-result { grid-column: 1 / -1; padding: 54px 24px; text-align: center; background: #fff; border: 1px solid var(--line); border-radius: 8px; }
.empty-mark { width: 50px; height: 50px; display: grid; place-items: center; margin: auto; border-radius: 8px; background: var(--navy); color: #fff; font-weight: 900; }
.jobs-empty h3 { margin: 17px 0 7px; }
.jobs-empty p, .no-result { color: var(--muted); }
.recruitment-values { margin-top: 28px; padding: 28px 32px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; background: #fff; border: 1px solid var(--line); border-radius: 8px; }
.recruitment-values div { display: flex; flex-direction: column; gap: 5px; padding-left: 16px; border-left: 3px solid var(--teal); }
.recruitment-values strong { font-size: .9rem; }
.recruitment-values span { color: var(--muted); font-size: .76rem; line-height: 1.55; }

.page { width: min(1200px, calc(100% - 32px)); margin: 26px auto 44px; display: grid; grid-template-columns: 310px minmax(0, 1fr); gap: 22px; align-items: start; }
.intro { position: sticky; top: 94px; padding: 26px; border-radius: 8px; background: var(--navy); color: #fff; }
.intro h1 { margin: 0 0 12px; font-size: 1.55rem; line-height: 1.28; }
.intro p { margin: 0; color: #d7e3ee; font-size: .85rem; line-height: 1.65; }
.job-sidebar-banner { width: calc(100% + 52px); height: 150px; display: block; margin: -26px -26px 22px; border-radius: 8px 8px 0 0; object-fit: cover; }
.back-link { display: inline-flex; color: #b7cbe0; font-size: .76rem; font-weight: 750; text-decoration: none; }
.back-link::before { content: "←"; margin-right: 7px; }
.job-summary { margin: 24px 0 0; padding-top: 8px; border-top: 1px solid #ffffff24; }
.job-summary div { padding: 12px 0; display: grid; grid-template-columns: 88px 1fr; gap: 12px; border-bottom: 1px solid #ffffff17; }
.job-summary dt { color: #9fb5ca; font-size: .7rem; }
.job-summary dd { margin: 0; color: #fff; font-size: .78rem; font-weight: 720; }
.application-content { display: grid; gap: 18px; min-width: 0; }
.card { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; box-shadow: 0 12px 28px #2337500d; overflow: hidden; }
.card-head { padding: 23px 27px 19px; border-bottom: 1px solid var(--line); }
.card-head h2 { margin: 0; font-size: 1.18rem; }
.card-head p { margin: 7px 0 0; color: var(--muted); font-size: .8rem; }
.job-detail-body { padding: 24px 27px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px; }
.job-detail-body div:first-child { grid-column: 1 / -1; }
.job-detail-body h3 { margin: 0 0 8px; font-size: .9rem; }
.job-detail-body p { margin: 0; color: #4b5d72; font-size: .82rem; line-height: 1.7; }
.form { padding: 24px 27px; }
.form-alert { padding: 11px 13px; margin: 0 0 18px; border: 1px solid #fecaca; background: #fff1f2; color: #b91c1c; border-radius: 7px; font-size: .8rem; font-weight: 700; }
.selected-job { margin-bottom: 24px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 15px; background: #f3f7fc; border: 1px solid var(--line); border-radius: 7px; }
.selected-job span { color: var(--muted); font-size: .73rem; }
.selected-job strong { font-size: .83rem; text-align: right; }
.section + .section { margin-top: 26px; padding-top: 24px; border-top: 1px solid var(--line); }
.section-title { margin: 0 0 16px; font-size: .92rem; }
.grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 17px; }
.field { min-width: 0; }
.field.full { grid-column: 1 / -1; }
.field label { display: block; margin: 0 0 7px; font-size: .78rem; font-weight: 720; color: #334155; }
.required { color: var(--danger); }
.control { width: 100%; min-height: 42px; padding: 9px 11px; border: 1px solid #cbd6e3; border-radius: 7px; background: #fff; color: var(--ink); outline: 0; }
.control:focus { border-color: var(--brand); box-shadow: 0 0 0 3px #2563eb1c; }
.control:disabled { background: #f1f5f9; color: #94a3b8; }
textarea.control { min-height: 80px; resize: vertical; }
.hint { margin: 5px 0 0; font-size: .7rem; color: var(--muted); }
.error { margin: 5px 0 0; font-size: .72rem; color: #c92a2a; font-weight: 650; }
.invalid { border-color: #e03131; }
.upload { padding: 13px; background: #f8fafc; border: 1px dashed #aebed0; border-radius: 7px; }
.check { display: flex !important; align-items: flex-start; gap: 10px; font-size: .77rem !important; line-height: 1.5; color: #475569; }
.check input { width: 18px; height: 18px; margin: 1px 0 0; accent-color: var(--brand); flex: 0 0 auto; }
.actions { display: flex; justify-content: flex-end; margin-top: 24px; }
.submit { min-height: 44px; border: 0; border-radius: 7px; background: var(--brand); color: #fff; padding: 0 20px; font-weight: 780; cursor: pointer; }
.submit:hover { filter: brightness(.95); }
.submit:disabled { opacity: .65; }
.footer { text-align: center; color: #8794a6; font-size: .7rem; padding: 0 16px 22px; }

.success { width: min(620px, calc(100% - 32px)); margin: 70px auto; text-align: center; padding: 42px; }
.success-icon { width: 58px; height: 58px; margin: 0 auto 18px; display: grid; place-items: center; border-radius: 50%; background: #e7f8f1; color: #087f5b; font-size: 1.7rem; font-weight: 900; }
.success h1 { margin: 0; font-size: 1.5rem; }
.success p { color: var(--muted); line-height: 1.6; }
.code { display: inline-flex; margin: 9px 0 20px; padding: 9px 13px; border: 1px solid var(--line); background: #f8fafc; border-radius: 7px; font-weight: 800; }
.back { display: inline-flex; align-items: center; min-height: 42px; padding: 0 17px; border-radius: 7px; background: var(--brand); color: #fff; text-decoration: none; font-weight: 750; }

@media (max-width: 860px) {
    .recruitment-hero { min-height: 480px; aspect-ratio: auto; padding: 34px; }
    .hero-search { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .hero-search-actions { justify-content: flex-end; }
    .hero-copy h1 { font-size: 2rem; }
    .hero-panel { padding: 20px 0 0; border-left: 0; border-top: 1px solid #ffffff33; display: grid; grid-template-columns: auto 1fr; column-gap: 12px; align-items: end; }
    .hero-panel > strong { font-size: 2.7rem; grid-row: span 2; }
    .hero-divider, .hero-panel p { display: none; }
    .page { grid-template-columns: 1fr; margin-top: 16px; }
    .intro { position: static; }
    .job-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0 18px; }
    .jobs-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .top { height: 60px; }
    .top-inner, .jobs-page, .page { width: calc(100% - 20px); }
    .top-note { display: none; }
    .jobs-page { margin-top: 10px; }
    .recruitment-hero { min-height: auto; padding: 26px 20px; justify-content: flex-start; background-image: none; }
    .recruitment-hero.has-image { padding-top: 214px; }
    .recruitment-hero.has-image::after { content: ""; position: absolute; inset: 0 0 auto; z-index: 0; height: 190px; background-image: var(--recruitment-hero-image); background-position: center; background-size: cover; border-bottom: 1px solid #ffffff2b; }
    .recruitment-hero .hero-copy, .recruitment-hero .hero-search { position: relative; z-index: 1; }
    .hero-copy h1 { font-size: 1.7rem; }
    .hero-copy p { font-size: .88rem; }
    .hero-search { grid-template-columns: 1fr; padding: 9px; }
    .hero-search-actions { width: 100%; }
    .hero-search-actions button { flex: 1; }
    .career-intro { grid-template-columns: 1fr; gap: 14px; padding: 18px; }
    .jobs-section { padding-top: 38px; }
    .jobs-heading { align-items: stretch; flex-direction: column; }
    .jobs-heading h2 { font-size: 1.4rem; }
    .job-search { width: 100%; }
    .job-card { padding: 20px; }
    .job-card-banner { margin: -20px -20px 18px; }
    .job-card.featured .job-card-banner { margin-top: -18px; }
    .job-card-bottom { align-items: stretch; flex-direction: column; }
    .job-card-bottom a { justify-content: center; }
    .recruitment-values { grid-template-columns: 1fr; gap: 18px; padding: 22px; }
    .page { gap: 12px; margin-bottom: 22px; }
    .intro { padding: 21px; }
    .job-sidebar-banner { width: calc(100% + 42px); margin: -21px -21px 18px; }
    .job-summary { grid-template-columns: 1fr; }
    .job-detail-body, .grid { grid-template-columns: 1fr; }
    .job-detail-body div:first-child { grid-column: auto; }
    .form, .card-head, .job-detail-body { padding-left: 18px; padding-right: 18px; }
    .selected-job { align-items: flex-start; flex-direction: column; }
    .selected-job strong { text-align: left; }
    .actions { position: sticky; bottom: 0; z-index: 5; padding-top: 12px; background: #fff; }
    .submit { width: 100%; }
}
</style>
@stack('head')
</head>
<body>
<header class="top">
    <div class="top-inner">
        <a class="brand" href="{{ route('recruitment.apply') }}">
            @if($settings->logo_path)
                <img src="{{ asset('storage/'.$settings->logo_path) }}" alt="{{ $settings->app_name }}">
            @else
                <span class="brand-mark">3</span><span>{{ $settings->app_name ?: '3RDVN' }}</span>
            @endif
        </a>
        <nav class="top-links" aria-label="Điều hướng tuyển dụng">
            <a class="top-note" href="https://3rdvn.io.vn/">Về 3RDVN</a>
            <a class="top-note" href="{{ route('recruitment.apply') }}#vi-tri-dang-tuyen">Vị trí đang tuyển</a>
        </nav>
    </div>
</header>
@yield('content')
@stack('scripts')
</body>
</html>
