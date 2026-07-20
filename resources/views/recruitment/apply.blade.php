@extends('recruitment.layout')
@section('title',$vacancy->title)
@section('content')
<main class="page application-page">
    <aside class="intro job-sidebar">
        @if($vacancy->banner_path)
            <img class="job-sidebar-banner" src="{{ asset('storage/'.$vacancy->banner_path) }}" alt="{{ $vacancy->title }}">
        @endif
        <a class="back-link" href="{{ route('recruitment.apply') }}">Quay lại danh sách</a>
        <span class="job-code light">{{ $vacancy->code }}</span>
        <h1>{{ $vacancy->title }}</h1>
        @if($vacancy->short_description)<p>{{ $vacancy->short_description }}</p>@endif
        <dl class="job-summary">
            @if($vacancy->department)<div><dt>Phòng ban</dt><dd>{{ $vacancy->department }}</dd></div>@endif
            @if($vacancy->salesProject)<div><dt>Dự án</dt><dd>{{ $vacancy->salesProject->name }}</dd></div>@endif
            @if($vacancy->work_location)<div><dt>Địa điểm</dt><dd>{{ $vacancy->work_location }}</dd></div>@endif
            <div><dt>Hình thức</dt><dd>{{ $vacancy->employmentTypeLabel() }}</dd></div>
            <div><dt>Mức lương</dt><dd>{{ $vacancy->salaryLabel() }}</dd></div>
            <div><dt>Số lượng</dt><dd>{{ $vacancy->quantity }} vị trí</dd></div>
            @if($vacancy->application_deadline)<div><dt>Hạn nhận CV</dt><dd>{{ $vacancy->application_deadline->format('d/m/Y') }}</dd></div>@endif
        </dl>
    </aside>

    <div class="application-content">
        @if($vacancy->description || $vacancy->requirements || $vacancy->benefits)
            <section class="card job-detail">
                <div class="card-head"><h2>Thông tin vị trí</h2></div>
                <div class="job-detail-body">
                    @if($vacancy->description)<div><h3>Mô tả công việc</h3><p>{!! nl2br(e($vacancy->description)) !!}</p></div>@endif
                    @if($vacancy->requirements)<div><h3>Yêu cầu</h3><p>{!! nl2br(e($vacancy->requirements)) !!}</p></div>@endif
                    @if($vacancy->benefits)<div><h3>Quyền lợi</h3><p>{!! nl2br(e($vacancy->benefits)) !!}</p></div>@endif
                </div>
            </section>
        @endif

        <section class="card" id="form-ung-tuyen">
            <div class="card-head">
                <span class="eyebrow dark">ỨNG TUYỂN TRỰC TUYẾN</span>
                <h2>Thông tin ứng viên</h2>
                <p>Các trường có dấu <span class="required">*</span> là bắt buộc.</p>
            </div>
            <form class="form" method="post" action="{{ route('recruitment.store') }}" enctype="multipart/form-data" id="application-form">
                @csrf
                <input type="hidden" name="job_vacancy_id" value="{{ $vacancy->id }}">
                <input name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-10000px" aria-hidden="true">
                @error('job_vacancy_id')<p class="form-alert">{{ $message }}</p>@enderror

                <div class="selected-job">
                    <span>Vị trí ứng tuyển</span>
                    <strong>{{ $vacancy->title }}{{ $vacancy->salesProject ? ' · '.$vacancy->salesProject->name : '' }}</strong>
                </div>

                <div class="section">
                    <h3 class="section-title">Thông tin cá nhân</h3>
                    <div class="grid">
                        <div class="field"><label>Họ và tên <span class="required">*</span></label><input class="control @error('full_name') invalid @enderror" name="full_name" value="{{ old('full_name') }}" autocomplete="name" required>@error('full_name')<p class="error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label>Email <span class="required">*</span></label><input class="control @error('email') invalid @enderror" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<p class="error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label>Số điện thoại <span class="required">*</span></label><input class="control @error('phone') invalid @enderror" type="tel" name="phone" value="{{ old('phone') }}" inputmode="tel" required>@error('phone')<p class="error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label>Ngày sinh</label><input class="control" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
                        <div class="field"><label>Giới tính</label><select class="control" name="gender"><option value="">Chọn giới tính</option><option value="male" @selected(old('gender')==='male')>Nam</option><option value="female" @selected(old('gender')==='female')>Nữ</option><option value="other" @selected(old('gender')==='other')>Khác</option></select></div>
                    </div>
                </div>

                <div class="section">
                    <h3 class="section-title">Kinh nghiệm làm việc</h3>
                    <div class="grid">
                        <div class="field"><label>Vị trí hiện tại/gần nhất</label><input class="control" name="current_position" value="{{ old('current_position') }}"></div>
                        <div class="field"><label>Công ty gần nhất</label><input class="control" name="latest_company" value="{{ old('latest_company') }}"></div>
                        <div class="field"><label>Số năm kinh nghiệm</label><input class="control" type="number" min="0" max="60" name="experience_years" value="{{ old('experience_years') }}"></div>
                        <div class="field"><label>Trình độ học vấn</label><input class="control" name="education_level" value="{{ old('education_level') }}"></div>
                        <div class="field"><label>Mức lương mong muốn</label><input class="control" type="number" min="0" name="expected_salary" value="{{ old('expected_salary') }}" inputmode="numeric"><p class="hint">VNĐ/tháng</p></div>
                        <div class="field"><label>Có thể bắt đầu từ ngày</label><input class="control" type="date" name="available_from" value="{{ old('available_from') }}"></div>
                    </div>
                </div>

                <div class="section">
                    <h3 class="section-title">Địa chỉ hiện tại</h3>
                    <div class="grid">
                        <div class="field"><label>Tỉnh/Thành phố <span class="required">*</span></label><select class="control @error('province_code') invalid @enderror" name="province_code" id="province" required><option value="">Chọn tỉnh/thành phố</option>@foreach($provinces as $code=>$name)<option value="{{ $code }}" @selected((string)old('province_code')===(string)$code)>{{ $name }}</option>@endforeach</select>@error('province_code')<p class="error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label>Quận/Huyện <span class="required">*</span></label><select class="control @error('district_code') invalid @enderror" name="district_code" id="district" data-old="{{ old('district_code') }}" required disabled><option value="">Chọn quận/huyện</option></select>@error('district_code')<p class="error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label>Phường/Xã <span class="required">*</span></label><select class="control @error('ward_code') invalid @enderror" name="ward_code" id="ward" data-old="{{ old('ward_code') }}" required disabled><option value="">Chọn phường/xã</option></select>@error('ward_code')<p class="error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label>Địa chỉ chi tiết</label><input class="control" name="address_line" value="{{ old('address_line') }}" autocomplete="street-address"></div>
                    </div>
                </div>

                <div class="section">
                    <h3 class="section-title">Hồ sơ đính kèm</h3>
                    <div class="grid">
                        <div class="field full"><label>CV <span class="required">*</span></label><div class="upload"><input type="file" name="cv" accept=".pdf,.doc,.docx" required><p class="hint">PDF, DOC hoặc DOCX; tối đa 10 MB.</p></div>@error('cv')<p class="error">{{ $message }}</p>@enderror</div>
                        <div class="field full"><label>Giới thiệu ngắn</label><textarea class="control" name="cover_letter" maxlength="3000" placeholder="Kinh nghiệm nổi bật hoặc lý do bạn phù hợp với vị trí...">{{ old('cover_letter') }}</textarea></div>
                        <div class="field full"><label class="check"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>Tôi xác nhận thông tin cung cấp là chính xác và đồng ý để 3RDVN xử lý dữ liệu cho mục đích tuyển dụng.</span></label>@error('consent')<p class="error">{{ $message }}</p>@enderror</div>
                    </div>
                </div>
                <div class="actions"><button class="submit" type="submit">Gửi hồ sơ ứng tuyển</button></div>
            </form>
            <div class="footer">© {{ date('Y') }} 3RDVN. Thông tin ứng viên được bảo mật.</div>
        </section>
    </div>
</main>
@endsection
@push('scripts')
<script>
(() => {
    const p = document.querySelector('#province');
    const d = document.querySelector('#district');
    const w = document.querySelector('#ward');
    const f = document.querySelector('#application-form');
    const fill = (element, data, label, selected = '') => {
        element.innerHTML = '<option value="">' + label + '</option>';
        Object.entries(data).forEach(([value, text]) => element.add(new Option(text, value, false, String(value) === String(selected))));
        element.disabled = false;
    };
    async function loadDistricts(selected = '') {
        w.innerHTML = '<option value="">Chọn phường/xã</option>';
        w.disabled = true;
        if (!p.value) { d.disabled = true; return; }
        d.disabled = true;
        const response = await fetch('/dia-chi/quan-huyen/' + encodeURIComponent(p.value));
        fill(d, await response.json(), 'Chọn quận/huyện', selected);
    }
    async function loadWards(selected = '') {
        if (!d.value) { w.disabled = true; return; }
        w.disabled = true;
        const response = await fetch('/dia-chi/phuong-xa/' + encodeURIComponent(d.value));
        fill(w, await response.json(), 'Chọn phường/xã', selected);
    }
    p.addEventListener('change', () => loadDistricts());
    d.addEventListener('change', () => loadWards());
    if (p.value) loadDistricts(d.dataset.old).then(() => { if (d.value) loadWards(w.dataset.old); });
    f.addEventListener('submit', () => {
        const button = f.querySelector('.submit');
        button.disabled = true;
        button.textContent = 'Đang gửi hồ sơ...';
    });
})();
</script>
@endpush
