<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Docupipe Integration
    |--------------------------------------------------------------------------
    |
    | When enabled, OCR jobs for certain providers get sent to Docupipe rather
    | than the OpenAI Vision endpoint. Configure the URL, API key and the
    | schema identifiers that map to each provider.
    |
    */

    'enabled' => env('DOCUPIPE_ENABLED', false),
    'base_url' => rtrim(env('DOCUPIPE_BASE_URL', 'https://api.docupipe.ai'), '/'),
    'submit_path' => env('DOCUPIPE_SUBMIT_PATH', '/v1/documents'),
    'api_key' => env('DOCUPIPE_API_KEY'),
    'request_timeout' => env('DOCUPIPE_REQUEST_TIMEOUT', 120),

    'schema_map' => [
        'siderurgica' => env('DOCUPIPE_SCHEMA_SISE'),
        'megasa' => env('DOCUPIPE_SCHEMA_MEGASA'),
    ],

    'default_schema' => env('DOCUPIPE_SCHEMA_DEFAULT'),
];
