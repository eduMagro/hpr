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

class CortadoraDobladoraEncarretadoEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    public function actualizar(ActualizarEtiquetaDatos $datos): ActualizarEtiquetaResultado
    {
        return DB::transaction(function () use ($datos) {
            /** @var Maquina $maquina */
            $maquina = Maquina::findOrFail($datos->maquinaId);
            log::info("CortadoraDobladoraEncarretadoEtiquetaServicio::actualizar - Iniciando actualización para etiqueta {$datos->etiquetaSubId} en máquina {$maquina->id}");
            // Bloqueo etiqueta + elementos
            $etiqueta = Etiqueta::with('planilla')
                ->where('etiqueta_sub_id', $datos->etiquetaSubId)
                ->lockForUpdate()
                ->firstOrFail();
            // 👇 IMPORTANTE: extrae del DTO
            $longitudSeleccionada = $datos->longitudSeleccionada;
            $operario1Id          = $datos->operario1Id;
            $operario2Id          = $datos->operario2Id;
            $solicitarRecargaAuto = $datos->opciones['recarga_auto'] ?? true;
            $planilla = $etiqueta->planilla_id ? Planilla::find($etiqueta->planilla_id) : null;

            $warnings = [];
            $productosAfectados = [];

            // Elementos de la etiqueta en esta máquina (primaria o secundaria)
            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($q) use ($maquina) {
                    $q->where('maquina_id', $maquina->id)
                        ->orWhere('maquina_id_2', $maquina->id);
                })
                ->get();

            // Diámetros requeridos por lo presente en la máquina
            $diametrosConPesos = $this->agruparPesosPorDiametro($elementosEnMaquina);
            $diametrosRequeridos = $this->normalizarDiametros(array_keys($diametrosConPesos));

            switch ($etiqueta->estado) {
                // -------------------------------------------- ESTADO PENDIENTE --------------------------------------------
                case 'pendiente':

                    // ─────────────────────────────────────────────────────────────────────
                    // 1) LOG AUXILIAR: contexto de lo que vamos a necesitar
                    // ─────────────────────────────────────────────────────────────────────
                    // Log::info("🔍 Diámetros requeridos", $diametrosRequeridos);
                    // Log::info(
                    //     "📦 Productos totales en máquina {$maquina->id}",
                    //     $maquina->productos()->with('productoBase')->get()->toArray()
                    // );

                    // ─────────────────────────────────────────────────────────────────────
                    // 2) BASE QUERY: traer productos de la máquina solo de los diámetros
                    //    que pide la etiqueta (diametrosRequeridos). Cargamos productoBase
                    //    para poder filtrar/leer diametro/longitud/tipo con comodidad.
                    // ─────────────────────────────────────────────────────────────────────
                    $productosQuery = $maquina->productos()
                        ->whereHas('productoBase', function ($query) use ($diametrosRequeridos) {
                            $query->whereIn('diametro', $diametrosRequeridos);
                        })
                        ->with('productoBase');

                    // ─────────────────────────────────────────────────────────────────────
                    // 3) VALIDACIÓN DE LONGITUD (solo si la materia prima es "barra")
                    //    - Si en la máquina hay barras de varias longitudes y el usuario
                    //      no ha elegido ninguna, paramos y pedimos que seleccione.
                    //    - Si eligió longitud, filtramos por esa longitud.
                    // ─────────────────────────────────────────────────────────────────────
                    if ($maquina->tipo_material === 'barra') {
                        // Cargamos una primera muestra para explorar longitudes existentes
                        $productosPrevios = $productosQuery->get();

                        // Obtenemos las longitudes disponibles en producto_base (únicas)
                        $longitudes = $productosPrevios->pluck('productoBase.longitud')->unique();

                        // Si hay varias longitudes y no nos han dicho cuál usar, paramos
                        if ($longitudes->count() > 1 && !$longitudSeleccionada) {
                            return response()->json([
                                'success' => false,
                                'error'   => "Hay varias longitudes disponibles para barras (" . $longitudes->implode(', ') . " m). Selecciona una longitud para continuar.",
                            ], 400);
                        }

                        // Si sí nos han indicado una longitud, la aplicamos al filtrado
                        if ($longitudSeleccionada) {
                            $productosQuery->whereHas('productoBase', function ($query) use ($longitudSeleccionada) {
                                $query->where('longitud', $longitudSeleccionada);
                            });
                        }

                        // Re-ejecutamos la query con los filtros definitivos
                        $productos = $productosQuery->orderBy('peso_stock')->get();
                    } else {
                        // Si no trabajamos con barras, ejecutamos tal cual
                        $productos = $productosQuery->orderBy('peso_stock')->get();
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 4) SI TRAS FILTRAR NO QUEDA NADA, NO PODEMOS FABRICAR
                    // ─────────────────────────────────────────────────────────────────────
                    if ($productos->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'error'   => 'No se encontraron productos en la máquina con los diámetros especificados y la longitud indicada.',
                        ], 400);
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 5) AGRUPAR POR DIÁMETRO para facilitar los chequeos posteriores.
                    //    Nota: casteamos a (int) por si vinieran strings desde BD.
                    // ─────────────────────────────────────────────────────────────────────
                    $productosAgrupados = $productos->groupBy(fn($p) => (int) $p->productoBase->diametro);

                    // ─────────────────────────────────────────────────────────────────────
                    // 6) CHEQUEO DE FALTANTES (diámetros sin NINGÚN producto en máquina)
                    //
                    //    Si un diámetro requerido no tiene ni un solo producto en la máquina,
                    //    no podemos empezar: generamos recarga por cada faltante y salimos.
                    //
                    //    Motivo de parar: no existe material del diámetro, no es solo que
                    //    haya poco; es que no hay NADA para empezar a cortar/fabricar.
                    // ─────────────────────────────────────────────────────────────────────
                    $faltantes = [];
                    foreach ($diametrosRequeridos as $diametroReq) {
                        if (!$productosAgrupados->has((int)$diametroReq) || $productosAgrupados[(int)$diametroReq]->isEmpty()) {
                            $faltantes[] = (int) $diametroReq;
                        }
                    }

                    if (!empty($faltantes)) {
                        // Cancelamos la transacción principal para no dejar estados a medias
                        DB::rollBack();

                        // Por cada diámetro faltante, solicitamos recarga (no hay material)
                        foreach ($faltantes as $diametroFaltante) {
                            $productoBaseFaltante = ProductoBase::where('diametro', $diametroFaltante)
                                ->where('tipo', $maquina->tipo_material) // usar SIEMPRE el campo real
                                ->first();

                            if ($productoBaseFaltante) {
                                // Transacción corta y autónoma: el movimiento se registra pase lo que pase
                                DB::transaction(function () use ($productoBaseFaltante, $maquina) {
                                    $this->generarMovimientoRecargaMateriaPrima($productoBaseFaltante, $maquina, null);
                                    Log::info('✅ Movimiento de recarga creado (faltante)', [
                                        'producto_base_id' => $productoBaseFaltante->id,
                                        'maquina_id'       => $maquina->id,
                                    ]);
                                });
                            } else {
                                Log::warning("No se encontró ProductoBase para Ø{$diametroFaltante} y tipo {$maquina->tipo_material}");
                            }
                        }

                        // En faltantes SÍ paramos: no podemos arrancar sin ningún material de ese diámetro
                        return response()->json([
                            'success' => false,
                            'error'   => 'No hay materias primas disponibles para los siguientes diámetros: '
                                . implode(', ', $faltantes)
                                . '. Se han generado automáticamente las solicitudes de recarga.',
                        ], 400);
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 7) SIMULACIÓN DE CONSUMO (sin tocar BD) PARA DETECTAR INSUFICIENCIAS
                    //    Objetivo: prever si, con el stock actual y la demanda por diámetro,
                    //    habrá déficit. La simulación reparte el peso necesario entre los
                    //    productos disponibles del mismo diámetro, agotando primero el que
                    //    menos peso tiene (minimiza restos).
                    //
                    //    Resultado: por cada diámetro, obtenemos:
                    //      - un "plan" de consumo por producto (SOLO informativo)
                    //      - un "pendiente" (déficit) si el stock total no alcanza
                    //    Con esto, avisamos al gruista/operario y opcionalmente creamos
                    //    movimiento de recarga. NO se descuenta stock real aquí.
                    // ─────────────────────────────────────────────────────────────────────

                    $warnings   = $warnings ?? [];
                    $simulacion = []; // [diametro => ['plan' => [[producto_id, consumo_previsto]], 'pendiente' => kg]]

                    foreach ($diametrosConPesos as $diametro => $pesoNecesario) {

                        // Productos de este diámetro (ya filtrados por longitud si es barra)
                        $productosPorDiametro = $productos
                            ->filter(fn($p) => (int)$p->productoBase->diametro === (int)$diametro)
                            // Estrategia: agotar primero el que menos stock tiene
                            ->sortBy('peso_stock'); // ascendente

                        $restante   = (float) $pesoNecesario;
                        $plan       = []; // [[producto_id, consumo_previsto_kg], ...]
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

                        $pendiente = max(0, $restante); // kg que faltarán si no llega recarga

                        $simulacion[(int)$diametro] = [
                            'plan'      => $plan,      // SOLO informativo para logs/UI
                            'pendiente' => $pendiente, // 0 si alcanza; >0 si faltará
                            'stock'     => $stockTotal // útil para logs
                        ];
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 8) ALERTAS Y (OPCIONAL) SOLICITUD DE RECARGA PARA LOS DIÁMETROS QUE
                    //    QUEDARÁN CORTOS. NO paramos el flujo: seguimos a "fabricando".
                    // ─────────────────────────────────────────────────────────────────────

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

                            // Aviso claro para UI (toast/alerta)
                            $warnings[] = "Advertencia: Ø{$dInsuf} mm quedará corto. "
                                . "Faltarán ~" . number_format($deficitKg, 2) . " kg (stock actual: "
                                . number_format($stockActual, 2) . " kg). Se ha solicitado recarga.";

                            // Log detallado con el "plan" simulado (útil para trazabilidad)
                            Log::warning('⚠️ Simulación: déficit previsto en diámetro', [
                                'maquina_id' => $maquina->id,
                                'diametro'   => $dInsuf,
                                'pendiente'  => $deficitKg,
                                'plan'       => $simulacion[$dInsuf]['plan'],
                                'stock'      => $stockActual,
                                'necesario'  => (float)($diametrosConPesos[$dInsuf] ?? 0),
                            ]);

                            // (Opcional) solicitar recarga automática, sin parar el flujo
                            if ($solicitarRecargaAuto ?? true) { // flag por si quieres desactivarlo
                                $productoBase = ProductoBase::where('diametro', $dInsuf)
                                    ->where('tipo', $maquina->tipo_material)
                                    ->first();

                                if ($productoBase) {
                                    try {
                                        // Tu método existente. productoId = null → materia prima genérica
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

                    // ─────────────────────────────────────────────────────────────────────
                    // 9) ARRANQUE DE FABRICACIÓN: cambiamos estados de planilla/etiqueta/elementos
                    //    - Si la planilla no tenía fecha de inicio, la fijamos y pasamos a "fabricando".
                    //    - Marcamos elementos en máquina como "fabricando" y asignamos operarios.
                    //    - Ponemos la etiqueta en "fabricando".
                    // ─────────────────────────────────────────────────────────────────────
                    if ($etiqueta->planilla) {
                        if (is_null($etiqueta->planilla->fecha_inicio)) {
                            $etiqueta->planilla->fecha_inicio = now();
                            $etiqueta->planilla->estado       = "fabricando";
                            $etiqueta->planilla->save();
                        }
                    } else {
                        // Caso raro: etiqueta sin planilla asociada → no podemos continuar
                        return response()->json([
                            'success' => false,
                            'error'   => 'La etiqueta no tiene una planilla asociada.',
                        ], 400);
                    }

                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->estado     = "fabricando";
                        $elemento->save();
                    }

                    $etiqueta->estado        = "fabricando";
                    $etiqueta->operario1_id  = $operario1Id;
                    $etiqueta->operario2_id  = $operario2Id;
                    $etiqueta->fecha_inicio  = now();
                    $etiqueta->save();

                    break;

                // -------------------------------------------- ESTADO FABRICANDO --------------------------------------------

                case 'fabricando': {
                        // ¿Quedan elementos en esta máquina que NO estén completados/fabricados?
                        $quedanPendientes = $elementosEnMaquina->contains(function ($e) {
                            return !in_array($e->estado, ['fabricado', 'completado'], true);
                        });

                        if (!$quedanPendientes) {
                            // No devuelvas JSON ni hagas rollBack: lanza excepción y que el controlador responda.
                            throw new ServicioEtiquetaException(
                                'Todos los elementos en la máquina ya han sido completados.',
                                [
                                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                                    'maquina_id'      => $maquina->id,
                                ]
                            );
                        }

                        // Ejecuta la lógica de consumos (no retorna nada)
                        $productosAfectados = [];
                        $numeroCompletados = 0;

                        $this->actualizarElementosYConsumosCompleto(
                            elementosEnMaquina: $elementosEnMaquina,
                            maquina: $maquina,
                            etiqueta: $etiqueta,
                            warnings: $warnings,
                            productosAfectados: $productosAfectados,
                            planilla: $planilla,
                            solicitanteId: $operario1Id
                        );

                        break;
                    }
                case 'fabricada':
                case 'parcialmente completada':
                    // Transición típica a dobladora manual si aplica
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
                    throw new RuntimeException('Etiqueta ya completada.' . $etiqueta);

                default:
                    Log::info('CortadoraDobladoraEtiquetaServicio: sin transición para estado', [
                        'estado' => $etiqueta->estado,
                        'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                    ]);
                    break;
            }

            // Si planilla queda sin pendientes en esta máquina, reordenar cola
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
