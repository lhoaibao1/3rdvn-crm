<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="description" content="@yield('description', '3RDVN cung cấp giải pháp vận hành bán hàng, công nghệ và dịch vụ tài chính.')">
<meta name="theme-color" content="#080a0d">
<title>@yield('title', '3RDVN - Tech & Financial Services')</title>
<link rel="canonical" href="https://3rdvn.io.vn/">
@if($settings->favicon_path)<link rel="icon" href="{{ asset('storage/'.$settings->favicon_path) }}">@endif
<link rel="stylesheet" href="{{ asset('site/css/corporate.css') }}?v=20260718">
@stack('head')
</head>
<body>
<header class="site-header" data-header>
    <a class="site-brand" href="{{ route('website.home') }}" aria-label="3RDVN - Trang chủ">
        @if($settings->logo_path)<img src="{{ asset('storage/'.$settings->logo_path) }}" alt="3RDVN">
        @else<span class="brand-symbol">3</span><strong>3RDVN</strong>@endif
    </a>
    <nav class="desktop-nav" aria-label="Điều hướng chính">
        <a href="#giai-phap">Giải pháp</a><a href="#van-hanh">Cách vận hành</a>
        <a href="#ve-3rdvn">Về 3RDVN</a><a href="https://ungtuyen.3rdvn.io.vn/">Tuyển dụng</a>
    </nav>
    <a class="header-contact" href="#lien-he">Liên hệ</a>
    <button class="menu-toggle" type="button" aria-label="Mở menu" aria-expanded="false" data-menu-toggle><span></span><span></span></button>
    <nav class="mobile-nav" aria-label="Điều hướng di động" data-mobile-nav hidden>
        <a href="#giai-phap">Giải pháp</a><a href="#van-hanh">Cách vận hành</a>
        <a href="#ve-3rdvn">Về 3RDVN</a><a href="https://ungtuyen.3rdvn.io.vn/">Tuyển dụng</a><a href="#lien-he">Liên hệ</a>
    </nav>
</header>
@yield('content')
<footer class="site-footer" id="lien-he">
    <div class="footer-main">
        <div class="footer-intro"><span class="section-kicker">LET'S BUILD</span><h2>Biến mục tiêu tăng trưởng thành một hệ thống có thể vận hành.</h2></div>
        <div class="footer-contact">
            <a href="mailto:Hoai-Bao.Luong@3rd-vn.io.vn">Hoai-Bao.Luong@3rd-vn.io.vn</a>
            <a href="tel:+84898150192">(+84) 898 150 192</a>
            <p>39 Đường số 12, Cityland Park Hills, Phường 10, Gò Vấp, TP. Hồ Chí Minh</p>
        </div>
    </div>
    <div class="footer-bottom"><strong>3RDVN</strong><span>Third-Party Financial Services Partners</span><span>© {{ date('Y') }} 3RDVN</span></div>
</footer>
<script src="{{ asset('site/js/corporate.js') }}?v=20260718" defer></script>
@stack('scripts')
</body>
</html>
