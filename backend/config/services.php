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

    'quipu' => [
        'api_url' => env('PG_API_URL'),
        'merchant_id' => env('PG_MERCHANT_ID'),
        'type_rid' => env('PG_TYPE_RID'),
        // Hosted-payment-page language. Keep 'ka' for Georgian; set PG_HPP_LANGUAGE=en
        // to fall back to English if the gateway's Georgian HPP template misbehaves.
        'hpp_language' => env('PG_HPP_LANGUAGE', 'ka'),
        'cert_base64' => env('PG_CERT_BASE64'),
        'key_base64' => env('PG_KEY_BASE64'),
        'ca_base64' => env('PG_CA_BASE64'),
        'tls_reject_unauthorized' => env('PG_TLS_REJECT_UNAUTHORIZED', true),
    ],

];
