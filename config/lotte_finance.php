<?php

return [
    'workflow_source' => 'checkdup_api_workflow_project.zip',
    'live_api_ready' => env('LOTTE_FINANCE_LIVE_API_READY', false),
    'endpoint' => env('LOTTE_FINANCE_API_ENDPOINT', 'https://api-engagement.lottefinance.vn/api/transaction/requestTransaction'),
    'device_id' => env('LOTTE_FINANCE_DEVICE_ID'),
    'bearer_token' => env('LOTTE_FINANCE_BEARER_TOKEN'),
    'api_version' => env('LOTTE_FINANCE_API_VERSION', '2.0.18'),
    'accept_language' => env('LOTTE_FINANCE_ACCEPT_LANGUAGE', 'vi'),
    'scheme_cache_ttl_minutes' => env('LOTTE_FINANCE_SCHEME_CACHE_TTL_MINUTES', 720),
    'note' => 'Zip chỉ có SERVICEID/request mẫu, chưa có URL endpoint thật. Khi có endpoint sẽ bật live API runner.',
    'steps' => [
        'scheme_detail_legacy' => [
            'step' => 3,
            'title' => 'Request lấy chi tiết scheme',
            'service_id' => '682ab99100d268bf4422c8be',
            'required_fields' => ['DEVICEID', 'SERVICEID', 'scheme'],
        ],
        'check_dup_ekyc' => [
            'step' => 7,
            'title' => 'Check DUP / kéo kết quả eKYC',
            'service_id' => null,
            'required_fields' => ['request_id', 'device_id', 'id_number', 'full_name', 'dob', 'pull_ekyc_result'],
        ],
        'scheme_search' => [
            'step' => 46,
            'title' => 'Request tìm kiếm scheme/catalog',
            'service_id' => env('LOTTE_FINANCE_SCHEME_SEARCH_SERVICE_ID', '64536f1e3a577e4641efa7f5'),
            'required_fields' => ['DEVICEID', 'SERVICEID', 'CRITERIAS', 'SEARCH', 'SKIP', 'LIMIT'],
        ],
        'scheme_detail' => [
            'step' => 28,
            'title' => 'Request lấy chi tiết scheme',
            'service_id' => env('LOTTE_FINANCE_SCHEME_DETAIL_SERVICE_ID', '6453c9b63a577e2634efa89b'),
            'required_fields' => ['SERVICEID', 'SCHEMEID', 'DEVICEID'],
        ],
        'push_ocr_to_los' => [
            'step' => 49,
            'title' => 'Đẩy kết quả OCR vào LOS',
            'service_id' => '65fd3e46b6332b6a097d5e31',
            'required_fields' => ['FLOW_ID', 'APPLICATION_ID', 'NRIC', 'REQUEST_ID', 'TYPE', 'RAW', 'DEVICEID', 'SERVICEID'],
        ],
        'upload_ekyc_image_front' => [
            'step' => 60,
            'title' => 'Upload ảnh CCCD/eKYC',
            'service_id' => null,
            'required_fields' => ['user_id', 'request_id', 'device_id', 'image_id', 'id_card'],
        ],
        'loan_calculation' => [
            'step' => 61,
            'title' => 'Request tính khoản vay/trả góp',
            'service_id' => '65a957a271747f6d66ad6789',
            'required_fields' => ['DEVICEID', 'SERVICEID', 'amount', 'interest', 'loanTerm', 'schemeCode', 'business'],
        ],
        'upload_ekyc_image_back' => [
            'step' => 64,
            'title' => 'Upload ảnh CCCD/eKYC',
            'service_id' => null,
            'required_fields' => ['user_id', 'request_id', 'device_id', 'image_id', 'id_card'],
        ],
    ],
];
