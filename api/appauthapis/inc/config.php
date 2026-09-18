<?php
/**
 * Multi-app mobile auth configuration.
 * Change APP_AUTH_JWT_SECRET in production (env var preferred).
 */
return [
    'jwt_secret' => getenv('APP_AUTH_JWT_SECRET') ?: 'TechXpert_AppAuth_ChangeMe_InProduction_2026',
    'jwt_algorithm' => 'HS512',
    'access_token_ttl_seconds' => 86400,      // 24 hours
    'refresh_token_ttl_seconds' => 604800,  // 7 days
    'otp_ttl_minutes' => 10,
    'otp_length' => 6,

    // First-time login: auto-grant role when user has no user_apps row for this app
    'default_role_by_app' => [
        'HOMECARE' => 'CUSTOMER',
        'PARKING' => 'CUSTOMER',
        'MYGATE' => 'RESIDENT',
    ],

    'valid_app_codes' => ['HOMECARE', 'MYGATE', 'PARKING'],

    // Set true while debugging send_otp 500 errors; disable on production
    'debug' => true,

    // WhatsApp OTP: chatmybot (new) or interakt (legacy)
    'whatsapp' => [
        'provider' => getenv('APP_AUTH_WHATSAPP_PROVIDER') ?: 'chatmybot',
        'chatmybot' => [
            'endpoint' => 'https://wa.chatmybot.in/gateway/wabuissness/v1/message/batchapi',
            'access_token' => getenv('CHATMYBOT_ACCESS_TOKEN') ?: '586c48de-e869-45af-984c-c6c319ea5a6a',
            'auth_scheme' => 'accessToken',
            'otp_template_id' => 'f3539660-8863-4829-bdf5-0de52e2b6f2c',
            'otp_include_button' => true,
            'otp_button_param' => '',
            'button_index' => '0',
        ],
    ],
];
