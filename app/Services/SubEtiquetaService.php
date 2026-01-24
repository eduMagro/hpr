<?php

namespace App\Services;

use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\OrdenPlanilla;
use Illuminate\Support\Facades\Schema;
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
        $padre = null;
        if ($elemento->etiqueta_id) {
            $padre = Etiqueta::lockForUpdate()->find($elemento->etiqueta_id);
        }

        // Si no se encontró por etiqueta_id, buscar por código padre
        if (!$padre && $subIdOriginal) {
            $codigoPadre = Str::before($subIdOriginal, '.');
            $padre = Etiqueta::lockForUpdate()
                ->where('planilla_id', $elemento->planilla_id)
                ->where('codigo', $codigoPadre)
                ->first();

            // Actualizar el etiqueta_id del elemento para futuras operaciones
            if ($padre) {
                $elemento->etiqueta_id = $padre->id;
                $elemento->save();
            }
        }

        // Si no se encontró ninguna etiqueta padre, devolver el subIdOriginal sin cambios
        if (!$padre) {
            return [$subIdOriginal, $subIdOriginal];
        }

        $codigoPadre   = (string) $padre->codigo;
        $prefijoPadre  = $codigoPadre . '.';

        $maq = Maquina::findOrFail($nuevaMaquinaReal);
        $esMSR20 = strtoupper($maq->codigo ?? '') === 'MSR20';

        // MSR20: agrupa con hermanos, resto: un elemento por sub
        $subDestino = $esMSR20
            ? $this->modoEncarretado($elemento, $padre, $prefijoPadre, $nuevaMaquinaReal, $subIdOriginal)
            : $this->modoBarra($elemento, $padre, $prefijoPadre, $subIdOriginal);

        // Nada cambió
        if ($subDestino === $subIdOriginal) {
            return [$subDestino, $subIdOriginal];
        }

        // Recalcular pesos (sub-origen, sub-destino y padre)
        $this->recalcularPesos($codigoPadre, array_filter([$subIdOriginal, $subDestino]));

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
        $padre = null;
        if ($elemento->etiqueta_id) {
            $padre = Etiqueta::lockForUpdate()->find($elemento->etiqueta_id);
        }

        // Si no se encontró por etiqueta_id, buscar por código padre
        if (!$padre && $subIdOriginal) {
            $codigoPadreSub = Str::before($subIdOriginal, '.');
            $padre = Etiqueta::lockForUpdate()
                ->where('planilla_id', $elemento->planilla_id)
                ->where('codigo', $codigoPadreSub)
                ->first();

            // Actualizar el etiqueta_id del elemento para futuras operaciones
            if ($padre) {
                $elemento->etiqueta_id = $padre->id;
                $elemento->save();
            }
        }

        // Si no se encontró ninguna etiqueta padre, devolver el subIdOriginal sin cambios
        if (!$padre) {
            return [$subIdOriginal, $subIdOriginal];
        }

        $codigoPadre   = (string) $padre->codigo;
        $prefijoPadre  = $codigoPadre . '.';

        /** @var Maquina $maq */
        $maq  = Maquina::findOrFail($nuevaMaquinaReal);
        $tipo = strtolower((string) ($maq->tipo_material ?? ''));

        $subDestino = $tipo === 'barra'
            ? $this->modoBarra($elemento, $padre, $prefijoPadre, $subIdOriginal)
            : $this->modoEncarretado($elemento, $padre, $prefijoPadre, $nuevaMaquinaReal, $subIdOriginal);

        // Nada cambió
        if ($subDestino === $subIdOriginal) {
            return [$subDestino, $subIdOriginal];
        }

        // Recalcular pesos (sub-origen, sub-destino y padre)
        $this->recalcularPesos($codigoPadre, array_filter([$subIdOriginal, $subDestino]));

        return [$subDestino, $subIdOriginal];
    }

    /* ===========================  MODO BARRA  =========================== */

    protected function modoBarra(Elemento $elemento, Etiqueta $padre, string $prefijoPadre, ?string $subIdOriginal): string
    {
        // si ya tiene sub del mismo prefijo y es única → conservar
        if ($subIdOriginal && str_starts_with($subIdOriginal, $prefijoPadre)) {
            $cuantos = Elemento::where('etiqueta_sub_id', $subIdOriginal)->count();
            if ($cuantos === 1) {
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

        // 1) Localizar HERMANOS: mismo CÓDIGO PADRE (no etiqueta_id) y YA en esa máquina
        // Busca por prefijo del etiqueta_sub_id para encontrar hermanos de diferentes etiquetas
        $hermanos = Elemento::whereNotNull('etiqueta_sub_id')
            ->where('etiqueta_sub_id', 'like', $prefijoPadre . '%')
            ->where('id', '!=', $elemento->id) // Excluir el elemento actual
            ->get()
            ->filter(fn($e) => (int) $this->obtenerMaquinaReal($e) === $nuevaMaquinaReal);

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
        } else {
            // Todas las subs están llenas, crear una nueva
            $subDestino = Etiqueta::generarCodigoSubEtiqueta($codigoPadre);
            $etiquetaIdDestino = $this->asegurarFilaSub($subDestino, $padre);
        }

        // 5) Asignar el elemento a la sub destino
        if ($elemento->etiqueta_sub_id !== $subDestino) {
            $elemento->etiqueta_sub_id = $subDestino;
            if ($etiquetaIdDestino) {
                $elemento->etiqueta_id = $etiquetaIdDestino;
            }
            $elemento->save();
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
            return;
        }

        Etiqueta::where('etiqueta_sub_id', $subId)->delete();
    }

    /** Crea fila de etiquetas para la sub (copia datos del padre) si no existe. Devuelve el ID. */
    protected function asegurarFilaSub(string $subId, Etiqueta $padre): int
    {
        // Primero verificar si ya existe (con y sin soft deletes)
        $existente = Etiqueta::withTrashed()->where('etiqueta_sub_id', $subId)->first();

        if ($existente) {
            // Si está eliminada (soft delete), restaurarla
            if ($existente->trashed()) {
                $existente->restore();
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
            return (int) $etiquetaSub->id;
        } catch (\Illuminate\Database\QueryException $e) {
            // Si falla por duplicado, buscar la existente (incluyendo soft deleted)
            if ($e->errorInfo[1] == 1062) {
                $existente = Etiqueta::withTrashed()->where('etiqueta_sub_id', $subId)->first();
                if ($existente) {
                    if ($existente->trashed()) {
                        $existente->restore();
                    }
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
        }

        $pesoPadre = (float) Elemento::where('etiqueta_sub_id', 'like', $codigoPadre . '.%')->sum('peso');
        Etiqueta::where('codigo', $codigoPadre)->whereNull('etiqueta_sub_id')->update(['peso' => $pesoPadre]);
    }

    /** Normaliza cadenas. */
    protected function normalizar(string $s): string
    {
        return Str::of($s)->lower()->ascii()->replaceMatches('/\s+/', ' ')->trim()->__toString();
    }

    /**
     * Obtiene la máquina "real" del elemento.
     * Usa el accessor del modelo: maquina_id_2 ?? maquina_id
     */
    protected function obtenerMaquinaReal(Elemento $e): ?int
    {
        return $e->maquina_real_id;
    }

    /* ===============  COMPRIMIR / DESCOMPRIMIR ETIQUETAS  =============== */

    /**
     * COMPRIMIR: Agrupa elementos hermanos de una máquina en etiquetas compartidas.
     * Máximo 5 elementos por etiqueta_sub_id.
     * Solo afecta elementos asignados a la máquina y posiciones especificadas.
     *
     * Los hermanos son elementos que comparten el mismo CÓDIGO PADRE (extraído del etiqueta_sub_id).
     * Ejemplo: ETQ2512001.01, ETQ2512001.02, ETQ2512001.03 son hermanos (código padre: ETQ2512001)
     *
     * @param int $maquinaId ID de la máquina
     * @param array $posiciones Posiciones de planillas a filtrar (de orden_planillas)
     * @return array ['success' => bool, 'message' => string, 'stats' => array]
     */
    public function comprimirEtiquetasPorMaquina(int $maquinaId, array $posiciones = []): array
    {
        $maxElementosPorSub = 5;
        $stats = [
            'elementos_procesados' => 0,
            'subetiquetas_antes' => 0,
            'subetiquetas_despues' => 0,
            'movimientos' => 0,
        ];

        try {
            // 1) Obtener planilla_ids según las posiciones seleccionadas en orden_planillas
            $planillaIds = [];
            if (!empty($posiciones)) {
                $planillaIds = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->whereIn('posicion', $posiciones)
                    ->pluck('planilla_id')
                    ->toArray();
            }

            // 2) Obtener elementos de esta máquina, filtrados por planilla si hay posiciones
            // Solo elementos cuya etiqueta tenga estado 'pendiente'
            $query = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id')
                ->whereHas('etiquetaRelacion', function ($q) {
                    $q->where('estado', 'pendiente');
                });

            // Filtrar por planilla_id si hay posiciones seleccionadas
            if (!empty($planillaIds)) {
                $query->whereIn('planilla_id', $planillaIds);
            }

            $elementos = $query->get();

            if ($elementos->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay elementos con etiquetas pendientes para comprimir.',
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

                $subsOriginales = [];

                // Procesar cada tipo por separado (estribos y normales no se mezclan)
                $tiposElementos = [
                    'normales' => $normales->sortBy('etiqueta_sub_id')->values(),
                    'estribos' => $estribos->sortBy('etiqueta_sub_id')->values(),
                ];

                // Rastrear estados originales por subetiqueta y qué subs destino se usan
                $estadosPorSubOriginal = [];
                $subsDestino = [];

                foreach ($tiposElementos as $tipoNombre => $elementosDelTipo) {
                    if ($elementosDelTipo->isEmpty()) {
                        continue;
                    }

                    // Redistribuir: todos los elementos del tipo van a la primera subetiqueta disponible
                    // hasta llenarla (máx 5), luego a la siguiente, etc.
                    $subActual = null;
                    $contadorEnSub = 0;

                    foreach ($elementosDelTipo as $elemento) {
                        $stats['elementos_procesados']++;
                        $subOriginal = $elemento->etiqueta_sub_id;
                        $subsOriginales[] = $subOriginal;

                        // Guardar el estado de la sub original si no lo tenemos
                        if (!isset($estadosPorSubOriginal[$subOriginal])) {
                            $estadoOriginal = Etiqueta::where('etiqueta_sub_id', $subOriginal)->value('estado');
                            $estadosPorSubOriginal[$subOriginal] = $estadoOriginal ?? 'pendiente';
                        }

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

                            // Inicializar tracking de estados para esta sub destino
                            if (!isset($subsDestino[$subActual])) {
                                $subsDestino[$subActual] = [];
                            }
                        }

                        // Rastrear qué estados originales van a cada sub destino
                        $subsDestino[$subActual][] = $estadosPorSubOriginal[$subOriginal];

                        // Mover elemento a la sub actual si es diferente
                        if ($elemento->etiqueta_sub_id !== $subActual) {
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
                        }

                        $contadorEnSub++;
                    }
                }

                // Recalcular estado de las subs destino si mezclaron estados diferentes
                foreach ($subsDestino as $subId => $estadosOriginales) {
                    $estadosUnicos = array_unique($estadosOriginales);

                    if (count($estadosUnicos) > 1) {
                        // Mezclaron estados diferentes → fabricando + fecha_finalizacion = null
                        Etiqueta::where('etiqueta_sub_id', $subId)->update([
                            'estado' => 'fabricando',
                            'fecha_finalizacion' => null,
                        ]);
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
                        ->orWhere('maquina_id_2', $maquinaId);
                })
                    ->where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                    ->pluck('etiqueta_sub_id')
                    ->unique()
                    ->toArray();

                $subsAfectadas = array_unique(array_merge($subsOriginalesUnicas, $elementosActualizados));
                $this->recalcularPesos($codigoPadre, $subsAfectadas);
            }

            // Contar subetiquetas únicas después (con el mismo filtro de planillas)
            $queryDespues = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id');

            if (!empty($planillaIds)) {
                $queryDespues->whereIn('planilla_id', $planillaIds);
            }

            $elementosDespues = $queryDespues->get();
            $stats['subetiquetas_despues'] = $elementosDespues->pluck('etiqueta_sub_id')->unique()->count();

            return [
                'success' => true,
                'message' => "Compresión completada. {$stats['movimientos']} elementos reagrupados. " .
                    "Subetiquetas: {$stats['subetiquetas_antes']} → {$stats['subetiquetas_despues']}",
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al comprimir: ' . $e->getMessage(),
                'stats' => $stats,
            ];
        }
    }

    /**
     * DESCOMPRIMIR: Separa elementos en subetiquetas individuales (1 elemento = 1 subetiqueta).
     * Solo afecta elementos asignados a la máquina y posiciones especificadas.
     *
     * @param int $maquinaId ID de la máquina
     * @param array $posiciones Posiciones de planillas a filtrar (de orden_planillas)
     * @return array ['success' => bool, 'message' => string, 'stats' => array]
     */
    public function descomprimirEtiquetasPorMaquina(int $maquinaId, array $posiciones = []): array
    {
        $stats = [
            'elementos_procesados' => 0,
            'subetiquetas_antes' => 0,
            'subetiquetas_despues' => 0,
            'movimientos' => 0,
        ];

        try {
            // 1) Obtener planilla_ids según las posiciones seleccionadas en orden_planillas
            $planillaIds = [];
            if (!empty($posiciones)) {
                $planillaIds = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->whereIn('posicion', $posiciones)
                    ->pluck('planilla_id')
                    ->toArray();
            }

            // 2) Obtener elementos de esta máquina, filtrados por planilla si hay posiciones
            // Solo elementos cuya etiqueta tenga estado 'pendiente'
            $query = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id')
                ->whereHas('etiquetaRelacion', function ($q) {
                    $q->where('estado', 'pendiente');
                });

            // Filtrar por planilla_id si hay posiciones seleccionadas
            if (!empty($planillaIds)) {
                $query->whereIn('planilla_id', $planillaIds);
            }

            $elementos = $query->get();

            if ($elementos->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay elementos con etiquetas pendientes para descomprimir.',
                    'stats' => $stats,
                ];
            }

            // Contar subetiquetas únicas antes
            $stats['subetiquetas_antes'] = $elementos->pluck('etiqueta_sub_id')->unique()->count();

            // 3) Agrupar elementos por CÓDIGO PADRE (extraído del etiqueta_sub_id)
            $gruposPorCodigoPadre = $elementos->groupBy(function ($elemento) {
                return Str::before($elemento->etiqueta_sub_id, '.');
            });

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
                    continue;
                }

                // Agrupar elementos por su etiqueta_sub_id actual
                $elementosPorSub = $elementosGrupo->groupBy('etiqueta_sub_id');
                $subsOriginales = $elementosPorSub->keys()->toArray();

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
                    }
                }

                // 4) Limpiar subs que quedaron vacías y recalcular pesos
                foreach ($subsOriginales as $subOriginal) {
                    $this->eliminarSubSiVacia($subOriginal);
                }

                // Recalcular pesos del padre - obtener todas las subs afectadas
                $elementosActualizados = Elemento::where(function ($q) use ($maquinaId) {
                    $q->where('maquina_id', $maquinaId)
                        ->orWhere('maquina_id_2', $maquinaId);
                })
                    ->where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                    ->pluck('etiqueta_sub_id')
                    ->unique()
                    ->toArray();

                $subsAfectadas = array_unique(array_merge($subsOriginales, $elementosActualizados));
                $this->recalcularPesos($codigoPadre, $subsAfectadas);
            }

            // Contar subetiquetas únicas después (con el mismo filtro de planillas)
            $queryDespues = Elemento::where(function ($q) use ($maquinaId) {
                $q->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId);
            })
                ->whereNotNull('etiqueta_sub_id');

            if (!empty($planillaIds)) {
                $queryDespues->whereIn('planilla_id', $planillaIds);
            }

            $elementosDespues = $queryDespues->get();
            $stats['subetiquetas_despues'] = $elementosDespues->pluck('etiqueta_sub_id')->unique()->count();

            return [
                'success' => true,
                'message' => "Descompresión completada. {$stats['movimientos']} elementos separados. " .
                    "Subetiquetas: {$stats['subetiquetas_antes']} → {$stats['subetiquetas_despues']}",
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al descomprimir: ' . $e->getMessage(),
                'stats' => $stats,
            ];
        }
    }
}
