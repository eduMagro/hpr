<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalizacionPaquete extends Model
{
    protected $table = 'localizaciones_paquetes';

    // si tu tabla tiene timestamps puedes dejarlos, si no los desactivas:
    public $timestamps = false;

    protected $fillable = [
        'paquete_id',
        'x1',
        'y1',
        'x2',
        'y2',
    ];

    /**
     * Relación con el paquete
     * Un registro pertenece a un paquete.
     */
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    /**
     * (Opcional) Relación con localización si la usas
     * Por ejemplo, si quieres saber a qué localización pertenece.
     */
    public function localizacion()
    {
        return $this->belongsTo(Localizacion::class, 'localizacion_id');
    }

    public function localizacionPaquete()
    {
        return $this->hasOne(LocalizacionPaquete::class, 'paquete_id');
    }

    public function localizacionesPaquetes()
    {
        return $this->hasMany(LocalizacionPaquete::class, 'localizacion_id');
    }
}
