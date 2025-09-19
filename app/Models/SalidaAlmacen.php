<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalidaAlmacen extends Model
{
    protected $table = 'salidas_almacen';

    protected $fillable = [
        'codigo',
        'fecha',
        'estado',
        'camionero_id',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'fecha' => 'date',
    ];
    // Relaciones
    public function albaranes()
    {
        return $this->hasMany(AlbaranVenta::class, 'salida_id');
    }

    public function camionero()
    {
        return $this->belongsTo(User::class, 'camionero_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function getPesoTotalAttribute(): float
    {
        return $this->albaranes
            ->flatMap->lineas  // juntar todas las líneas de todos los albaranes
            ->sum('cantidad_kg'); // 👈 cambia 'cantidad' por el campo real (ej. peso_kg)
    }
}
