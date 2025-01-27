<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etiqueta extends Model
{
    use HasFactory;

    protected $table = 'etiquetas';

    protected $fillable = [
        'planilla_id',
        'users_id_1',
        'users_id_2',
        'producto_id',
        'producto_id_2',
        'nombre',
        'paquete_id',
        'numero_etiqueta',
        'peso',
        'fecha_inicio',
        'fecha_finalizacion',
        'estado',
    ];

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'etiqueta_id', 'id');
    }

    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }
    public function getPesoKgAttribute()
    {
        if (!$this->peso_total) {
            return 'No asignado';
        }

        return number_format($this->peso_total, 2, ',', '.') . ' kg';
    }
}
