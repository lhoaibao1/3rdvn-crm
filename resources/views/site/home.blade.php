@extends('site.layout')
@section('title', '3RDVN - Tech & Financial Services')
@section('description', '3RDVN xây dựng và vận hành lực lượng bán hàng, quy trình CRM và giải pháp công nghệ cho doanh nghiệp tài chính.')
@section('content')
<main>
<section class="hero" style="--hero-image:url('{{ asset('site/images/team-operations.jpg') }}')">
    <div class="hero-shade" aria-hidden="true"></div>
    <div class="hero-topline"><span>TECH & FINANCIAL SERVICES</span><span>HO CHI MINH CITY · VIETNAM</span></div>
    <div class="hero-content">
        <h1>3RDVN</h1>
        <p class="hero-statement">Xây lực lượng bán hàng.<br>Chuẩn hoá vận hành.<br><span data-rotating-text data-words="Tăng tốc tăng trưởng.|Kết nối đúng khách hàng.|Biến dữ liệu thành hành động.">Tăng tốc tăng trưởng.</span></p>
        <div class="hero-actions"><a class="button button-primary" href="#giai-phap">Khám phá giải pháp</a><a class="text-link" href="https://ungtuyen.3rdvn.io.vn/">Gia nhập đội ngũ <span>↗</span></a></div>
    </div>
    <a class="hero-scroll" href="#giai-phap" aria-label="Xem phần tiếp theo"><span>Cuộn để khám phá</span><i>↓</i></a>
</section>
<section class="manifesto section-shell" id="giai-phap">
    <div class="manifesto-heading reveal"><span class="section-kicker">WHAT WE DO</span><h2>Không chỉ cung cấp nhân sự. Chúng tôi thiết kế một hệ thống bán hàng có thể mở rộng.</h2></div>
    <p class="manifesto-copy reveal">Từ tuyển dụng, đào tạo, phân phối dữ liệu đến CRM và quản trị hiệu suất, 3RDVN kết nối con người, quy trình và công nghệ trong cùng một nhịp vận hành.</p>
</section>
<section class="services section-shell">
    <article class="service-row reveal"><span class="service-index">01</span><div><h3>Direct Sales</h3><p>Xây dựng đội ngũ bán hàng trực tiếp theo dự án, địa bàn và mục tiêu tăng trưởng.</p></div><span class="service-tag">FIELD OPERATIONS</span></article>
    <article class="service-row reveal"><span class="service-index">02</span><div><h3>Tele Sales</h3><p>Tổ chức kịch bản, phân phối dữ liệu và quản trị hiệu suất theo từng tuyến bán hàng.</p></div><span class="service-tag">SALES ENGINE</span></article>
    <article class="service-row reveal"><span class="service-index">03</span><div><h3>CRM & Workflow</h3><p>Chuẩn hoá hành trình Lead, Application, phê duyệt và lịch sử xử lý trên một nền tảng.</p></div><span class="service-tag">TECHNOLOGY</span></article>
    <article class="service-row reveal"><span class="service-index">04</span><div><h3>Data & API</h3><p>Kết nối dữ liệu, đối soát và API đối tác để giảm thao tác thủ công và tăng tính kiểm soát.</p></div><span class="service-tag">INTEGRATION</span></article>
</section>
<section class="operating-section" id="van-hanh"><div class="section-shell">
    <div class="operating-head reveal"><span class="section-kicker light">HOW IT WORKS</span><h2>Built to scale.</h2><p>Mỗi dự án được vận hành bằng một cấu trúc rõ ràng, đo lường được và có thể cải tiến liên tục.</p></div>
    <div class="operating-grid">
        <article class="operating-card reveal"><span>01</span><h3>Thiết kế</h3><p>Xác định mục tiêu, quy trình, vai trò và tiêu chuẩn dữ liệu.</p></article>
        <article class="operating-card reveal"><span>02</span><h3>Triển khai</h3><p>Tuyển dụng, đào tạo và đưa đội ngũ vào đúng luồng công việc.</p></article>
        <article class="operating-card reveal"><span>03</span><h3>Vận hành</h3><p>Theo dõi hiệu suất, chất lượng hồ sơ và tốc độ xử lý theo thời gian thực.</p></article>
        <article class="operating-card reveal"><span>04</span><h3>Tối ưu</h3><p>Dùng dữ liệu thực tế để điều chỉnh kịch bản, phân bổ và năng suất.</p></article>
    </div>
</div></section>
<section class="about section-shell" id="ve-3rdvn">
    <div class="about-title reveal"><span class="section-kicker">ABOUT 3RDVN</span><h2>Đứng giữa công nghệ và lực lượng bán hàng.</h2></div>
    <div class="about-body reveal"><p>3RDVN là đối tác dịch vụ tài chính bên thứ ba, tập trung vào tổ chức lực lượng bán hàng, vận hành dữ liệu và xây dựng công cụ giúp các đội ngũ làm việc nhất quán hơn.</p><p>Chúng tôi ưu tiên sự minh bạch, khả năng kiểm soát và trải nghiệm thực tế của nhân viên tuyến đầu.</p></div>
</section>
<section class="careers section-shell">
    <div class="careers-head reveal"><div><span class="section-kicker">CAREERS</span><h2>Cùng 3RDVN xây những đội ngũ mạnh hơn.</h2></div><a class="text-link dark" href="https://ungtuyen.3rdvn.io.vn/">Xem tất cả vị trí <span>↗</span></a></div>
    @if($vacancies->isNotEmpty())<div class="career-list">
        @foreach($vacancies as $vacancy)<a class="career-item reveal" href="https://ungtuyen.3rdvn.io.vn/vi-tri/{{ $vacancy->slug }}"><div><span>{{ $vacancy->code }}</span><h3>{{ $vacancy->title }}</h3></div><p>{{ $vacancy->work_location ?: 'Theo khu vực dự án' }}</p><strong>{{ $vacancy->employmentTypeLabel() }} ↗</strong></a>@endforeach
    </div>@else<div class="career-empty reveal"><p>Danh sách vị trí mới sẽ được cập nhật tại cổng tuyển dụng.</p><a class="button button-dark" href="https://ungtuyen.3rdvn.io.vn/">Mở cổng tuyển dụng</a></div>@endif
</section>
</main>
@endsection
