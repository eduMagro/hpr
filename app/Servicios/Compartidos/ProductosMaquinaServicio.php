<?php

namespace App\Servicios\Compartidos;

use App\Models\Maquina;

class ProductosMaquinaServicio
{
    /**
     * Filtra productos de una máquina por diámetros requeridos y, opcionalmente, longitud exacta si el material es barra.
     */
    public function filtrarPorDiametroYLongitud(Maquina $maquina, array $diametrosRequeridos, ?int $longitudSeleccionada)
    {
        $q = $maquina->productos()
            ->whereHas('productoBase', function ($query) use ($diametrosRequeridos) {
                $query->whereIn('diametro', $diametrosRequeridos);
            })
            ->with('productoBase');

        if ($maquina->tipo_material === 'barra' && $longitudSeleccionada) {
            $q->whereHas('productoBase', function ($query) use ($longitudSeleccionada) {
                $query->where('longitud', $longitudSeleccionada);
            });
        }

        return $q->get();
    }
}
