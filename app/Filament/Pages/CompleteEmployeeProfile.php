<?php

namespace App\Filament\Pages;

use App\Models\CandidateApplication;
use App\Support\VietnamAddressCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CompleteEmployeeProfile extends Page
{
    protected static ?string $slug = 'employee-work/bo-sung-ho-so';
    protected static ?string $title = 'Bổ sung hồ sơ';
    protected static ?string $navigationLabel = 'Bổ sung hồ sơ';
    protected static string | \UnitEnum | null $navigationGroup = 'Employee - Work';
    protected static ?int $navigationSort = 82;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected string $view = 'filament.pages.complete-employee-profile';

    public ?string $phone=null,$date_of_birth=null,$gender=null,$identity_number=null,$identity_issued_date=null,$identity_issued_place=null;
    public ?string $address_line=null,$province_code=null,$district_code=null,$ward_code=null;
    public ?string $bank_code=null,$bank_name=null,$bank_account_number=null,$bank_account_name=null,$bank_branch=null;
    public ?string $tax_code=null,$social_insurance_number=null,$emergency_contact_name=null,$emergency_contact_phone=null;
    public array $provinceOptions=[],$districtOptions=[],$wardOptions=[];

    public static function canAccess(): bool
    {
        return Schema::hasTable('candidate_applications') && CandidateApplication::query()->where('converted_user_id',auth()->id())->exists();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(),403);
        $user=auth()->user();
        foreach (['phone','gender','identity_number','identity_issued_place','address_line','province_code','district_code','ward_code','bank_code','bank_name','bank_account_number','bank_account_name','bank_branch','tax_code','social_insurance_number','emergency_contact_name','emergency_contact_phone'] as $field) $this->{$field}=$user->{$field};
        $this->date_of_birth=$user->date_of_birth?->format('Y-m-d');
        $this->identity_issued_date=$user->identity_issued_date?->format('Y-m-d');
        $this->provinceOptions=VietnamAddressCatalog::provinceOptions();
        $this->districtOptions=VietnamAddressCatalog::districtOptions($this->province_code);
        $this->wardOptions=VietnamAddressCatalog::wardOptions($this->district_code);
    }

    public function updatedProvinceCode(): void
    {
        $this->district_code=null;$this->ward_code=null;
        $this->districtOptions=VietnamAddressCatalog::districtOptions($this->province_code);$this->wardOptions=[];
    }

    public function updatedDistrictCode(): void
    {
        $this->ward_code=null;$this->wardOptions=VietnamAddressCatalog::wardOptions($this->district_code);
    }

    public function save(): void
    {
        $user=auth()->user();
        $data=$this->validate([
            'phone'=>['nullable','string','max:24',Rule::unique('users','phone')->ignore($user->getKey())],
            'date_of_birth'=>['nullable','date','before:today'],'gender'=>['nullable',Rule::in(['male','female','other'])],
            'identity_number'=>['nullable','string','max:50',Rule::unique('users','identity_number')->ignore($user->getKey())],
            'identity_issued_date'=>['nullable','date'],'identity_issued_place'=>['nullable','string','max:150'],
            'address_line'=>['nullable','string','max:500'],'province_code'=>['nullable','string','max:20'],'district_code'=>['nullable','string','max:20'],'ward_code'=>['nullable','string','max:20'],
            'bank_code'=>['nullable','string','max:50'],'bank_name'=>['nullable','string','max:150'],'bank_account_number'=>['nullable','string','max:50'],'bank_account_name'=>['nullable','string','max:150'],'bank_branch'=>['nullable','string','max:150'],
            'tax_code'=>['nullable','string','max:50'],'social_insurance_number'=>['nullable','string','max:50'],'emergency_contact_name'=>['nullable','string','max:150'],'emergency_contact_phone'=>['nullable','string','max:24'],
        ],['phone.unique'=>'Số điện thoại đã được sử dụng.','identity_number.unique'=>'Số giấy tờ đã được sử dụng.']);

        $location=$this->ward_code ? VietnamAddressCatalog::wardInfo($this->ward_code) : null;
        if ($location) $data=[...$data,'province_code'=>(string)$location['province_code'],'province_name'=>$location['province_name'],'district_code'=>(string)$location['district_code'],'district_name'=>$location['district_name'],'ward_code'=>(string)$location['ward_code'],'ward_name'=>$location['ward_name']];
        $user->update($data);
        Notification::make()->title('Đã cập nhật hồ sơ nhân sự')->success()->send();
    }
}
