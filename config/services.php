<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stalwart' => [
        'jmap_url' => env('STALWART_JMAP_URL', 'http://127.0.0.1:18080/jmap/'),
        'api_key' => env('STALWART_API_KEY'),
        'admin_email' => env('STALWART_ADMIN_EMAIL'),
        'admin_password' => env('STALWART_ADMIN_PASSWORD'),
        'smtp_host' => env('STALWART_SMTP_HOST', 'mail.3rdvn.io.vn'),
        'smtp_port' => env('STALWART_SMTP_PORT', 587),
        'account_id' => env('STALWART_ACCOUNT_ID', 'b'),
        'domain_id' => env('STALWART_DOMAIN_ID', 'b'),
        'domain' => env('MAILBOX_DOMAIN', '3rdvn.io.vn'),
        'webmail_url' => env('WEBMAIL_URL', 'https://mail.3rdvn.io.vn'),
        'sso_secret' => env('MAIL_SSO_SECRET'),
        'sso_issuer' => env('MAIL_SSO_ISSUER', '3rdvn-crm'),
    ],

    'vpn_directory' => [
        'token' => env('VPN_DIRECTORY_API_TOKEN'),
    ],

    'feol_bridge' => [
        'token' => env('FEOL_BRIDGE_API_TOKEN'),
        'poll_seconds' => (int) env('FEOL_BRIDGE_POLL_SECONDS', 5),
        'landing_origin' => env('FEOL_LANDING_ORIGIN', 'https://os.saigonbpo.vn'),
        'landing_campaign' => env('FEOL_LANDING_CAMPAIGN', 'fe-cashloan-deeplink'),
        'partner_campaign_code' => env('FEOL_PARTNER_CAMPAIGN_CODE', 'CTV_FEC_DL'),
        'landing_sale_code' => env('FEOL_LANDING_SALE_CODE'),
        'landing_encrypt_key' => env('FEOL_LANDING_ENCRYPT_KEY'),
        'partner_landing_url' => env('FEOL_PARTNER_LANDING_URL'),
        'partner_submit_url' => env('FEOL_PARTNER_SUBMIT_URL', 'https://backend-ws.saigonbpo.vn/os_ws_lio_and_fe/landingPageFE/createFEOL'),
        'partner_timeout_seconds' => (int) env('FEOL_PARTNER_TIMEOUT_SECONDS', 20),
        'loan_amount_min' => (int) env('FEOL_LOAN_AMOUNT_MIN', 10000000),
        'loan_amount_max' => (int) env('FEOL_LOAN_AMOUNT_MAX', 100000000),
    ],

    'web_push' => [
        'subject' => env('WEB_PUSH_SUBJECT', env('APP_URL')),
        'ttl' => (int) env('WEB_PUSH_TTL', 300),
    ],

];
