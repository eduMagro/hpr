<?php

namespace App\Servicios\Etiquetas\Fabrica;

use App\Models\Maquina;
use App\Servicios\Etiquetas\Contratos\EtiquetaServicio;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class FabricaEtiquetaServicio
{
    public function __construct(private Container $container) {}

    public function porMaquina(Maquina $maquina): EtiquetaServicio
    {
        $mapa = config('maquinas.mapa_por_tipo', []);
        $tipo = $maquina->tipo ?? null;
        if (!$tipo || !isset($mapa[$tipo])) {
            throw new InvalidArgumentException('No hay servicio de etiqueta configurado para el tipo de máquina: ' . ($tipo ?? 'desconocido'));
        }
        $clase = $mapa[$tipo];
        return $this->container->make($clase);
    }
}
