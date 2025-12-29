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
        $tipo = $maquina->tipo;
        if (!$tipo) {
            throw new InvalidArgumentException('La máquina no tiene tipo definido.');
        }
        // Rama especial para cortadora_dobladora
        if ($tipo === 'cortadora_dobladora' || $tipo === 'estribadora') {
            $material = strtolower(trim((string)$maquina->tipo_material)); // 'barra' | 'encarretado'
            $mapaMaterial = config('maquinas.cortadora_dobladora_por_material', []);
            $clase = $mapaMaterial[$material] ?? null;

            if (!$clase) {
                throw new InvalidArgumentException(
                    "No hay servicio configurado para cortadora_dobladora con tipo_material={$material}"
                );
            }

            $servicio = $this->container->make($clase);
            if (!$servicio instanceof EtiquetaServicio) {
                throw new InvalidArgumentException("La clase [$clase] no implementa EtiquetaServicio");
            }
            return $servicio;
        }

        // Resto de tipos “simples”
        $mapa = config('maquinas.mapa_por_tipo', []);
        $clase = $mapa[$tipo] ?? null;
        if (!$clase) {
            throw new InvalidArgumentException("No hay servicio configurado para el tipo de máquina: {$tipo}");
        }

        $servicio = $this->container->make($clase);
        if (!$servicio instanceof EtiquetaServicio) {
            throw new InvalidArgumentException("La clase [$clase] no implementa EtiquetaServicio");
        }
        return $servicio;
    }
}
