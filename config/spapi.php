<?php

return [
    'installation_type' => 'single',

    'single' => [
        'lwa' => [
            'client_id' => env('SPAPI_LWA_CLIENT_ID'),
            'client_secret' => env('SPAPI_LWA_CLIENT_SECRET'),
            'refresh_token' => env('SPAPI_LWA_REFRESH_TOKEN'),
        ],

        // Valid options are NA, EU, FE
        'endpoint' => env('SPAPI_ENDPOINT_REGION', 'NA'),
        'sandbox' => env('SPAPI_SANDBOX', false),
    ],

    'debug' => env('SPAPI_DEBUG', false),
    'debug_file' => env('SPAPI_DEBUG_FILE'),
];
