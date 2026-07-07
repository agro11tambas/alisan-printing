<?php

return [
    'provider' => env('WA_PROVIDER', 'fonnte'),

    'fonnte' => [
        'url' => env('WA_GATEWAY_URL', 'https://api.fonnte.com/send'),
        'api_key' => env('WA_API_KEY', ''),
    ],
];
