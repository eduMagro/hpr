<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LogPlanificacionProduccion extends Model
{
    protected $table = 'logs_planificacion_produccion';

    protected $fillable = [
        'user_id',
        'accion',
        'descripcion',
        'detalles',
        'datos_reversion',
        'maquina_id',
        'planilla_id',
        'elemento_id',
        'revertido',
        'revertido_at',
        'revertido_por',
    ];

    protected $casts = [
        'detalles' => 'array',
        'datos_reversion' => 'array',
        'revertido' => 'boolean',
        'revertido_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function revertidoPorUser()
    {
        return $this->belongsTo(User::class, 'revertido_por');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function elemento()
    {
        return $this->belongsTo(Elemento::class);
    }

    /**
     * Registrar una acción en el log con datos de reversión
     */
    public static function registrar(string $accion, string $descripcion, array $detalles = [], array $extras = [], array $datosReversion = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'detalles' => $detalles,
            'datos_reversion' => !empty($datosReversion) ? $datosReversion : null,
            'maquina_id' => $extras['maquina_id'] ?? null,
            'planilla_id' => $extras['planilla_id'] ?? null,
            'elemento_id' => $extras['elemento_id'] ?? null,
        ]);
    }

    /**
     * Verificar si esta acción puede ser revertida
     */
    public function puedeRevertirse(): bool
    {
        // No se puede revertir si ya fue revertido
        if ($this->revertido) {
            return false;
        }

        // No se puede revertir si no hay datos de reversión
        if (empty($this->datos_reversion)) {
            return false;
        }

        // Solo se puede revertir la última acción no revertida
        $ultimaAccion = self::where('revertido', false)
            ->whereNotNull('datos_reversion')
            ->orderBy('id', 'desc')
            ->first();

        return $ultimaAccion && $ultimaAccion->id === $this->id;
    }

    /**
     * Revertir esta acción
     */
    public function revertir(): bool
    {
        if (!$this->puedeRevertirse()) {
            return false;
        }

        $datos = $this->datos_reversion;

        DB::beginTransaction();
        try {
            switch ($this->accion) {
                case 'mover_elementos':
                    $this->revertirMoverElementos($datos);
                    break;

                case 'cambiar_posicion':
                    $this->revertirCambiarPosicion($datos);
                    break;

                case 'balancear_carga':
                case 'optimizar_planillas':
                    $this->revertirOperacionMasiva($datos);
                    break;

                case 'priorizar_obras':
                    $this->revertirPriorizarObras($datos);
                    break;

                default:
                    throw new \Exception("Acción '{$this->accion}' no soporta reversión");
            }

            // Marcar como revertido
            $this->update([
                'revertido' => true,
                'revertido_at' => now(),
                'revertido_por' => auth()->id(),
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Revertir movimiento de elementos
     */
    private function revertirMoverElementos(array $datos): void
    {
        foreach ($datos['elementos'] as $elem) {
            Elemento::where('id', $elem['id'])->update([
                'maquina_id' => $elem['maquina_id'],
                'orden_planilla_id' => $elem['orden_planilla_id'],
            ]);
        }

        // Restaurar orden_planillas si es necesario
        if (!empty($datos['orden_planillas_eliminados'])) {
            foreach ($datos['orden_planillas_eliminados'] as $op) {
                OrdenPlanilla::create($op);
            }
        }

        // Eliminar orden_planillas creados si es necesario
        if (!empty($datos['orden_planillas_creados'])) {
            OrdenPlanilla::whereIn('id', $datos['orden_planillas_creados'])->delete();
        }
    }

    /**
     * Revertir cambio de posición
     */
    private function revertirCambiarPosicion(array $datos): void
    {
        if (!empty($datos['posiciones'])) {
            foreach ($datos['posiciones'] as $pos) {
                OrdenPlanilla::where('id', $pos['id'])->update(['posicion' => $pos['posicion']]);
            }
        }
    }

    /**
     * Revertir operación masiva (balanceo/optimización)
     */
    private function revertirOperacionMasiva(array $datos): void
    {
        // Restaurar elementos
        if (!empty($datos['elementos'])) {
            foreach ($datos['elementos'] as $elem) {
                Elemento::where('id', $elem['id'])->update([
                    'maquina_id' => $elem['maquina_id'],
                    'orden_planilla_id' => $elem['orden_planilla_id'],
                ]);
            }
        }

        // Restaurar orden_planillas eliminados
        if (!empty($datos['orden_planillas_eliminados'])) {
            foreach ($datos['orden_planillas_eliminados'] as $op) {
                OrdenPlanilla::create($op);
            }
        }

        // Eliminar orden_planillas creados
        if (!empty($datos['orden_planillas_creados'])) {
            OrdenPlanilla::whereIn('id', $datos['orden_planillas_creados'])->delete();
        }
    }

    /**
     * Revertir priorización de obras
     */
    private function revertirPriorizarObras(array $datos): void
    {
        if (!empty($datos['posiciones'])) {
            $cases = [];
            $ids = [];
            foreach ($datos['posiciones'] as $pos) {
                $cases[] = "WHEN {$pos['id']} THEN {$pos['posicion']}";
                $ids[] = $pos['id'];
            }

            if (!empty($cases)) {
                $caseSql = implode(' ', $cases);
                $idsSql = implode(',', $ids);
                DB::statement("UPDATE orden_planillas SET posicion = CASE id {$caseSql} END WHERE id IN ({$idsSql})");
            }
        }
    }
}
