<?php

namespace App\Servicios\Etiquetas\Servicios;

use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Producto;
use App\Models\ProductoBase;
use App\Servicios\Etiquetas\Base\ServicioEtiquetaBase;
use App\Servicios\Etiquetas\Contratos\EtiquetaServicio;
use App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos;
use App\Servicios\Etiquetas\Resultados\ActualizarEtiquetaResultado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Servicios\Exceptions\ServicioEtiquetaException;
use Illuminate\Support\Str;
use App\Logger\CorteBarraLogger;

class CortadoraDobladoraBarraEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    private const MERMA_POR_CORTE_M = 0.00; // m/corte (ajustable)
    private const EPS = 1e-6;

    public function actualizar(ActualizarEtiquetaDatos $datos): ActualizarEtiquetaResultado
    {

        return DB::transaction(function () use ($datos) {
            $maquina = Maquina::findOrFail($datos->maquinaId);
            if ($maquina->tipo_material !== 'barra') {
                throw new ServicioEtiquetaException('Servicio exclusivo para máquinas de barras.');
            }
            if (is_null($datos->longitudSeleccionada)) {
                throw new ServicioEtiquetaException('Falta la longitud de barra.');
            }

            $etiqueta = Etiqueta::with('planilla')
                ->where('etiqueta_sub_id', $datos->etiquetaSubId)
                ->lockForUpdate()
                ->firstOrFail();

            $planilla = $etiqueta->planilla_id ? Planilla::find($etiqueta->planilla_id) : null;

            $avisos = [];
            $productosAfectados = [];

            $elementosEnMaquina = $etiqueta->elementos()
                ->where(fn($q) => $q->where('maquina_id', $maquina->id)
                    ->orWhere('maquina_id_2', $maquina->id))
                ->get();

            if ($elementosEnMaquina->isEmpty()) {
                throw new ServicioEtiquetaException('No hay elementos de esta etiqueta en la máquina.');
            }
            // 👇 aquí tienes el diámetro real de la etiqueta que se está fabricando
            $diametroElemento = (int) $elementosEnMaquina->first()->diametro;
            // 🔑 Normaliza longitud: nunca null a partir de aquí
            $producto = $this->encontrarProductoBarraPorDiametroYLongitud(
                $maquina,
                $diametroElemento,
                $datos->longitudSeleccionada
            );
            $longitudBarraSeleccionada = $producto->productoBase->longitud;

            switch ($etiqueta->estado) {
                case 'pendiente':
                    if (!$planilla) {
                        throw new ServicioEtiquetaException('La etiqueta no tiene planilla asociada.');
                    }
                    if (is_null($planilla->fecha_inicio)) {
                        $planilla->fecha_inicio = now();
                        $planilla->estado = 'fabricando';
                        $planilla->save();
                    }

                    // Primer clic: Asignar el producto actual a cada elemento
                    foreach ($elementosEnMaquina as $e) {
                        $e->estado = 'fabricando';
                        $e->producto_id = $producto->id; // Guardar producto del primer clic
                        $e->save();
                    }

                    // Asignar el producto/colada actual de la máquina a la etiqueta (primer clic)
                    $etiqueta->producto_id = $producto->id;

                    $etiqueta->estado       = 'fabricando';
                    $etiqueta->operario1_id = $datos->operario1Id;
                    $etiqueta->operario2_id = $datos->operario2Id;
                    $etiqueta->fecha_inicio = now();
                    $etiqueta->save();
                    break;

                case 'fabricando':
                    $quedan = $elementosEnMaquina->contains(fn($e) => !in_array($e->estado, ['fabricado', 'completado'], true));
                    if (!$quedan) {
                        throw new ServicioEtiquetaException('Todos los elementos ya están completados en esta máquina.');
                    }

                    // Segundo clic: Verificar si el producto/colada cambió desde el primer clic
                    // Igual que en CortadoraDobladoraEncarretadoEtiquetaServicio
                    $productoActual = $producto; // El producto encontrado para este diámetro y longitud

                    // Actualizar etiqueta si el producto cambió
                    if ($etiqueta->producto_id && $etiqueta->producto_id != $productoActual->id) {
                        if (!$etiqueta->producto_id_2) {
                            $etiqueta->producto_id_2 = $productoActual->id;
                        } elseif ($etiqueta->producto_id_2 != $productoActual->id && !$etiqueta->producto_id_3) {
                            $etiqueta->producto_id_3 = $productoActual->id;
                        }
                    } elseif (!$etiqueta->producto_id) {
                        $etiqueta->producto_id = $productoActual->id;
                    }
                    $etiqueta->save();

                    // Actualizar elementos si el producto cambió (igual que en encarretado)
                    foreach ($elementosEnMaquina as $elemento) {
                        if ($elemento->producto_id && $elemento->producto_id != $productoActual->id) {
                            // El producto cambió desde el primer clic
                            if (!$elemento->producto_id_2) {
                                $elemento->producto_id_2 = $productoActual->id;
                            } elseif ($elemento->producto_id_2 != $productoActual->id && !$elemento->producto_id_3) {
                                $elemento->producto_id_3 = $productoActual->id;
                            }
                        } elseif (!$elemento->producto_id) {
                            $elemento->producto_id = $productoActual->id;
                        }
                        $elemento->save();
                    }

                    $this->consumirPorBarras(
                        $elementosEnMaquina,
                        $maquina,
                        $etiqueta,
                        $longitudBarraSeleccionada,
                        $diametroElemento,
                        $avisos,
                        $productosAfectados,
                        $datos
                    );

                    // ✅ LÓGICA DE COMPLETADO (elementos, etiqueta y planilla)
                    $this->evaluarYActualizarEstados($etiqueta, $maquina, $elementosEnMaquina, $planilla);
                    break;

                case 'fabricada':
                case 'parcialmente completada':
                    // Derivar automáticamente a dobladora manual si aplica
                    $dobladora = Maquina::where('tipo', 'dobladora_manual')
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
                    throw new ServicioEtiquetaException('Etiqueta ya completada.');
            }

            $etiqueta->refresh();

            return new ActualizarEtiquetaResultado(
                etiqueta: $etiqueta,
                warnings: $avisos,
                productosAfectados: $productosAfectados,
                metricas: ['maquina_id' => $maquina->id]
            );
        });
    }

    // ----------------------------
    // Consumo por barras (packing)
    // ----------------------------



    private function consumirPorBarras(
        $elementos,
        Maquina $maquina,
        Etiqueta $etiqueta,
        int $longitudBarraSeleccionada,   // metros (p.ej. 12)
        int $diametroSeleccionado,        // mm
        array &$avisos,
        array &$productosAfectados,
        ActualizarEtiquetaDatos $datos
    ): void {
        // Obtener desperdicio manual desde las opciones (en cm, convertir a metros)
        $desperdicioManualCm = $datos->opciones['desperdicio_manual_cm'] ?? null;
        $desperdicioManualM = $desperdicioManualCm !== null ? (float) $desperdicioManualCm / 100 : null;
        $porDiametro = [];
        foreach ($elementos as $el) {
            $diam = (int) $el->diametro;
            $lenM = max(0, ((float) $el->longitud) / 100.0); // BD en cm → m
            if ($lenM <= 0) continue;

            $porDiametro[$diam]['elementos'][]  = $el;
        }

        foreach ($porDiametro as $diametro => $grupo) {
            $elementosGrupo = $grupo['elementos'] ?? [];
            if (empty($elementosGrupo)) continue;

            // === Peso teórico por barra (kg) ===
            $area_m2 = (pi() * pow($diametroSeleccionado, 2)) / 4 / 1_000_000;
            $pesoPorMetro = $area_m2 * 7850; // kg/m
            $pesoBarraCompletoKg = $pesoPorMetro * $longitudBarraSeleccionada; // kg/barra completa

            // Si hay desperdicio manual, calcular el peso real a consumir por barra
            // El desperdicio manual representa la sobra real (en metros)
            if ($desperdicioManualM !== null && $desperdicioManualM >= 0) {
                // La longitud realmente usada = longitud barra - desperdicio
                $longitudUsadaM = max(0, $longitudBarraSeleccionada - $desperdicioManualM);
                $pesoBarraEstimado = $pesoPorMetro * $longitudUsadaM; // kg a consumir
                Log::info('📏 Consumo con desperdicio manual', [
                    'desperdicio_manual_cm' => $desperdicioManualM * 100,
                    'longitud_barra_m' => $longitudBarraSeleccionada,
                    'longitud_usada_m' => $longitudUsadaM,
                    'peso_barra_completa_kg' => round($pesoBarraCompletoKg, 3),
                    'peso_a_consumir_kg' => round($pesoBarraEstimado, 3),
                ]);
            } else {
                // Sin desperdicio manual, usar peso de barra completa (comportamiento original)
                $pesoBarraEstimado = $pesoBarraCompletoKg;
            }

            // === Productos disponibles (mismo diámetro, misma longitud, tipo barra) ===
            $productos = $maquina->productos()
                ->whereHas('productoBase', fn($q) => $q
                    ->where('diametro', $diametro)
                    ->where('longitud', $longitudBarraSeleccionada)
                    ->where('tipo', 'barra'))
                ->with('productoBase')
                ->orderBy('peso_stock') // consumimos primero los más bajos para vaciar lotes
                ->get();

            // === Recorremos elemento a elemento (cada elemento trae su cantidad 'barras' = nº de piezas) ===
            foreach ($elementosGrupo as $elemento) {
                $longitudPiezaM   = $elemento->longitud / 100; // m
                $cantidadPiezas   = max(1, (int) ($elemento->barras ?? 1)); // nº piezas a fabricar
                $piezasPorBarra   = (int) floor($longitudBarraSeleccionada / $longitudPiezaM);

                if ($piezasPorBarra <= 0) {
                    $avisos[] = "❌ El elemento ID {$elemento->id} de {$longitudPiezaM}m no cabe en barra de {$longitudBarraSeleccionada}m.";
                    Log::warning("❌ Elemento ID {$elemento->id} de {$longitudPiezaM}m no cabe en barra de {$longitudBarraSeleccionada}m");
                    continue;
                }

                $barrasNecesarias = (int) ceil($cantidadPiezas / $piezasPorBarra);
                $pesoTotalElemento = $barrasNecesarias * $pesoBarraEstimado;

                // Para asignación final al elemento: guardamos hasta 3 productos distintos usados
                $productosAsignados = [];
                $piezasPendientes   = $cantidadPiezas;

                // === Consumimos barra a barra para este elemento ===
                for ($i = 0; $i < $barrasNecesarias; $i++) {
                    $pendienteKg      = $pesoBarraEstimado;               // peso completo de UNA barra
                    $productoUsadoBar = null;                              // id del primer producto que aporte a esta barra
                    $piezasEstaBarra  = min($piezasPorBarra, $piezasPendientes);

                    foreach ($productos as $prod) {
                        $disp = (float) ($prod->peso_stock ?? 0);
                        if ($disp <= 0) continue;

                        $consumo = min($disp, $pendienteKg);
                        if ($consumo > 0) {
                            // Primer producto que contribuye a esta barra = producto "asignado" a la barra
                            if ($productoUsadoBar === null) {
                                $productoUsadoBar = $prod->id;
                            }

                            $prod->peso_stock -= $consumo;
                            $pendienteKg      -= $consumo;

                            if ($prod->peso_stock <= self::EPS) {
                                $prod->peso_stock = 0;
                                $prod->estado = 'consumido';
                                $prod->ubicacion_id = null;
                                $prod->maquina_id = null;
                            }

                            $prod->save();

                            $productosAfectados[] = [
                                'id'           => $prod->id,
                                'peso_stock'   => $prod->peso_stock,
                                'peso_inicial' => $prod->peso_inicial ?? null,
                            ];

                            if ($pendienteKg <= self::EPS) {
                                break;
                            }
                        }
                    }

                    // Si no hemos logrado completar el peso de la barra, pedimos recarga y lo registramos
                    if ($pendienteKg > self::EPS) {
                        $pb = ProductoBase::where('diametro', $diametro)
                            ->where('longitud', $longitudBarraSeleccionada)
                            ->where('tipo', 'barra')
                            ->first();

                        if ($pb) {
                            try {
                                $this->generarMovimientoRecargaMateriaPrima($pb, $maquina, null, $etiqueta->operario1_id ?? auth()->id());
                                Log::warning('⚠️ Recarga solicitada: peso insuficiente para terminar una barra', [
                                    'maquina_id'        => $maquina->id,
                                    'producto_base_id'  => $pb->id,
                                    'faltan_kg'         => $pendienteKg,
                                    'elemento_id'       => $elemento->id,
                                    'barra_indice'      => $i,
                                ]);
                            } catch (\Throwable $e) {
                                Log::error('❌ Error solicitando recarga', ['error' => $e->getMessage()]);
                            }
                        }

                        // Aviso para interfaz si quieres mostrarlo
                        $avisos[] = "No se pudo completar una barra para el elemento {$elemento->id}. Faltan ~" . round($pendienteKg, 3) . " kg.";
                    }

                    // Guardamos el producto usado para esta barra (solo para el log)
                    if ($productoUsadoBar !== null && !in_array($productoUsadoBar, $productosAsignados, true)) {
                        $productosAsignados[] = $productoUsadoBar;
                    }

                    // Reducimos piezas pendientes
                    $piezasPendientes -= $piezasEstaBarra;
                    if ($piezasPendientes <= 0) {
                        break; // este elemento ya está cubierto
                    }
                }

                // Marcar elemento como fabricado (las asignaciones de producto ya se hicieron antes)
                $elemento->estado = 'fabricado';
                $elemento->save();
                // ============================
                // === CALCULAR SOBRA TOTAL
                // Si hay desperdicio manual, usarlo; si no, calcular teórico
                if ($desperdicioManualCm !== null) {
                    // Usar desperdicio manual (ya está en cm)
                    $sobranteCm = (float) $desperdicioManualCm;
                } else {
                    // Calcular teórico: longitud no utilizada en última barra
                    $sumaLongitudesPiezas = $cantidadPiezas * $longitudPiezaM;
                    $longitudTotalBarras = $barrasNecesarias * $longitudBarraSeleccionada;
                    $sobraTotalM = max(0, $longitudTotalBarras - $sumaLongitudesPiezas);
                    $sobranteCm = round($sobraTotalM * 100, 2);
                }

                // === Recuperar patrón de letras si viene en opciones ===
                $patronLetras = $datos->opciones['patron_letras'] ?? implode(' + ', array_fill(0, $piezasPorBarra, 'A'));

                // Obtener códigos de los productos asignados al elemento
                $codigoProducto1 = optional(Producto::find($elemento->producto_id))->codigo;
                $codigoProducto2 = $elemento->producto_id_2 ? optional(Producto::find($elemento->producto_id_2))->codigo : null;
                $codigoProducto3 = $elemento->producto_id_3 ? optional(Producto::find($elemento->producto_id_3))->codigo : null;
                $materiaPrima = implode(', ', array_filter([$codigoProducto1, $codigoProducto2, $codigoProducto3]));

                app(CorteBarraLogger::class)->registrar([
                    'timestamp'         => now()->toDateTimeString(),
                    'Operario'          => auth()->user()->nombre_completo ?? null,
                    'Cód. Planilla'     => $etiqueta->planilla->codigo_limpio ?? null,
                    'Cód. Etiqueta'     => $etiqueta->etiqueta_sub_id,
                    'Cód. Elemento'     => $elemento->codigo,
                    'Máquina'           => $maquina->nombre,
                    'Materia prima'     => $materiaPrima ?: 'N/A',
                    'Diametro'          => $diametro,
                    'Longitud pieza (m)' => $longitudPiezaM,
                    'Longitud barra (m)' => $longitudBarraSeleccionada,
                    'Piezas/barra'      => $piezasPorBarra,
                    'Piezas fabricadas' => $cantidadPiezas,
                    'Barras usadas'     => $barrasNecesarias,
                    'Patrón'            => $patronLetras,
                    'Sobrante (cm)'     => $sobranteCm,
                    'Tipo sobrante'     => $desperdicioManualCm !== null ? 'manual' : 'teórico',
                    'comentario'        => 'corte simple',
                ]);
            }
        }
    }





    // ----------------------------
    // Estados: elementos/etiqueta/planilla
    // ----------------------------

    private function evaluarYActualizarEstados(Etiqueta $etiqueta, Maquina $maquina, $elementosEnMaquina, ?Planilla $planilla): void
    {
        // Contadores
        $numCompletadosEnMaquina = $elementosEnMaquina->where('estado', 'fabricado')->count();
        $totalEnMaquina          = $elementosEnMaquina->count();
        foreach ($elementosEnMaquina as $elemento) {
            $elemento->estado = "fabricado";
            $elemento->save();
        }
        $textoEnsamblado = strtolower($etiqueta->planilla->ensamblado ?? '');
        $comentarioPlanilla = strtolower($planilla->comentario ?? '');

        // === Reglas especiales ===
        if (str_contains($textoEnsamblado, 'taller')) {
            // Si están completados todos los de esta máquina
            if ($totalEnMaquina > 0 && $numCompletadosEnMaquina >= $totalEnMaquina) {

                $etiqueta->estado = 'fabricada';
                $etiqueta->fecha_finalizacion = now();
                $this->actualizarPesoEtiqueta($etiqueta);

                $etiqueta->save();
            }

            // Encolar a soldadora (maquina_id_3) si es posible
            $soldadora = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                ->when($maquina->obra_id, fn($q) => $q->where('obra_id', $maquina->obra_id))
                ->orderBy('id')
                ->first();

            if ($soldadora) {
                foreach ($elementosEnMaquina as $el) {
                    $el->maquina_id_3 = $soldadora->id;
                    $el->save();
                }
            } else {
                Log::warning('No se encontró soldadora disponible para taller.', ['etiqueta' => $etiqueta->etiqueta_sub_id]);
            }
        } elseif (str_contains($textoEnsamblado, 'carcasas')) {
            // Completos todos excepto Ø5
            $completosSin5 = $etiqueta->elementos()
                ->where('diametro', '!=', 5.00)
                ->where('estado', '!=', 'fabricado')
                ->doesntExist();

            if ($completosSin5) {
                $etiqueta->estado = $maquina->tipo === 'estribadora' ? 'fabricada' : 'completada';
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();
            }

            // Si no estamos en cortadora_dobladora, enviar a ensambladora como segunda máquina
            if ($maquina->tipo !== 'cortadora_dobladora') {
                $ensambladora = Maquina::where('tipo', 'ensambladora')->first();
                if ($ensambladora) {
                    foreach ($elementosEnMaquina as $el) {
                        if (is_null($el->maquina_id_2)) {
                            $el->maquina_id_2 = $ensambladora->id;
                            $el->save();
                        }
                    }
                }
            }
        } elseif (Str::of($etiqueta->nombre ?? '')->lower()->contains('pates')) {
            // Regla "pates": marcar fabricada y encolar en dobladora manual + cola de orden_planillas
            DB::transaction(function () use ($etiqueta, $maquina) {
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

                    $planillaId = $etiqueta->planilla_id ?? optional($etiqueta->planilla)->id;
                    if ($planillaId) {
                        $yaExiste = OrdenPlanilla::where('maquina_id', $dobladora->id)
                            ->where('planilla_id', $planillaId)
                            ->lockForUpdate()
                            ->exists();

                        if (!$yaExiste) {
                            $ultimaPos = OrdenPlanilla::where('maquina_id', $dobladora->id)
                                ->select('posicion')
                                ->orderByDesc('posicion')
                                ->lockForUpdate()
                                ->value('posicion');

                            OrdenPlanilla::create([
                                'maquina_id'  => $dobladora->id,
                                'planilla_id' => $planillaId,
                                'posicion'    => is_null($ultimaPos) ? 0 : ($ultimaPos + 1),
                            ]);
                        }
                    }
                } else {
                    Log::warning('No hay dobladora manual disponible para "pates".', ['etiqueta' => $etiqueta->etiqueta_sub_id]);
                }
            });
        } else {
            // Lógica normal: si todos los elementos de la etiqueta están "fabricado" -> completada
            $todosElementosEtiquetaFabricados = $etiqueta->elementos()
                ->where('estado', '!=', 'fabricado')
                ->doesntExist();

            if ($todosElementosEtiquetaFabricados) {
                $etiqueta->estado = 'completada';
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();
            }
        }
    }

    // ----------------------------
    // Utilidades
    // ----------------------------

    /**
     * Resuelve la longitud de barra para un diámetro concreto **disponible en la máquina**.
     * - Filtra por producto base: diametro + tipo=barra + (en esta máquina).
     * - Si el usuario ha elegido una longitud, se valida que exista para ese diámetro.
     * - Si hay varias y no hay selección, se lanza excepción con las opciones.
     */

    private function encontrarProductoBarraPorDiametroYLongitud(Maquina $maquina, int $diametroMm, int $longitudMm): Producto
    {
        $longitudM = intdiv($longitudMm, 100); // o floor($longitudMm / 1000)

        $producto = $maquina->productos()
            ->whereHas('productoBase', function ($q) use ($diametroMm, $longitudM) {
                $q->where('diametro', $diametroMm)
                    ->where('tipo', 'barra')
                    ->where('longitud', $longitudM);
            })
            ->with('productoBase:id,diametro,longitud')
            ->first();

        if (!$producto) {
            response()->json([
                'success' => false,
                'message' => "No se encontró ferralla en barra en la máquina con Ø{$diametroMm} mm y longitud {$longitudM} m.",
            ], 404)->throwResponse();
        }

        return $producto;
    }


    private function calcularKgNecesariosParaEtiqueta($elementos, int $longitudBarraM, int $diametroMm): array
    {
        // kg por metro (sección * densidad)
        $area_m2 = (pi() * pow($diametroMm, 2)) / 4 / 1_000_000;
        $kgPorMetro = $area_m2 * 7850;
        $kgPorBarra = $kgPorMetro * $longitudBarraM;

        $totalBarras = 0;
        foreach ($elementos as $el) {
            $longPiezaM = max(0, ((float)$el->longitud) / 100.0);
            if ($longPiezaM <= 0) {
                continue;
            }
            $piezas = max(1, (int)($el->barras ?? 1));
            $piezasPorBarra = (int)floor($longitudBarraM / $longPiezaM);
            if ($piezasPorBarra <= 0) {
                continue;
            }
            $totalBarras += (int)ceil($piezas / $piezasPorBarra);
        }

        return [
            'barras'       => $totalBarras,
            'kg_por_barra' => $kgPorBarra,
            'kg_total'     => $totalBarras * $kgPorBarra,
        ];
    }

    private function stockDisponibleKg(Maquina $maquina, int $diametroMm, int $longitudBarraM): float
    {
        return (float)$maquina->productos()
            ->whereHas('productoBase', fn($q) => $q
                ->where('diametro', $diametroMm)
                ->where('longitud', $longitudBarraM)
                ->where('tipo', 'barra'))
            ->sum('peso_stock');
    }

    private function precheckMateriaPrima(
        $elementosEnMaquina,
        Maquina $maquina,
        int $longitudBarraM,
        int $diametroMm,
        array &$avisos
    ): array {
        $need = $this->calcularKgNecesariosParaEtiqueta($elementosEnMaquina, $longitudBarraM, $diametroMm);
        $have = $this->stockDisponibleKg($maquina, $diametroMm, $longitudBarraM);

        $faltan = max(0, $need['kg_total'] - $have);
        $recargaId = null;

        if ($faltan > self::EPS) {
            $pb = ProductoBase::where('diametro', $diametroMm)
                ->where('longitud', $longitudBarraM)
                ->where('tipo', 'barra')
                ->first();

            $avisos[] = "Faltan aprox. " . round($faltan, 2) . " kg para completar la etiqueta.";
            if ($pb) {
                try {
                    $mov = $this->generarMovimientoRecargaMateriaPrima($pb, $maquina, null, auth()->id());
                    $recargaId = $mov->id ?? null;
                    Log::warning('⚠️ Precheck: recarga solicitada', [
                        'movimiento_id'   => $recargaId,
                        'maquina_id'      => $maquina->id,
                        'producto_base_id' => $pb->id,
                        'faltan_kg'       => $faltan,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Precheck: error al solicitar recarga', ['error' => $e->getMessage()]);
                }
            }
        }

        return [
            'kg_necesarios' => $need['kg_total'],
            'kg_disponibles' => $have,
            'kg_faltantes'  => $faltan,
            'recarga_id'    => $recargaId,
            'barras'        => $need['barras'],
            'kg_por_barra'  => $need['kg_por_barra'],
        ];
    }

    private function kgPorMetro(int $diametroMm): float
    {
        // kg/m ≈ d^2 / 162.28
        return ($diametroMm * $diametroMm) / 162.28;
    }

    private function empaquetarEnBarras(array $longitudesM, float $longitudBarraSeleccionada): array

    {
        rsort($longitudesM); // descendente
        $barras = [];
        foreach ($longitudesM as $len) {
            $puesto = false;
            foreach ($barras as &$barra) {
                $suma = array_sum($barra);
                $cortes = count($barra);
                $ocupado = $suma + max(0, $cortes) * self::MERMA_POR_CORTE_M;
                if ($ocupado + $len + self::MERMA_POR_CORTE_M <= $longitudBarraSeleccionada + self::EPS) {
                    $barra[] = $len;
                    $puesto = true;
                    break;
                }
            }
            if (!$puesto) $barras[] = [$len];
        }
        return $barras;
    }
}
