<?php

namespace Database\Seeders;

use App\Models\CrmLookup;
use Illuminate\Database\Seeder;

class CrmLookupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'document_type' => [
                'citizen_id' => 'Căn cước',
                'cccd' => 'Căn cước công dân',
                'passport' => 'Hộ chiếu',
            ],
            'issued_place' => [
                'ccs' => 'CCS',
                'bo_cong_an' => 'Bộ Công An',
            ],
            'department' => [
                'CVTVTD' => 'CVTVTD',
                'CVTV' => 'CVTV',
                'TTLK' => 'TTLK',
            ],
            'employment_status' => [
                'active' => 'Hoạt động',
                'deactive' => 'Không hoạt động',
                'deleted' => 'Đã xoá',
            ],
            'office' => [
                '3RDVN - HCMC' => '3RDVN - HCMC',
                '3RDVN - Online' => '3RDVN - Online',
            ],
            'contract_type' => [
                'collaborator' => 'Cộng tác viên',
                'probation' => 'Nhân viên thử việc',
                'official' => 'Nhân viên chính thức',
                'partner' => 'Partner',
            ],
            'sales_code' => [
                'F1' => 'F1',
                'RDVN' => 'RDVN',
            ],
        ];

        foreach ($groups as $type => $items) {
            $sort = 10;

            foreach ($items as $key => $label) {
                CrmLookup::query()->updateOrCreate(
                    ['type' => $type, 'key' => $key],
                    ['label' => $label, 'sort_order' => $sort, 'is_active' => true],
                );

                $sort += 10;
            }
        }
    }
}
