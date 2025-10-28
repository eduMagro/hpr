<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Importación de Planillas
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar los parámetros de importación de planillas,
    | incluyendo las estrategias de subetiquetas por máquina.
    |
    */

    'importacion' => [
        /*
        |--------------------------------------------------------------------------
        | Diámetros Permitidos
        |--------------------------------------------------------------------------
        |
        | Lista de diámetros válidos para elementos (en mm).
        |
        */
        'diametros_permitidos' => [5, 8, 10, 12, 16, 20, 25, 32],

        /*
        |--------------------------------------------------------------------------
        | Tiempo de Setup por Elemento
        |--------------------------------------------------------------------------
        |
        | Tiempo en segundos que se añade por cada elemento para setup.
        | Por defecto: 1200 segundos = 20 minutos
        |
        */
        'tiempo_setup_elemento' => 1200,

        /*
        |--------------------------------------------------------------------------
        | Días de Entrega por Defecto
        |--------------------------------------------------------------------------
        |
        | Días que se añaden a la fecha actual para calcular fecha estimada de entrega.
        |
        */
        'dias_entrega_default' => 7,

        /*
        |--------------------------------------------------------------------------
        | Tamaño Máximo de Archivo
        |--------------------------------------------------------------------------
        |
        | Tamaño máximo del archivo Excel en KB.
        | Por defecto: 10240 KB = 10 MB
        |
        */
        'max_file_size' => 10240,

        /*
        |--------------------------------------------------------------------------
        | Estrategia de Subetiquetas por Defecto
        |--------------------------------------------------------------------------
        |
        | Estrategia que se usará cuando no se encuentre configuración específica
        | para una máquina.
        |
        | Opciones:
        | - 'individual': Una subetiqueta por cada elemento
        | - 'agrupada': Una subetiqueta por grupo de elementos similares
        | - 'legacy': Usa el campo tipo_material de la máquina (compatibilidad)
        |
        */
        'estrategia_subetiquetas_default' => 'agrupada',

        /*
        |--------------------------------------------------------------------------
        | Estrategias de Subetiquetas por Máquina
        |--------------------------------------------------------------------------
        |
        | Define la estrategia de generación de subetiquetas para cada máquina.
        |
        | Formato:
        | 'codigo_maquina' => 'estrategia'
        |
        | Puedes configurar por:
        | 1. Código específico de máquina (ej: 'F12', 'PS12', 'CM')
        | 2. Tipo de máquina (ej: 'cortadora_dobladora', 'cortadora')
        |
        | Estrategias disponibles:
        |
        | ┌─────────────┬──────────────────────────────────────────────────────┐
        | │ individual  │ Crea una subetiqueta única por cada elemento        │
        | │             │ Útil para: Máquinas que procesan barras individuales│
        | │             │ Ejemplo: Cortadora manual (CM)                       │
        | ├─────────────┼──────────────────────────────────────────────────────┤
        | │ agrupada    │ Crea una subetiqueta por grupo de elementos         │
        | │             │ similares (mismo Ø, longitud, dobles, dimensiones)  │
        | │             │ Útil para: Máquinas que procesan lotes              │
        | │             │ Ejemplo: Dobladora automática (F12)                 │
        | ├─────────────┼──────────────────────────────────────────────────────┤
        | │ legacy      │ Usa el campo tipo_material de la máquina            │
        | │             │ - tipo_material='barra' → individual                │
        | │             │ - tipo_material='encarretado' → agrupada            │
        | │             │ Mantiene compatibilidad con sistema anterior        │
        | └─────────────┴──────────────────────────────────────────────────────┘
        |
        */
        'estrategias_subetiquetas' => [
            /*
            |------------------------------------------------------------
            | Configuración por Código Específico de Máquina
            |------------------------------------------------------------
            */

            // Cortadora Manual → Individual (una etiqueta por elemento)
            'CM' => 'individual',

            // Dobladoras Automáticas → Agrupada (una etiqueta por grupo similar)
            'F12' => 'agrupada',
            'PS12' => 'agrupada',
            'MS16' => 'agrupada',

            /*
            |------------------------------------------------------------
            | Configuración por Tipo de Máquina
            |------------------------------------------------------------
            |
            | Esta configuración se aplica a todas las máquinas de este tipo
            | que NO tengan configuración específica por código.
            |
            */

            // Todas las cortadoras-dobladoras sin configuración específica
            'cortadora_dobladora' => 'agrupada',

            // Todas las cortadoras sin configuración específica
            'cortadora' => 'individual',
        ],

        /*
        |--------------------------------------------------------------------------
        | Ejemplos de Configuración
        |--------------------------------------------------------------------------
        |
        | A continuación algunos ejemplos de cómo configurar según tus necesidades:
        |
        | EJEMPLO 1: Todas las máquinas usan estrategia individual
        | ----------------------------------------------------------
        | 'estrategia_subetiquetas_default' => 'individual',
        | 'estrategias_subetiquetas' => [],
        |
        |
        | EJEMPLO 2: Por defecto agrupada, pero CM es individual
        | -------------------------------------------------------
        | 'estrategia_subetiquetas_default' => 'agrupada',
        | 'estrategias_subetiquetas' => [
        |     'CM' => 'individual',
        | ],
        |
        |
        | EJEMPLO 3: Configuración mixta detallada
        | -----------------------------------------
        | 'estrategia_subetiquetas_default' => 'legacy',
        | 'estrategias_subetiquetas' => [
        |     'CM' => 'individual',          // Cortadora manual
        |     'F12' => 'agrupada',           // Dobladora Schnell F12
        |     'PS12' => 'agrupada',          // Dobladora Progress PS12
        |     'MS16' => 'agrupada',          // Dobladora MEP MS16
        |     'cortadora' => 'individual',   // Resto de cortadoras
        | ],
        |
        |
        | EJEMPLO 4: Mantener compatibilidad con sistema anterior
        | --------------------------------------------------------
        | 'estrategia_subetiquetas_default' => 'legacy',
        | 'estrategias_subetiquetas' => [],
        |
        */
    ],
];
