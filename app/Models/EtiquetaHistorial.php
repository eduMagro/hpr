<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Modelo para gestionar el historial de cambios de etiquetas
 * Permite implementar funcionalidad de UNDO (deshacer cambios)
 */
class EtiquetaHistorial extends Model
{
    protected $table = 'etiqueta_historial';

    protected $fillable = [
        'etiqueta_id',
        'etiqueta_sub_id',
        'maquina_id',
        'usuario_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'snapshot_etiqueta',
        'snapshot_elementos',
        'snapshot_productos',
        'snapshot_planilla',
        'paquete_id_anterior',
        'revertido',
        'revertido_at',
        'revertido_por',
    ];

    protected $casts = [
        'snapshot_etiqueta' => 'array',
        'snapshot_elementos' => 'array',
        'snapshot_productos' => 'array',
        'snapshot_planilla' => 'array',
        'revertido' => 'boolean',
        'revertido_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function etiqueta()
    {
        return $this->belongsTo(Etiqueta::class, 'etiqueta_id');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function revertidoPor()
    {
        return $this->belongsTo(User::class, 'revertido_por');
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Registra un cambio en el historial ANTES de que ocurra
     *
     * @param Etiqueta $etiqueta La etiqueta que va a cambiar
     * @param string $accion Tipo de acción (fabricar, completar, empaquetar, etc.)
     * @param string $estadoNuevo El nuevo estado al que pasará
     * @param int|null $maquinaId ID de la máquina donde ocurre
     * @param int|null $usuarioId ID del usuario que realiza la acción
     * @param array $productosConsumidos Array de productos que se consumirán
     * @return self
     */
    public static function registrarCambio(
        Etiqueta $etiqueta,
        string $accion,
        string $estadoNuevo,
        ?int $maquinaId = null,
        ?int $usuarioId = null,
        array $productosConsumidos = []
    ): ?self {
        try {
            // Cargar relaciones necesarias
            $etiqueta->loadMissing(['elementos', 'planilla']);

            // Helper para convertir fechas a formato MySQL
            $formatearFecha = function($fecha) {
                if (empty($fecha)) return null;
                if (is_string($fecha)) {
                    // Si ya tiene formato MySQL, devolverlo
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fecha)) {
                        return substr($fecha, 0, 19); // Truncar a Y-m-d H:i:s
                    }
                    return $fecha;
                }
                if ($fecha instanceof \DateTimeInterface) {
                    return $fecha->format('Y-m-d H:i:s');
                }
                return null;
            };

            // Snapshot de la etiqueta
            $snapshotEtiqueta = [
                'id' => $etiqueta->id,
                'estado' => $etiqueta->estado,
                'paquete_id' => $etiqueta->paquete_id,
                'fecha_inicio' => $formatearFecha($etiqueta->getRawOriginal('fecha_inicio')),
                'fecha_finalizacion' => $formatearFecha($etiqueta->getRawOriginal('fecha_finalizacion')),
                'fecha_inicio_ensamblado' => $formatearFecha($etiqueta->getRawOriginal('fecha_inicio_ensamblado')),
                'fecha_finalizacion_ensamblado' => $formatearFecha($etiqueta->getRawOriginal('fecha_finalizacion_ensamblado')),
                'fecha_inicio_soldadura' => $formatearFecha($etiqueta->getRawOriginal('fecha_inicio_soldadura')),
                'fecha_finalizacion_soldadura' => $formatearFecha($etiqueta->getRawOriginal('fecha_finalizacion_soldadura')),
                'operario1_id' => $etiqueta->operario1_id,
                'operario2_id' => $etiqueta->operario2_id,
                'peso' => $etiqueta->peso,
            ];

            // Snapshot de elementos
            $snapshotElementos = $etiqueta->elementos->map(function ($elemento) {
                return [
                    'id' => $elemento->id,
                    'estado' => $elemento->estado,
                    'producto_id' => $elemento->producto_id,
                    'producto_id_2' => $elemento->producto_id_2,
                    'producto_id_3' => $elemento->producto_id_3,
                    'users_id' => $elemento->users_id,
                    'users_id_2' => $elemento->users_id_2,
                ];
            })->toArray();

            // Snapshot de productos consumidos (con peso anterior)
            $snapshotProductos = [];
            foreach ($productosConsumidos as $prod) {
                $prodId = $prod['id'] ?? ($prod->id ?? null);
                if (!$prodId) continue;

                $snapshotProductos[] = [
                    'id' => $prodId,
                    'codigo' => $prod['codigo'] ?? ($prod->codigo ?? null),
                    'peso_consumido' => (float) ($prod['consumido'] ?? $prod['peso_consumido'] ?? 0),
                    'peso_stock_anterior' => (float) ($prod['peso_stock_anterior'] ?? ($prod['peso_stock'] ?? 0)),
                    'estado_anterior' => $prod['estado_anterior'] ?? ($prod['estado'] ?? 'fabricando'),
                ];
            }

            // Snapshot de planilla
            $snapshotPlanilla = null;
            if ($etiqueta->planilla) {
                $snapshotPlanilla = [
                    'id' => $etiqueta->planilla->id,
                    'estado' => $etiqueta->planilla->getRawOriginal('estado'),
                    'fecha_inicio' => $formatearFecha($etiqueta->planilla->getRawOriginal('fecha_inicio')),
                    'fecha_finalizacion' => $formatearFecha($etiqueta->planilla->getRawOriginal('fecha_finalizacion')),
                ];
            }

            return self::create([
                'etiqueta_id' => $etiqueta->id,
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                'maquina_id' => $maquinaId,
                'usuario_id' => $usuarioId,
                'accion' => $accion,
                'estado_anterior' => $etiqueta->estado,
                'estado_nuevo' => $estadoNuevo,
                'snapshot_etiqueta' => $snapshotEtiqueta,
                'snapshot_elementos' => $snapshotElementos,
                'snapshot_productos' => $snapshotProductos,
                'snapshot_planilla' => $snapshotPlanilla,
                'paquete_id_anterior' => $etiqueta->paquete_id,
                'revertido' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al registrar historial de etiqueta', [
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id ?? 'N/A',
                'accion' => $accion,
                'error' => $e->getMessage(),
            ]);
            return null; // No fallar la operación principal
        }
    }

    /**
     * Obtiene el último cambio NO revertido de una etiqueta
     */
    public static function ultimoCambio(string $etiquetaSubId): ?self
    {
        return self::where('etiqueta_sub_id', $etiquetaSubId)
            ->where('revertido', false)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtiene los últimos N cambios no revertidos de una etiqueta
     */
    public static function ultimosCambios(string $etiquetaSubId, int $limite = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('etiqueta_sub_id', $etiquetaSubId)
            ->where('revertido', false)
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * Verifica si se puede deshacer (hay cambios pendientes)
     */
    public static function puedeDeshacer(string $etiquetaSubId): bool
    {
        return self::where('etiqueta_sub_id', $etiquetaSubId)
            ->where('revertido', false)
            ->exists();
    }

    // ==================== MÉTODOS DE INSTANCIA ====================

    /**
     * Revierte este cambio específico
     *
     * @param int|null $usuarioId Usuario que realiza la reversión
     * @return array Resultado de la reversión
     */
    public function revertir(?int $usuarioId = null): array
    {
        if ($this->revertido) {
            return [
                'success' => false,
                'message' => 'Este cambio ya fue revertido anteriormente.',
            ];
        }

        return DB::transaction(function () use ($usuarioId) {
            $resultado = [
                'success' => true,
                'message' => '',
                'cambios' => [],
            ];

            // 1. REVERTIR ETIQUETA
            $etiqueta = Etiqueta::find($this->etiqueta_id);
            if (!$etiqueta) {
                throw new \Exception("Etiqueta no encontrada (ID: {$this->etiqueta_id})");
            }

            $snapshot = $this->snapshot_etiqueta;

            // Guardar paquete_id actual ANTES del update (para verificar después si hay que eliminar el paquete)
            $paqueteIdAntesDeCambio = $etiqueta->paquete_id;

            // Helper para normalizar fechas (convierte ISO a MySQL format)
            $normalizarFecha = function($fecha) {
                if (empty($fecha)) return null;
                if (is_string($fecha)) {
                    // Si tiene T o Z es formato ISO, convertir
                    if (str_contains($fecha, 'T') || str_contains($fecha, 'Z')) {
                        try {
                            return \Carbon\Carbon::parse($fecha)->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            return null;
                        }
                    }
                    return $fecha;
                }
                return $fecha;
            };

            $etiqueta->update([
                'estado' => $snapshot['estado'],
                'paquete_id' => $snapshot['paquete_id'],
                'fecha_inicio' => $normalizarFecha($snapshot['fecha_inicio']),
                'fecha_finalizacion' => $normalizarFecha($snapshot['fecha_finalizacion']),
                'fecha_inicio_ensamblado' => $normalizarFecha($snapshot['fecha_inicio_ensamblado'] ?? null),
                'fecha_finalizacion_ensamblado' => $normalizarFecha($snapshot['fecha_finalizacion_ensamblado'] ?? null),
                'fecha_inicio_soldadura' => $normalizarFecha($snapshot['fecha_inicio_soldadura'] ?? null),
                'fecha_finalizacion_soldadura' => $normalizarFecha($snapshot['fecha_finalizacion_soldadura'] ?? null),
                'operario1_id' => $snapshot['operario1_id'] ?? null,
                'operario2_id' => $snapshot['operario2_id'] ?? null,
            ]);

            $resultado['cambios'][] = "Etiqueta restaurada a estado: {$snapshot['estado']}";

            // 2. REVERTIR ELEMENTOS
            if (!empty($this->snapshot_elementos)) {
                foreach ($this->snapshot_elementos as $elemData) {
                    $elemento = Elemento::find($elemData['id']);
                    if ($elemento) {
                        $elemento->update([
                            'estado' => $elemData['estado'],
                            'producto_id' => $elemData['producto_id'],
                            'producto_id_2' => $elemData['producto_id_2'],
                            'producto_id_3' => $elemData['producto_id_3'],
                            'users_id' => $elemData['users_id'] ?? null,
                            'users_id_2' => $elemData['users_id_2'] ?? null,
                        ]);
                    }
                }
                $resultado['cambios'][] = "Elementos restaurados: " . count($this->snapshot_elementos);
            }

            // 3. REVERTIR PRODUCTOS (devolver stock)
            if (!empty($this->snapshot_productos)) {
                foreach ($this->snapshot_productos as $prodData) {
                    $producto = Producto::find($prodData['id']);
                    if ($producto) {
                        // Devolver el peso consumido
                        $pesoDevolver = $prodData['peso_consumido'] ?? 0;
                        $producto->peso_stock += $pesoDevolver;

                        // Si estaba consumido y ahora tiene stock, volver a estado fabricando
                        if ($producto->estado === 'consumido' && $producto->peso_stock > 0) {
                            $producto->estado = $prodData['estado_anterior'] ?? 'fabricando';
                        }

                        $producto->save();

                        $resultado['cambios'][] = "Producto {$prodData['codigo']}: +{$pesoDevolver}kg devueltos";
                    }
                }
            }

            // 4. REVERTIR PLANILLA (si aplica)
            if (!empty($this->snapshot_planilla)) {
                $planilla = Planilla::find($this->snapshot_planilla['id']);
                if ($planilla) {
                    $planilla->refresh(); // Recargar datos actualizados
                    $estadoAnteriorPlanilla = $this->snapshot_planilla['estado'];
                    $estadoActualPlanilla = $planilla->getRawOriginal('estado');
                    $estadoEtiquetaRestaurado = $snapshot['estado'];

                    // Verificar si TODAS las etiquetas de la planilla están en pendiente
                    $todasPendientes = $planilla->etiquetas()
                        ->where('estado', '!=', 'pendiente')
                        ->doesntExist();

                    if ($todasPendientes) {
                        // Todas las etiquetas están pendientes, restaurar planilla completamente
                        $planilla->update([
                            'estado' => 'pendiente',
                            'fecha_inicio' => null,
                            'fecha_finalizacion' => null,
                        ]);
                        $resultado['cambios'][] = "Planilla restaurada a estado: pendiente (todas las etiquetas pendientes)";
                    } elseif ($estadoActualPlanilla !== $estadoAnteriorPlanilla) {
                        // Verificar si hay otras etiquetas en proceso
                        $hayEnProceso = $planilla->etiquetas()
                            ->whereIn('estado', ['fabricando', 'completada', 'fabricada', 'en-paquete'])
                            ->exists();

                        if (!$hayEnProceso && $estadoAnteriorPlanilla === 'pendiente') {
                            // Ninguna etiqueta en proceso, revertir a pendiente
                            $planilla->update([
                                'estado' => 'pendiente',
                                'fecha_inicio' => null,
                                'fecha_finalizacion' => null,
                            ]);
                            $resultado['cambios'][] = "Planilla restaurada a estado: pendiente";
                        } elseif ($estadoActualPlanilla === 'completada' && $estadoEtiquetaRestaurado !== 'completada') {
                            // La planilla estaba completada pero la etiqueta ya no lo está
                            $planilla->update([
                                'estado' => 'fabricando',
                                'fecha_finalizacion' => null,
                            ]);
                            $resultado['cambios'][] = "Planilla restaurada a estado: fabricando";
                        }
                    }
                }
            }

            // 5. GESTIONAR PAQUETE (si la etiqueta estaba en un paquete antes del undo)
            $paqueteIdDelSnapshot = $snapshot['paquete_id'] ?? null;

            // Si la etiqueta estaba en un paquete ANTES del undo y el snapshot dice que NO debería estar
            if ($paqueteIdAntesDeCambio && !$paqueteIdDelSnapshot) {
                $paquete = Paquete::find($paqueteIdAntesDeCambio);
                if ($paquete) {
                    $codigoPaquete = $paquete->codigo;
                    $resultado['cambios'][] = "Etiqueta removida del paquete {$codigoPaquete}";

                    // Verificar si el paquete quedó vacío (ya no tiene etiquetas asociadas)
                    $etiquetasRestantes = Etiqueta::where('paquete_id', $paquete->id)->count();

                    if ($etiquetasRestantes === 0) {
                        // Eliminar el paquete vacío
                        $paquete->delete();
                        $resultado['cambios'][] = "Paquete {$codigoPaquete} eliminado (quedó vacío)";
                        Log::info('Paquete eliminado por quedar vacío tras undo', [
                            'paquete_id' => $paqueteIdAntesDeCambio,
                            'paquete_codigo' => $codigoPaquete,
                            'etiqueta_sub_id' => $this->etiqueta_sub_id,
                        ]);
                    }
                }
            }

            // 6. Marcar este registro como revertido
            $this->update([
                'revertido' => true,
                'revertido_at' => now(),
                'revertido_por' => $usuarioId,
            ]);

            $resultado['message'] = "Cambio revertido exitosamente. Estado restaurado a: {$snapshot['estado']}";

            Log::info('Cambio de etiqueta revertido', [
                'historial_id' => $this->id,
                'etiqueta_sub_id' => $this->etiqueta_sub_id,
                'estado_restaurado' => $snapshot['estado'],
                'usuario_id' => $usuarioId,
                'cambios' => $resultado['cambios'],
            ]);

            return $resultado;
        });
    }

    /**
     * Obtiene una descripción legible del cambio
     */
    public function getDescripcionAttribute(): string
    {
        $estadoAnt = $this->estado_anterior ?? 'N/A';
        $estadoNuevo = $this->estado_nuevo;

        return "{$this->accion}: {$estadoAnt} → {$estadoNuevo}";
    }

    // ==================== MANTENIMIENTO ====================

    /**
     * Limpia registros de historial antiguos para evitar crecimiento descontrolado.
     * Por defecto elimina registros revertidos con más de 30 días y
     * registros no revertidos con más de 90 días.
     *
     * @param int $diasRevertidos Días para eliminar registros ya revertidos
     * @param int $diasNoRevertidos Días para eliminar registros no revertidos
     * @return int Cantidad de registros eliminados
     */
    public static function limpiarHistorialAntiguo(int $diasRevertidos = 30, int $diasNoRevertidos = 90): int
    {
        $eliminados = 0;

        // Eliminar registros revertidos antiguos (ya no sirven para UNDO)
        $eliminados += self::where('revertido', true)
            ->where('created_at', '<', now()->subDays($diasRevertidos))
            ->delete();

        // Eliminar registros muy antiguos (aunque no estén revertidos)
        $eliminados += self::where('created_at', '<', now()->subDays($diasNoRevertidos))
            ->delete();

        if ($eliminados > 0) {
            Log::info('Limpieza de historial de etiquetas', [
                'registros_eliminados' => $eliminados,
                'dias_revertidos' => $diasRevertidos,
                'dias_no_revertidos' => $diasNoRevertidos,
            ]);
        }

        return $eliminados;
    }

    /**
     * Obtiene estadísticas del historial para monitoreo
     *
     * @return array
     */
    public static function estadisticas(): array
    {
        return [
            'total' => self::count(),
            'pendientes_revertir' => self::where('revertido', false)->count(),
            'revertidos' => self::where('revertido', true)->count(),
            'ultima_semana' => self::where('created_at', '>=', now()->subWeek())->count(),
            'por_accion' => self::selectRaw('accion, COUNT(*) as total')
                ->groupBy('accion')
                ->pluck('total', 'accion')
                ->toArray(),
        ];
    }
}
