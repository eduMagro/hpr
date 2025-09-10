<?php

return [
    // Mapa por tipo de máquina -> clase del servicio
    'mapa_por_tipo' => [
        'cortadora_dobladora' => \App\Servicios\Etiquetas\Servicios\CortadoraDobladoraEtiquetaServicio::class,
        'cortadora_manual'    => \App\Servicios\Etiquetas\Servicios\EnsambladoraEtiquetaServicio::class, // corta a mano pero usa flujo base
        'dobladora manual'    => \App\Servicios\Etiquetas\Servicios\DobladoraEtiquetaServicio::class,
        'ensambladora'        => \App\Servicios\Etiquetas\Servicios\EnsambladoraEtiquetaServicio::class,
        'estribadora'         => \App\Servicios\Etiquetas\Servicios\EnsambladoraEtiquetaServicio::class, // similar a corte/fabricación
        'soldadora'           => \App\Servicios\Etiquetas\Servicios\SoldadoraEtiquetaServicio::class,
    ],

    // Fallback configurable para ubicaciones cuando no se encuentra por código
    'ubicacion_fallback_id' => 33,
];
