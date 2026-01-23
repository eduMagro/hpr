<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modelo de IA por defecto
    |--------------------------------------------------------------------------
    |
    | El modelo que usará Ferrallin para comprender los mensajes.
    | Puede ser cambiado por el usuario desde la interfaz.
    |
    */
    'modelo_defecto' => env('ASISTENTE_MODELO', 'claude-haiku'),

    /*
    |--------------------------------------------------------------------------
    | Modelos disponibles
    |--------------------------------------------------------------------------
    |
    | Lista de modelos que el usuario puede seleccionar.
    | Cada modelo tiene su proveedor, ID de API, descripción y coste aproximado.
    |
    */
    'modelos' => [
        // ═══════════════════════════════════════════════════════════════
        // ANTHROPIC (Claude)
        // ═══════════════════════════════════════════════════════════════
        'claude-haiku' => [
            'nombre' => 'Claude Haiku',
            'proveedor' => 'anthropic',
            'modelo_id' => 'claude-3-haiku-20240307',
            'descripcion' => 'Rápido y económico. Ideal para clasificación.',
            'coste' => 'Muy bajo (~$0.25/millón tokens)',
            'velocidad' => 'Muy rápido',
            'capacidad' => 'Buena comprensión, respuestas concisas',
            'max_tokens' => 4096,
            'activo' => true,
        ],
        'claude-sonnet' => [
            'nombre' => 'Claude Sonnet',
            'proveedor' => 'anthropic',
            'modelo_id' => 'claude-sonnet-4-20250514',
            'descripcion' => 'Equilibrio entre velocidad y capacidad.',
            'coste' => 'Medio (~$3/millón tokens)',
            'velocidad' => 'Rápido',
            'capacidad' => 'Excelente comprensión y razonamiento',
            'max_tokens' => 8192,
            'activo' => true,
        ],
        'claude-opus' => [
            'nombre' => 'Claude Opus',
            'proveedor' => 'anthropic',
            'modelo_id' => 'claude-opus-4-20250514',
            'descripcion' => 'El más potente. Para casos complejos.',
            'coste' => 'Alto (~$15/millón tokens)',
            'velocidad' => 'Moderado',
            'capacidad' => 'Máxima comprensión y análisis profundo',
            'max_tokens' => 8192,
            'activo' => true,
        ],

        // ═══════════════════════════════════════════════════════════════
        // OPENAI (GPT)
        // ═══════════════════════════════════════════════════════════════
        'gpt-4o-mini' => [
            'nombre' => 'GPT-4o Mini',
            'proveedor' => 'openai',
            'modelo_id' => 'gpt-4o-mini',
            'descripcion' => 'Versión económica de GPT-4o.',
            'coste' => 'Bajo (~$0.15/millón tokens)',
            'velocidad' => 'Muy rápido',
            'capacidad' => 'Buena comprensión general',
            'max_tokens' => 4096,
            'activo' => true,
        ],
        'gpt-4o' => [
            'nombre' => 'GPT-4o',
            'proveedor' => 'openai',
            'modelo_id' => 'gpt-4o',
            'descripcion' => 'Modelo principal de OpenAI.',
            'coste' => 'Medio (~$5/millón tokens)',
            'velocidad' => 'Rápido',
            'capacidad' => 'Excelente comprensión multimodal',
            'max_tokens' => 4096,
            'activo' => true,
        ],
        'gpt-4-turbo' => [
            'nombre' => 'GPT-4 Turbo',
            'proveedor' => 'openai',
            'modelo_id' => 'gpt-4-turbo',
            'descripcion' => 'GPT-4 optimizado para velocidad.',
            'coste' => 'Medio-Alto (~$10/millón tokens)',
            'velocidad' => 'Moderado',
            'capacidad' => 'Análisis profundo y contexto largo',
            'max_tokens' => 4096,
            'activo' => true,
        ],

        // ═══════════════════════════════════════════════════════════════
        // LOCAL (Sin coste, sin límites)
        // ═══════════════════════════════════════════════════════════════
        'local' => [
            'nombre' => '🆓 Análisis Local (Gratuito)',
            'proveedor' => 'local',
            'modelo_id' => null,
            'descripcion' => 'Sin límites de uso. Análisis por patrones sin IA externa.',
            'coste' => '🆓 GRATIS',
            'velocidad' => '⚡ Instantáneo',
            'capacidad' => 'Consultas básicas predefinidas',
            'max_tokens' => null,
            'activo' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de proveedores
    |--------------------------------------------------------------------------
    */
    'proveedores' => [
        'anthropic' => [
            'api_url' => 'https://api.anthropic.com/v1/messages',
            'api_key_env' => 'ANTHROPIC_API_KEY',
            'version' => '2023-06-01',
        ],
        'openai' => [
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'api_key_env' => 'OPENAI_API_KEY',
        ],
        'local' => [
            'api_url' => null,
            'api_key_env' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts y reintentos
    |--------------------------------------------------------------------------
    */
    'timeout' => env('ASISTENTE_TIMEOUT', 30),
    'reintentos' => env('ASISTENTE_REINTENTOS', 2),
];
