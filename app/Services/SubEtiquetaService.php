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
        $this->asegurarFilaSub($subNuevo, $padre);

        // mover elemento a la nueva sub
        $elemento->etiqueta_sub_id = $subNuevo;
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
            $this->asegurarFilaSub($subNuevo, $padre);

            $elemento->etiqueta_sub_id = $subNuevo;
            $elemento->save();

            // si la original se quedó vacía, limpiarla
            if ($subIdOriginal && $subIdOriginal !== $subNuevo) {
                $this->eliminarSubSiVacia($subIdOriginal);
            }

            Log::info('🆕 [Encarretado] Creo y asigno sub nueva (sin hermanos)', ['sub' => $subNuevo]);
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

        if ($subConEspacio) {
            // Hay espacio en una sub existente
            $subDestino = (string) $subConEspacio;

            Log::info('✅ [Encarretado] Sub con espacio encontrada', [
                'sub' => $subDestino,
                'elementos_actuales' => $subsCounts[$subDestino],
                'max' => $maxElementosPorSub,
            ]);
        } else {
            // Todas las subs están llenas, crear una nueva
            $subDestino = Etiqueta::generarCodigoSubEtiqueta($codigoPadre);
            $this->asegurarFilaSub($subDestino, $padre);

            Log::info('🆕 [Encarretado] Todas las subs llenas, creo nueva', [
                'sub' => $subDestino,
                'subs_llenas' => $subsCounts->all(),
            ]);
        }

        // 5) Asignar el elemento a la sub destino
        if ($elemento->etiqueta_sub_id !== $subDestino) {
            $elemento->etiqueta_sub_id = $subDestino;
            $elemento->save();
            Log::info('📌 [Encarretado] Elemento asignado a sub', [
                'elemento' => $elemento->id,
                'sub'      => $subDestino,
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

    /** Crea fila de etiquetas para la sub (copia datos del padre) si no existe. */
    protected function asegurarFilaSub(string $subId, Etiqueta $padre): void
    {
        $data = [
            'codigo'          => $padre->codigo,
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

        // Usar try-catch para manejar race conditions
        try {
            $created = Etiqueta::firstOrCreate(
                ['etiqueta_sub_id' => $subId],
                $data
            );

            if ($created->wasRecentlyCreated) {
                Log::info('🧱 Fila sub creada', ['sub' => $subId]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Si hay error de duplicado, simplemente ignorar (ya existe)
            if ($e->errorInfo[1] != 1062) {
                throw $e;
            }
            Log::info('ℹ️ Sub ya existía (race condition evitada)', ['sub' => $subId]);
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
}
