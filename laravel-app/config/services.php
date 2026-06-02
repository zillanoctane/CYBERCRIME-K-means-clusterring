<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi service eksternal
    |--------------------------------------------------------------------------
    */

    'ml' => [
        'url' => env('ML_SERVICE_URL', 'http://ml-service:8000'),
        'key' => env('ML_API_KEY', 'siancek-dev-key'),
        'timeout' => env('ML_REQUEST_TIMEOUT', 120),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],
];
