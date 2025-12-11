<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Modelo para agrupar visualmente etiquetas con mismo diámetro y dimensiones.
 * Las etiquetas originales se mantienen intactas para poder imprimirlas individualmente.
 */
class GrupoResumen extends Model
{
    protected $table = 'grupos_resumen';

    protected $fillable = [
        'codigo',
        'planilla_id',
        'maquina_id',
        'diametro',
        'dimensiones',
        'total_elementos',
        'peso_total',
        'total_etiquetas',
        'usuario_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'diametro' => 'decimal:2',
        'peso_total' => 'decimal:3',
        'total_elementos' => 'integer',
        'total_etiquetas' => 'integer',
    ];

    // ==================== RELACIONES ====================

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'grupo_resumen_id');
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Genera un código único para el grupo: GRP + AAMM + secuencial
     */
    public static function generarCodigo(): string
    {
        return DB::transaction(function () {
            $prefijo = 'GRP' . now()->format('ym');

            $ultimo = self::where('codigo', 'like', $prefijo . '%')
                ->lockForUpdate()
                ->orderByRaw("CAST(SUBSTRING(codigo, " . (strlen($prefijo) + 1) . ") AS UNSIGNED) DESC")
                ->value('codigo');

            $siguiente = 1;
            if ($ultimo) {
                $numero = (int) substr($ultimo, strlen($prefijo));
                $siguiente = $numero + 1;
            }

            return $prefijo . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
        });
    }

    // ==================== MÉTODOS DE INSTANCIA ====================

    /**
     * Obtiene todos los elementos de las etiquetas del grupo
     */
    public function elementos()
    {
        $subIds = $this->etiquetas()->pluck('etiqueta_sub_id')->toArray();

        if (empty($subIds)) {
            return Elemento::whereRaw('1 = 0'); // Query vacía
        }

        return Elemento::whereIn('etiqueta_sub_id', $subIds);
    }

    /**
     * Recalcula las estadísticas del grupo basándose en las etiquetas asignadas
     */
    public function recalcularEstadisticas(): void
    {
        $etiquetas = $this->etiquetas()->get();
        $subIds = $etiquetas->pluck('etiqueta_sub_id')->toArray();

        $this->total_etiquetas = $etiquetas->count();

        if (!empty($subIds)) {
            $this->total_elementos = Elemento::whereIn('etiqueta_sub_id', $subIds)->count();
            $this->peso_total = (float) Elemento::whereIn('etiqueta_sub_id', $subIds)->sum('peso');
        } else {
            $this->total_elementos = 0;
            $this->peso_total = 0;
        }

        $this->save();
    }

    /**
     * Desagrupa: quita las etiquetas del grupo y lo marca como inactivo
     */
    public function desagrupar(): void
    {
        DB::transaction(function () {
            // Quitar referencia de las etiquetas
            $this->etiquetas()->update(['grupo_resumen_id' => null]);

            // Marcar grupo como inactivo
            $this->activo = false;
            $this->total_etiquetas = 0;
            $this->total_elementos = 0;
            $this->peso_total = 0;
            $this->save();
        });
    }

    /**
     * Verifica si el grupo tiene etiquetas con estado pendiente
     */
    public function tienePendientes(): bool
    {
        return $this->etiquetas()->where('estado', 'pendiente')->exists();
    }

    /**
     * Obtiene el estado predominante de las etiquetas del grupo
     */
    public function getEstadoPredominanteAttribute(): string
    {
        $estados = $this->etiquetas()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->orderByDesc('total')
            ->pluck('total', 'estado')
            ->toArray();

        if (empty($estados)) {
            return 'pendiente';
        }

        // Si hay alguna fabricando, el grupo está fabricando
        if (isset($estados['fabricando'])) {
            return 'fabricando';
        }

        // Si todas están completadas, el grupo está completado
        $totalEtiquetas = array_sum($estados);
        if (isset($estados['completada']) && $estados['completada'] == $totalEtiquetas) {
            return 'completada';
        }

        return array_key_first($estados);
    }

    /**
     * Descripción legible del grupo
     */
    public function getDescripcionAttribute(): string
    {
        $dims = $this->dimensiones ?: 'barra';
        return "Ø{$this->diametro} | {$dims}";
    }
}
