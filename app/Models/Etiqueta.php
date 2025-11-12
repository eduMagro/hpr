<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Etiqueta
 * 
 * Representa una etiqueta de identificación para un conjunto de elementos
 * Las etiquetas agrupan elementos (barras/estribos) que pertenecen a un mismo lote
 */
class Etiqueta extends Model
{
    protected $table = 'etiquetas';

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
     */
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'etiqueta_id');
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

    // ==================== MÉTODOS ÚTILES ====================

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