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
        'ubicacion_id',
        'soldador1',
        'soldador2',
        'ensamblador1',
        'ensamblador2',
        'nombre',
        'paquete_id',
        'numero_etiqueta',
        'peso',
        'fecha_inicio',
        'fecha_finalizacion',
        'estado',
    ];

    public function getIdEtAttribute()
    {
        return 'ET' . $this->id;
    }
    // Relaciones
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'etiqueta_id', 'id');
    }
    // Relación con el modelo Producto (si existe)
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    // Relación con el modelo Producto (si existe)
    public function producto2()
    {
        return $this->belongsTo(Producto::class, 'producto_id_2');
    }
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'ensamblador1');
    }

    // Accessors
    public function getPesoKgAttribute()
    {
        if (is_null($this->peso)) {
            return 'No asignado';
        }

        return number_format((float) $this->peso, 2, ',', '.') . ' kg';
    }

    public function getUserNameAttribute()
    {
        return optional($this->user)->name ?? 'N/A';
    }

    public function getUser2NameAttribute()
    {
        return optional($this->user2)->name ?? 'N/A';
    }

    public function getSoldNameAttribute()
    {
        return optional($this->soldador1)->name ?? 'N/A';
    }

    public function getSold2NameAttribute()
    {
        return optional($this->soldador2)->name ?? 'N/A';
    }
    // Relación con el modelo User
    public function soldador1()
    {
        return $this->belongsTo(User::class, 'soldador1');
    }

    public function soldador2()
    {
        return $this->belongsTo(User::class, 'soldador2');
    }

    public function ensamblador1()
    {
        return $this->belongsTo(User::class, 'ensamblador1');
    }

    public function ensamblador2()
    {
        return $this->belongsTo(User::class, 'ensamblador2');

    }
}
