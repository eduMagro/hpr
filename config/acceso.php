<?php

return [
    // Lista de emails con acceso total (sin restricciones)
    'super_emails' => array_filter(array_map('trim', explode(',', env('SUPER_ADMINS', '')))),

    // Rutas permitidas por rol
    'rutas_operario' => [
        'produccion.trabajadores',
        'users.',
        'alertas.',
        'productos.',
        'pedidos.',
        'ayuda.',
        'maquinas.',
        'entradas.',
    ],

    // Opcional: si usas transportistas
    'rutas_transportista' => [
        'users.',
        'alertas.',
        'ayuda.',
        'planificacion.',
        'salidas.',
        'usuarios.',
        'nominas.',
    ],
];
