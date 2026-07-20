@extends('layouts.app', ['title' => $profile->exists ? 'Sửa Hồ sơ' : 'Thêm Hồ sơ'])

@section('content')
<form class="form-card" method="POST" action="{{ $profile->exists ? route('profiles.update', $profile) : route('profiles.store') }}">
    @csrf @if($profile->exists) @method('PUT') @endif
    <div class="form-grid">
        <label class="field"><span>Tên khách hàng</span><input name="customer_name" value="{{ old('customer_name', $profile->customer_name) }}" required></label>
        <label class="field"><span>SĐT</span><input name="phone" value="{{ old('phone', $profile->phone) }}"></label>
        <label class="field"><span>Email</span><input name="email" value="{{ old('email', $profile->email) }}"></label>
        <label class="field"><span>CCCD/CMND</span><input name="identity_number" value="{{ old('identity_number', $profile->identity_number) }}"></label>
        <label class="field"><span>Sản phẩm quan tâm</span><input name="product_interest" value="{{ old('product_interest', $profile->product_interest) }}"></label>
        <label class="field full"><span>Địa chỉ</span><textarea name="address">{{ old('address', $profile->address) }}</textarea></label>
        <label class="field full"><span>Ghi chú</span><textarea name="note">{{ old('note', $profile->note) }}</textarea></label>
    </div>
    <div class="actions"><a class="secondary-btn" href="{{ route('profiles.index') }}">Hủy</a><button class="primary-btn">Lưu</button></div>
</form>
@endsection
