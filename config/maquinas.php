<?php


return [

    // Tipos "simples" => clase de servicio
    'mapa_por_tipo' => [
        'cortadora'       => \App\Servicios\Etiquetas\Servicios\DobladoraEtiquetaServicio::class,
        'dobladora'       => \App\Servicios\Etiquetas\Servicios\EnsambladoraEtiquetaServicio::class,
        'ensambladora'    => \App\Servicios\Etiquetas\Servicios\SoldadoraEtiquetaServicio::class,
        'cortadora_manual' => \App\Servicios\Etiquetas\Servicios\CortadoraDobladoraBarraEtiquetaServicio::class,

    ],

    // Rama especial para cortadora_dobladora según material
    'cortadora_dobladora_por_material' => [
        'barra'       => \App\Servicios\Etiquetas\Servicios\CortadoraDobladoraBarraEtiquetaServicio::class,
        'encarretado' => \App\Servicios\Etiquetas\Servicios\CortadoraDobladoraEncarretadoEtiquetaServicio::class,
    ],
];
