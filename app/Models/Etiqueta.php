<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Etiqueta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'etiquetas';

    protected $appends = ['peso_kg'];

    protected $casts = [
        'fecha_inicio' => 'datetime:Y-m-d H:i',
        'fecha_finalizacion' => 'datetime:Y-m-d H:i',
        'fecha_inicio_ensamblado' => 'datetime:Y-m-d H:i',
        'fecha_finalizacion_ensamblado' => 'datetime:Y-m-d H:i',
        'fecha_inicio_soldadura' => 'datetime:Y-m-d H:i',
        'fecha_finalizacion_soldadura' => 'datetime:Y-m-d H:i',
    ];

    protected $fillable = [
        'codigo',
        'etiqueta_sub_id',
        'planilla_id',
        'paquete_id',
        'producto_id',
        'producto_id_2',
        'ubicacion_id',
        'operario1_id',
        'operario2_id',
        'soldador1_id',
        'soldador2_id',
        'ensamblador1_id',
        'ensamblador2_id',
        'nombre',
        'marca',
        'numero_etiqueta',
        'peso',
        'fecha_inicio',
        'fecha_finalizacion',
        'fecha_inicio_ensamblado',
        'fecha_finalizacion_ensamblado',
        'fecha_inicio_soldadura',
        'fecha_finalizacion_soldadura',
        'estado',
        'subido',
    ];

    // ==================== RELACIONES ====================

    /**
     * Relación: Una etiqueta pertenece a una planilla
     */
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Relación: Una etiqueta pertenece a un paquete
     */
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    /**
     * Relación: Una etiqueta puede tener múltiples elementos
     * Los elementos son las barras o estribos individuales
     * Usa etiqueta_sub_id para distinguir subetiquetas correctamente
     */
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'etiqueta_sub_id', 'etiqueta_sub_id');
    }

    /**
     * Relación: Una etiqueta puede tener productos asociados
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function producto2()
    {
        return $this->belongsTo(Producto::class, 'producto_id_2');
    }

    /**
     * Relación: Una etiqueta tiene una ubicación física
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    /**
     * Relación: Usuario relacionado con el ensamblador principal
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'ensamblador1_id');
    }

    /**
     * Relaciones con operarios
     */
    public function operario1()
    {
        return $this->belongsTo(User::class, 'operario1_id');
    }

    public function operario2()
    {
        return $this->belongsTo(User::class, 'operario2_id');
    }

    public function soldador1()
    {
        return $this->belongsTo(User::class, 'soldador1_id');
    }

    public function soldador2()
    {
        return $this->belongsTo(User::class, 'soldador2_id');
    }

    public function ensamblador1()
    {
        return $this->belongsTo(User::class, 'ensamblador1_id');
    }

    public function ensamblador2()
    {
        return $this->belongsTo(User::class, 'ensamblador2_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Accessor: Formatea el peso en kilogramos
     */
    public function getPesoKgAttribute()
    {
        if (is_null($this->peso)) {
            return 'No asignado';
        }

        return number_format((float) $this->peso, 2, ',', '.') . ' kg';
    }

    /**
     * Accessor: Obtiene el nombre del ensamblador principal
     */
    public function getUserNameAttribute()
    {
        return optional($this->ensamblador1)->name ?? 'N/A';
    }

    /**
     * Accessor: Obtiene el nombre del ensamblador secundario
     */
    public function getUser2NameAttribute()
    {
        return optional($this->ensamblador2)->name ?? 'N/A';
    }

    /**
     * Accessor: Obtiene el nombre del soldador principal
     */
    public function getSoldNameAttribute()
    {
        return optional($this->soldador1)->name ?? 'N/A';
    }

    /**
     * Accessor: Obtiene el nombre del soldador secundario
     */
    public function getSold2NameAttribute()
    {
        return optional($this->soldador2)->name ?? 'N/A';
    }

    // ==================== MÉTODOS ÚTILES ====================

    /**
     * Genera un nuevo código de subetiqueta basado en el código padre
     * Formato: CODIGO.XX donde XX es el siguiente número disponible
     *
     * @param string $codigoPadre El código de la etiqueta padre (ej: ETQ2512001)
     * @return string El nuevo código de subetiqueta (ej: ETQ2512001.03)
     */
    public static function generarCodigoSubEtiqueta(string $codigoPadre): string
    {
        // Buscar el último sufijo usado para este código padre
        $ultimaSub = self::where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED) DESC")
            ->value('etiqueta_sub_id');

        if ($ultimaSub) {
            // Extraer el número y sumarle 1
            $partes = explode('.', $ultimaSub);
            $ultimoNumero = (int) end($partes);
            $siguienteNumero = $ultimoNumero + 1;
        } else {
            // No hay subetiquetas, empezar en 01
            $siguienteNumero = 1;
        }

        return $codigoPadre . '.' . str_pad($siguienteNumero, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene el total de elementos en esta etiqueta
     */
    public function cantidadElementos()
    {
        return $this->elementos()->count();
    }

    /**
     * Verifica si la etiqueta contiene solo barras
     */
    public function esSoloBarras()
    {
        foreach ($this->elementos as $elemento) {
            if ($this->esEstribo($elemento)) {
                return false;
            }
        }
        return $this->elementos->count() > 0;
    }

    /**
     * Verifica si la etiqueta contiene solo estribos
     */
    public function esSoloEstribos()
    {
        foreach ($this->elementos as $elemento) {
            if (!$this->esEstribo($elemento)) {
                return false;
            }
        }
        return $this->elementos->count() > 0;
    }

    /**
     * Determina si un elemento es un estribo
     */
    private function esEstribo($elemento)
    {
        if (isset($elemento->figura)) {
            $figura = strtolower($elemento->figura);
            return in_array($figura, ['estribo', 'cerco', 'u', 'l']);
        }
        if (isset($elemento->longitud)) {
            return (float) $elemento->longitud < 3.0;
        }
        return false;
    }
}
