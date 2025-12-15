<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\GrupoResumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para agrupar visualmente etiquetas con mismo diámetro y dimensiones.
 * Las etiquetas originales se mantienen intactas para poder imprimirlas individualmente.
 */
class ResumenEtiquetaService
{
    /**
     * Agrupa etiquetas visualmente por diámetro + dimensiones.
     * Las etiquetas ORIGINALES se mantienen intactas.
     *
     * @param int $planillaId ID de la planilla
     * @param int|null $maquinaId ID de la máquina (opcional, filtra elementos)
     * @param int|null $usuarioId ID del usuario que realiza la acción
     * @return array Resultado de la operación
     */
    public function resumir(
        int $planillaId,
        ?int $maquinaId = null,
        ?int $usuarioId = null
    ): array {
        return DB::transaction(function () use ($planillaId, $maquinaId, $usuarioId) {

            // 1. Obtener etiquetas elegibles (pendientes, no agrupadas ya)
            $query = Etiqueta::where('planilla_id', $planillaId)
                ->where('estado', 'pendiente')
                ->whereNull('grupo_resumen_id');

            if ($maquinaId) {
                // Filtrar etiquetas cuyos elementos estén en esta máquina
                $query->whereHas('elementos', function ($q) use ($maquinaId) {
                    $q->where(function ($subQ) use ($maquinaId) {
                        $subQ->where('maquina_id', $maquinaId)
                            ->orWhere('maquina_id_2', $maquinaId)
                            ->orWhere('maquina_id_3', $maquinaId);
                    });
                });
            }

            $etiquetas = $query->with('elementos')->get();

            if ($etiquetas->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay etiquetas pendientes para agrupar',
                    'grupos' => [],
                    'stats' => ['grupos_creados' => 0, 'etiquetas_agrupadas' => 0],
                ];
            }

            // 2. Agrupar etiquetas por diámetro + dimensiones de sus elementos
            $agrupaciones = [];

            foreach ($etiquetas as $etiqueta) {
                $elementos = $etiqueta->elementos;
                if ($elementos->isEmpty()) {
                    continue;
                }

                // Filtrar elementos por máquina si se especificó
                if ($maquinaId) {
                    $elementos = $elementos->filter(function ($e) use ($maquinaId) {
                        return $e->maquina_id == $maquinaId
                            || $e->maquina_id_2 == $maquinaId
                            || $e->maquina_id_3 == $maquinaId;
                    });
                }

                if ($elementos->isEmpty()) {
                    continue;
                }

                // Obtener perfil de la etiqueta (diámetro + dimensiones del primer elemento)
                // Asumimos que todos los elementos de una etiqueta tienen mismas características
                $primerElemento = $elementos->first();
                $diametro = (float) $primerElemento->diametro;
                $dimensiones = $this->normalizarDimensiones($primerElemento->dimensiones);

                $key = "{$diametro}|{$dimensiones}";

                if (!isset($agrupaciones[$key])) {
                    $agrupaciones[$key] = [
                        'diametro' => $diametro,
                        'dimensiones' => $dimensiones,
                        'dimensiones_original' => $primerElemento->dimensiones,
                        'etiquetas' => [],
                    ];
                }

                $agrupaciones[$key]['etiquetas'][] = $etiqueta;
            }

            // 3. Crear grupos solo para los que tienen más de 1 etiqueta
            $gruposCreados = [];
            $stats = ['grupos_creados' => 0, 'etiquetas_agrupadas' => 0];

            foreach ($agrupaciones as $key => $grupoData) {
                if (count($grupoData['etiquetas']) <= 1) {
                    continue; // No agrupar etiquetas individuales
                }

                // Crear grupo de resumen
                $grupo = GrupoResumen::create([
                    'codigo' => GrupoResumen::generarCodigo(),
                    'planilla_id' => $planillaId,
                    'maquina_id' => $maquinaId,
                    'diametro' => $grupoData['diametro'],
                    'dimensiones' => $grupoData['dimensiones_original'],
                    'usuario_id' => $usuarioId,
                    'activo' => true,
                ]);

                // Asignar etiquetas al grupo
                foreach ($grupoData['etiquetas'] as $etiqueta) {
                    $etiqueta->grupo_resumen_id = $grupo->id;
                    $etiqueta->save();
                    $stats['etiquetas_agrupadas']++;
                }

                // Recalcular estadísticas del grupo
                $grupo->recalcularEstadisticas();

                $gruposCreados[] = $grupo->fresh(['etiquetas']);
                $stats['grupos_creados']++;

                Log::info('Grupo de resumen creado', [
                    'grupo_id' => $grupo->id,
                    'codigo' => $grupo->codigo,
                    'diametro' => $grupo->diametro,
                    'dimensiones' => $grupo->dimensiones,
                    'etiquetas' => count($grupoData['etiquetas']),
                ]);
            }

            return [
                'success' => true,
                'message' => $stats['grupos_creados'] > 0
                    ? "Resumen completado: {$stats['grupos_creados']} grupos con {$stats['etiquetas_agrupadas']} etiquetas"
                    : 'No se encontraron etiquetas similares para agrupar',
                'grupos' => $gruposCreados,
                'stats' => $stats,
            ];
        });
    }

    /**
     * Vista previa de los grupos que se crearían sin ejecutar cambios.
     *
     * @param int $planillaId ID de la planilla
     * @param int|null $maquinaId ID de la máquina (opcional)
     * @return array Vista previa de grupos
     */
    public function previsualizar(int $planillaId, ?int $maquinaId = null): array
    {
        $query = Etiqueta::where('planilla_id', $planillaId)
            ->where('estado', 'pendiente')
            ->whereNull('grupo_resumen_id');

        if ($maquinaId) {
            $query->whereHas('elementos', function ($q) use ($maquinaId) {
                $q->where(function ($subQ) use ($maquinaId) {
                    $subQ->where('maquina_id', $maquinaId)
                        ->orWhere('maquina_id_2', $maquinaId)
                        ->orWhere('maquina_id_3', $maquinaId);
                });
            });
        }

        $etiquetas = $query->with(['elementos', 'planilla'])->get();

        // Agrupar
        $agrupaciones = [];
        foreach ($etiquetas as $etiqueta) {
            $elementos = $etiqueta->elementos;
            if ($elementos->isEmpty()) {
                continue;
            }

            if ($maquinaId) {
                $elementos = $elementos->filter(function ($e) use ($maquinaId) {
                    return $e->maquina_id == $maquinaId
                        || $e->maquina_id_2 == $maquinaId
                        || $e->maquina_id_3 == $maquinaId;
                });
            }

            if ($elementos->isEmpty()) {
                continue;
            }

            $primerElemento = $elementos->first();
            $diametro = (float) $primerElemento->diametro;
            $dimensiones = $this->normalizarDimensiones($primerElemento->dimensiones);
            $key = "{$diametro}|{$dimensiones}";

            if (!isset($agrupaciones[$key])) {
                $agrupaciones[$key] = [
                    'diametro' => $diametro,
                    'dimensiones' => $primerElemento->dimensiones ?: 'barra',
                    'etiquetas' => [],
                    'total_elementos' => 0,
                    'peso_total' => 0,
                ];
            }

            $cantidadElementos = $elementos->count();
            $pesoElementos = $elementos->sum('peso');

            $agrupaciones[$key]['etiquetas'][] = [
                'id' => $etiqueta->id,
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                'nombre' => $etiqueta->nombre,
                'planilla_codigo' => $etiqueta->planilla->codigo_limpio ?? $etiqueta->planilla->codigo ?? 'N/A',
                'elementos' => $cantidadElementos,
                'elementos_detalle' => $elementos->map(fn($e) => [
                    'id' => $e->id,
                    'codigo' => $e->codigo,
                    'marca' => $e->marca,
                    'peso' => round($e->peso, 2),
                    'diametro' => $e->diametro,
                    'dimensiones' => $e->dimensiones,
                ])->values()->toArray(),
                'peso' => round($pesoElementos, 2),
            ];
            $agrupaciones[$key]['total_elementos'] += $cantidadElementos;
            $agrupaciones[$key]['peso_total'] += $pesoElementos;
        }

        // Filtrar solo grupos con más de 1 etiqueta
        $preview = collect($agrupaciones)
            ->filter(fn($g) => count($g['etiquetas']) > 1)
            ->map(fn($g) => [
                ...$g,
                'peso_total' => round($g['peso_total'], 2),
                'total_etiquetas' => count($g['etiquetas']),
            ])
            ->values()
            ->toArray();

        return [
            'grupos' => $preview,
            'total_grupos' => count($preview),
            'total_etiquetas' => collect($preview)->sum('total_etiquetas'),
            'total_elementos' => collect($preview)->sum('total_elementos'),
            'peso_total' => round(collect($preview)->sum('peso_total'), 2),
        ];
    }

    /**
     * Desagrupa un grupo específico.
     *
     * @param int $grupoId ID del grupo a desagrupar
     * @return array Resultado de la operación
     */
    public function desagrupar(int $grupoId): array
    {
        $grupo = GrupoResumen::find($grupoId);

        if (!$grupo) {
            return ['success' => false, 'message' => 'Grupo no encontrado'];
        }

        if (!$grupo->activo) {
            return ['success' => false, 'message' => 'Este grupo ya está desagrupado'];
        }

        $totalEtiquetas = $grupo->total_etiquetas;
        $codigo = $grupo->codigo;

        $grupo->desagrupar();

        Log::info('Grupo de resumen desagrupado', [
            'grupo_id' => $grupoId,
            'codigo' => $codigo,
            'etiquetas_liberadas' => $totalEtiquetas,
        ]);

        return [
            'success' => true,
            'message' => "Grupo {$codigo} desagrupado: {$totalEtiquetas} etiquetas liberadas",
        ];
    }

    /**
     * Desagrupa todos los grupos activos de una planilla.
     *
     * @param int $planillaId ID de la planilla
     * @param int|null $maquinaId ID de la máquina (opcional)
     * @return array Resultado de la operación
     */
    public function desagruparTodos(int $planillaId, ?int $maquinaId = null): array
    {
        $query = GrupoResumen::where('planilla_id', $planillaId)->where('activo', true);

        if ($maquinaId) {
            $query->where('maquina_id', $maquinaId);
        }

        $grupos = $query->get();
        $total = $grupos->count();

        if ($total === 0) {
            return ['success' => true, 'message' => 'No hay grupos activos para desagrupar'];
        }

        foreach ($grupos as $grupo) {
            $grupo->desagrupar();
        }

        Log::info('Grupos de resumen desagrupados masivamente', [
            'planilla_id' => $planillaId,
            'maquina_id' => $maquinaId,
            'grupos_desagrupados' => $total,
        ]);

        return [
            'success' => true,
            'message' => "{$total} grupos desagrupados",
            'total' => $total,
        ];
    }

    /**
     * Obtiene los grupos activos de una planilla.
     *
     * @param int $planillaId ID de la planilla
     * @param int|null $maquinaId ID de la máquina (opcional)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerGrupos(int $planillaId, ?int $maquinaId = null)
    {
        $query = GrupoResumen::where('planilla_id', $planillaId)
            ->where('activo', true)
            ->with(['etiquetas' => function ($q) {
                $q->with('elementos');
            }]);

        if ($maquinaId) {
            $query->where('maquina_id', $maquinaId);
        }

        return $query->orderBy('diametro')->orderBy('dimensiones')->get();
    }

    /**
     * Obtiene las etiquetas de un grupo para imprimir.
     *
     * @param int $grupoId ID del grupo
     * @return array Datos de etiquetas para impresión
     */
    public function obtenerEtiquetasParaImprimir(int $grupoId): array
    {
        $grupo = GrupoResumen::with(['etiquetas.elementos', 'etiquetas.planilla.obra', 'etiquetas.planilla.cliente'])
            ->findOrFail($grupoId);

        $etiquetas = $grupo->etiquetas->map(function ($etiqueta) {
            return [
                'id' => $etiqueta->id,
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                'codigo' => $etiqueta->codigo,
                'nombre' => $etiqueta->nombre,
                'planilla_codigo' => $etiqueta->planilla->codigo ?? '',
                'planilla_codigo_limpio' => $etiqueta->planilla->codigo_limpio ?? $etiqueta->planilla->codigo ?? '',
                'planilla_seccion' => $etiqueta->planilla->seccion ?? '',
                'obra' => $etiqueta->planilla->obra->obra ?? 'N/A',
                'cliente' => $etiqueta->planilla->cliente->empresa ?? 'N/A',
                'peso' => round($etiqueta->elementos->sum('peso'), 2),
                'elementos_count' => $etiqueta->elementos->count(),
                // Datos completos de elementos para renderizar SVG
                'elementos' => $etiqueta->elementos->map(function ($el) {
                    return [
                        'id' => $el->id,
                        'diametro' => $el->diametro,
                        'dimensiones' => $el->dimensiones,
                        'barras' => $el->barras,
                        'peso' => $el->peso,
                        'tipo' => $el->tipo,
                    ];
                })->toArray(),
            ];
        });

        return [
            'grupo' => [
                'id' => $grupo->id,
                'codigo' => $grupo->codigo,
                'diametro' => $grupo->diametro,
                'dimensiones' => $grupo->dimensiones,
                'planilla_id' => $grupo->planilla_id,
                'maquina_id' => $grupo->maquina_id,
            ],
            'etiquetas' => $etiquetas->toArray(),
        ];
    }

    /**
     * Cambia el estado de todas las etiquetas de un grupo.
     * Los tiempos de fabricación se reparten entre todas las etiquetas.
     *
     * @param int $grupoId ID del grupo
     * @param int $maquinaId ID de la máquina
     * @param int $longitudSeleccionada Longitud seleccionada (para máquinas de barra)
     * @param int|null $usuarioId ID del usuario que realiza la acción
     * @return array Resultado de la operación
     */
    public function cambiarEstadoGrupo(
        int $grupoId,
        int $maquinaId,
        int $longitudSeleccionada = 0,
        ?int $usuarioId = null
    ): array {
        $grupo = GrupoResumen::with('etiquetas')->find($grupoId);

        if (!$grupo || !$grupo->activo) {
            return ['success' => false, 'message' => 'Grupo no encontrado o inactivo'];
        }

        $etiquetas = $grupo->etiquetas;
        if ($etiquetas->isEmpty()) {
            return ['success' => false, 'message' => 'El grupo no tiene etiquetas'];
        }

        // Determinar estado actual (tomar el de la primera etiqueta)
        $estadoActual = strtolower($etiquetas->first()->estado ?? 'pendiente');

        // Determinar siguiente estado
        $siguienteEstado = match ($estadoActual) {
            'pendiente' => 'fabricando',
            'fabricando' => 'completada',
            default => null,
        };

        if (!$siguienteEstado) {
            return [
                'success' => false,
                'message' => "Las etiquetas ya están en estado '{$estadoActual}'"
            ];
        }

        $ahora = now();
        $totalEtiquetas = $etiquetas->count();

        return DB::transaction(function () use (
            $etiquetas, $siguienteEstado, $ahora, $totalEtiquetas, $usuarioId, $grupo
        ) {
            $etiquetasActualizadas = [];
            $etiquetasParaImprimir = [];

            foreach ($etiquetas as $index => $etiqueta) {
                $estadoAnterior = $etiqueta->estado;

                if ($siguienteEstado === 'fabricando') {
                    // Inicio de fabricación
                    $etiqueta->estado = 'fabricando';
                    $etiqueta->fecha_inicio = $ahora;
                    $etiqueta->operario1_id = $usuarioId;
                } elseif ($siguienteEstado === 'completada') {
                    // Completar fabricación - repartir tiempo
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = $ahora;

                    // Calcular tiempo total y repartir
                    if ($etiqueta->fecha_inicio) {
                        $tiempoTotalSegundos = $etiqueta->fecha_inicio->diffInSeconds($ahora);
                        $tiempoPorEtiqueta = (int) round($tiempoTotalSegundos / $totalEtiquetas);

                        // Ajustar fecha_inicio para que el tiempo sea proporcional
                        $etiqueta->fecha_inicio = $ahora->copy()->subSeconds($tiempoPorEtiqueta);
                    }

                    // Recolectar etiquetas que NO se han impreso para impresión automática
                    if (!$etiqueta->impresa) {
                        $etiquetasParaImprimir[] = [
                            'id' => $etiqueta->id,
                            'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                        ];
                        // Marcar como impresa
                        $etiqueta->impresa = true;
                    }
                }

                $etiqueta->save();

                // Actualizar estado de los elementos
                // Los elementos usan género masculino: fabricado, completado, etc.
                $estadoElemento = match ($siguienteEstado) {
                    'fabricando' => 'fabricando',
                    'completada' => 'completado',
                    'fabricada' => 'fabricado',
                    'ensamblada' => 'ensamblado',
                    'soldada' => 'soldado',
                    default => $siguienteEstado,
                };
                Elemento::where('etiqueta_sub_id', $etiqueta->etiqueta_sub_id)
                    ->update(['estado' => $estadoElemento]);

                $etiquetasActualizadas[] = [
                    'id' => $etiqueta->id,
                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $siguienteEstado,
                ];

                Log::info('Estado de etiqueta actualizado via grupo', [
                    'grupo_id' => $grupo->id,
                    'etiqueta_id' => $etiqueta->id,
                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $siguienteEstado,
                ]);
            }

            // Si se completó el grupo, desagrupar las etiquetas para trabajar individualmente
            $desagrupado = false;
            if ($siguienteEstado === 'completada') {
                // Desagrupar: quitar grupo_resumen_id de las etiquetas y marcar grupo como inactivo
                Etiqueta::where('grupo_resumen_id', $grupo->id)
                    ->update(['grupo_resumen_id' => null]);

                $grupo->activo = false;
                $grupo->save();
                $desagrupado = true;

                Log::info('Grupo desagrupado automáticamente al completar', [
                    'grupo_id' => $grupo->id,
                    'codigo' => $grupo->codigo,
                    'etiquetas_liberadas' => $totalEtiquetas,
                ]);
            }

            return [
                'success' => true,
                'message' => "Grupo actualizado a '{$siguienteEstado}'" . ($desagrupado ? ' y desagrupado' : ''),
                'estado' => $siguienteEstado,
                'nuevo_estado' => $siguienteEstado,
                'grupo_id' => $grupo->id,
                'planilla_id' => $grupo->planilla_id,
                'maquina_id' => $grupo->maquina_id,
                'etiquetas_actualizadas' => count($etiquetasActualizadas),
                'etiquetas' => $etiquetasActualizadas,
                'desagrupado' => $desagrupado,
                'imprimir_etiquetas' => $etiquetasParaImprimir,
            ];
        });
    }

    /**
     * Normaliza las dimensiones para comparación consistente.
     *
     * @param string|null $dimensiones
     * @return string
     */
    private function normalizarDimensiones(?string $dimensiones): string
    {
        if (empty($dimensiones)) {
            return 'barra';
        }

        // Normalizar: minúsculas, quitar espacios múltiples, trim
        $normalizado = mb_strtolower(trim($dimensiones));
        $normalizado = preg_replace('/\s+/', ' ', $normalizado);

        return $normalizado;
    }
}
