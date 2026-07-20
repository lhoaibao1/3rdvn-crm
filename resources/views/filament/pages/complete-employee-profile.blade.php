<x-filament-panels::page>
<form wire:submit="save" class="employee-form">
<div class="employee-head"><div><h2>Bổ sung hồ sơ nhân sự</h2><p>UID và Employee Code do hệ thống quản lý. Bạn chỉ cập nhật thông tin cá nhân của mình.</p></div><button type="submit" wire:loading.attr="disabled">Lưu hồ sơ</button></div>
<section><h3>Thông tin cá nhân</h3><div class="form-grid">
<label><span>Số điện thoại</span><input wire:model="phone" type="tel">@error('phone')<small>{{ $message }}</small>@enderror</label>
<label><span>Ngày sinh</span><input wire:model="date_of_birth" type="date">@error('date_of_birth')<small>{{ $message }}</small>@enderror</label>
<label><span>Giới tính</span><select wire:model="gender"><option value="">Chọn giới tính</option><option value="male">Nam</option><option value="female">Nữ</option><option value="other">Khác</option></select></label>
<label><span>CCCD/CMND/Hộ chiếu</span><input wire:model="identity_number">@error('identity_number')<small>{{ $message }}</small>@enderror</label>
<label><span>Ngày cấp</span><input wire:model="identity_issued_date" type="date"></label><label><span>Nơi cấp</span><input wire:model="identity_issued_place"></label>
</div></section>
<section><h3>Địa chỉ hiện tại</h3><div class="form-grid">
<label><span>Tỉnh/Thành phố</span><select wire:model.live="province_code"><option value="">Chọn tỉnh/thành phố</option>@foreach($provinceOptions as $code=>$name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select></label>
<label><span>Quận/Huyện</span><select wire:model.live="district_code" @disabled(!$province_code)><option value="">Chọn quận/huyện</option>@foreach($districtOptions as $code=>$name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select></label>
<label><span>Phường/Xã</span><select wire:model="ward_code" @disabled(!$district_code)><option value="">Chọn phường/xã</option>@foreach($wardOptions as $code=>$name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select></label>
<label><span>Địa chỉ chi tiết</span><input wire:model="address_line"></label>
</div></section>
<section><h3>Ngân hàng, thuế và liên hệ</h3><div class="form-grid">
<label><span>Tên ngân hàng</span><input wire:model="bank_name"></label><label><span>Mã ngân hàng</span><input wire:model="bank_code"></label>
<label><span>Số tài khoản</span><input wire:model="bank_account_number"></label><label><span>Chủ tài khoản</span><input wire:model="bank_account_name"></label>
<label><span>Chi nhánh ngân hàng</span><input wire:model="bank_branch"></label><label><span>Mã số thuế</span><input wire:model="tax_code"></label>
<label><span>Mã BHXH</span><input wire:model="social_insurance_number"></label><label><span>Người liên hệ khẩn cấp</span><input wire:model="emergency_contact_name"></label>
<label><span>SĐT liên hệ khẩn cấp</span><input wire:model="emergency_contact_phone" type="tel"></label>
</div></section>
<div class="employee-actions"><button type="submit" wire:loading.attr="disabled">Lưu hồ sơ</button></div>
</form>
<style>
.employee-form{display:grid;gap:16px}.employee-head,.employee-form section{background:#fff;border:1px solid #e2e8f0;border-radius:8px}.employee-head{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:20px 22px}.employee-head h2{margin:0;font-size:1.2rem;color:#0f172a}.employee-head p{margin:5px 0 0;color:#64748b;font-size:.82rem}.employee-form section{padding:20px 22px}.employee-form h3{margin:0 0 16px;font-size:.94rem}.form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.employee-form label span{display:block;margin-bottom:6px;color:#334155;font-size:.79rem;font-weight:700}.employee-form input,.employee-form select{width:100%;height:42px;padding:0 11px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#0f172a;outline:0}.employee-form input:focus,.employee-form select:focus{border-color:#2563eb;box-shadow:0 0 0 3px #2563eb1a}.employee-form small{display:block;margin-top:5px;color:#dc2626}.employee-form button{height:42px;padding:0 17px;border:0;border-radius:7px;background:#2563eb;color:#fff;font-weight:750;cursor:pointer}.employee-actions{display:flex;justify-content:flex-end}@media(max-width:900px){.form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.employee-head{align-items:flex-start;flex-direction:column}.employee-head button{display:none}.form-grid{grid-template-columns:1fr}.employee-form section,.employee-head{padding:17px}.employee-actions button{width:100%}}
</style>
</x-filament-panels::page>
