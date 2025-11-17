<?php

namespace App\Servicios\Etiquetas\Base;

namespace App\Servicios\Etiquetas\Base;

use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\Movimiento;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\OrdenPlanilla;
use App\Models\ProductoBase;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Servicios\Exceptions\ServicioEtiquetaException;

abstract class ServicioEtiquetaBase
{
    /** Bloquea etiqueta y elementos asociados para evitar condiciones de carrera */
    protected function bloquearEtiquetaConElementos(int $etiquetaSubId): Etiqueta
    {
        log::info("Bloqueando etiqueta para actualización: $etiquetaSubId");
        return DB::transaction(function () use ($etiquetaSubId) {
            /** @var Etiqueta $etiqueta */
            $etiqueta = Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)
                ->lockForUpdate()
                ->firstOrFail();
            log::info("Bloqueada etiqueta para actualización: $etiquetaSubId (id={$etiqueta->id}, estado={$etiqueta->estado})");
            // Precargar relaciones necesarias bajo el mismo candado si aplica
            $etiqueta->load(['elementos' => function ($q) {
                $q->lockForUpdate();
            }]);

            return $etiqueta;
        }, 1); // nivel de intento mínimo
    }

    /** Actualiza el peso total de la etiqueta sumando sus elementos */
    protected function actualizarPesoEtiqueta(Etiqueta $etiqueta): void
    {
        $pesoTotal = $etiqueta->elementos()->sum('peso');
        $etiqueta->peso = $pesoTotal;
        // No hace save aquí, se espera que el llamador lo haga
    }

    /** Agrupa pesos por diámetro a partir de una colección de elementos */
    protected function agruparPesosPorDiametro($elementos): array
    {
        $resultado = [];
        foreach ($elementos as $el) {
            $diametro = (int) round((float) $el->diametro);
            $peso = (float) $el->peso;
            $resultado[$diametro] = ($resultado[$diametro] ?? 0) + $peso;
        }
        return $resultado;
    }

    /** Normaliza un array de diámetros a enteros únicos */
    protected function normalizarDiametros(array $diametros): array
    {
        return array_values(array_unique(array_map(fn($d) => (int) round((float) $d), $diametros)));
    }

    /** Devuelve una ubicación para la máquina por su código con fallback */
    protected function resolverUbicacionParaMaquina(Maquina $maquina, int $fallbackId = 33): Ubicacion
    {
        $ubicacion = Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();
        if (!$ubicacion) {
            $ubicacion = Ubicacion::findOrFail($fallbackId);
        }
        return $ubicacion;
    }
    /**
     * ✅ GENERAR RECARGA (reutilizable)
     * Evita duplicados (misma máquina + mismo producto_base + pendiente).
     */
    protected function generarMovimientoRecargaMateriaPrima(
        ProductoBase $productoBase,
        Maquina $maquina,
        ?int $productoId = null,
        ?int $solicitanteId = null,
        int $prioridad = 1,
        bool $evitarDuplicados = true
    ): int {
        if (!class_exists(Movimiento::class)) {
            Log::error('Modelo Movimiento no existe.');
            throw new \RuntimeException('No se pudo registrar la solicitud de recarga (modelo inexistente).');
        }

        $q = Movimiento::query();

        if ($evitarDuplicados) {
            $existente = $q->where('tipo', 'Recarga materia prima')
                ->where('estado', 'pendiente')
                ->whereNull('maquina_origen')
                ->where('maquina_destino', $maquina->id)
                ->where('producto_base_id', $productoBase->id)
                ->when($productoId, fn($qq) => $qq->where('producto_id', $productoId))
                ->first();

            if ($existente) {
                Log::info('Recarga ya pendiente: no se duplica', [
                    'movimiento_id' => $existente->id,
                    'maquina_id' => $maquina->id,
                    'producto_base_id' => $productoBase->id,
                ]);
                return (int) $existente->id;
            }
        }

        $tipo     = strtolower((string) $productoBase->tipo);
        $diametro = (string) $productoBase->diametro;
        $long     = $productoBase->longitud;
        $longStr  = is_null($long) ? 'N/D' : "{$long} m"; // ajusta si tu unidad es mm

        $descripcion = sprintf(
            'Se solicita materia prima %s (Ø%s, %s) en la máquina %s',
            $tipo,
            $diametro,
            $longStr,
            $maquina->nombre ?? "#{$maquina->id}"
        );

        $nuevo = Movimiento::create([
            'tipo'             => 'Recarga materia prima',
            'maquina_origen'   => null,
            'maquina_destino'  => $maquina->id,
            'nave_id'          => $maquina->obra_id, // Nave donde se ejecuta el movimiento
            'producto_id'      => $productoId,
            'producto_base_id' => $productoBase->id,
            'estado'           => 'pendiente',
            'descripcion'      => $descripcion,
            'prioridad'        => $prioridad,
            'fecha_solicitud'  => now(),
            'solicitado_por'   => $solicitanteId,
        ]);

        Log::info('Movimiento de recarga creado', [
            'movimiento_id'    => $nuevo->id,
            'maquina_id'       => $maquina->id,
            'producto_base_id' => $productoBase->id,
        ]);

        return (int) $nuevo->id;
    }
    // --------------------------------------------
    // 🧩 1) Helper: completar etiqueta si corresponde
    // --------------------------------------------
    /**
     * Marca la etiqueta como 'completada' si todos sus elementos están
     * en alguno de los estados $estadosOk (por defecto: fabricado/completado).
     * Devuelve true si la completó en esta llamada.
     */
    protected function completarEtiquetaSiCorresponde(
        Etiqueta $etiqueta,
        array $fabricadoCompletado = ['fabricado', 'completado']
    ): bool {
        $quedanSinCompletar = $etiqueta->elementos()
            ->where(function ($q) use ($fabricadoCompletado) {
                $q->whereNull('estado')->orWhereNotIn('estado', $fabricadoCompletado);
            })
            ->exists();

        if ($quedanSinCompletar) {
            return false;
        }

        if ($etiqueta->estado !== 'completada') {
            $etiqueta->estado = 'completada';
            $etiqueta->fecha_finalizacion = now();

            // Actualizar peso total de la etiqueta
            $this->actualizarPesoEtiqueta($etiqueta);

            $etiqueta->save();

            Log::info('Etiqueta marcada como completada', [
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                'planilla_id'     => $etiqueta->planilla_id,
                'peso_total'      => $etiqueta->peso,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Versión “completa” con tu lógica de negocio:
     * - Marca elementos como fabricado
     * - Descuenta consumos y asigna producto_id{,_2,_3}
     * - Reglas: TALLER / CARCASAS / PATES
     * - Sacar de cola en máquina y compactar posiciones
     * - Cerrar planilla si corresponde
     *
     * IMPORTANTE: No devuelve JsonResponse ni hace rollBack. Lanza excepciones de dominio.
     */
    protected function actualizarElementosYConsumosCompleto(
        $elementosEnMaquina,
        Maquina $maquina,
        Etiqueta $etiqueta,
        array   &$warnings,
        array   &$productosAfectados,
        ?Planilla $planilla,
        ?int    $solicitanteId = null
    ): void {

        // 0) Marcar todos los elementos en esta máquina como “fabricado”
        foreach ($elementosEnMaquina as $elemento) {
            $elemento->estado = 'fabricado';
            $elemento->save();
        }

        // 1) CONSUMOS (con pool por diámetro y locks)
        $consumos = [];
        foreach ($elementosEnMaquina->groupBy(fn($e) => (int)$e->diametro) as $diametro => $elementos) {

            // Regla especial ensambladora: sólo Ø5
            if ($maquina->tipo === 'ensambladora' && (int)$diametro !== 5) {
                continue;
            }

            $pesoNecesarioTotal = (float) $elementos->sum('peso');

            $productosPorDiametro = $maquina->productos()
                ->whereHas('productoBase', fn($q) => $q->where('diametro', (int)$diametro))
                ->with('productoBase')
                ->orderBy('peso_stock')
                ->lockForUpdate()
                ->get();

            // Si no hay oferta de ese diámetro → crear recarga o abortar con excepción
            if ($productosPorDiametro->isEmpty()) {
                $pb = ProductoBase::where('diametro', (int)$diametro)
                    ->where('tipo', $maquina->tipo_material)
                    ->first();

                if ($pb) {
                    // Solicitamos recarga y abortamos el flujo lanzando excepción (controlador responde 400)
                    $this->generarMovimientoRecargaMateriaPrima($pb, $maquina, null, $solicitanteId);
                    Log::info('Recarga solicitada por falta total de oferta', [
                        'diametro' => (int)$diametro,
                        'maquina_id' => $maquina->id,
                        'producto_base_id' => $pb->id
                    ]);
                    throw new ServicioEtiquetaException(
                        "No se encontraron materias primas para el diámetro Ø{$diametro}. Se solicitó recarga.",
                        ['diametro' => (int)$diametro, 'maquina_id' => $maquina->id]
                    );
                }

                Log::warning('Falta oferta y PB inexistente', [
                    'diametro' => (int)$diametro,
                    'maquina_id' => $maquina->id,
                    'tipo' => $maquina->tipo_material
                ]);
                throw new ServicioEtiquetaException(
                    "No existe materia prima configurada para Ø{$diametro} mm (tipo {$maquina->tipo_material}).",
                    ['diametro' => (int)$diametro, 'tipo' => $maquina->tipo_material]
                );
            }

            $consumos[$diametro] = [];

            foreach ($productosPorDiametro as $producto) {
                if ($pesoNecesarioTotal <= 0) break;

                $pesoInicial = (float) ($producto->peso_inicial ?? $producto->peso_stock);
                $disponible  = (float) $producto->peso_stock;
                $restar      = min($disponible, $pesoNecesarioTotal);

                if ($restar <= 0) continue;

                $producto->peso_stock = $disponible - $restar;
                $pesoNecesarioTotal  -= $restar;

                if ($producto->peso_stock <= 0) {
                    $producto->peso_stock   = 0;
                    $producto->estado       = 'consumido';
                    $producto->ubicacion_id = null;
                    $producto->maquina_id   = null;
                }
                $producto->save();

                $productosAfectados[] = [
                    'id'           => $producto->id,
                    'peso_stock'   => $producto->peso_stock,
                    'peso_inicial' => $pesoInicial,
                    'n_colada'     => $producto->n_colada,
                ];
                $consumos[$diametro][] = [
                    'producto_id' => $producto->id,
                    'consumido'   => $restar,
                ];
            }

            // Si falta stock residual, dispara recarga (warning) y continúa
            if ($pesoNecesarioTotal > 0) {
                $pb = ProductoBase::where('diametro', (int)$diametro)
                    ->where('tipo', $maquina->tipo_material)
                    ->first();
                if ($pb) {
                    $this->generarMovimientoRecargaMateriaPrima($pb, $maquina, null, $solicitanteId);
                    $warnings[] = "Stock insuficiente para Ø{$diametro} en {$maquina->nombre}. Se ha solicitado recarga.";
                } else {
                    $warnings[] = "Stock insuficiente para Ø{$diametro} y no existe ProductoBase configurado (tipo {$maquina->tipo_material}).";
                }
            }
        }

        // 2) Asignar productos a elementos (pool compartido por diámetro)
        foreach ($elementosEnMaquina as $elemento) {
            $d = (int) $elemento->diametro;
            if (!isset($consumos[$d])) {
                $consumos[$d] = [];
            }
            $disponibles = &$consumos[$d];

            $pesoRestante = (float) $elemento->peso;
            $asignados = [];

            while ($pesoRestante > 0 && count($disponibles) > 0) {
                $cons = &$disponibles[0];
                if ($cons['consumido'] <= $pesoRestante) {
                    $asignados[]  = $cons['producto_id'];
                    $pesoRestante -= $cons['consumido'];
                    array_shift($disponibles);
                } else {
                    $asignados[]      = $cons['producto_id'];
                    $cons['consumido'] -= $pesoRestante;
                    $pesoRestante      = 0;
                }
            }

            $elemento->producto_id   = $asignados[0] ?? null;
            $elemento->producto_id_2 = $asignados[1] ?? null;
            $elemento->producto_id_3 = $asignados[2] ?? null;
            // ya fue marcado fabricado arriba, pero si quieres reforzar:
            if ($pesoRestante <= 0) {
                $elemento->estado = 'fabricado';
            }
            $elemento->save();
        }

        // ACTUALIZAR PESO TOTAL DE LA ETIQUETA SIEMPRE
        $this->actualizarPesoEtiqueta($etiqueta);
        $etiqueta->save();

        //COMPLETAMOS ETIQUETA SI CORRESPONDE
        $this->completarEtiquetaSiCorresponde($etiqueta);

        // 3) Reglas por “ensamblado” en planilla
        $ensambladoText = strtolower($etiqueta->planilla->ensamblado ?? '');

        if (str_contains($ensambladoText, 'taller')) {
            // si el comentario NO contiene estas claves, aplicar flujo de soldadora
            $coment = strtolower($planilla->comentario ?? '');
            if (!str_contains($coment, 'amarrado') && !str_contains($coment, 'ensamblado amarrado')) {

                // Mandar a soldadora (buscando disponible; si no, la menos ocupada)
                $maquinaSoldar = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                    ->whereDoesntHave('elementos')
                    ->first();

                if (!$maquinaSoldar) {
                    $maquinaSoldar = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                        ->orderBy('id') // o por carga real si tienes métrica
                        ->first();
                }

                if ($maquinaSoldar) {
                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->maquina_id_3 = $maquinaSoldar->id;
                        $elemento->save();
                    }
                } else {
                    throw new ServicioEtiquetaException("No se encontró una máquina de soldar disponible para taller.");
                }
            }
        } elseif (str_contains($ensambladoText, 'carcasas')) {
            // Todos los elementos de la etiqueta listos (excluye Ø5 si corresponde)
            $elementosEtiquetaCompletos = $etiqueta->elementos()
                ->where('diametro', '!=', 5.00) // como en tu código original
                ->where('estado', '!=', 'fabricado')
                ->doesntExist();

            if ($elementosEtiquetaCompletos) {
                $etiqueta->estado = ($maquina->tipo === 'estribadora') ? 'fabricada' : 'completada';
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();
            }

            // Si no estamos en cortadora_dobladora, empujar a ensambladora
            if ($maquina->tipo !== 'cortadora_dobladora') {
                $maquinaEnsambladora = Maquina::where('tipo', 'ensambladora')->first();
                if ($maquinaEnsambladora) {
                    foreach ($elementosEnMaquina as $elemento) {
                        if (is_null($elemento->maquina_id_2)) {
                            $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                            $elemento->save();
                        }
                    }
                }
            }
        } else {
            // Regla especial “pates”
            if (Str::of($etiqueta->nombre ?? '')->lower()->contains('pates')) {
                DB::transaction(function () use ($etiqueta, $maquina) {

                    // 2) buscar dobladora manual (preferir misma obra)
                    $dobladora = Maquina::where('tipo', 'dobladora_manual')
                        ->when($maquina->obra_id, fn($q) => $q->where('obra_id', $maquina->obra_id))
                        ->orderBy('id')
                        ->first();

                    if ($dobladora) {
                        // 3) mandar elementos de ESTA máquina a maquina_id_2
                        Elemento::where('etiqueta_sub_id', $etiqueta->etiqueta_sub_id)
                            ->where('maquina_id', $maquina->id)
                            ->update(['maquina_id_2' => $dobladora->id]);

                        // 3.b) encolar planilla en la dobladora si no estaba
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
                        } else {
                            Log::warning('No se pudo encolar planilla en dobladora: planilla_id nulo', [
                                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                                'dobladora_id'    => $dobladora->id,
                            ]);
                        }
                    } else {
                        Log::warning('No hay dobladora manual para asignar maquina_id_2', [
                            'maquina_origen_id' => $maquina->id,
                            'etiqueta_sub_id'   => $etiqueta->etiqueta_sub_id,
                        ]);
                    }
                });
            }
        }

        // 4) Si ya no quedan elementos de ESTA planilla en ESTA máquina → sacar de cola
        if ($planilla) {
            // ✅ Si ya no quedan elementos de esta planilla en ESTA máquina, sacarla de la cola y compactar posiciones
            $quedanPendientesEnEstaMaquina = Elemento::where('planilla_id', $planilla->id)
                ->where('maquina_id', $maquina->id)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '!=', 'fabricado');
                })
                ->exists();

            // ❌ DESHABILITADO: La verificación automática de paquetes y eliminación de planilla
            // ahora se hace manualmente desde la vista de máquina con el botón "Planilla Completada"
            /*
            if (! $quedanPendientesEnEstaMaquina) {

                // 🔍 Verificamos que todas las etiquetas de esa planilla tengan paquete asignado
                $todasEtiquetasEnPaquete = $planilla->etiquetas()
                    ->whereDoesntHave('paquete') // etiquetas sin paquete
                    ->doesntExist();

                if ($todasEtiquetasEnPaquete) {
                    DB::transaction(function () use ($planilla, $maquina) {
                        // 1) Buscar registro en la cola
                        $registro = OrdenPlanilla::where('planilla_id', $planilla->id)
                            ->where('maquina_id', $maquina->id)
                            ->lockForUpdate()
                            ->first();

                        if ($registro) {
                            $posicionEliminada = $registro->posicion;

                            // 2) Eliminar de la cola
                            $registro->delete();

                            // 3) Reordenar posiciones posteriores
                            OrdenPlanilla::where('maquina_id', $maquina->id)
                                ->where('posicion', '>', $posicionEliminada)
                                ->decrement('posicion');
                        }
                    });
                }
            }
            */
            // 5) Si todos los elementos de la planilla están fabricados → cerrar planilla
            $todosElementosPlanillaCompletos = $planilla->elementos()
                ->where('estado', '!=', 'fabricado')
                ->doesntExist();

            if ($todosElementosPlanillaCompletos) {
                $planilla->fecha_finalizacion = now();
                $planilla->estado = 'completada';
                $planilla->save();

                // Limpieza de cola + compactar posiciones
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
}
