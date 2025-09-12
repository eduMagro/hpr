<?php

namespace App\Servicios\Etiquetas\Servicios;

use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\ProductoBase;
use App\Servicios\Etiquetas\Base\ServicioEtiquetaBase;
use App\Servicios\Etiquetas\Contratos\EtiquetaServicio;
use App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos;
use App\Servicios\Etiquetas\Resultados\ActualizarEtiquetaResultado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use App\Servicios\Exceptions\ServicioEtiquetaException;


class CortadoraDobladoraBarraEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    public function actualizar(ActualizarEtiquetaDatos $datos): ActualizarEtiquetaResultado
    {
        return DB::transaction(function () use ($datos) {
            /** @var Maquina $maquina */
            $maquina = Maquina::findOrFail($datos->maquinaId);
            Log::info("CortadoraDobladoraBarraEtiquetaServicio::actualizar - Iniciando actualización para etiqueta {$datos->etiquetaSubId} en máquina {$maquina->id}");

            $etiqueta = Etiqueta::with('planilla')
                ->where('etiqueta_sub_id', $datos->etiquetaSubId)
                ->lockForUpdate()
                ->firstOrFail();

            $longitudSeleccionada = $datos->longitudSeleccionada;
            $operario1Id          = $datos->operario1Id;
            $operario2Id          = $datos->operario2Id;
            $solicitarRecargaAuto = $datos->opciones['recarga_auto'] ?? true;
            $planilla             = $etiqueta->planilla_id ? Planilla::find($etiqueta->planilla_id) : null;

            $warnings = [];
            $productosAfectados = [];

            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($q) use ($maquina) {
                    $q->where('maquina_id', $maquina->id)
                        ->orWhere('maquina_id_2', $maquina->id);
                })
                ->get();

            $diametrosConPesos  = $this->agruparPesosPorDiametro($elementosEnMaquina);
            $diametrosRequeridos = $this->normalizarDiametros(array_keys($diametrosConPesos));

            switch ($etiqueta->estado) {
                case 'pendiente':
                    $productosQuery = $maquina->productos()
                        ->whereHas('productoBase', function ($query) use ($diametrosRequeridos) {
                            $query->whereIn('diametro', $diametrosRequeridos);
                        })
                        ->with('productoBase');

                    if ($maquina->tipo_material === 'barra') {
                        $productosPrevios = $productosQuery->get();
                        $longitudes = $productosPrevios->pluck('productoBase.longitud')->unique();

                        if ($longitudes->count() > 1 && !$longitudSeleccionada) {
                            // ❌ error → lanzamos excepción (el controller decidirá el JSON)
                            throw new ServicioEtiquetaException(
                                "Hay varias longitudes disponibles para barras ({$longitudes->implode(', ')} m). Selecciona una longitud para continuar.",
                                [
                                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                                    'maquina_id' => $maquina->id,
                                    'longitudes_disponibles' => $longitudes->values()->all(),
                                ]
                            );
                        }

                        if ($longitudSeleccionada) {
                            $productosQuery->whereHas('productoBase', function ($query) use ($longitudSeleccionada) {
                                $query->where('longitud', $longitudSeleccionada);
                            });
                        }
                    }

                    $productos = $productosQuery->orderBy('peso_stock')->get();

                    if ($productos->isEmpty()) {
                        throw new ServicioEtiquetaException(
                            'No se encontraron productos en la máquina con los diámetros especificados y la longitud indicada.',
                            [
                                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                                'maquina_id'      => $maquina->id,
                                'diametros'       => $diametrosRequeridos,
                                'longitud'        => $longitudSeleccionada,
                            ]
                        );
                    }

                    $productosAgrupados = $productos->groupBy(fn($p) => (int) $p->productoBase->diametro);

                    $faltantes = [];
                    foreach ($diametrosRequeridos as $diametroReq) {
                        if (!$productosAgrupados->has((int)$diametroReq) || $productosAgrupados[(int)$diametroReq]->isEmpty()) {
                            $faltantes[] = (int) $diametroReq;
                        }
                    }

                    if (!empty($faltantes)) {
                        // Generamos recargas (en subtransacciones) y luego lanzamos excepción
                        foreach ($faltantes as $diametroFaltante) {
                            $productoBaseFaltante = ProductoBase::where('diametro', $diametroFaltante)
                                ->where('tipo', $maquina->tipo_material)
                                ->first();

                            if ($productoBaseFaltante) {
                                try {
                                    DB::transaction(function () use ($productoBaseFaltante, $maquina) {
                                        $this->generarMovimientoRecargaMateriaPrima($productoBaseFaltante, $maquina, null);
                                    });
                                    Log::info('✅ Movimiento de recarga creado (faltante)', [
                                        'producto_base_id' => $productoBaseFaltante->id,
                                        'maquina_id'       => $maquina->id,
                                    ]);
                                } catch (\Throwable $e) {
                                    Log::error('❌ Error creando recarga (faltante)', [
                                        'maquina_id' => $maquina->id,
                                        'diametro'   => $diametroFaltante,
                                        'error'      => $e->getMessage(),
                                    ]);
                                }
                            } else {
                                Log::warning("No se encontró ProductoBase para Ø{$diametroFaltante} y tipo {$maquina->tipo_material}");
                            }
                        }

                        throw new ServicioEtiquetaException(
                            'No hay materias primas disponibles para algunos diámetros; se han generado solicitudes de recarga.',
                            [
                                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                                'maquina_id'      => $maquina->id,
                                'faltantes'       => $faltantes,
                            ]
                        );
                    }

                    // Simulación de consumo para detectar insuficiencias (sin parar el flujo)
                    $simulacion = [];
                    foreach ($diametrosConPesos as $diametro => $pesoNecesario) {
                        $productosPorDiametro = $productos
                            ->filter(fn($p) => (int)$p->productoBase->diametro === (int)$diametro)
                            ->sortBy('peso_stock');

                        $restante = (float) $pesoNecesario;
                        $plan = [];
                        $stockTotal = 0.0;

                        foreach ($productosPorDiametro as $prod) {
                            $disponible = (float) ($prod->peso_stock ?? 0);
                            if ($disponible <= 0) continue;

                            $stockTotal += $disponible;
                            if ($restante <= 0) break;

                            $consumoPrevisto = min($disponible, $restante);
                            if ($consumoPrevisto > 0) {
                                $plan[]    = ['producto_id' => $prod->id, 'consumo' => $consumoPrevisto];
                                $restante -= $consumoPrevisto;
                            }
                        }

                        $simulacion[(int)$diametro] = [
                            'plan'      => $plan,
                            'pendiente' => max(0, $restante),
                            'stock'     => $stockTotal,
                        ];
                    }

                    $diamInsuf = collect($simulacion)
                        ->filter(fn($info) => ($info['pendiente'] ?? 0) > 0)
                        ->keys()
                        ->map(fn($d) => (int)$d)
                        ->values()
                        ->all();

                    if (!empty($diamInsuf)) {
                        foreach ($diamInsuf as $dInsuf) {
                            $deficitKg   = $simulacion[$dInsuf]['pendiente'] ?? null;
                            $stockActual = $simulacion[$dInsuf]['stock']     ?? null;

                            $warnings[] = "Advertencia: Ø{$dInsuf} mm quedará corto. Faltarán ~" . number_format($deficitKg, 2) . " kg (stock actual: " . number_format($stockActual, 2) . " kg). Se ha solicitado recarga.";

                            if ($solicitarRecargaAuto ?? true) {
                                $productoBase = ProductoBase::where('diametro', $dInsuf)
                                    ->where('tipo', $maquina->tipo_material)
                                    ->first();

                                if ($productoBase) {
                                    try {
                                        $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina, null);
                                        Log::info('📣 Recarga solicitada (déficit previsto)', [
                                            'maquina_id'       => $maquina->id,
                                            'producto_base_id' => $productoBase->id,
                                            'diametro'         => $dInsuf,
                                            'deficit_kg'       => $deficitKg,
                                        ]);
                                    } catch (\Throwable $e) {
                                        Log::error('❌ Error al solicitar recarga (déficit previsto)', [
                                            'maquina_id'       => $maquina->id,
                                            'producto_base_id' => $productoBase->id ?? null,
                                            'diametro'         => $dInsuf,
                                            'deficit_kg'       => $deficitKg,
                                            'error'            => $e->getMessage(),
                                        ]);
                                    }
                                } else {
                                    Log::warning("No se encontró ProductoBase para Ø{$dInsuf} y tipo {$maquina->tipo_material} (recarga no creada).");
                                }
                            }
                        }
                    }

                    if ($etiqueta->planilla) {
                        if (is_null($etiqueta->planilla->fecha_inicio)) {
                            $etiqueta->planilla->fecha_inicio = now();
                            $etiqueta->planilla->estado       = "fabricando";
                            $etiqueta->planilla->save();
                        }
                    } else {
                        throw new ServicioEtiquetaException(
                            'La etiqueta no tiene una planilla asociada.',
                            ['etiqueta_sub_id' => $etiqueta->etiqueta_sub_id]
                        );
                    }

                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->estado = "fabricando";
                        $elemento->save();
                    }

                    $etiqueta->estado       = "fabricando";
                    $etiqueta->operario1_id = $operario1Id;
                    $etiqueta->operario2_id = $operario2Id;
                    $etiqueta->fecha_inicio = now();
                    $etiqueta->save();

                    break;

                case 'fabricando':
                    $quedanPendientes = $elementosEnMaquina->contains(function ($e) {
                        return !in_array($e->estado, ['fabricado', 'completado'], true);
                    });

                    if (!$quedanPendientes) {
                        throw new ServicioEtiquetaException(
                            'Todos los elementos en la máquina ya han sido completados.',
                            [
                                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                                'maquina_id'      => $maquina->id,
                            ]
                        );
                    }

                    // ⚠️ CORREGIDO: nombre del método
                    $this->actualizarElementosYConsumos(
                        elementosEnMaquina: $elementosEnMaquina,
                        maquinA: $maquina, // (PHP no es case-sensitive con nombres de parámetros nombrados, pero ponlo igual)
                        etiqueta: $etiqueta,
                        warnings: $warnings,
                        productosAfectados: $productosAfectados,
                        planilla: $planilla
                    );
                    break;

                case 'fabricada':
                case 'parcialmente completada':
                    $dobladora = Maquina::where('tipo', 'dobladora manual')
                        ->when($maquina->obra_id, fn($q) => $q->where('obra_id', $maquina->obra_id))
                        ->orderBy('id')
                        ->first();
                    if ($dobladora) {
                        foreach ($elementosEnMaquina as $el) {
                            if (is_null($el->maquina_id_2)) {
                                $el->maquina_id_2 = $dobladora->id;
                                $el->save();
                            }
                        }
                    }
                    break;

                case 'completada':
                    throw new ServicioEtiquetaException(
                        'Etiqueta ya completada.',
                        ['etiqueta_sub_id' => $etiqueta->etiqueta_sub_id]
                    );

                default:
                    Log::info('CortadoraDobladoraEtiquetaServicio: sin transición para estado', [
                        'estado' => $etiqueta->estado,
                        'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                    ]);
                    break;
            }

            if ($planilla) {
                $quedanPendientesEnEstaMaquina = Elemento::where('planilla_id', $planilla->id)
                    ->where(function ($q) use ($maquina) {
                        $q->where('maquina_id', $maquina->id)
                            ->orWhere('maquina_id_2', $maquina->id);
                    })
                    ->where(function ($q) {
                        $q->whereNull('estado')->orWhere('estado', '!=', 'completado');
                    })
                    ->exists();

                if (!$quedanPendientesEnEstaMaquina) {
                    DB::transaction(function () use ($planilla, $maquina) {
                        $registro = OrdenPlanilla::where('planilla_id', $planilla->id)
                            ->where('maquina_id', $maquina->id)
                            ->lockForUpdate()
                            ->first();
                        if ($registro) {
                            $pos = $registro->posicion;
                            $registro->delete();
                            OrdenPlanilla::where('maquina_id', $maquina->id)
                                ->where('posicion', '>', $pos)
                                ->decrement('posicion');
                        }
                    });
                }
            }

            $etiqueta->refresh();

            return new ActualizarEtiquetaResultado(
                etiqueta: $etiqueta,
                warnings: $warnings,
                productosAfectados: $productosAfectados,
                metricas: [
                    'maquina_id' => $maquina->id,
                ]
            );
        });
    }

    /**
     * Reutiliza la implementación de Ensambladora para consumos, pero con reglas de esta máquina
     */
    private function actualizarElementosYConsumos($elementosEnMaquina, Maquina $maquina, Etiqueta &$etiqueta, array &$warnings, array &$productosAfectados, ?Planilla $planilla): void
    {
        // Copiado de Ensambladora con ajustes de no bloqueo por faltante (warnings + recarga)
        $consumos = [];
        foreach ($elementosEnMaquina->groupBy('diametro') as $diametro => $elementos) {
            $pesoNecesarioTotal = $elementos->sum('peso');
            $productosPorDiametro = $maquina->productos()
                ->whereHas('productoBase', fn($q) => $q->where('diametro', $diametro))
                ->with('productoBase')
                ->orderBy('peso_stock')
                ->get();

            if ($productosPorDiametro->isEmpty()) {
                $pb = ProductoBase::where('diametro', $diametro)
                    ->where('tipo', $maquina->tipo_material)
                    ->first();
                if ($pb) {
                    $this->generarMovimientoRecargaMateriaPrima($pb, $maquina, null, $etiqueta->operario1_id ?? auth()->id());
                    $warnings[] = "No hay materias para Ø{$diametro}. Se ha solicitado recarga.";
                }
                continue;
            }

            $consumos[$diametro] = [];
            foreach ($productosPorDiametro as $producto) {
                if ($pesoNecesarioTotal <= 0) break;
                $pesoInicial = $producto->peso_inicial ?? $producto->peso_stock;
                $restar = min($producto->peso_stock, $pesoNecesarioTotal);
                $producto->peso_stock -= $restar;
                $pesoNecesarioTotal -= $restar;
                if ($producto->peso_stock == 0) {
                    $producto->estado = 'consumido';
                    $producto->ubicacion_id = null;
                    $producto->maquina_id = null;
                }
                $producto->save();
                $productosAfectados[] = [
                    'id' => $producto->id,
                    'peso_stock' => $producto->peso_stock,
                    'peso_inicial' => $pesoInicial,
                ];
                $consumos[$diametro][] = [
                    'producto_id' => $producto->id,
                    'consumido' => $restar,
                ];
            }

            if ($pesoNecesarioTotal > 0) {
                $pb = ProductoBase::where('diametro', $diametro)
                    ->where('tipo', $maquina->tipo_material)
                    ->first();
                if ($pb) {
                    $this->generarMovimientoRecargaMateriaPrima($pb, $maquina, null, $etiqueta->operario1_id ?? auth()->id());
                    $warnings[] = "Stock insuficiente para Ø{$diametro} en {$maquina->nombre}. Se ha solicitado recarga.";
                }
            }
        }

        foreach ($elementosEnMaquina as $elemento) {
            $pesoRestante = $elemento->peso;
            $disponibles = $consumos[$elemento->diametro] ?? [];
            $asignados = [];
            while ($pesoRestante > 0 && count($disponibles) > 0) {
                $cons = &$disponibles[0];
                if ($cons['consumido'] <= $pesoRestante) {
                    $asignados[] = $cons['producto_id'];
                    $pesoRestante -= $cons['consumido'];
                    array_shift($disponibles);
                } else {
                    $asignados[] = $cons['producto_id'];
                    $cons['consumido'] -= $pesoRestante;
                    $pesoRestante = 0;
                }
            }

            $elemento->producto_id   = $asignados[0] ?? null;
            $elemento->producto_id_2 = $asignados[1] ?? null;
            $elemento->producto_id_3 = $asignados[2] ?? null;
            if ($pesoRestante <= 0) {
                $elemento->estado = 'fabricado';
            }
            $elemento->save();
        }
    }
}
