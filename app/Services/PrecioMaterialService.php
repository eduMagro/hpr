<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\Obra;
use App\Models\PrecioMaterialDiametro;
use App\Models\PrecioMaterialFormato;
use App\Models\PrecioMaterialExcepcion;

class PrecioMaterialService
{
    /**
     * Calcula el coste de material para un elemento.
     *
     * Fórmula: (precio_referencia + incremento_diametro + incremento_formato) × toneladas
     *
     * @param Elemento $elemento
     * @return array{coste: float, desglose: array}
     */
    public function calcularCosteElemento(Elemento $elemento): array
    {
        // Obtener datos del elemento
        $diametro = (int) $elemento->diametro;
        $longitudMetros = $elemento->longitud ? $elemento->longitud / 100 : null; // cm a metros
        $pesoKg = (float) $elemento->peso;
        $toneladas = $pesoKg / 1000;

        // Obtener producto y su información
        $producto = $elemento->producto;
        if (!$producto) {
            return [
                'coste' => 0,
                'desglose' => [
                    'error' => 'Elemento sin producto asignado',
                ],
            ];
        }

        // Obtener fabricante y distribuidor del producto
        $fabricanteId = $producto->fabricante_id;
        $distribuidorId = $producto->distribuidor_id;
        $fabricante = $producto->fabricante;

        // Obtener precio de referencia desde PedidoGlobal
        $precioReferencia = $this->obtenerPrecioReferencia($producto);

        if ($precioReferencia === null) {
            return [
                'coste' => 0,
                'desglose' => [
                    'error' => 'No se encontró precio de referencia',
                    'producto_id' => $producto->id,
                ],
            ];
        }

        // Obtener incremento por diámetro
        $incrementoDiametro = PrecioMaterialDiametro::getIncremento($diametro);

        // Determinar formato basado en longitud y si es encarretado
        $esEncarretado = $this->esProductoEncarretado($producto);
        $formato = PrecioMaterialFormato::determinarFormato($longitudMetros, $esEncarretado);

        // Obtener incremento por formato
        $incrementoFormato = 0;
        $formatoCodigo = $formato ? $formato->codigo : PrecioMaterialFormato::ESTANDAR_12M;
        $origenIncremento = 'formato_base';

        // Buscar excepción (prioridad: distribuidor+fabricante > solo fabricante > formato base)
        $excepcion = PrecioMaterialExcepcion::buscar($distribuidorId, $fabricanteId, $formatoCodigo);

        if ($excepcion) {
            $incrementoFormato = (float) $excepcion->incremento;
            $origenIncremento = $excepcion->distribuidor_id ? 'excepcion_especifica' : 'excepcion_fabricante';
        } elseif ($formato) {
            $incrementoFormato = (float) $formato->incremento;
        }

        // Calcular coste final
        $precioTonelada = $precioReferencia + $incrementoDiametro + $incrementoFormato;
        $coste = $precioTonelada * $toneladas;

        return [
            'coste' => round($coste, 2),
            'desglose' => [
                'precio_referencia' => $precioReferencia,
                'incremento_diametro' => $incrementoDiametro,
                'incremento_formato' => $incrementoFormato,
                'precio_tonelada' => round($precioTonelada, 2),
                'toneladas' => round($toneladas, 4),
                'diametro' => $diametro,
                'longitud_metros' => $longitudMetros,
                'formato_codigo' => $formatoCodigo,
                'es_encarretado' => $esEncarretado,
                'origen_incremento' => $origenIncremento,
                'fabricante_id' => $fabricanteId,
                'fabricante_nombre' => $fabricante?->nombre,
                'distribuidor_id' => $distribuidorId,
            ],
        ];
    }

    /**
     * Calcula el coste total de material para una obra.
     *
     * @param Obra $obra
     * @return array{coste_total: float, elementos_count: int, errores_count: int}
     */
    public function calcularCosteObra(Obra $obra): array
    {
        $costeTotal = 0;
        $elementosCount = 0;
        $erroresCount = 0;

        // Obtener todos los elementos de las planillas de la obra
        $elementos = Elemento::whereHas('planilla', function ($query) use ($obra) {
            $query->where('obra_id', $obra->id);
        })->get();

        foreach ($elementos as $elemento) {
            $resultado = $this->calcularCosteElemento($elemento);

            if (isset($resultado['desglose']['error'])) {
                $erroresCount++;
            } else {
                $costeTotal += $resultado['coste'];
                $elementosCount++;
            }
        }

        return [
            'coste_total' => round($costeTotal, 2),
            'elementos_count' => $elementosCount,
            'errores_count' => $erroresCount,
        ];
    }

    /**
     * Obtiene el precio de referencia desde el PedidoGlobal asociado al producto.
     */
    protected function obtenerPrecioReferencia($producto): ?float
    {
        // Intentar obtener desde entrada -> pedidoProducto -> pedidoGlobal
        $entrada = $producto->entrada;
        if (!$entrada) {
            return null;
        }

        $pedidoProducto = $entrada->pedidoProducto;
        if (!$pedidoProducto) {
            return null;
        }

        $pedidoGlobal = $pedidoProducto->pedidoGlobal ?? null;
        if (!$pedidoGlobal) {
            return null;
        }

        return (float) $pedidoGlobal->precio_referencia;
    }

    /**
     * Determina si un producto es encarretado.
     * Esto debería basarse en alguna propiedad del producto o su base.
     */
    protected function esProductoEncarretado($producto): bool
    {
        // Verificar por el tipo del producto base
        $productoBase = $producto->productoBase;
        if ($productoBase && strtolower($productoBase->tipo ?? '') === 'encarretado') {
            return true;
        }

        // También se podría verificar por la longitud (encarretado suele ser muy largo)
        // o por algún campo específico que se añada más adelante

        return false;
    }

    /**
     * Determina si un nombre corresponde a Siderúrgica Sevillana.
     */
    protected function esSiderurgicaSevillana(?string $nombre): bool
    {
        if (!$nombre) {
            return false;
        }

        $nombre = mb_strtolower($nombre, 'UTF-8');
        return str_contains($nombre, 'siderurgica') || str_contains($nombre, 'siderúrgica');
    }

    /**
     * Obtiene el resumen de precios para mostrar en una interfaz.
     */
    public function obtenerResumenPrecios(): array
    {
        return [
            'diametros' => PrecioMaterialDiametro::activos()
                ->orderBy('diametro')
                ->get(['diametro', 'incremento']),
            'formatos' => PrecioMaterialFormato::activos()
                ->orderBy('codigo')
                ->get(['codigo', 'nombre', 'incremento_general', 'incremento_siderurgica']),
            'excepciones' => PrecioMaterialExcepcion::activos()
                ->with(['distribuidor:id,nombre', 'fabricante:id,nombre'])
                ->get(['id', 'distribuidor_id', 'fabricante_id', 'formato_codigo', 'incremento', 'notas']),
        ];
    }
}
