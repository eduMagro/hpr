<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Project Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración del proyecto de Firebase. Necesitas crear un proyecto
    | en Firebase Console y obtener las credenciales.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Ruta al archivo JSON de credenciales de la cuenta de servicio.
    | Descárgalo desde Firebase Console > Configuración > Cuentas de servicio
    |
    */

    'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase/service-account.json')),

    /*
    |--------------------------------------------------------------------------
    | Web Push Configuration (for frontend)
    |--------------------------------------------------------------------------
    |
    | Configuración para inicializar Firebase en el navegador.
    | Obtén estos valores desde Firebase Console > Configuración > General > Tus apps
    |
    */

    'web' => [
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_APP_ID'),
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FCM API URL
    |--------------------------------------------------------------------------
    */

    'fcm_url' => 'https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send',
];
