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

    'admin' => [
        'token' => env('ADMIN_API_TOKEN'),
    ],

    'payment' => [
        // enot | yookassa | mock
        'provider' => env('PAYMENT_PROVIDER', 'enot'),
    ],

    'enot' => [
        // Base API url, trailing slash optional.
        'url' => env('ENOT_URL', 'https://api.enot.io'),
        // Касса: идентификатор, API-ключ (авторизация) и «Дополнительный ключ» (подпись вебхука).
        'shop_id' => env('ENOT_SHOP_ID'),
        'api_key' => env('ENOT_API_KEY'),
        'secret_key' => env('ENOT_SECRET_KEY'),
        // Код метода СБП в тарифах кассы (берётся из payment-tariffs, обычно "sbp").
        'sbp_service_code' => env('ENOT_SBP_SERVICE_CODE', 'sbp'),
        // Время жизни инвойса в минутах (Enot max — 5 дней).
        'expire' => (int) env('ENOT_INVOICE_EXPIRE', 300),
        'return_url' => env('ENOT_RETURN_URL', env('APP_URL', 'http://localhost')),
    ],

    'playwallet' => [
        'environment' => env('PLAYWALLET_ENVIRONMENT', 'dev'),
        'url_dev' => env('PLAYWALLET_URL_DEV', 'https://dev.merchant.playwallet.bot/api/merchant/'),
        'api_key_dev' => env('PLAYWALLET_API_KEY_DEV'),
        'url_prod' => env('PLAYWALLET_URL_PROD', 'https://merchant.playwallet.bot/api/merchant/'),
        'api_key_prod' => env('PLAYWALLET_API_KEY_PROD'),
        'service_id' => env('PLAYWALLET_SERVICE_ID'),
    ],

    'yookassa' => [
        'shop_id' => env('YOOKASSA_ID'),
        'secret_key' => env('YOOKASSA_KEY'),
        'return_url' => env('YOOKASSA_RETURN_URL', env('APP_URL', 'http://localhost')),
    ],
];
