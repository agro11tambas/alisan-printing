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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
     * Upload gambar invoice dikirim ke server luar di tengah request user,
     * sehingga user ikut menunggu jawabannya. Timeout dibuat pendek dan bisa
     * diatur: kalau server gambar tersendat, lebih baik gagal cepat daripada
     * menahan halaman belasan detik.
     */
    'image_upload' => [
        'token' => env('IMAGE_UPLOAD_TOKEN'),
        'url' => env('IMAGE_UPLOAD_URL', 'https://image.alisanprinting.com/upload12552.php'),
        'timeout' => (int) env('IMAGE_UPLOAD_TIMEOUT', 15),
        'connect_timeout' => (int) env('IMAGE_UPLOAD_CONNECT_TIMEOUT', 5),
    ],

];
