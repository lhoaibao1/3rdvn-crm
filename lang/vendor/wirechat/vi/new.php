<?php

return [
    'chat' => [
        'labels' => ['heading' => 'Cuộc trò chuyện mới', 'you' => 'Bạn'],
        'inputs' => [
            'search' => ['label' => 'Tìm người dùng', 'placeholder' => 'Nhập tên, UID, mã nhân viên, email hoặc SĐT'],
        ],
        'actions' => ['new_group' => ['label' => 'Tạo nhóm mới']],
        'messages' => ['empty_search_result' => 'Không tìm thấy người dùng phù hợp.'],
    ],
    'group' => [
        'labels' => ['heading' => 'Tạo nhóm mới', 'add_members' => 'Thêm thành viên'],
        'inputs' => [
            'name' => ['label' => 'Tên nhóm', 'placeholder' => 'Nhập tên nhóm'],
            'description' => ['label' => 'Mô tả', 'placeholder' => 'Không bắt buộc'],
            'search' => ['label' => 'Tìm kiếm', 'placeholder' => 'Tìm người dùng'],
            'photo' => ['label' => 'Ảnh nhóm'],
        ],
        'actions' => [
            'cancel' => ['label' => 'Hủy'],
            'next' => ['label' => 'Tiếp tục'],
            'create' => ['label' => 'Tạo nhóm'],
        ],
        'messages' => [
            'members_limit_error' => 'Số thành viên không được vượt quá :count.',
            'empty_search_result' => 'Không tìm thấy người dùng phù hợp.',
        ],
    ],
];
