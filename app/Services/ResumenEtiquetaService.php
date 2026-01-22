<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\GrupoResumen;
use App\Models\Maquina;
use App\Models\Producto;
use App\Models\ProductoBase;
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
                            ->orWhere('maquina_id_2', $maquinaId);
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
                            || $e->maquina_id_2 == $maquinaId;
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
                        ->orWhere('maquina_id_2', $maquinaId);
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
                        || $e->maquina_id_2 == $maquinaId;
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

        Log::info('Grupo de resumen desagrupado manualmente', [
            'grupo_id' => $grupoId,
            'codigo' => $codigo,
            'etiquetas_liberadas' => $totalEtiquetas,
        ]);

        return [
            'success' => true,
            'message' => "Grupo {$codigo} desagrupado: {$totalEtiquetas} etiquetas liberadas (no se reagruparán automáticamente)",
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

        // Obtener la máquina
        $maquina = Maquina::find($maquinaId);
        if (!$maquina) {
            return ['success' => false, 'message' => 'Máquina no encontrada'];
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
            $etiquetas, $siguienteEstado, $ahora, $totalEtiquetas, $usuarioId, $grupo, $maquina, $maquinaId
        ) {
            $etiquetasActualizadas = [];
            $etiquetasParaImprimir = [];
            $productosAfectados = [];
            $productoNColada = null;
            $producto2NColada = null;

            // Obtener todos los elementos del grupo para asignación de productos
            $elementosDelGrupo = Elemento::whereIn('etiqueta_sub_id', $etiquetas->pluck('etiqueta_sub_id'))
                ->where(function ($q) use ($maquinaId) {
                    $q->where('maquina_id', $maquinaId)
                        ->orWhere('maquina_id_2', $maquinaId);
                })
                ->get();

            // Agrupar elementos por diámetro para asignación de productos
            $elementosPorDiametro = $elementosDelGrupo->groupBy(fn($e) => (int) $e->diametro);

            // Buscar productos disponibles en la máquina por diámetro
            $productosPorDiametro = [];
            foreach ($elementosPorDiametro->keys() as $diametro) {
                $productosPorDiametro[$diametro] = $maquina->productos()
                    ->whereHas('productoBase', fn($q) => $q->where('diametro', $diametro))
                    ->where('peso_stock', '>', 0)
                    ->with('productoBase')
                    ->orderBy('peso_stock')
                    ->get();
            }

            foreach ($etiquetas as $index => $etiqueta) {
                $estadoAnterior = $etiqueta->estado;

                if ($siguienteEstado === 'fabricando') {
                    // ═══════════════════════════════════════════════════════════════════
                    // PRIMER CLIC: PENDIENTE -> FABRICANDO
                    // Asignar producto_id a etiqueta y elementos
                    // ═══════════════════════════════════════════════════════════════════

                    // Obtener elementos de esta etiqueta en la máquina
                    $elementosEtiqueta = $elementosDelGrupo->where('etiqueta_sub_id', $etiqueta->etiqueta_sub_id);

                    foreach ($elementosEtiqueta as $elemento) {
                        $diametro = (int) $elemento->diametro;
                        $productos = $productosPorDiametro[$diametro] ?? collect();

                        if ($productos->isNotEmpty()) {
                            $producto = $productos->first();
                            $elemento->producto_id = $producto->id;
                            $elemento->estado = 'fabricando';
                            $elemento->users_id = $usuarioId;
                            $elemento->save();

                            // Guardar colada del primer producto para la respuesta
                            if (!$productoNColada) {
                                $productoNColada = $producto->n_colada;
                            }
                        } else {
                            $elemento->estado = 'fabricando';
                            $elemento->users_id = $usuarioId;
                            $elemento->save();
                        }
                    }

                    // Asignar producto_id a la etiqueta (usar el primer producto encontrado)
                    $primerElemento = $elementosEtiqueta->first();
                    if ($primerElemento && $primerElemento->producto_id && !$etiqueta->producto_id) {
                        $etiqueta->producto_id = $primerElemento->producto_id;
                    }

                    $etiqueta->estado = 'fabricando';
                    $etiqueta->fecha_inicio = $ahora;
                    $etiqueta->operario1_id = $usuarioId;

                } elseif ($siguienteEstado === 'completada') {
                    // ═══════════════════════════════════════════════════════════════════
                    // SEGUNDO CLIC: FABRICANDO -> COMPLETADA
                    // Verificar si el producto cambió, asignar producto_id_2, consumir stock
                    // ═══════════════════════════════════════════════════════════════════

                    $elementosEtiqueta = $elementosDelGrupo->where('etiqueta_sub_id', $etiqueta->etiqueta_sub_id);

                    foreach ($elementosEtiqueta as $elemento) {
                        $diametro = (int) $elemento->diametro;
                        $productos = $productosPorDiametro[$diametro] ?? collect();

                        if ($productos->isNotEmpty()) {
                            $productoActual = $productos->first();

                            // Verificar si el producto cambió desde el primer clic
                            if ($elemento->producto_id && $elemento->producto_id != $productoActual->id) {
                                // El producto cambió, asignar a producto_id_2 o producto_id_3
                                if (!$elemento->producto_id_2) {
                                    $elemento->producto_id_2 = $productoActual->id;
                                } elseif ($elemento->producto_id_2 != $productoActual->id && !$elemento->producto_id_3) {
                                    $elemento->producto_id_3 = $productoActual->id;
                                }
                            } elseif (!$elemento->producto_id) {
                                $elemento->producto_id = $productoActual->id;
                            }

                            // Consumir stock del producto
                            $pesoElemento = (float) ($elemento->peso_kg ?? 0);
                            if ($pesoElemento > 0 && $productoActual->peso_stock >= $pesoElemento) {
                                $productoActual->peso_stock -= $pesoElemento;
                                if ($productoActual->peso_stock <= 0) {
                                    $productoActual->peso_stock = 0;
                                    $productoActual->estado = 'consumido';
                                }
                                $productoActual->save();

                                $productosAfectados[] = [
                                    'id' => $productoActual->id,
                                    'peso_stock' => $productoActual->peso_stock,
                                    'peso_inicial' => $productoActual->peso_inicial ?? null,
                                ];
                            }

                            // Guardar coladas para la respuesta
                            if (!$productoNColada && $elemento->producto_id) {
                                $prod1 = Producto::find($elemento->producto_id);
                                $productoNColada = $prod1?->n_colada;
                            }
                            if (!$producto2NColada && $elemento->producto_id_2) {
                                $prod2 = Producto::find($elemento->producto_id_2);
                                $producto2NColada = $prod2?->n_colada;
                            }
                        }

                        $elemento->estado = 'completado';
                        $elemento->save();
                    }

                    // Verificar si el producto de la etiqueta cambió
                    $primerElemento = $elementosEtiqueta->first();
                    if ($primerElemento) {
                        if ($etiqueta->producto_id && $primerElemento->producto_id && $etiqueta->producto_id != $primerElemento->producto_id) {
                            if (!$etiqueta->producto_id_2) {
                                $etiqueta->producto_id_2 = $primerElemento->producto_id;
                            }
                        } elseif (!$etiqueta->producto_id && $primerElemento->producto_id) {
                            $etiqueta->producto_id = $primerElemento->producto_id;
                        }
                    }

                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = $ahora;

                    // Calcular tiempo total y repartir
                    if ($etiqueta->fecha_inicio) {
                        $tiempoTotalSegundos = $etiqueta->fecha_inicio->diffInSeconds($ahora);
                        $tiempoPorEtiqueta = (int) round($tiempoTotalSegundos / $totalEtiquetas);
                        $etiqueta->fecha_inicio = $ahora->copy()->subSeconds($tiempoPorEtiqueta);
                    }

                    // Recolectar etiquetas que NO se han impreso para impresión automática
                    if (!$etiqueta->impresa) {
                        $etiquetasParaImprimir[] = [
                            'id' => $etiqueta->id,
                            'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                        ];
                        $etiqueta->impresa = true;
                    }
                }

                $etiqueta->save();

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
                    'producto_n_colada' => $productoNColada,
                ]);
            }

            // Si se completó el grupo, desagrupar las etiquetas para trabajar individualmente
            $desagrupado = false;
            if ($siguienteEstado === 'completada') {
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
                'productos_afectados' => $productosAfectados,
                'producto_n_colada' => $productoNColada,
                'producto2_n_colada' => $producto2NColada,
            ];
        });
    }

    // ==================== MÉTODOS MULTI-PLANILLA ====================

    /**
     * Agrupa etiquetas de MÚLTIPLES planillas revisadas de una máquina.
     * Las etiquetas se agrupan por diámetro + dimensiones sin importar la planilla de origen.
     *
     * @param int $maquinaId ID de la máquina
     * @param int|null $usuarioId ID del usuario que realiza la acción
     * @return array Resultado de la operación
     */
    public function resumirMultiplanilla(int $maquinaId, ?int $usuarioId = null): array
    {
        Log::info('📦 resumirMultiplanilla INICIO', ['maquina_id' => $maquinaId]);

        return DB::transaction(function () use ($maquinaId, $usuarioId) {
            Log::info('📦 resumirMultiplanilla - Dentro de transacción');

            // 1. Obtener etiquetas elegibles de planillas REVISADAS
            $query = Etiqueta::where('estado', 'pendiente')
                ->whereNull('grupo_resumen_id')
                ->whereHas('planilla', function ($q) {
                    $q->where('revisada', true);
                })
                ->whereHas('elementos', function ($q) use ($maquinaId) {
                    $q->where(function ($subQ) use ($maquinaId) {
                        $subQ->where('maquina_id', $maquinaId)
                            ->orWhere('maquina_id_2', $maquinaId);
                    });
                });

            Log::info('📦 resumirMultiplanilla - Ejecutando query OPTIMIZADA');

            // Query optimizada: obtener planillas REVISADAS activas en la máquina (con elementos pendientes/fabricando)
            $planillasActivas = DB::table('elementos')
                ->join('planillas', 'elementos.planilla_id', '=', 'planillas.id')
                ->where('planillas.revisada', true)
                ->where(function ($q) use ($maquinaId) {
                    $q->where('elementos.maquina_id', $maquinaId)
                        ->orWhere('elementos.maquina_id_2', $maquinaId);
                })
                ->whereIn('elementos.estado', ['pendiente', 'fabricando'])
                ->whereNotNull('elementos.planilla_id')
                ->distinct()
                ->pluck('elementos.planilla_id')
                ->toArray();

            Log::info('📦 resumirMultiplanilla - Planillas activas en máquina: ' . count($planillasActivas), ['ids' => $planillasActivas]);

            if (empty($planillasActivas)) {
                Log::info('📦 resumirMultiplanilla - No hay planillas activas');
                return [
                    'success' => true,
                    'message' => 'No hay planillas con elementos pendientes en esta máquina',
                    'grupos' => [],
                    'stats' => ['grupos_creados' => 0, 'etiquetas_agrupadas' => 0, 'planillas_involucradas' => 0],
                ];
            }

            // Obtener IDs de etiquetas de esas planillas activas
            $etiquetaIds = DB::table('elementos')
                ->where(function ($q) use ($maquinaId) {
                    $q->where('maquina_id', $maquinaId)
                        ->orWhere('maquina_id_2', $maquinaId);
                })
                ->whereIn('planilla_id', $planillasActivas)
                ->whereNotNull('etiqueta_id')
                ->distinct()
                ->pluck('etiqueta_id')
                ->toArray();

            Log::info('📦 resumirMultiplanilla - IDs de etiquetas: ' . count($etiquetaIds));

            if (empty($etiquetaIds)) {
                return [
                    'success' => true,
                    'message' => 'No hay etiquetas para agrupar en las planillas activas',
                    'grupos' => [],
                    'stats' => ['grupos_creados' => 0, 'etiquetas_agrupadas' => 0, 'planillas_involucradas' => 0],
                ];
            }

            // Ahora buscar etiquetas elegibles con esos IDs
            $etiquetas = Etiqueta::whereIn('id', $etiquetaIds)
                ->where('estado', 'pendiente')
                ->whereNull('grupo_resumen_id')
                ->whereHas('planilla', fn($q) => $q->where('revisada', true))
                ->with(['elementos', 'planilla'])
                ->limit(500)
                ->get();

            Log::info('📦 resumirMultiplanilla - Etiquetas encontradas: ' . $etiquetas->count());

            if ($etiquetas->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay etiquetas pendientes en planillas revisadas para agrupar',
                    'grupos' => [],
                    'stats' => ['grupos_creados' => 0, 'etiquetas_agrupadas' => 0, 'planillas_involucradas' => 0],
                ];
            }

            // 2. Agrupar etiquetas por diámetro + dimensiones (ignorando planilla)
            $agrupaciones = [];
            $planillasInvolucradas = [];

            foreach ($etiquetas as $etiqueta) {
                $elementos = $etiqueta->elementos->filter(function ($e) use ($maquinaId) {
                    return $e->maquina_id == $maquinaId
                        || $e->maquina_id_2 == $maquinaId;
                });

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
                        'dimensiones' => $dimensiones,
                        'dimensiones_original' => $primerElemento->dimensiones,
                        'etiquetas' => [],
                        'planillas' => [],
                    ];
                }

                $agrupaciones[$key]['etiquetas'][] = $etiqueta;
                $agrupaciones[$key]['planillas'][$etiqueta->planilla_id] = true;
                $planillasInvolucradas[$etiqueta->planilla_id] = true;
            }

            // 3. Crear grupos solo para los que tienen más de 1 etiqueta
            $gruposCreados = [];
            $stats = [
                'grupos_creados' => 0,
                'etiquetas_agrupadas' => 0,
                'planillas_involucradas' => count($planillasInvolucradas),
            ];

            foreach ($agrupaciones as $key => $grupoData) {
                if (count($grupoData['etiquetas']) <= 1) {
                    continue;
                }

                // Crear grupo de resumen (planilla_id = null para multi-planilla)
                $grupo = GrupoResumen::create([
                    'codigo' => GrupoResumen::generarCodigo(),
                    'planilla_id' => null, // Multi-planilla
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

                Log::info('Grupo de resumen multi-planilla creado', [
                    'grupo_id' => $grupo->id,
                    'codigo' => $grupo->codigo,
                    'diametro' => $grupo->diametro,
                    'dimensiones' => $grupo->dimensiones,
                    'etiquetas' => count($grupoData['etiquetas']),
                    'planillas' => array_keys($grupoData['planillas']),
                ]);
            }

            return [
                'success' => true,
                'message' => $stats['grupos_creados'] > 0
                    ? "Resumen multi-planilla completado: {$stats['grupos_creados']} grupos con {$stats['etiquetas_agrupadas']} etiquetas de {$stats['planillas_involucradas']} planillas"
                    : 'No se encontraron etiquetas similares para agrupar entre planillas',
                'grupos' => $gruposCreados,
                'stats' => $stats,
            ];
        });
    }

    /**
     * Vista previa del resumen multi-planilla sin ejecutar cambios.
     *
     * @param int $maquinaId ID de la máquina
     * @return array Vista previa de grupos
     */
    public function previsualizarMultiplanilla(int $maquinaId): array
    {
        $query = Etiqueta::where('estado', 'pendiente')
            ->whereNull('grupo_resumen_id')
            ->whereHas('planilla', function ($q) {
                $q->where('revisada', true);
            })
            ->whereHas('elementos', function ($q) use ($maquinaId) {
                $q->where(function ($subQ) use ($maquinaId) {
                    $subQ->where('maquina_id', $maquinaId)
                        ->orWhere('maquina_id_2', $maquinaId);
                });
            });

        $etiquetas = $query->with(['elementos', 'planilla'])->get();

        // Agrupar
        $agrupaciones = [];
        $planillasInvolucradas = [];

        foreach ($etiquetas as $etiqueta) {
            $elementos = $etiqueta->elementos->filter(function ($e) use ($maquinaId) {
                return $e->maquina_id == $maquinaId
                    || $e->maquina_id_2 == $maquinaId;
            });

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
                    'planillas' => [],
                ];
            }

            $cantidadElementos = $elementos->count();
            $pesoElementos = $elementos->sum('peso');
            $planillaCodigo = $etiqueta->planilla->codigo_limpio ?? $etiqueta->planilla->codigo ?? 'N/A';

            $agrupaciones[$key]['etiquetas'][] = [
                'id' => $etiqueta->id,
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                'nombre' => $etiqueta->nombre,
                'planilla_id' => $etiqueta->planilla_id,
                'planilla_codigo' => $planillaCodigo,
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
            $agrupaciones[$key]['planillas'][$etiqueta->planilla_id] = $planillaCodigo;
            $planillasInvolucradas[$etiqueta->planilla_id] = $planillaCodigo;
        }

        // Filtrar solo grupos con más de 1 etiqueta
        $preview = collect($agrupaciones)
            ->filter(fn($g) => count($g['etiquetas']) > 1)
            ->map(fn($g) => [
                ...$g,
                'peso_total' => round($g['peso_total'], 2),
                'total_etiquetas' => count($g['etiquetas']),
                'total_planillas' => count($g['planillas']),
                'planillas_codigos' => array_values($g['planillas']),
            ])
            ->values()
            ->toArray();

        return [
            'grupos' => $preview,
            'total_grupos' => count($preview),
            'total_etiquetas' => collect($preview)->sum('total_etiquetas'),
            'total_elementos' => collect($preview)->sum('total_elementos'),
            'peso_total' => round(collect($preview)->sum('peso_total'), 2),
            'planillas_involucradas' => $planillasInvolucradas,
            'total_planillas' => count($planillasInvolucradas),
        ];
    }

    /**
     * Desagrupa todos los grupos multi-planilla activos de una máquina.
     *
     * @param int $maquinaId ID de la máquina
     * @return array Resultado de la operación
     */
    public function desagruparTodosMaquina(int $maquinaId): array
    {
        // Grupos multi-planilla (planilla_id IS NULL) de esta máquina
        $grupos = GrupoResumen::where('maquina_id', $maquinaId)
            ->whereNull('planilla_id')
            ->where('activo', true)
            ->get();

        $total = $grupos->count();

        if ($total === 0) {
            return ['success' => true, 'message' => 'No hay grupos multi-planilla activos para desagrupar'];
        }

        foreach ($grupos as $grupo) {
            $grupo->desagrupar();
        }

        Log::info('Grupos multi-planilla desagrupados masivamente', [
            'maquina_id' => $maquinaId,
            'grupos_desagrupados' => $total,
        ]);

        return [
            'success' => true,
            'message' => "{$total} grupos multi-planilla desagrupados",
            'total' => $total,
        ];
    }

    /**
     * Obtiene los grupos multi-planilla activos de una máquina.
     *
     * @param int $maquinaId ID de la máquina
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerGruposMultiplanilla(int $maquinaId)
    {
        return GrupoResumen::where('maquina_id', $maquinaId)
            ->whereNull('planilla_id')
            ->where('activo', true)
            ->with(['etiquetas' => function ($q) {
                $q->with(['elementos', 'planilla']);
            }])
            ->orderBy('diametro')
            ->orderBy('dimensiones')
            ->get();
    }

    /**
     * Deshace el último estado de un grupo de etiquetas.
     * Revierte: completada -> fabricando -> pendiente
     *
     * @param int $grupoId ID del grupo
     * @return array Resultado de la operación
     */
    public function deshacerEstadoGrupo(int $grupoId): array
    {
        $grupo = GrupoResumen::with('etiquetas')->find($grupoId);

        if (!$grupo) {
            return ['success' => false, 'message' => 'Grupo no encontrado'];
        }

        // Mapeo de estados para revertir
        $estadosReversos = [
            'completada' => 'fabricando',
            'fabricando' => 'pendiente',
            'fabricada' => 'fabricando',
            'ensamblada' => 'fabricando',
            'soldada' => 'fabricando',
        ];

        // Obtener estado actual del grupo (basado en la primera etiqueta)
        $primeraEtiqueta = $grupo->etiquetas->first();
        if (!$primeraEtiqueta) {
            return ['success' => false, 'message' => 'El grupo no tiene etiquetas'];
        }

        $estadoActual = strtolower($primeraEtiqueta->estado ?? 'pendiente');

        if (!isset($estadosReversos[$estadoActual])) {
            return [
                'success' => false,
                'message' => "No se puede deshacer el estado '{$estadoActual}'. Solo se puede revertir desde completada/fabricando."
            ];
        }

        $nuevoEstado = $estadosReversos[$estadoActual];

        // Actualizar todas las etiquetas del grupo
        $etiquetaIds = $grupo->etiquetas->pluck('id')->toArray();
        Etiqueta::whereIn('id', $etiquetaIds)->update(['estado' => $nuevoEstado]);

        Log::info('Estado de grupo revertido', [
            'grupo_id' => $grupoId,
            'estado_anterior' => $estadoActual,
            'estado_nuevo' => $nuevoEstado,
            'etiquetas_afectadas' => count($etiquetaIds),
        ]);

        return [
            'success' => true,
            'message' => "Estado revertido de {$estadoActual} a {$nuevoEstado} para " . count($etiquetaIds) . " etiquetas",
            'estado_anterior' => $estadoActual,
            'estado_nuevo' => $nuevoEstado,
            'etiquetas_afectadas' => count($etiquetaIds),
        ];
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
