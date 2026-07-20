@extends('recruitment.layout')
@section('title','Cơ hội nghề nghiệp')
@section('content')
@php
    $heroVacancy = $vacancies->first(fn ($vacancy) => filled($vacancy->banner_path));
    $departments = $vacancies->pluck('department')->filter()->unique()->sort()->values();
    $locations = $vacancies->pluck('work_location')->filter()->unique()->sort()->values();
@endphp
<main class="jobs-page">
    <section
        class="recruitment-hero {{ $heroVacancy?->banner_path ? 'has-image' : '' }}"
        @if($heroVacancy?->banner_path)
            style="--recruitment-hero-image: url('{{ asset('storage/'.$heroVacancy->banner_path) }}')"
        @endif
    >
        <div class="hero-copy">
            <span class="eyebrow">CƠ HỘI NGHỀ NGHIỆP TẠI 3RDVN</span>
            <h1>Kiến tạo sự nghiệp cùng 3RDVN</h1>
            <p>Khám phá môi trường làm việc hướng đến kết quả, nơi mỗi đóng góp đều tạo ra giá trị rõ ràng.</p>
        </div>

        <form class="hero-search" id="job-filter-form" role="search">
            <label class="hero-search-field hero-search-keyword">
                <span>Từ khóa</span>
                <input id="job-search" type="search" placeholder="Tên vị trí, dự án, phòng ban..." autocomplete="off">
            </label>
            <label class="hero-search-field">
                <span>Khối công việc</span>
                <select id="job-department">
                    <option value="">Tất cả phòng ban</option>
                    @foreach($departments as $department)
                        <option value="{{ mb_strtolower($department) }}">{{ $department }}</option>
                    @endforeach
                </select>
            </label>
            <label class="hero-search-field">
                <span>Địa điểm</span>
                <select id="job-location">
                    <option value="">Tất cả địa điểm</option>
                    @foreach($locations as $location)
                        <option value="{{ mb_strtolower($location) }}">{{ $location }}</option>
                    @endforeach
                </select>
            </label>
            <div class="hero-search-actions">
                <button type="submit">Tìm việc</button>
                <button type="reset">Đặt lại</button>
            </div>
        </form>
    </section>

    <section class="career-intro" aria-label="Giá trị nghề nghiệp">
        <div><strong>Cơ hội phát triển</strong><span>Lộ trình rõ ràng và cơ hội học hỏi liên tục.</span></div>
        <div><strong>Môi trường thực chiến</strong><span>Làm việc trên các dự án tài chính có tác động thật.</span></div>
        <div><strong>Ghi nhận xứng đáng</strong><span>Hiệu quả được đo lường minh bạch và công bằng.</span></div>
    </section>

    <section class="jobs-section" id="vi-tri-dang-tuyen">
        <div class="jobs-heading">
            <div>
                <span class="eyebrow dark">VỊ TRÍ ĐANG TUYỂN</span>
                <h2>Tìm vị trí phù hợp với bạn</h2>
            </div>
            <p class="jobs-count"><strong id="visible-job-count">{{ $vacancies->count() }}</strong> cơ hội đang mở</p>
        </div>

        <div class="jobs-grid" id="jobs-grid">
            @forelse($vacancies as $vacancy)
                <article
                    class="job-card {{ $vacancy->is_featured ? 'featured' : '' }}"
                    data-search="{{ mb_strtolower($vacancy->title.' '.$vacancy->department.' '.$vacancy->work_location.' '.($vacancy->salesProject?->name ?? '')) }}"
                    data-department="{{ mb_strtolower((string) $vacancy->department) }}"
                    data-location="{{ mb_strtolower((string) $vacancy->work_location) }}"
                >
                    @if($vacancy->banner_path)
                        <a class="job-card-banner" href="{{ route('recruitment.job', $vacancy) }}">
                            <img src="{{ asset('storage/'.$vacancy->banner_path) }}" alt="{{ $vacancy->title }}" loading="lazy">
                        </a>
                    @endif
                    <div class="job-card-top">
                        <span class="job-code">{{ $vacancy->code }}</span>
                        @if($vacancy->is_featured)<span class="featured-label">Vị trí nổi bật</span>@endif
                    </div>
                    <h3><a href="{{ route('recruitment.job', $vacancy) }}">{{ $vacancy->title }}</a></h3>
                    <div class="job-meta">
                        @if($vacancy->department)<span>{{ $vacancy->department }}</span>@endif
                        @if($vacancy->salesProject)<span>{{ $vacancy->salesProject->name }}</span>@endif
                        @if($vacancy->work_location)<span>{{ $vacancy->work_location }}</span>@endif
                        <span>{{ $vacancy->employmentTypeLabel() }}</span>
                    </div>
                    @if($vacancy->short_description)
                        <p>{{ str($vacancy->short_description)->limit(170) }}</p>
                    @endif
                    <div class="job-card-bottom">
                        <div>
                            <small>Mức lương</small>
                            <strong>{{ $vacancy->salaryLabel() }}</strong>
                        </div>
                        <a href="{{ route('recruitment.job', $vacancy) }}">Ứng tuyển</a>
                    </div>
                    @if($vacancy->application_deadline)
                        <div class="deadline">Nhận hồ sơ đến {{ $vacancy->application_deadline->format('d/m/Y') }}</div>
                    @endif
                </article>
            @empty
                <div class="jobs-empty">
                    <span class="empty-mark">3</span>
                    <h3>Chưa có vị trí đang mở</h3>
                    <p>Các cơ hội mới sẽ được cập nhật tại đây. Cảm ơn bạn đã quan tâm đến 3RDVN.</p>
                </div>
            @endforelse
        </div>
        <p class="no-result" id="no-result" hidden>Không tìm thấy vị trí phù hợp. Hãy thử từ khóa hoặc bộ lọc khác.</p>
    </section>

    <section class="recruitment-values">
        <div><strong>Quy trình minh bạch</strong><span>Mỗi hồ sơ có mã ứng tuyển và trạng thái xử lý riêng.</span></div>
        <div><strong>Dữ liệu bảo mật</strong><span>Thông tin chỉ được sử dụng cho mục đích tuyển dụng.</span></div>
        <div><strong>Kết nối trực tiếp</strong><span>Hồ sơ được chuyển thẳng đến bộ phận phụ trách.</span></div>
    </section>
</main>
@endsection
@push('scripts')
<script>
(() => {
    const form = document.querySelector('#job-filter-form');
    if (!form) return;

    const input = document.querySelector('#job-search');
    const department = document.querySelector('#job-department');
    const location = document.querySelector('#job-location');
    const cards = [...document.querySelectorAll('.job-card')];
    const empty = document.querySelector('#no-result');
    const count = document.querySelector('#visible-job-count');

    const normalize = value => String(value || '').trim().toLocaleLowerCase('vi');
    const filter = () => {
        const keyword = normalize(input.value);
        const selectedDepartment = normalize(department.value);
        const selectedLocation = normalize(location.value);
        let visible = 0;

        cards.forEach(card => {
            const show = (!keyword || card.dataset.search.includes(keyword))
                && (!selectedDepartment || card.dataset.department === selectedDepartment)
                && (!selectedLocation || card.dataset.location === selectedLocation);

            card.hidden = !show;
            if (show) visible++;
        });

        count.textContent = visible;
        empty.hidden = visible !== 0;
    };

    form.addEventListener('submit', event => {
        event.preventDefault();
        filter();
        document.querySelector('#vi-tri-dang-tuyen')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    form.addEventListener('reset', () => requestAnimationFrame(filter));
    input.addEventListener('input', filter);
    department.addEventListener('change', filter);
    location.addEventListener('change', filter);
})();
</script>
@endpush
