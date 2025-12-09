<?php

namespace App\Services;

use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\Maquina;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubEtiquetaService
{
    /**
     * Reubica subetiqueta según la máquina destino.
     * - MSR20: Agrupa elementos con hermanos del mismo código padre
     * - Resto: Un elemento por etiqueta_sub_id
     * Devuelve [subIdDestino, subIdOriginal]
     */
    public function reubicarParaProduccion(Elemento $elemento, int $nuevaMaquinaReal): array
    {
        $subIdOriginal = $elemento->getOriginal('etiqueta_sub_id') ?? $elemento->etiqueta_sub_id;

        // Buscar etiqueta padre: primero por etiqueta_id, si no existe por etiqueta_sub_id
        if ($elemento->etiqueta_id) {
            $padre = Etiqueta::lockForUpdate()->findOrFail($elemento->etiqueta_id);
        } else {
            // Extraer código padre del etiqueta_sub_id (ej: "ETQ123.01" -> "ETQ123")
            $codigoPadre = Str::before($subIdOriginal, '.');
            $padre = Etiqueta::lockForUpdate()
                ->where('planilla_id', $elemento->planilla_id)
                ->where('codigo', $codigoPadre)
                ->firstOrFail();

            // Actualizar el etiqueta_id del elemento para futuras operaciones
            $elemento->etiqueta_id = $padre->id;
            $elemento->save();
            Log::info('🔧 etiqueta_id restaurado desde etiqueta_sub_id', [
                'elemento_id' => $elemento->id,
                'etiqueta_id' => $padre->id,
                'codigo_padre' => $codigoPadre,
            ]);
        }

        $codigoPadre   = (string) $padre->codigo;
        $prefijoPadre  = $codigoPadre . '.';

        $maq = Maquina::findOrFail($nuevaMaquinaReal);
        $esMSR20 = strtoupper($maq->codigo ?? '') === 'MSR20';

        Log::info('🔁 Reubicar (producción)', [
            'elemento'      => $elemento->id,
            'sub_original'  => $subIdOriginal,
            'maquina_real'  => $nuevaMaquinaReal,
            'maquina_codigo' => $maq->codigo,
            'es_MSR20'      => $esMSR20,
        ]);

        // MSR20: agrupa con hermanos, resto: un elemento por sub
        $subDestino = $esMSR20
            ? $this->modoEncarretado($elemento, $padre, $prefijoPadre, $nuevaMaquinaReal, $subIdOriginal)
            : $this->modoBarra($elemento, $padre, $prefijoPadre, $subIdOriginal);

        // Nada cambió
        if ($subDestino === $subIdOriginal) {
            Log::info('✅ Sin cambios de sub', ['sub' => $subDestino]);
            return [$subDestino, $subIdOriginal];
        }

        // Recalcular pesos (sub-origen, sub-destino y padre)
        $this->recalcularPesos($codigoPadre, array_filter([$subIdOriginal, $subDestino]));

        Log::info('🏁 Reubicación OK', ['de' => $subIdOriginal, 'a' => $subDestino]);
        return [$subDestino, $subIdOriginal];
    }

    /**
     * Reubica subetiqueta según tipo de material de la máquina destino.
     * Devuelve [subIdDestino, subIdOriginal]
     */
    public function reubicarSegunTipoMaterial(Elemento $elemento, int $nuevaMaquinaReal): array
    {
        $subIdOriginal = $elemento->getOriginal('etiqueta_sub_id');

        // Buscar etiqueta padre: primero por etiqueta_id, si no existe por etiqueta_sub_id
        if ($elemento->etiqueta_id) {
            $padre = Etiqueta::lockForUpdate()->findOrFail($elemento->etiqueta_id);
        } else {
            // Extraer código padre del etiqueta_sub_id (ej: "ETQ123.01" -> "ETQ123")
            $codigoPadreSub = Str::before($subIdOriginal, '.');
            $padre = Etiqueta::lockForUpdate()
                ->where('planilla_id', $elemento->planilla_id)
                ->where('codigo', $codigoPadreSub)
                ->firstOrFail();

            // Actualizar el etiqueta_id del elemento para futuras operaciones
            $elemento->etiqueta_id = $padre->id;
            $elemento->save();
            Log::info('🔧 etiqueta_id restaurado desde etiqueta_sub_id', [
                'elemento_id' => $elemento->id,
                'etiqueta_id' => $padre->id,
                'codigo_padre' => $codigoPadreSub,
            ]);
        }

        $codigoPadre   = (string) $padre->codigo;
        $prefijoPadre  = $codigoPadre . '.';

        /** @var Maquina $maq */
        $maq  = Maquina::findOrFail($nuevaMaquinaReal);
        $tipo = strtolower((string) ($maq->tipo_material ?? ''));

        Log::info('🔁 Reubicar', [
            'elemento'      => $elemento->id,
            'sub_original'  => $subIdOriginal,
            'maquina_real'  => $nuevaMaquinaReal,
            'tipo'          => $tipo ?: '(vacío)',
        ]);

        $subDestino = $tipo === 'barra'
            ? $this->modoBarra($elemento, $padre, $prefijoPadre, $subIdOriginal)
            : $this->modoEncarretado($elemento, $padre, $prefijoPadre, $nuevaMaquinaReal, $subIdOriginal);

        // Nada cambió
        if ($subDestino === $subIdOriginal) {
            Log::info('✅ Sin cambios de sub', ['sub' => $subDestino]);
            return [$subDestino, $subIdOriginal];
        }

        // Recalcular pesos (sub-origen, sub-destino y padre)
        $this->recalcularPesos($codigoPadre, array_filter([$subIdOriginal, $subDestino]));

        Log::info('🏁 Reubicación OK', ['de' => $subIdOriginal, 'a' => $subDestino]);
        return [$subDestino, $subIdOriginal];
    }

    /* ===========================  MODO BARRA  =========================== */

    protected function modoBarra(Elemento $elemento, Etiqueta $padre, string $prefijoPadre, ?string $subIdOriginal): string
    {
        // si ya tiene sub del mismo prefijo y es única → conservar
        if ($subIdOriginal && str_starts_with($subIdOriginal, $prefijoPadre)) {
            $cuantos = Elemento::where('etiqueta_sub_id', $subIdOriginal)->count();
            if ($cuantos === 1) {
                Log::info('🟢 Barra: conservo sub única', ['sub' => $subIdOriginal]);
                return $subIdOriginal;
            }
        }

        // crear nueva sub (mismo prefijo/código)
        $subNuevo = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);
        $etiquetaSubId = $this->asegurarFilaSub($subNuevo, $padre);

        // mover elemento a la nueva sub
        $elemento->etiqueta_sub_id = $subNuevo;
        $elemento->etiqueta_id = $etiquetaSubId;
        $elemento->save();

        // si la sub original se quedó vacía → eliminarla
        if ($subIdOriginal && $subIdOriginal !== $subNuevo) {
            $this->eliminarSubSiVacia($subIdOriginal);
        }

        Log::info('🆕 Barra: sub nueva asignada', ['sub' => $subNuevo]);
        return $subNuevo;
    }

    /* ========================  MODO ENCARRETADO  ======================== */

    /**
     * ENCARRETADO:
     * - Si hay hermanos (mismo prefijo en la máquina destino), unifica TODAS las subs en una canónica
     *   (la de sufijo más bajo) y el elemento adopta esa sub.
     * - Si NO hay hermanos, crea una sub nueva (mismo código, siguiente sufijo libre) y la asigna.
     * - No se renombra nada arbitrariamente.
     */
    protected function modoEncarretado(
        Elemento $elemento,
        Etiqueta $padre,
        string $prefijoPadre,
        int $nuevaMaquinaReal,
        ?string $subIdOriginal
    ): string {
        $codigoPadre = (string) $padre->codigo;
        $maxElementosPorSub = 5; // Límite de elementos por etiqueta_sub_id

        // 1) Localizar HERMANOS: mismo etiqueta_id + mismo prefijo y YA en esa máquina (según obtenerMaquinaReal)
        $hermanos = Elemento::where('etiqueta_id', $elemento->etiqueta_id)
            ->whereNotNull('etiqueta_sub_id')
            ->where('etiqueta_sub_id', 'like', $prefijoPadre . '%')
            ->where('id', '!=', $elemento->id) // Excluir el elemento actual
            ->get()
            ->filter(fn($e) => (int) $this->obtenerMaquinaReal($e) === $nuevaMaquinaReal);

        Log::info('🧾 [Encarretado] Hermanos en máquina destino', [
            'total'   => $hermanos->count(),
            'prefijo' => $prefijoPadre,
            'maq'     => $nuevaMaquinaReal,
            'max_por_sub' => $maxElementosPorSub,
        ]);

        if ($hermanos->isEmpty()) {
            // 2) No hay hermanos → crear sub nueva (mismo código) y asignar
            $subNuevo = Etiqueta::generarCodigoSubEtiqueta($codigoPadre);
            $etiquetaSubId = $this->asegurarFilaSub($subNuevo, $padre);

            $elemento->etiqueta_sub_id = $subNuevo;
            $elemento->etiqueta_id = $etiquetaSubId;
            $elemento->save();

            // si la original se quedó vacía, limpiarla
            if ($subIdOriginal && $subIdOriginal !== $subNuevo) {
                $this->eliminarSubSiVacia($subIdOriginal);
            }

            Log::info('🆕 [Encarretado] Creo y asigno sub nueva (sin hermanos)', ['sub' => $subNuevo, 'etiqueta_id' => $etiquetaSubId]);
            return $subNuevo;
        }

        // 3) Agrupar hermanos por etiqueta_sub_id y contar
        $subsCounts = $hermanos->groupBy('etiqueta_sub_id')->map->count();

        // 4) Buscar una sub que tenga espacio (menos de $maxElementosPorSub elementos)
        $subConEspacio = $subsCounts
            ->sortBy(function ($count, $sid) {
                // Ordenar por sufijo numérico para preferir las más bajas
                return (int) (preg_match('/\.(\d+)$/', (string) $sid, $m) ? $m[1] : 9999);
            })
            ->filter(fn($count) => $count < $maxElementosPorSub)
            ->keys()
            ->first();

        $etiquetaIdDestino = null;
        if ($subConEspacio) {
            // Hay espacio en una sub existente
            $subDestino = (string) $subConEspacio;
            // Obtener el etiqueta_id de la subetiqueta existente
            $etiquetaIdDestino = Etiqueta::where('etiqueta_sub_id', $subDestino)->value('id');

            Log::info('✅ [Encarretado] Sub con espacio encontrada', [
                'sub' => $subDestino,
                'elementos_actuales' => $subsCounts[$subDestino],
                'max' => $maxElementosPorSub,
            ]);
        } else {
            // Todas las subs están llenas, crear una nueva
            $subDestino = Etiqueta::generarCodigoSubEtiqueta($codigoPadre);
            $etiquetaIdDestino = $this->asegurarFilaSub($subDestino, $padre);

            Log::info('🆕 [Encarretado] Todas las subs llenas, creo nueva', [
                'sub' => $subDestino,
                'subs_llenas' => $subsCounts->all(),
            ]);
        }

        // 5) Asignar el elemento a la sub destino
        if ($elemento->etiqueta_sub_id !== $subDestino) {
            $elemento->etiqueta_sub_id = $subDestino;
            if ($etiquetaIdDestino) {
                $elemento->etiqueta_id = $etiquetaIdDestino;
            }
            $elemento->save();
            Log::info('📌 [Encarretado] Elemento asignado a sub', [
                'elemento' => $elemento->id,
                'sub'      => $subDestino,
                'etiqueta_id' => $etiquetaIdDestino,
            ]);
        }

        // 6) Si su sub original ya no tiene elementos, limpiarla
        if ($subIdOriginal && $subIdOriginal !== $subDestino) {
            $this->eliminarSubSiVacia($subIdOriginal);
        }

        // 7) Devolver sub final. Pesos se recalculan fuera.
        return $subDestino;
    }


    /* ============================  HELPERS  ============================ */

    /** Solo elimina la fila de la sub si ya no hay elementos que dependan de ella. */
    protected function eliminarSubSiVacia(string $subId): void
    {
        $quedan = Elemento::where('etiqueta_sub_id', $subId)->lockForUpdate()->exists();
        if ($quedan) {
            // recalcular peso por si acaso
            if (Schema::hasColumn('etiquetas', 'peso')) {
                $peso = (float) Elemento::where('etiqueta_sub_id', $subId)->sum('peso');
                Etiqueta::where('etiqueta_sub_id', $subId)->update(['peso' => $peso]);
            }
            Log::info('ℹ️ Sub NO eliminada: aún tiene elementos', ['sub' => $subId]);
            return;
        }

        $borradas = Etiqueta::where('etiqueta_sub_id', $subId)->delete();
        Log::info('🧹 Sub eliminada (vacía)', ['sub' => $subId, 'filas' => $borradas]);
    }

    /** Crea fila de etiquetas para la sub (copia datos del padre) si no existe. Devuelve el ID. */
    protected function asegurarFilaSub(string $subId, Etiqueta $padre): int
    {
        Log::info('🔍 [asegurarFilaSub] Buscando sub', ['sub' => $subId]);

        // Primero verificar si ya existe (con y sin soft deletes)
        $existente = Etiqueta::withTrashed()->where('etiqueta_sub_id', $subId)->first();

        Log::info('🔍 [asegurarFilaSub] Resultado búsqueda', [
            'sub' => $subId,
            'encontrada' => $existente ? true : false,
            'id' => $existente?->id,
            'deleted_at' => $existente?->deleted_at,
        ]);

        if ($existente) {
            // Si está eliminada (soft delete), restaurarla
            if ($existente->trashed()) {
                $existente->restore();
                Log::info('🧱 Fila sub restaurada', ['sub' => $subId, 'id' => $existente->id]);
            } else {
                Log::info('🧱 Fila sub ya existe', ['sub' => $subId, 'id' => $existente->id]);
            }
            return (int) $existente->id;
        }

        // Crear nueva fila copiando datos del padre
        $data = [
            'codigo'          => $padre->codigo,
            'etiqueta_sub_id' => $subId,
            'planilla_id'     => $padre->planilla_id,
            'nombre'          => $padre->nombre,
            'estado'          => $padre->estado ?? 'pendiente',
            'peso'            => 0.0,
        ];

        foreach (
            [
                'producto_id',
                'producto_id_2',
                'ubicacion_id',
                'operario1_id',
                'operario2_id',
                'soldador1_id',
                'soldador2_id',
                'ensamblador1_id',
                'ensamblador2_id',
                'marca',
                'paquete_id',
                'numero_etiqueta',
                'fecha_inicio',
                'fecha_finalizacion',
                'fecha_inicio_ensamblado',
                'fecha_finalizacion_ensamblado',
                'fecha_inicio_soldadura',
                'fecha_finalizacion_soldadura',
            ] as $col
        ) {
            if (Schema::hasColumn('etiquetas', $col)) $data[$col] = $padre->$col;
        }

        try {
            $etiquetaSub = Etiqueta::create($data);
            Log::info('🧱 Fila sub creada', ['sub' => $subId, 'id' => $etiquetaSub->id]);
            return (int) $etiquetaSub->id;
        } catch (\Illuminate\Database\QueryException $e) {
            // Si falla por duplicado, buscar la existente (incluyendo soft deleted)
            if ($e->errorInfo[1] == 1062) {
                $existente = Etiqueta::withTrashed()->where('etiqueta_sub_id', $subId)->first();
                if ($existente) {
                    if ($existente->trashed()) {
                        $existente->restore();
                    }
                    Log::info('🧱 Fila sub recuperada tras conflicto', ['sub' => $subId, 'id' => $existente->id]);
                    return (int) $existente->id;
                }
            }
            throw $e;
        }
    }

    /** Recalcula pesos para una lista de sub-ids y para el padre. */
    protected function recalcularPesos(string $codigoPadre, array $subIds): void
    {
        if (!Schema::hasColumn('etiquetas', 'peso')) return;

        $subIds = array_values(array_unique($subIds));
        foreach ($subIds as $sid) {
            $peso = (float) Elemento::where('etiqueta_sub_id', $sid)->sum('peso');
            Etiqueta::where('etiqueta_sub_id', $sid)->update(['peso' => $peso]);
            Log::info('🧮 Peso sub recalculado', ['sub' => $sid, 'peso' => $peso]);
        }

        $pesoPadre = (float) Elemento::where('etiqueta_sub_id', 'like', $codigoPadre . '.%')->sum('peso');
        Etiqueta::where('codigo', $codigoPadre)->whereNull('etiqueta_sub_id')->update(['peso' => $pesoPadre]);
        Log::info('🧮 Peso padre recalculado', ['codigo' => $codigoPadre, 'peso' => $pesoPadre]);
    }

    /** Normaliza cadenas. */
    protected function normalizar(string $s): string
    {
        return Str::of($s)->lower()->ascii()->replaceMatches('/\s+/', ' ')->trim()->__toString();
    }

    /** Determina la máquina real de un elemento. */
    protected function obtenerMaquinaReal(Elemento $e): ?int
    {
        return $e->maquina_id ?? $e->maquina_id_2 ?? $e->maquina_id_3 ?? null;
    }

    /* ===============  COMPRIMIR / DESCOMPRIMIR ETIQUETAS  =============== */

    /**
     * COMPRIMIR: Agrupa elementos hermanos de una máquina en etiquetas compartidas.
     * Máximo 5 elementos por etiqueta_sub_id.
     * Solo afecta elementos asignados a la máquina especificada.
     *
     * Los hermanos son elementos que comparten el mismo CÓDIGO PADRE (extraído del etiqueta_sub_id).
     * Ejemplo: ETQ2512001.01, ETQ2512001.02, ETQ2512001.03 son hermanos (código padre: ETQ2512001)
     *
     * @param int $maquinaId ID de la máquina
     * @return array ['success' => bool, 'message' => string, 'stats' => array]
     */
    public function comprimirEtiquetasPorMaquina(int $maquinaId): array
    {
        $maxElementosPorSub = 5;
        $stats = [
            'elementos_procesados' => 0,
            'subetiquetas_antes' => 0,
            'subetiquetas_despues' => 0,
            'movimientos' => 0,
        ];

        try {
            // 1) Obtener todos los elementos de esta máquina (en cualquier campo maquina_id)
            $elementos = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId)
                    ->orWhere('maquina_id_3', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id')
                ->get();

            if ($elementos->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay elementos asignados a esta máquina para comprimir.',
                    'stats' => $stats,
                ];
            }

            // Contar subetiquetas únicas antes
            $stats['subetiquetas_antes'] = $elementos->pluck('etiqueta_sub_id')->unique()->count();

            // 2) Agrupar elementos por CÓDIGO PADRE (extraído del etiqueta_sub_id)
            // ETQ2512001.01 -> código padre: ETQ2512001
            $gruposPorCodigoPadre = $elementos->groupBy(function ($elemento) {
                return Str::before($elemento->etiqueta_sub_id, '.');
            });

            Log::info('🗜️ [Comprimir] Grupos por código padre', [
                'maquina_id' => $maquinaId,
                'total_elementos' => $elementos->count(),
                'grupos' => $gruposPorCodigoPadre->map->count()->toArray(),
            ]);

            foreach ($gruposPorCodigoPadre as $codigoPadre => $elementosGrupo) {
                // Si solo hay 1 elemento en este grupo, no hay nada que comprimir
                if ($elementosGrupo->count() <= 1) {
                    $stats['elementos_procesados']++;
                    continue;
                }

                // Obtener etiqueta padre (la fila sin sufijo o la primera con este código)
                $padre = Etiqueta::where('codigo', $codigoPadre)
                    ->whereNull('etiqueta_sub_id')
                    ->first();

                if (!$padre) {
                    // Buscar por etiqueta_sub_id que empiece con el código
                    $padre = Etiqueta::where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                        ->first();
                }

                if (!$padre) {
                    Log::warning('🗜️ [Comprimir] No se encontró etiqueta padre', ['codigo' => $codigoPadre]);
                    continue;
                }

                $prefijoPadre = $codigoPadre . '.';

                // SEPARAR ESTRIBOS DE ELEMENTOS NORMALES
                // Estribo = dobles_barra >= 4 AND diametro <= 16
                $estribos = $elementosGrupo->filter(function ($e) {
                    return (int) $e->dobles_barra >= 4 && (int) $e->diametro <= 16;
                });
                $normales = $elementosGrupo->reject(function ($e) {
                    return (int) $e->dobles_barra >= 4 && (int) $e->diametro <= 16;
                });

                Log::info('🗜️ [Comprimir] Separación estribos/normales', [
                    'codigo_padre' => $codigoPadre,
                    'estribos' => $estribos->count(),
                    'normales' => $normales->count(),
                ]);

                // Obtener el siguiente número disponible para este código padre (incluyendo soft deleted)
                $ultimaSub = Etiqueta::withTrashed()
                    ->where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                    ->orderByRaw("CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED) DESC")
                    ->value('etiqueta_sub_id');

                $siguienteNumero = 1;
                if ($ultimaSub) {
                    $partes = explode('.', $ultimaSub);
                    $siguienteNumero = ((int) end($partes)) + 1;
                }

                Log::info('🗜️ [Comprimir] Siguiente número calculado', [
                    'codigo_padre' => $codigoPadre,
                    'ultima_sub' => $ultimaSub,
                    'siguiente_numero' => $siguienteNumero,
                ]);

                $subsOriginales = [];

                // Procesar cada tipo por separado (estribos y normales no se mezclan)
                $tiposElementos = [
                    'normales' => $normales->sortBy('etiqueta_sub_id')->values(),
                    'estribos' => $estribos->sortBy('etiqueta_sub_id')->values(),
                ];

                foreach ($tiposElementos as $tipoNombre => $elementosDelTipo) {
                    if ($elementosDelTipo->isEmpty()) {
                        continue;
                    }

                    Log::info("🗜️ [Comprimir] Procesando {$tipoNombre}", [
                        'codigo_padre' => $codigoPadre,
                        'elementos' => $elementosDelTipo->count(),
                        'subs_actuales' => $elementosDelTipo->pluck('etiqueta_sub_id')->unique()->toArray(),
                    ]);

                    // Redistribuir: todos los elementos del tipo van a la primera subetiqueta disponible
                    // hasta llenarla (máx 5), luego a la siguiente, etc.
                    $subActual = null;
                    $contadorEnSub = 0;

                    foreach ($elementosDelTipo as $elemento) {
                        $stats['elementos_procesados']++;
                        $subsOriginales[] = $elemento->etiqueta_sub_id;

                        // Si no hay sub actual o está llena, obtener/crear una nueva
                        if ($subActual === null || $contadorEnSub >= $maxElementosPorSub) {
                            if ($subActual === null) {
                                // Primera iteración: usar la primera sub del tipo (la más baja)
                                $subActual = $elementosDelTipo->first()->etiqueta_sub_id;
                            } else {
                                // Sub llena: crear nueva subetiqueta con número incremental local
                                $subActual = $codigoPadre . '.' . str_pad($siguienteNumero, 2, '0', STR_PAD_LEFT);
                                $siguienteNumero++;
                                $this->asegurarFilaSub($subActual, $padre);
                            }
                            $contadorEnSub = 0;
                        }

                        // Mover elemento a la sub actual si es diferente
                        if ($elemento->etiqueta_sub_id !== $subActual) {
                            $subOriginal = $elemento->etiqueta_sub_id;

                            // Obtener el etiqueta_id de la subetiqueta destino
                            $etiquetaSubId = Etiqueta::where('etiqueta_sub_id', $subActual)->value('id');

                            if (!$etiquetaSubId) {
                                // Crear la fila de subetiqueta si no existe
                                $etiquetaSubId = $this->asegurarFilaSub($subActual, $padre);
                            }

                            $elemento->etiqueta_sub_id = $subActual;
                            $elemento->etiqueta_id = $etiquetaSubId;
                            $elemento->save();

                            $stats['movimientos']++;

                            Log::info('🗜️ [Comprimir] Elemento movido', [
                                'elemento' => $elemento->id,
                                'tipo' => $tipoNombre,
                                'de' => $subOriginal,
                                'a' => $subActual,
                            ]);
                        }

                        $contadorEnSub++;
                    }
                }

                // Limpiar subs que quedaron vacías
                $subsOriginalesUnicas = array_unique($subsOriginales);
                foreach ($subsOriginalesUnicas as $subOriginal) {
                    $this->eliminarSubSiVacia($subOriginal);
                }

                // Recalcular pesos
                $elementosActualizados = Elemento::where(function ($q) use ($maquinaId) {
                    $q->where('maquina_id', $maquinaId)
                        ->orWhere('maquina_id_2', $maquinaId)
                        ->orWhere('maquina_id_3', $maquinaId);
                })
                    ->where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                    ->pluck('etiqueta_sub_id')
                    ->unique()
                    ->toArray();

                $subsAfectadas = array_unique(array_merge($subsOriginalesUnicas, $elementosActualizados));
                $this->recalcularPesos($codigoPadre, $subsAfectadas);
            }

            // Contar subetiquetas únicas después
            $elementosDespues = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId)
                    ->orWhere('maquina_id_3', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id')
                ->get();

            $stats['subetiquetas_despues'] = $elementosDespues->pluck('etiqueta_sub_id')->unique()->count();

            Log::info('✅ [Comprimir] Completado', $stats);

            return [
                'success' => true,
                'message' => "Compresión completada. {$stats['movimientos']} elementos reagrupados. " .
                    "Subetiquetas: {$stats['subetiquetas_antes']} → {$stats['subetiquetas_despues']}",
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            Log::error('❌ [Comprimir] Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Error al comprimir: ' . $e->getMessage(),
                'stats' => $stats,
            ];
        }
    }

    /**
     * DESCOMPRIMIR: Separa elementos en subetiquetas individuales (1 elemento = 1 subetiqueta).
     * Solo afecta elementos asignados a la máquina especificada.
     *
     * @param int $maquinaId ID de la máquina
     * @return array ['success' => bool, 'message' => string, 'stats' => array]
     */
    public function descomprimirEtiquetasPorMaquina(int $maquinaId): array
    {
        $stats = [
            'elementos_procesados' => 0,
            'subetiquetas_antes' => 0,
            'subetiquetas_despues' => 0,
            'movimientos' => 0,
        ];

        try {
            // 1) Obtener todos los elementos de esta máquina
            $elementos = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId)
                    ->orWhere('maquina_id_3', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id')
                ->get();

            if ($elementos->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay elementos asignados a esta máquina para descomprimir.',
                    'stats' => $stats,
                ];
            }

            // Contar subetiquetas únicas antes
            $stats['subetiquetas_antes'] = $elementos->pluck('etiqueta_sub_id')->unique()->count();

            // 2) Agrupar elementos por CÓDIGO PADRE (extraído del etiqueta_sub_id)
            $gruposPorCodigoPadre = $elementos->groupBy(function ($elemento) {
                return Str::before($elemento->etiqueta_sub_id, '.');
            });

            Log::info('📤 [Descomprimir] Grupos por código padre', [
                'maquina_id' => $maquinaId,
                'total_elementos' => $elementos->count(),
                'grupos' => $gruposPorCodigoPadre->map->count()->toArray(),
            ]);

            foreach ($gruposPorCodigoPadre as $codigoPadre => $elementosGrupo) {
                // Obtener etiqueta padre
                $padre = Etiqueta::where('codigo', $codigoPadre)
                    ->whereNull('etiqueta_sub_id')
                    ->first();

                if (!$padre) {
                    $padre = Etiqueta::where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                        ->first();
                }

                if (!$padre) {
                    Log::warning('📤 [Descomprimir] No se encontró etiqueta padre', ['codigo' => $codigoPadre]);
                    continue;
                }

                // Agrupar elementos por su etiqueta_sub_id actual
                $elementosPorSub = $elementosGrupo->groupBy('etiqueta_sub_id');
                $subsOriginales = $elementosPorSub->keys()->toArray();

                Log::info('📤 [Descomprimir] Procesando grupo', [
                    'codigo_padre' => $codigoPadre,
                    'elementos' => $elementosGrupo->count(),
                    'subs_actuales' => $elementosPorSub->map->count()->toArray(),
                ]);

                // Obtener el siguiente número disponible para este código padre (incluyendo soft deleted)
                $ultimaSub = Etiqueta::withTrashed()
                    ->where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                    ->orderByRaw("CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED) DESC")
                    ->value('etiqueta_sub_id');

                $siguienteNumero = 1;
                if ($ultimaSub) {
                    $partes = explode('.', $ultimaSub);
                    $siguienteNumero = ((int) end($partes)) + 1;
                }

                Log::info('📤 [Descomprimir] Siguiente número calculado', [
                    'codigo_padre' => $codigoPadre,
                    'ultima_sub' => $ultimaSub,
                    'siguiente_numero' => $siguienteNumero,
                ]);

                // 3) Para cada subetiqueta que tenga más de 1 elemento, separar
                foreach ($elementosPorSub as $subId => $elementosEnSub) {
                    // Si solo hay 1 elemento en esta sub, ya está descomprimido
                    if ($elementosEnSub->count() <= 1) {
                        $stats['elementos_procesados']++;
                        continue;
                    }

                    // El primer elemento se queda en la sub original
                    $primerElemento = true;

                    foreach ($elementosEnSub as $elemento) {
                        $stats['elementos_procesados']++;

                        // El primer elemento se queda donde está
                        if ($primerElemento) {
                            $primerElemento = false;
                            continue;
                        }

                        // Generar nueva subetiqueta con número incremental local
                        $subNueva = $codigoPadre . '.' . str_pad($siguienteNumero, 2, '0', STR_PAD_LEFT);
                        $siguienteNumero++; // Incrementar para el siguiente

                        $etiquetaSubId = $this->asegurarFilaSub($subNueva, $padre);

                        $subOriginal = $elemento->etiqueta_sub_id;

                        // Mover elemento a su nueva sub
                        $elemento->etiqueta_sub_id = $subNueva;
                        $elemento->etiqueta_id = $etiquetaSubId;
                        $elemento->save();

                        $stats['movimientos']++;

                        Log::info('📤 [Descomprimir] Elemento separado', [
                            'elemento' => $elemento->id,
                            'de' => $subOriginal,
                            'a' => $subNueva,
                        ]);
                    }
                }

                // 4) Limpiar subs que quedaron vacías y recalcular pesos
                foreach ($subsOriginales as $subOriginal) {
                    $this->eliminarSubSiVacia($subOriginal);
                }

                // Recalcular pesos del padre - obtener todas las subs afectadas
                $elementosActualizados = Elemento::where(function ($q) use ($maquinaId) {
                    $q->where('maquina_id', $maquinaId)
                        ->orWhere('maquina_id_2', $maquinaId)
                        ->orWhere('maquina_id_3', $maquinaId);
                })
                    ->where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                    ->pluck('etiqueta_sub_id')
                    ->unique()
                    ->toArray();

                $subsAfectadas = array_unique(array_merge($subsOriginales, $elementosActualizados));
                $this->recalcularPesos($codigoPadre, $subsAfectadas);
            }

            // Contar subetiquetas únicas después
            $elementosDespues = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId)
                    ->orWhere('maquina_id_3', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id')
                ->get();

            $stats['subetiquetas_despues'] = $elementosDespues->pluck('etiqueta_sub_id')->unique()->count();

            Log::info('✅ [Descomprimir] Completado', $stats);

            return [
                'success' => true,
                'message' => "Descompresión completada. {$stats['movimientos']} elementos separados. " .
                    "Subetiquetas: {$stats['subetiquetas_antes']} → {$stats['subetiquetas_despues']}",
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            Log::error('❌ [Descomprimir] Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Error al descomprimir: ' . $e->getMessage(),
                'stats' => $stats,
            ];
        }
    }
}
