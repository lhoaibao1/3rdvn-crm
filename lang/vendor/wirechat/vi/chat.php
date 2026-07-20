<?php

return [
    'labels' => [
        'you_replied_to_yourself' => 'Bạn đã trả lời chính mình',
        'participant_replied_to_you' => ':sender đã trả lời bạn',
        'participant_replied_to_themself' => ':sender đã trả lời chính mình',
        'participant_replied_other_participant' => ':sender đã trả lời :receiver',
        'you' => 'Bạn',
        'user' => 'Người dùng',
        'replying_to' => 'Đang trả lời :participant',
        'replying_to_yourself' => 'Đang trả lời chính mình',
        'attachment' => 'Tệp đính kèm',
    ],
    'inputs' => [
        'message' => ['label' => 'Tin nhắn', 'placeholder' => 'Nhập tin nhắn'],
        'media' => ['label' => 'Ảnh và video', 'placeholder' => 'Ảnh và video'],
        'files' => ['label' => 'Tệp', 'placeholder' => 'Tệp'],
    ],
    'message_groups' => [
        'today' => 'Hôm nay',
        'yesterday' => 'Hôm qua',
    ],
    'actions' => [
        'open_group_info' => ['label' => 'Thông tin nhóm'],
        'open_chat_info' => ['label' => 'Thông tin cuộc trò chuyện'],
        'close_chat' => ['label' => 'Đóng cuộc trò chuyện'],
        'clear_chat' => [
            'label' => 'Xóa lịch sử trò chuyện',
            'confirmation_message' => 'Bạn có chắc muốn xóa lịch sử phía mình? Tin nhắn của thành viên khác không bị ảnh hưởng.',
        ],
        'delete_chat' => [
            'label' => 'Xóa cuộc trò chuyện',
            'confirmation_message' => 'Bạn có chắc muốn xóa cuộc trò chuyện phía mình? Cuộc trò chuyện của thành viên khác không bị xóa.',
        ],
        'delete_for_everyone' => ['label' => 'Xóa với mọi người', 'confirmation_message' => 'Bạn có chắc chắn?'],
        'delete_for_me' => ['label' => 'Xóa phía tôi', 'confirmation_message' => 'Bạn có chắc chắn?'],
        'reply' => ['label' => 'Trả lời'],
        'exit_group' => ['label' => 'Rời nhóm', 'confirmation_message' => 'Bạn có chắc muốn rời nhóm?'],
        'upload_file' => ['label' => 'Tệp'],
        'upload_media' => ['label' => 'Ảnh và video'],
    ],
    'messages' => [
        'cannot_exit_self_or_private_conversation' => 'Không thể rời cuộc trò chuyện cá nhân.',
        'owner_cannot_exit_conversation' => 'Chủ nhóm không thể rời nhóm.',
        'rate_limit' => 'Bạn thao tác quá nhanh, vui lòng thử lại.',
        'conversation_not_found' => 'Không tìm thấy cuộc trò chuyện.',
        'conversation_id_required' => 'Thiếu mã cuộc trò chuyện.',
        'invalid_conversation_input' => 'Dữ liệu cuộc trò chuyện không hợp lệ.',
    ],
    'info' => [
        'heading' => ['label' => 'Thông tin cuộc trò chuyện'],
        'actions' => [
            'delete_chat' => [
                'label' => 'Xóa cuộc trò chuyện',
                'confirmation_message' => 'Bạn có chắc muốn xóa cuộc trò chuyện phía mình? Cuộc trò chuyện của thành viên khác không bị xóa.',
            ],
        ],
        'messages' => [
            'invalid_conversation_type_error' => 'Chỉ hỗ trợ cuộc trò chuyện cá nhân.',
        ],
    ],
    'group' => [
        'info' => [
            'heading' => ['label' => 'Thông tin nhóm'],
            'labels' => [
                'members' => 'Thành viên',
                'add_description' => 'Thêm mô tả nhóm',
            ],
            'inputs' => [
                'name' => ['label' => 'Tên nhóm', 'placeholder' => 'Nhập tên nhóm'],
                'description' => ['label' => 'Mô tả', 'placeholder' => 'Không bắt buộc'],
                'photo' => ['label' => 'Ảnh nhóm'],
            ],
            'actions' => [
                'delete_group' => [
                    'label' => 'Xóa nhóm',
                    'confirmation_message' => 'Bạn có chắc muốn xóa nhóm?',
                    'helper_text' => 'Cần xóa các thành viên trước khi xóa nhóm.',
                ],
                'add_members' => ['label' => 'Thêm thành viên'],
                'group_permissions' => ['label' => 'Quyền trong nhóm'],
                'exit_group' => ['label' => 'Rời nhóm', 'confirmation_message' => 'Bạn có chắc muốn rời nhóm?'],
            ],
            'messages' => ['invalid_conversation_type_error' => 'Chỉ hỗ trợ cuộc trò chuyện nhóm.'],
        ],
        'members' => [
            'heading' => ['label' => 'Thành viên'],
            'inputs' => [
                'search' => ['label' => 'Tìm kiếm', 'placeholder' => 'Tìm thành viên'],
            ],
            'labels' => [
                'members' => 'Thành viên',
                'owner' => 'Chủ nhóm',
                'admin' => 'Quản trị viên',
                'no_members_found' => 'Không tìm thấy thành viên',
            ],
            'actions' => [
                'send_message_to_yourself' => ['label' => 'Nhắn tin cho chính mình'],
                'send_message_to_member' => ['label' => 'Nhắn tin cho :member'],
                'dismiss_admin' => [
                    'label' => 'Gỡ quyền quản trị',
                    'confirmation_message' => 'Bạn có chắc muốn gỡ quyền quản trị của :member?',
                ],
                'make_admin' => [
                    'label' => 'Đặt làm quản trị viên',
                    'confirmation_message' => 'Bạn có chắc muốn đặt :member làm quản trị viên?',
                ],
                'remove_from_group' => [
                    'label' => 'Xóa khỏi nhóm',
                    'confirmation_message' => 'Bạn có chắc muốn xóa :member khỏi nhóm?',
                ],
                'load_more' => ['label' => 'Xem thêm'],
            ],
            'messages' => ['invalid_conversation_type_error' => 'Chỉ hỗ trợ cuộc trò chuyện nhóm.'],
        ],
        'add_members' => [
            'heading' => ['label' => 'Thêm thành viên'],
            'inputs' => [
                'search' => ['label' => 'Tìm kiếm', 'placeholder' => 'Tìm người dùng'],
            ],
            'labels' => [],
            'actions' => ['save' => ['label' => 'Lưu']],
            'messages' => [
                'invalid_conversation_type_error' => 'Chỉ hỗ trợ cuộc trò chuyện nhóm.',
                'members_limit_error' => 'Số thành viên không được vượt quá :count.',
                'member_already_exists' => 'Đã có trong nhóm.',
            ],
        ],
        'permissions' => [
            'heading' => ['label' => 'Quyền trong nhóm'],
            'inputs' => [
                'search' => ['label' => 'Tìm kiếm', 'placeholder' => 'Tìm kiếm'],
            ],
            'labels' => ['members_can' => 'Thành viên có thể'],
            'actions' => [
                'edit_group_information' => [
                    'label' => 'Sửa thông tin nhóm',
                    'helper_text' => 'Bao gồm tên, ảnh và mô tả nhóm.',
                ],
                'send_messages' => ['label' => 'Gửi tin nhắn'],
                'add_other_members' => ['label' => 'Thêm thành viên khác'],
            ],
            'messages' => [],
        ],
    ],
];
