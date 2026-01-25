<?php

namespace App\Servicios\Etiquetas\Servicios;

use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Paquete;
use App\Models\Producto;
use App\Servicios\Etiquetas\Base\ServicioEtiquetaBase;
use App\Servicios\Etiquetas\Contratos\EtiquetaServicio;
use App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos;
use App\Servicios\Etiquetas\Resultados\ActualizarEtiquetaResultado;
use App\Servicios\Exceptions\ServicioEtiquetaException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de fabricación para GRÚA
 *
 * Flujo especial:
 * 1. El operario escanea el QR del producto a usar
 * 2. Se le pregunta si usa el paquete completo o quita barras
 * 3. Si usa completo: producto pasa a consumido y ubicacion = null
 * 4. Si quita barras: se resta el peso de la etiqueta al peso_stock del producto
 */
class GruaEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    public function actualizar(ActualizarEtiquetaDatos $datos): ActualizarEtiquetaResultado
    {
        // Validar que se recibió el producto escaneado
        $productoId = $datos->opciones['producto_id'] ?? null;
        $usoPaqueteCompleto = $datos->opciones['paquete_completo'] ?? true;

        if (!$productoId) {
            throw new ServicioEtiquetaException(
                'Debes escanear el QR de un producto para fabricar con la grúa.',
                ['campo' => 'producto_id']
            );
        }

        return DB::transaction(function () use ($datos, $productoId, $usoPaqueteCompleto) {
            $maquina = Maquina::findOrFail($datos->maquinaId);
            $etiqueta = $this->bloquearEtiquetaConElementos((int) $datos->etiquetaSubId);
            $planilla = $etiqueta->planilla;

            // Obtener producto con lock (incluyendo productoBase para validar diámetro/longitud)
            $producto = Producto::with('productoBase')
                ->where('id', $productoId)
                ->lockForUpdate()
                ->first();

            if (!$producto) {
                throw new ServicioEtiquetaException(
                    'El producto escaneado no existe.',
                    ['producto_id' => $productoId]
                );
            }

            if ($producto->estado === 'consumido') {
                throw new ServicioEtiquetaException(
                    'El producto escaneado ya está consumido.',
                    ['producto_id' => $productoId, 'codigo' => $producto->codigo]
                );
            }

            // Obtener tipo, diámetro y longitud del producto
            $tipoProducto = strtolower($producto->productoBase->tipo ?? '');
            $diametroProducto = (float) ($producto->productoBase->diametro ?? $producto->diametro ?? 0);
            $longitudProducto = (float) ($producto->productoBase->longitud ?? $producto->longitud ?? 0);

            // Validar que el producto sea tipo barra
            if ($tipoProducto && $tipoProducto !== 'barra') {
                throw new ServicioEtiquetaException(
                    sprintf(
                        'El producto debe ser de tipo barra. Tipo actual: %s',
                        $producto->productoBase->tipo ?? 'desconocido'
                    ),
                    [
                        'tipo_producto' => $tipoProducto,
                        'codigo_producto' => $producto->codigo,
                    ]
                );
            }

            // Obtener diámetro y longitud de la etiqueta (del primer elemento)
            $primerElemento = $etiqueta->elementos()->first();
            if (!$primerElemento) {
                throw new ServicioEtiquetaException(
                    'La etiqueta no tiene elementos.',
                    ['etiqueta_sub_id' => $datos->etiquetaSubId]
                );
            }

            $diametroEtiqueta = (float) ($primerElemento->diametro ?? 0);
            $longitudEtiqueta = (float) ($primerElemento->longitud ?? 0);

            // Validar que el diámetro coincida (debe ser igual)
            if ($diametroProducto > 0 && $diametroEtiqueta > 0 && abs($diametroProducto - $diametroEtiqueta) > 0.1) {
                throw new ServicioEtiquetaException(
                    sprintf(
                        'El diámetro del producto (Ø%.1f mm) no coincide con el de la etiqueta (Ø%.1f mm).',
                        $diametroProducto,
                        $diametroEtiqueta
                    ),
                    [
                        'diametro_producto' => $diametroProducto,
                        'diametro_etiqueta' => $diametroEtiqueta,
                        'codigo_producto' => $producto->codigo,
                    ]
                );
            }

            // Validar que la longitud coincida (debe ser igual, tolerancia 0.1m)
            // La longitud del elemento está en cm, la del producto en metros
            $longitudEtiquetaMetros = $longitudEtiqueta / 100;

            if ($longitudProducto > 0 && $longitudEtiquetaMetros > 0 && abs($longitudProducto - $longitudEtiquetaMetros) > 0.1) {
                throw new ServicioEtiquetaException(
                    sprintf(
                        'La longitud del producto (%.2f m) no coincide con la de la etiqueta (%.2f m).',
                        $longitudProducto,
                        $longitudEtiquetaMetros
                    ),
                    [
                        'longitud_producto' => $longitudProducto,
                        'longitud_etiqueta_m' => $longitudEtiquetaMetros,
                        'codigo_producto' => $producto->codigo,
                    ]
                );
            }

            // Obtener elementos de esta etiqueta en esta máquina (o sin máquina asignada)
            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($q) use ($maquina) {
                    $q->where('maquina_id', $maquina->id)
                      ->orWhereNull('maquina_id');
                })
                ->where(function ($q) {
                    $q->whereNull('estado')
                      ->orWhereNotIn('estado', ['fabricado', 'completado']);
                })
                ->lockForUpdate()
                ->get();

            if ($elementosEnMaquina->isEmpty()) {
                throw new ServicioEtiquetaException(
                    'No hay elementos pendientes de fabricar en esta etiqueta.',
                    ['etiqueta_sub_id' => $datos->etiquetaSubId]
                );
            }

            $pesoEtiqueta = (float) $elementosEnMaquina->sum('peso');
            $pesoStockProducto = (float) $producto->peso_stock;

            $warnings = [];
            $productosAfectados = [];

            // Validar que el producto tiene suficiente peso
            if (!$usoPaqueteCompleto && $pesoStockProducto < $pesoEtiqueta) {
                throw new ServicioEtiquetaException(
                    sprintf(
                        'El producto no tiene suficiente stock (%.2f kg) para la etiqueta (%.2f kg).',
                        $pesoStockProducto,
                        $pesoEtiqueta
                    ),
                    ['peso_stock' => $pesoStockProducto, 'peso_etiqueta' => $pesoEtiqueta]
                );
            }

            // Procesar consumo del producto
            $pesoInicial = (float) ($producto->peso_inicial ?? $producto->peso_stock);
            $pesoConsumido = 0;

            if ($usoPaqueteCompleto) {
                // Paquete completo: consumir todo
                $pesoConsumido = $pesoStockProducto;
                $producto->peso_stock = 0;
                $producto->estado = 'consumido';
                $producto->ubicacion_id = null;
                $producto->maquina_id = null;
            } else {
                // Quitar barras: restar peso de la etiqueta
                $pesoConsumido = $pesoEtiqueta;
                $producto->peso_stock = $pesoStockProducto - $pesoEtiqueta;

                // Si el peso restante es muy bajo, marcar como consumido
                if ($producto->peso_stock <= 0.1) {
                    $producto->peso_stock = 0;
                    $producto->estado = 'consumido';
                    $producto->ubicacion_id = null;
                    $producto->maquina_id = null;
                }
            }

            $producto->save();

            // Registrar producto afectado
            $productosAfectados[] = [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'peso_stock' => $producto->peso_stock,
                'peso_inicial' => $pesoInicial,
                'n_colada' => $producto->n_colada,
                'consumido' => $pesoConsumido,
            ];

            // Asignar producto a elementos
            foreach ($elementosEnMaquina as $elemento) {
                $elemento->elaborado = 1;
                $elemento->producto_id = $producto->id;
                $elemento->save();
            }

            // Para grúa: marcar etiqueta directamente como completada (un solo click)
            $etiqueta->estado = 'completada';
            $etiqueta->fecha_inicio = $etiqueta->fecha_inicio ?? now();
            $etiqueta->fecha_finalizacion = now();

            // Actualizar peso de la etiqueta
            $this->actualizarPesoEtiqueta($etiqueta);
            $etiqueta->save();

            // Crear o usar paquete existente para la etiqueta
            $paquete = null;
            if ($etiqueta->paquete_id) {
                // Ya tiene paquete asignado
                $paquete = Paquete::find($etiqueta->paquete_id);
            }

            if (!$paquete) {
                // Crear nuevo paquete (sin ubicación, se asignará después en el mapa)
                $paquete = Paquete::crearConCodigoUnico([
                    'nave_id' => $maquina->obra_id,
                    'planilla_id' => $etiqueta->planilla_id,
                    'ubicacion_id' => null, // Se asignará en el mapa
                    'peso' => $pesoEtiqueta,
                    'estado' => 'pendiente',
                ]);

                // Asignar paquete a la etiqueta
                $etiqueta->paquete_id = $paquete->id;
                $etiqueta->save();
            }

            // Si todos los elementos de la planilla están fabricados, cerrar planilla
            if ($planilla) {
                $todosElementosPlanillaCompletos = $planilla->elementos()
                    ->where(function ($q) {
                        $q->whereNull('estado')
                          ->orWhere('estado', '!=', 'fabricado');
                    })
                    ->doesntExist();

                if ($todosElementosPlanillaCompletos) {
                    $planilla->estado = 'completada';
                    $planilla->save();
                }
            }

            return new ActualizarEtiquetaResultado(
                $etiqueta,
                $warnings,
                $productosAfectados,
                [
                    'elementos_fabricados' => $elementosEnMaquina->count(),
                    'peso_consumido' => $pesoConsumido,
                    'paquete_completo' => $usoPaqueteCompleto,
                    'paquete_id' => $paquete?->id,
                    'paquete_codigo' => $paquete?->codigo,
                ]
            );
        });
    }
}
