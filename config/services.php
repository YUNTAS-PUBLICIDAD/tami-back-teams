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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'imgur' => [
        'access_token' => env('IMGUR_ACCESS_TOKEN'),
    ],
    //'deploy' => [
    //'webhook_url' => env('DEPLOY_WEBHOOK_URL'),

//],
'github' => [
    //'token' => env('GITHUB_TOKEN'), no se usa 
    'repo' => env('GITHUB_REPO'),
    'app_id' => env('GITHUB_APP_ID'),
    'app_private_key_path' => env('GITHUB_APP_PRIVATE_KEY_PATH'),
    'installation_id' => env('GITHUB_APP_INSTALLATION_ID'),
],


];
