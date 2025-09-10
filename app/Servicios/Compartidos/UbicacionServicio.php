<?php

namespace App\Servicios\Compartidos;

use App\Models\Maquina;
use App\Models\Ubicacion;

class UbicacionServicio
{
    public function resolverPorMaquina(Maquina $maquina, int $fallbackId = 33): Ubicacion
    {
        $ubicacion = Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();
        if (!$ubicacion) {
            $ubicacion = Ubicacion::findOrFail($fallbackId);
        }
        return $ubicacion;
    }
}
