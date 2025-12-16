<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Sincronización FerraWin
    |--------------------------------------------------------------------------
    */

    'sync' => [
        /*
        |--------------------------------------------------------------------------
        | Días hacia atrás para buscar planillas
        |--------------------------------------------------------------------------
        |
        | Por defecto, busca planillas creadas en los últimos 7 días.
        |
        */
        'dias_atras' => env('FERRAWIN_SYNC_DIAS', 7),

        /*
        |--------------------------------------------------------------------------
        | Hora de ejecución automática
        |--------------------------------------------------------------------------
        |
        | Hora en formato 24h para la sincronización diaria automática.
        | Formato: "HH:MM"
        |
        */
        'hora_ejecucion' => env('FERRAWIN_SYNC_HORA', '14:00'),

        /*
        |--------------------------------------------------------------------------
        | Notificación por Email
        |--------------------------------------------------------------------------
        */
        'notificar_email' => env('FERRAWIN_SYNC_EMAIL', true),

        /*
        |--------------------------------------------------------------------------
        | Emails de notificación
        |--------------------------------------------------------------------------
        |
        | Lista de emails que recibirán el resumen de sincronización.
        | Dejar vacío para usar el email por defecto de la aplicación.
        |
        */
        'emails_notificacion' => array_filter(explode(',', env('FERRAWIN_SYNC_EMAILS', ''))),

        /*
        |--------------------------------------------------------------------------
        | Solo planillas nuevas
        |--------------------------------------------------------------------------
        |
        | Si es true, solo sincroniza planillas que no existen en Manager.
        | Si es false, también verifica actualizaciones en planillas existentes.
        |
        */
        'solo_nuevas' => env('FERRAWIN_SYNC_SOLO_NUEVAS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conexión a Base de Datos FerraWin
    |--------------------------------------------------------------------------
    |
    | Estos valores también se pueden configurar en .env
    |
    */
    'database' => [
        'host' => env('FERRAWIN_DB_HOST', '192.168.0.7'),
        'port' => env('FERRAWIN_DB_PORT', '1433'),
        'database' => env('FERRAWIN_DB_DATABASE', 'FERRAWIN'),
        'username' => env('FERRAWIN_DB_USERNAME', 'sa'),
        'password' => env('FERRAWIN_DB_PASSWORD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | API de Sincronización Remota
    |--------------------------------------------------------------------------
    */
    'api' => [
        'token' => env('FERRAWIN_API_TOKEN'),
        'production_url' => env('FERRAWIN_PRODUCTION_URL', 'https://tu-dominio.com'),
    ],
];
