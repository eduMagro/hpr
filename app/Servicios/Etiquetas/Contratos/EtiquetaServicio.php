<?php

namespace App\Servicios\Etiquetas\Contratos;

use App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos;
use App\Servicios\Etiquetas\Resultados\ActualizarEtiquetaResultado;

interface EtiquetaServicio
{
    /**
     * Orquesta la actualización de una etiqueta para una máquina concreta.
     * Debe manejar validaciones, transacción y efectos secundarios.
     */
    public function actualizar(ActualizarEtiquetaDatos $datos): ActualizarEtiquetaResultado;
}
