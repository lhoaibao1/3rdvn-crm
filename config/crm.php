<?php

return [
    'users' => [
        'default_password' => env('CRM_USER_DEFAULT_PASSWORD', '123456Aa@'),
    ],

    'team_publication' => [
        'enabled' => env('CRM_TEAM_PUBLISH_ENABLED', false),
        'connection' => env('CRM_TEAM_PUBLISH_CONNECTION', 'production_team_publish'),
    ],
];
