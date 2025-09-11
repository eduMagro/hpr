<?php


return [

    // Tipos “simples” => clase de servicio
    'mapa_por_tipo' => [
        // ejemplo:
        'cortadora'       => \App\Servicios\Etiquetas\Servicios\DobladoraEtiquetaServicio::class,
        'dobladora'       => \App\Servicios\Etiquetas\Servicios\EnsambladoraEtiquetaServicio::class,
        'ensambladora'    => \App\Servicios\Etiquetas\Servicios\SoldadoraEtiquetaServicio::class,
        // si tuvieras una cortadora_dobladora genérica por defecto:
        // 'cortadora_dobladora' => \App\Servicios\Etiquetas\Tipos\CortadoraDobladoraBarraServicio::class,
    ],

    // Rama especial para cortadora_dobladora según material
    'cortadora_dobladora_por_material' => [
        // claves **en minúsculas**
        'barra'       => \App\Servicios\Etiquetas\Servicios\CortadoraDobladoraBarraEtiquetaServicio::class,
        'encarretado' => \App\Servicios\Etiquetas\Servicios\CortadoraDobladoraEncarretadoEtiquetaServicio::class,
    ],
];
