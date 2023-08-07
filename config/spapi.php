<?php

return [
    'installation_type' => 'single',

    'aws' => [
        'dynamic' => false,
        'access_key_id' => env('SPAPI_AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('SPAPI_AWS_SECRET_ACCESS_KEY'),
        'role_arn' => env('SPAPI_AWS_ROLE_ARN'),
    ],

    'single' => [
        'lwa' => [
            'client_id' => env('SPAPI_LWA_CLIENT_ID'),
            'client_secret' => env('SPAPI_LWA_CLIENT_SECRET'),
            'refresh_token' => env('SPAPI_LWA_REFRESH_TOKEN'),
        ],

        // Valid options are NA, EU, FE
        'endpoint' => env('SPAPI_ENDPOINT_REGION', 'NA'),
    ],

    /**
     * Can be used as a security switch to turn API class registration
     * completely off for non-production environments.
     * If enabled, obtaining instances of API classes might cause
     * unintended API requests using the configured credentials.
     **/
    'registration_enabled' => env('SPAPI_REGISTRATION_ENABLED', true),

    'debug' => env('SPAPI_DEBUG', false),
    'debug_file' => env('SPAPI_DEBUG_FILE', 'php://output'),
];
