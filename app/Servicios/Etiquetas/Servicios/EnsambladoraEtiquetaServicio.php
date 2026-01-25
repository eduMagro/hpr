<?php

namespace App\Servicios\Etiquetas\Servicios;

use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\Movimiento;
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

class EnsambladoraEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    public function actualizar(ActualizarEtiquetaDatos $datos): ActualizarEtiquetaResultado
    {
        return DB::transaction(function () use ($datos) {
            // 1) Cargar máquina
            /** @var Maquina $maquina */
            $maquina = Maquina::findOrFail($datos->maquinaId);

            // 2) Bloquear etiqueta + elementos (consistencia)
            $etiqueta = Etiqueta::with('elementos.planilla')
                ->where('etiqueta_sub_id', $datos->etiquetaSubId)
                ->lockForUpdate()
                ->firstOrFail();

            $planillaId = $etiqueta->planilla_id;
            /** @var Planilla|null $planilla */
            $planilla = $planillaId ? Planilla::find($planillaId) : null;

            $warnings = [];
            $productosAfectados = [];

            $operario1 = $datos->operario1Id;
            $operario2 = $datos->operario2Id;

            $ensambladoText = Str::of((string) optional($planilla)->ensamblado)->lower();

            // Elementos de esta etiqueta en la máquina actual (sea maquina_id o maquina_id_2)
            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($query) use ($maquina) {
                    $query->where('maquina_id', $maquina->id)
                        ->orWhere('maquina_id_2', $maquina->id);
                })
                ->get();

            $pesoTotalMaquina = (float) $elementosEnMaquina->sum('peso');
            $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->count();
            $numeroElementosTotalesEnEtiqueta = $etiqueta->elementos()->count();

            $enOtrasMaquinas = $etiqueta->elementos()
                ->where('maquina_id', '!=', $maquina->id)
                ->exists();

            // Agrupar pesos por diámetro de los elementos en la máquina
            $diametrosConPesos = $this->agruparPesosPorDiametro($elementosEnMaquina);
            $diametrosRequeridos = array_map('intval', array_keys($diametrosConPesos));

            if (empty($diametrosRequeridos)) {
                $derivados = $elementosEnMaquina->pluck('diametro')
                    ->filter(fn($d) => $d !== null && $d !== '')
                    ->map(fn($d) => (int) round((float) $d))
                    ->unique()
                    ->values()
                    ->all();
                $diametrosRequeridos = $derivados;
            }

            switch ($etiqueta->estado) {
                case 'pendiente':
                    // Query base de productos por diámetros requeridos
                    $productosQuery = $maquina->productos()
                        ->whereHas('productoBase', function ($query) use ($diametrosRequeridos) {
                            $query->whereIn('diametro', $diametrosRequeridos);
                        })
                        ->with('productoBase');

                    // Validación de longitud para barras
                    if ($maquina->tipo_material === 'barra') {
                        $productosPrevios = $productosQuery->get();
                        $longitudes = $productosPrevios->pluck('productoBase.longitud')->unique();

                        if ($longitudes->count() > 1 && !$datos->longitudSeleccionada) {
                            // No obligar aún: si hay varias, intentamos continuar y avisar luego
                            Log::warning('Varias longitudes disponibles pero no se seleccionó ninguna. Se continuará sin filtrar por longitud.', [
                                'maquina_id' => $maquina->id,
                                'longitudes' => $longitudes->values()->all(),
                            ]);
                        }

                        if ($datos->longitudSeleccionada) {
                            $productosQuery->whereHas('productoBase', function ($query) use ($datos) {
                                $query->where('longitud', $datos->longitudSeleccionada);
                            });
                        }
                    }

                    $productos = $productosQuery->orderBy('peso_stock')->get();

                    if ($productos->isEmpty()) {
                        // Si no hay productos disponibles, generamos automáticamente recargas para todos los diámetros requeridos
                        foreach ($diametrosRequeridos as $diametroFaltante) {
                            $productoBaseFaltante = ProductoBase::where('diametro', $diametroFaltante)
                                ->where('tipo', $maquina->tipo_material)
                                ->first();

                            if ($productoBaseFaltante) {
                                try {
                                    $this->generarMovimientoRecargaMateriaPrima($productoBaseFaltante, $maquina, null, $operario1);
                                } catch (\Throwable $e) {
                                    Log::error('❌ Error creando movimiento de recarga (no había productos en máquina)', [
                                        'maquina_id'       => $maquina->id,
                                        'diametro'         => $diametroFaltante,
                                        'error'            => $e->getMessage(),
                                    ]);
                                }
                            }
                        }

                        // No bloquear. Avisar y seguir con warnings.
                        $warnings[] = 'No se encontraron productos en la máquina. Se han solicitado recargas automáticas para los diámetros requeridos.';

                        // Continuar sin lanzar excepción
                    }

                    // Agrupar por diámetro para detectar faltantes
                    $productosAgrupados = $productos->groupBy(fn($p) => (int) $p->productoBase->diametro);

                    $faltantes = [];
                    foreach ($diametrosRequeridos as $diametroReq) {
                        if (!$productosAgrupados->has((int)$diametroReq) || $productosAgrupados[(int)$diametroReq]->isEmpty()) {
                            $faltantes[] = (int) $diametroReq;
                        }
                    }

                    if (!empty($faltantes)) {
                        // Solicitar recarga por cada diámetro faltante
                        foreach ($faltantes as $diametroFaltante) {
                            $productoBaseFaltante = ProductoBase::where('diametro', $diametroFaltante)
                                ->where('tipo', $maquina->tipo_material)
                                ->first();

                            if ($productoBaseFaltante) {
                                // Nota: si esta transacción se revierte, estos movimientos también.
                                // Se podría migrar a afterCommit si se desea persistencia incluso en error.
                                $this->generarMovimientoRecargaMateriaPrima($productoBaseFaltante, $maquina, null, $operario1);
                            }
                        }

                        // No bloquear: avisar y continuar el flujo a 'fabricando'
                        $warnings[] = 'No hay materias primas disponibles para Ø ' . implode(', ', $faltantes) . '. Se han solicitado recargas automáticamente.';
                    }

                    // Simulación de consumo para detectar insuficiencias
                    $simulacion = [];
                    foreach ($diametrosConPesos as $diametro => $pesoNecesario) {
                        $productosPorDiametro = $productos
                            ->filter(fn($p) => (int)$p->productoBase->diametro === (int)$diametro)
                            ->sortBy('peso_stock');

                        $restante   = (float) $pesoNecesario;
                        $plan       = [];
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

                        $pendiente = max(0, $restante);
                        $simulacion[(int)$diametro] = [
                            'plan'      => $plan,
                            'pendiente' => $pendiente,
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

                            $warnings[] = "Advertencia: Ø{$dInsuf} mm quedará corto. Faltarán ~" . number_format((float)$deficitKg, 2) . " kg (stock actual: " . number_format((float)$stockActual, 2) . " kg). Se ha solicitado recarga.";

                            Log::warning('⚠️ Simulación: déficit previsto en diámetro', [
                                'maquina_id' => $maquina->id,
                                'diametro'   => $dInsuf,
                                'pendiente'  => $deficitKg,
                                'plan'       => $simulacion[$dInsuf]['plan'],
                                'stock'      => $stockActual,
                                'necesario'  => (float)($diametrosConPesos[$dInsuf] ?? 0),
                            ]);

                            $productoBase = ProductoBase::where('diametro', $dInsuf)
                                ->where('tipo', $maquina->tipo_material)
                                ->first();

                            if ($productoBase) {
                                try {
                                    $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina, null, $operario1);
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

                    // Arranque de fabricación: estados
                    if ($etiqueta->planilla) {
                        $etiqueta->planilla->estado = 'fabricando';
                        $etiqueta->planilla->save();
                    } else {
                        throw new RuntimeException('La etiqueta no tiene una planilla asociada.');
                    }

                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->users_id   = $operario1;
                        $elemento->users_id_2 = $operario2;
                        $elemento->save();
                    }

                    $etiqueta->estado        = 'fabricando';
                    $etiqueta->operario1_id  = $operario1;
                    $etiqueta->operario2_id  = $operario2;
                    $etiqueta->fecha_inicio  = now();
                    $etiqueta->save();

                    break;

                case 'fabricando':
                    $this->actualizarElementosYConsumos(
                        elementosEnMaquina: $elementosEnMaquina,
                        maquina: $maquina,
                        etiqueta: $etiqueta,
                        warnings: $warnings,
                        numeroElementosCompletadosEnMaquina: $numeroElementosCompletadosEnMaquina,
                        enOtrasMaquinas: $enOtrasMaquinas,
                        productosAfectados: $productosAfectados,
                        planilla: $planilla
                    );
                    break;

                case 'fabricada':
                    if ($maquina->tipo === 'ensambladora') {
                        $etiqueta->fecha_inicio_ensamblado = now();
                        $etiqueta->estado = 'ensamblando';
                        $etiqueta->save();
                    } elseif ($maquina->tipo === 'soldadora') {
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'soldando';
                        $etiqueta->save();
                    } elseif ($maquina->tipo === 'dobladora_manual') {
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'doblando';
                        $etiqueta->save();
                    } else {
                        if (
                            isset($elementosEnMaquina) &&
                            $elementosEnMaquina->count() > 0 &&
                            $numeroElementosCompletadosEnMaquina >= $elementosEnMaquina->count() &&
                            in_array($maquina->tipo, ['cortadora_dobladora', 'estribadora'])
                        ) {
                            throw new RuntimeException('Todos los elementos en la máquina ya han sido completados.');
                        }
                    }
                    break;

                case 'ensamblada':
                    if (
                        isset($elementosEnMaquina) &&
                        $elementosEnMaquina->count() > 0 &&
                        $numeroElementosCompletadosEnMaquina >= $elementosEnMaquina->count() &&
                        in_array($maquina->tipo, ['cortadora_dobladora', 'estribadora'])
                    ) {
                        throw new RuntimeException('Todos los elementos en la máquina ya han sido completados.');
                    }

                    $this->actualizarElementosYConsumos(
                        elementosEnMaquina: $elementosEnMaquina,
                        maquina: $maquina,
                        etiqueta: $etiqueta,
                        warnings: $warnings,
                        numeroElementosCompletadosEnMaquina: $numeroElementosCompletadosEnMaquina,
                        enOtrasMaquinas: $enOtrasMaquinas,
                        productosAfectados: $productosAfectados,
                        planilla: $planilla
                    );

                    if ($maquina->tipo === 'soldadora') {
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'soldando';
                        $etiqueta->soldador1 =  $operario1;
                        $etiqueta->soldador2 =  $operario2;
                        $etiqueta->save();
                    }
                    break;

                case 'ensamblando':
                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->users_id =  $operario1;
                        $elemento->users_id_2 =  $operario2;
                        $elemento->save();
                    }

                    // Completar ensamblaje
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->fecha_finalizacion_ensamblado = now();
                    $etiqueta->save();

                    // Consumos y asignación de productos similar a controlador
                    $consumos = [];
                    foreach ($this->agruparPesosPorDiametro($elementosEnMaquina) as $diametro => $pesoNecesarioTotal) {
                        if ($maquina->tipo == 'ensambladora' && (int)$diametro !== 5) {
                            continue;
                        }

                        $productosPorDiametro = $maquina->productos()
                            ->whereHas('productoBase', fn($q) => $q->where('diametro', $diametro))
                            ->orderBy('peso_stock')
                            ->get();

                        if ($productosPorDiametro->isEmpty()) {
                            throw new RuntimeException("No se encontraron materias primas para el diámetro {$diametro}.");
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
                            $productoBase = ProductoBase::where('diametro', $diametro)
                                ->where('tipo', $maquina->tipo_material)
                                ->first();

                            if ($productoBase) {
                                $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina, null, $operario1);
                                throw new RuntimeException("No hay suficiente materia prima para el diámetro {$diametro} en la máquina {$maquina->nombre}.");
                            }

                            Log::warning("No se encontró ProductoBase para diámetro {$diametro} y tipo {$maquina->tipo_material}");
                            throw new RuntimeException("No hay suficiente materia prima para el diámetro {$diametro} en la máquina {$maquina->nombre}.");
                        }
                    }

                    foreach ($elementosEnMaquina as $elemento) {
                        $pesoRestanteElemento = $elemento->peso;
                        $consumosDisponibles = $consumos[$elemento->diametro] ?? [];
                        $productosAsignados = [];

                        while ($pesoRestanteElemento > 0 && count($consumosDisponibles) > 0) {
                            $consumo = &$consumosDisponibles[0];

                            if ($consumo['consumido'] <= $pesoRestanteElemento) {
                                $productosAsignados[] = $consumo['producto_id'];
                                $pesoRestanteElemento -= $consumo['consumido'];
                                array_shift($consumosDisponibles);
                            } else {
                                $productosAsignados[] = $consumo['producto_id'];
                                $consumo['consumido'] -= $pesoRestanteElemento;
                                $pesoRestanteElemento = 0;
                            }
                        }

                        $elemento->producto_id = $productosAsignados[0] ?? null;
                        $elemento->producto_id_2 = $productosAsignados[1] ?? null;
                        $elemento->producto_id_3 = $productosAsignados[2] ?? null;
                        $elemento->save();
                    }

                    break;

                case 'soldando':
                    $etiqueta->fecha_finalizacion_soldadura = now();
                    $etiqueta->estado = 'completada';
                    $etiqueta->save();
                    break;

                case 'doblando':
                    $etiqueta->fecha_finalizacion_soldadura = now();
                    $etiqueta->estado = 'completada';
                    $etiqueta->save();
                    break;

                case 'completada':
                    throw new RuntimeException('Etiqueta ya completada.');

                default:
                    throw new RuntimeException('Estado desconocido de la etiqueta.');
            }

            // Post-actualización: Reglas adicionales (similar a actualizarElementosYConsumos)
            $this->postActualizacionColasYEstados($maquina, $etiqueta, $planilla);

            // Resultado
            $etiqueta->refresh();
            return new ActualizarEtiquetaResultado(
                etiqueta: $etiqueta,
                warnings: $warnings,
                productosAfectados: $productosAfectados,
                metricas: [
                    'maquina_id' => $maquina->id,
                    'peso_total_maquina' => $pesoTotalMaquina,
                    'elementos_totales_etiqueta' => $numeroElementosTotalesEnEtiqueta,
                ]
            );
        });
    }

    private function actualizarElementosYConsumos($elementosEnMaquina, Maquina $maquina, Etiqueta &$etiqueta, array &$warnings, int &$numeroElementosCompletadosEnMaquina, bool $enOtrasMaquinas, array &$productosAfectados, ?Planilla $planilla): void
    {
        $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->count();

        $consumos = [];
        foreach ($elementosEnMaquina->groupBy('diametro') as $diametro => $elementos) {
            if ($maquina->tipo == 'ensambladora' && (int)$diametro !== 5) {
                continue;
            }
            $pesoNecesarioTotal = $elementos->sum('peso');

            $productosPorDiametro = $maquina->productos()
                ->whereHas('productoBase', function ($query) use ($diametro) {
                    $query->where('diametro', $diametro);
                })
                ->with('productoBase')
                ->orderBy('peso_stock')
                ->get();

            if ($productosPorDiametro->isEmpty()) {
                // Sin materias en máquina: solicitar recarga y continuar sin bloquear
                $productoBase = ProductoBase::where('diametro', $diametro)
                    ->where('tipo', $maquina->tipo_material)
                    ->first();
                if ($productoBase) {
                    $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina, null, $etiqueta->operario1_id ?? auth()->id());
                } else {
                    Log::warning("No se encontró ProductoBase Ø{$diametro} / tipo {$maquina->tipo_material}");
                }
                $warnings[] = "No hay materias primas disponibles para Ø{$diametro} mm. Se ha solicitado recarga automáticamente.";
                // No generamos consumos para este diámetro
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
                // Stock insuficiente: solicitar recarga, avisar y seguir sin bloquear
                $productoBase = ProductoBase::where('diametro', $diametro)
                    ->where('tipo', $maquina->tipo_material)
                    ->first();

                if ($productoBase) {
                    $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina, null, $etiqueta->operario1_id ?? auth()->id());
                } else {
                    Log::warning("No se encontró ProductoBase Ø{$diametro} / tipo {$maquina->tipo_material}");
                }

                $warnings[] = "Stock insuficiente para Ø{$diametro} mm en la máquina {$maquina->nombre}. Se ha solicitado recarga.";
                // Continuamos sin lanzar excepción; los elementos de este diámetro no se marcarán como 'fabricado'
            }
        }

        foreach ($elementosEnMaquina as $elemento) {
            $pesoRestanteElemento = $elemento->peso;
            $consumosDisponibles = $consumos[$elemento->diametro] ?? [];
            $productosAsignados = [];

            while ($pesoRestanteElemento > 0 && count($consumosDisponibles) > 0) {
                $consumo = &$consumosDisponibles[0];

                if ($consumo['consumido'] <= $pesoRestanteElemento) {
                    $productosAsignados[] = $consumo['producto_id'];
                    $pesoRestanteElemento -= $consumo['consumido'];
                    array_shift($consumosDisponibles);
                } else {
                    $productosAsignados[] = $consumo['producto_id'];
                    $consumo['consumido'] -= $pesoRestanteElemento;
                    $pesoRestanteElemento = 0;
                }
            }

            $elemento->producto_id = $productosAsignados[0] ?? null;
            $elemento->producto_id_2 = $productosAsignados[1] ?? null;
            $elemento->producto_id_3 = $productosAsignados[2] ?? null;
            $elemento->save();
        }

        // Reglas según ensambladoText y nombre etiqueta se manejan en postActualizacionColasYEstados
    }

    private function postActualizacionColasYEstados(Maquina $maquina, Etiqueta $etiqueta, ?Planilla $planilla): void
    {
        if (!$planilla) {
            return;
        }

        $ensambladoText = Str::of((string) $planilla->ensamblado)->lower();
        $elementosEnMaquina = $etiqueta->elementos()
            ->where(function ($q) use ($maquina) {
                $q->where('maquina_id', $maquina->id)
                    ->orWhere('maquina_id_2', $maquina->id);
            })
            ->get();

        if ($ensambladoText->contains('taller')) {
            // Regla TALLER
            if (!$planilla->comentario || (!Str::of($planilla->comentario)->contains('amarrado') && !Str::of($planilla->comentario)->contains('ensamblado amarrado'))) {
                $etiqueta->estado = 'fabricada';
                $etiqueta->fecha_finalizacion = now();
                $this->actualizarPesoEtiqueta($etiqueta);
                $etiqueta->save();
            }
        } elseif ($ensambladoText->contains('carcasas')) {
            // Regla CARCASAS
            $etiqueta->estado = $maquina->tipo === 'estribadora' ? 'fabricada' : 'completada';
            $etiqueta->fecha_finalizacion = now();
            $etiqueta->save();

            if ($maquina->tipo !== 'cortadora_dobladora') {
                $maquinaEnsambladora = Maquina::where('tipo', 'ensambladora')->first();
                if ($maquinaEnsambladora) {
                    $algunoAsignado = false;
                    foreach ($elementosEnMaquina as $elemento) {
                        if (is_null($elemento->maquina_id_2)) {
                            $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                            $elemento->save();
                            $algunoAsignado = true;
                        }
                    }
                    // Actualizar estado2 de la etiqueta si se asignó algún elemento
                    if ($algunoAsignado && is_null($etiqueta->estado2)) {
                        $etiqueta->estado2 = 'pendiente';
                        $etiqueta->save();
                    }
                }
            }
        } else if (Str::of((string) $etiqueta->nombre)->lower()->contains('pates')) {
            // Regla PATES
            DB::transaction(function () use ($etiqueta, $maquina, $planilla) {
                $etiqueta->estado = 'fabricada';
                $etiqueta->fecha_finalizacion = now();
                $this->actualizarPesoEtiqueta($etiqueta);
                $etiqueta->save();

                $dobladora = Maquina::where('tipo', 'dobladora_manual')
                    ->when($maquina->obra_id, fn($q) => $q->where('obra_id', $maquina->obra_id))
                    ->orderBy('id')
                    ->first();

                if ($dobladora) {
                    Elemento::where('etiqueta_sub_id', $etiqueta->etiqueta_sub_id)
                        ->where('maquina_id', $maquina->id)
                        ->update(['maquina_id_2' => $dobladora->id]);

                    // Actualizar estado2 de la etiqueta
                    $etiqueta->estado2 = 'pendiente';
                    $etiqueta->save();

                    $planillaId = $etiqueta->planilla_id ?? optional($etiqueta->planilla)->id;
                    if ($planillaId) {
                        self::encolarPlanillaEnMaquina($dobladora->id, $planillaId);
                    } else {
                        Log::warning('No se pudo encolar planilla en dobladora: planilla_id nulo', [
                            'etiqueta_id' => $etiqueta->id ?? null,
                            'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id ?? null,
                            'dobladora_id' => $dobladora->id,
                        ]);
                    }
                } else {
                    Log::warning('No hay dobladora_manual para asignar maquina_id_2', [
                        'maquina_origen_id' => $maquina->id,
                        'etiqueta_sub_id'   => $etiqueta->etiqueta_sub_id,
                    ]);
                }
            });
        } else {
            // Lógica normal: marcar etiqueta como completada
            $etiqueta->estado = 'completada';
            $etiqueta->fecha_finalizacion = now();
            $etiqueta->save();
            }
        }

        // ❌ DESHABILITADO: La verificación automática de paquetes y eliminación de planilla
        // ahora se hace manualmente desde la vista de máquina con el botón "Planilla Completada"
        /*
        // Si ya no quedan elementos de esta planilla en esta máquina, gestionar cola
        $quedanPendientesEnEstaMaquina = Elemento::where('planilla_id', $planilla->id)
            ->where('maquina_id', $maquina->id)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '!=', 'fabricado');
            })
            ->exists();

        if (!$quedanPendientesEnEstaMaquina) {
            $todasEtiquetasEnPaquete = $planilla->etiquetas()
                ->whereDoesntHave('paquete')
                ->doesntExist();

            if ($todasEtiquetasEnPaquete) {
                DB::transaction(function () use ($planilla, $maquina) {
                    $registro = OrdenPlanilla::where('planilla_id', $planilla->id)
                        ->where('maquina_id', $maquina->id)
                        ->lockForUpdate()
                        ->first();

                    if ($registro) {
                        $posicionEliminada = $registro->posicion;
                        $registro->delete();

                        OrdenPlanilla::where('maquina_id', $maquina->id)
                            ->where('posicion', '>', $posicionEliminada)
                            ->decrement('posicion');
                    }
                });
            }
        }
        */

        // NOTA: La planilla se completa manualmente cuando el usuario lo indique
    }

    private function generarMovimientoRecargaMateriaPrima(ProductoBase $productoBase, Maquina $maquina, ?int $productoId = null, ?int $solicitadoPor = null): void
    {
        Movimiento::create([
            'tipo'              => 'Recarga materia prima',
            'maquina_origen'    => null,
            'maquina_destino'   => $maquina->id,
            'nave_id'           => $maquina->obra_id, // Nave donde se ejecuta el movimiento
            'producto_id'       => $productoId,
            'producto_base_id'  => $productoBase->id,
            'estado'            => 'pendiente',
            'descripcion'       => 'Se solicita materia prima del tipo '
                . strtolower($productoBase->tipo)
                . ' (Ø' . $productoBase->diametro . ', ' . $productoBase->longitud . ' mm) '
                . 'en la máquina ' . $maquina->nombre,
            'prioridad'         => 1,
            'fecha_solicitud'   => now(),
            'solicitado_por'    => $solicitadoPor ?? auth()->id(),
        ]);
    }

    private function generarMovimientoEtiqueta(Maquina $origen, Maquina $destino, int $etiquetaSubId, ?int $planillaId = null): void
    {
        $referencia = "etiqueta_sub {$etiquetaSubId}";

        $yaExiste = Movimiento::where('tipo', 'Movimiento paquete')
            ->where('estado', 'pendiente')
            ->where('maquina_origen',  $origen->id)
            ->where('maquina_destino', $destino->id)
            ->where('descripcion', 'like', "%{$referencia}%")
            ->lockForUpdate()
            ->exists();

        if ($yaExiste) {
            return;
        }

        Movimiento::create([
            'tipo'             => 'Movimiento paquete',
            'maquina_origen'   => $origen->id,
            'maquina_destino'  => $destino->id,
            'producto_id'      => null,
            'producto_base_id' => null,
            'estado'           => 'pendiente',
            'descripcion'      => 'Trasladar ' . $referencia
                . ($planillaId ? " (planilla {$planillaId})" : '')
                . ' desde ' . $origen->nombre . ' hasta ' . $destino->nombre . '.',
            'prioridad'        => 1,
            'fecha_solicitud'  => now(),
            'solicitado_por'   => auth()->id(),
        ]);
    }
}
