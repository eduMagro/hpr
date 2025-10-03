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

class CortadoraDobladoraBarraEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    private const MERMA_POR_CORTE_M = 0.01; // m/corte (ajustable)
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
                    foreach ($elementosEnMaquina as $e) {
                        $e->estado = 'fabricando';
                        $e->save();
                    }
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
                    log::info('Etiqueta en proceso de fabricación.');
                    $this->consumirPorBarras(
                        $elementosEnMaquina,
                        $maquina,
                        $etiqueta,
                        $longitudBarraSeleccionada,
                        $diametroElemento,
                        $avisos,
                        $productosAfectados
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
        array &$productosAfectados
    ): void {
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

            Log::info("\n📦 Preparando consumo para Ø{$diametro}mm: " . count($elementosGrupo) . " elementos.");

            // === Peso teórico por barra (kg) ===
            $area_m2 = (pi() * pow($diametroSeleccionado, 2)) / 4 / 1_000_000;
            $pesoPorMetro = $area_m2 * 7850; // kg/m
            $pesoBarraEstimado = $pesoPorMetro * $longitudBarraSeleccionada; // kg/barra
            Log::info(sprintf("📐 Peso teórico para Ø%dmm y %.2fm: %.3f kg", $diametroSeleccionado, $longitudBarraSeleccionada, $pesoBarraEstimado));

            // === Productos disponibles (mismo diámetro, misma longitud, tipo barra) ===
            $productos = $maquina->productos()
                ->whereHas('productoBase', fn($q) => $q
                    ->where('diametro', $diametro)
                    ->where('longitud', $longitudBarraSeleccionada)
                    ->where('tipo', 'barra'))
                ->with('productoBase')
                ->orderBy('peso_stock') // consumimos primero los más bajos para vaciar lotes
                ->get();

            Log::info("📦 Productos disponibles para Ø{$diametro}mm:", $productos->pluck('id', 'peso_stock')->toArray());

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

                Log::info(sprintf(
                    "🧾 Elemento ID %d → %d piezas (%.2fm) → %d barras → %.3f kg/b → %.3f kg total",
                    $elemento->id,
                    $cantidadPiezas,
                    $longitudPiezaM,
                    $barrasNecesarias,
                    $pesoBarraEstimado,
                    $pesoTotalElemento
                ));

                // Para asignación final al elemento: guardamos hasta 3 productos distintos usados
                $productosAsignados = [];
                $piezasPendientes   = $cantidadPiezas;

                // === Consumimos barra a barra para este elemento ===
                for ($i = 0; $i < $barrasNecesarias; $i++) {
                    $pendienteKg      = $pesoBarraEstimado;               // peso completo de UNA barra
                    $productoUsadoBar = null;                              // id del primer producto que aporte a esta barra
                    $piezasEstaBarra  = min($piezasPorBarra, $piezasPendientes);

                    Log::info("🟡 Consumiendo barra #{$i} para elemento ID {$elemento->id}. Necesita {$pesoBarraEstimado} kg (para {$piezasEstaBarra} piezas)");

                    foreach ($productos as $prod) {
                        $disp = (float) ($prod->peso_stock ?? 0);
                        if ($disp <= 0) continue;

                        $consumo = min($disp, $pendienteKg);
                        if ($consumo > 0) {
                            Log::info("➡️ Producto ID {$prod->id}: disponible {$disp} kg, se consumen {$consumo} kg");

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
                                Log::info("🛑 Producto ID {$prod->id} agotado. Marcado como consumido.");
                            }

                            $prod->save();

                            $productosAfectados[] = [
                                'id'           => $prod->id,
                                'peso_stock'   => $prod->peso_stock,
                                'peso_inicial' => $prod->peso_inicial ?? null,
                            ];

                            if ($pendienteKg <= self::EPS) {
                                Log::info("✅ Barra #{$i} completada con producto ID {$prod->id}");
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

                    // Guardamos el producto usado para esta barra en la lista de asignados (máx. 3 distintos)
                    if ($productoUsadoBar !== null && !in_array($productoUsadoBar, $productosAsignados, true)) {
                        $productosAsignados[] = $productoUsadoBar;
                        if (count($productosAsignados) >= 3) {
                            // ya tenemos los 3 campos que admite el elemento
                            // seguimos consumiendo pero no añadimos más ids
                        }
                    }

                    // Reducimos piezas pendientes
                    $piezasPendientes -= $piezasEstaBarra;
                    if ($piezasPendientes <= 0) {
                        break; // este elemento ya está cubierto
                    }
                }

                // === Asignación final de productos al elemento (hasta 3 ids distintos usados) ===
                $elemento->producto_id   = $productosAsignados[0] ?? null;
                $elemento->producto_id_2 = $productosAsignados[1] ?? null;
                $elemento->producto_id_3 = $productosAsignados[2] ?? null;
                $elemento->estado        = 'fabricado';
                $elemento->save();

                Log::info(
                    "🔗 Elemento ID {$elemento->id} asignado a productos: "
                        . json_encode([
                            $elemento->producto_id,
                            $elemento->producto_id_2,
                            $elemento->producto_id_3
                        ])
                );
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

        // Si ya no quedan elementos de esta planilla en ESTA máquina, sacar de cola (orden_planillas)
        if ($planilla) {
            $quedanEnEstaMaquina = Elemento::where('planilla_id', $planilla->id)
                ->where(fn($q) => $q->where('maquina_id', $maquina->id)
                    ->orWhere('maquina_id_2', $maquina->id))
                ->where(fn($q) => $q->whereNull('estado')->orWhere('estado', '!=', 'fabricado'))
                ->exists();

            if (!$quedanEnEstaMaquina) {
                // opcional: exigir que todas las etiquetas tengan paquete asignado antes de retirar
                $todasEtiquetasEnPaquete = $planilla->etiquetas()->whereDoesntHave('paquete')->doesntExist();

                if ($todasEtiquetasEnPaquete) {
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

            // Si todos los elementos de la planilla están fabricados, cerrar planilla y compactar posiciones
            $todosElementosPlanillaFabricados = $planilla->elementos()
                ->where('estado', '!=', 'fabricado')
                ->doesntExist();

            if ($todosElementosPlanillaFabricados) {
                $planilla->fecha_finalizacion = now();
                $planilla->estado = 'completada';
                $planilla->save();

                DB::transaction(function () use ($planilla, $maquina) {
                    OrdenPlanilla::where('planilla_id', $planilla->id)
                        ->where('maquina_id', $maquina->id)
                        ->delete();

                    $ordenes = OrdenPlanilla::where('maquina_id', $maquina->id)
                        ->orderBy('posicion')
                        ->lockForUpdate()
                        ->get();

                    foreach ($ordenes as $index => $orden) {
                        $orden->posicion = $index;
                        $orden->save();
                    }
                });
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

    private function encontrarProductoBarraPorDiametroYLongitud(Maquina $maquina, int $diametroMm, int $longitudM): Producto
    {
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
                'message' => "No se encontró producto en la máquina con Ø{$diametroMm} mm y longitud {$longitudM} cm.",
            ], 404)->throwResponse();
        }

        return $producto;
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
