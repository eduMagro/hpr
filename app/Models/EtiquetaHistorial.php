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
    ): self {
        // Cargar relaciones necesarias
        $etiqueta->loadMissing(['elementos', 'planilla']);

        // Snapshot de la etiqueta
        $snapshotEtiqueta = [
            'id' => $etiqueta->id,
            'estado' => $etiqueta->estado,
            'paquete_id' => $etiqueta->paquete_id,
            'fecha_inicio' => $etiqueta->fecha_inicio,
            'fecha_finalizacion' => $etiqueta->fecha_finalizacion,
            'fecha_inicio_ensamblado' => $etiqueta->fecha_inicio_ensamblado,
            'fecha_finalizacion_ensamblado' => $etiqueta->fecha_finalizacion_ensamblado,
            'fecha_inicio_soldadura' => $etiqueta->fecha_inicio_soldadura,
            'fecha_finalizacion_soldadura' => $etiqueta->fecha_finalizacion_soldadura,
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
            ];
        })->toArray();

        // Snapshot de productos consumidos (con peso anterior)
        $snapshotProductos = [];
        foreach ($productosConsumidos as $prod) {
            $snapshotProductos[] = [
                'id' => $prod['id'],
                'codigo' => $prod['codigo'] ?? null,
                'peso_consumido' => $prod['consumido'] ?? $prod['peso_consumido'] ?? 0,
                'peso_stock_anterior' => $prod['peso_stock_anterior'] ?? ($prod['peso_stock'] + ($prod['consumido'] ?? 0)),
                'estado_anterior' => $prod['estado_anterior'] ?? 'consumiendo',
            ];
        }

        // Snapshot de planilla
        $snapshotPlanilla = null;
        if ($etiqueta->planilla) {
            $snapshotPlanilla = [
                'id' => $etiqueta->planilla->id,
                'estado' => $etiqueta->planilla->getRawOriginal('estado'),
                'fecha_inicio' => $etiqueta->planilla->getRawOriginal('fecha_inicio'),
                'fecha_finalizacion' => $etiqueta->planilla->getRawOriginal('fecha_finalizacion'),
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

            $etiqueta->update([
                'estado' => $snapshot['estado'],
                'paquete_id' => $snapshot['paquete_id'],
                'fecha_inicio' => $snapshot['fecha_inicio'],
                'fecha_finalizacion' => $snapshot['fecha_finalizacion'],
                'fecha_inicio_ensamblado' => $snapshot['fecha_inicio_ensamblado'] ?? null,
                'fecha_finalizacion_ensamblado' => $snapshot['fecha_finalizacion_ensamblado'] ?? null,
                'fecha_inicio_soldadura' => $snapshot['fecha_inicio_soldadura'] ?? null,
                'fecha_finalizacion_soldadura' => $snapshot['fecha_finalizacion_soldadura'] ?? null,
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

                        // Si estaba consumido y ahora tiene stock, volver a estado consumiendo
                        if ($producto->estado === 'consumido' && $producto->peso_stock > 0) {
                            $producto->estado = $prodData['estado_anterior'] ?? 'consumiendo';
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
                    $estadoAnteriorPlanilla = $this->snapshot_planilla['estado'];
                    $estadoActualPlanilla = $planilla->getRawOriginal('estado');

                    // Solo revertir si el estado actual es diferente y la planilla cambió por esta etiqueta
                    if ($estadoActualPlanilla !== $estadoAnteriorPlanilla) {
                        // Verificar si hay otras etiquetas que mantengan el estado actual
                        $otrasEtiquetasFabricando = $planilla->etiquetas()
                            ->where('id', '!=', $this->etiqueta_id)
                            ->whereIn('estado', ['fabricando', 'completada', 'fabricada'])
                            ->exists();

                        if (!$otrasEtiquetasFabricando && $estadoAnteriorPlanilla === 'pendiente') {
                            // Ninguna otra etiqueta en proceso, revertir a pendiente
                            $planilla->estado = $estadoAnteriorPlanilla;
                            $planilla->fecha_inicio = $this->snapshot_planilla['fecha_inicio'];
                            $planilla->fecha_finalizacion = $this->snapshot_planilla['fecha_finalizacion'];
                            $planilla->save();
                            $resultado['cambios'][] = "Planilla restaurada a estado: {$estadoAnteriorPlanilla}";
                        } elseif ($estadoActualPlanilla === 'completada') {
                            // La planilla estaba completada, verificar si debe volver a fabricando
                            $todasCompletadas = $planilla->etiquetas()
                                ->where('id', '!=', $this->etiqueta_id)
                                ->whereNotIn('estado', ['completada', 'fabricada', 'en-paquete'])
                                ->doesntExist();

                            if (!$todasCompletadas) {
                                // Hay etiquetas sin completar (incluyendo la que estamos revirtiendo)
                                $planilla->estado = 'fabricando';
                                $planilla->fecha_finalizacion = null;
                                $planilla->save();
                                $resultado['cambios'][] = "Planilla restaurada a estado: fabricando";
                            }
                        }
                    }
                }
            }

            // 5. Marcar este registro como revertido
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
}
