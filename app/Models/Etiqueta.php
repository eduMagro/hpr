<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Etiqueta extends Model
{
    use HasFactory;

    protected $table = 'etiquetas';
    protected $appends = ['peso_kg'];
    protected $fillable = [
        'codigo',
        'etiqueta_sub_id',
        'planilla_id',
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
        'paquete_id',
        'numero_etiqueta',
        'peso',
        'fecha_inicio',
        'fecha_finalizacion',
        'fecha_inicio_ensamblado',
        'fecha_finalizacion_ensamblado',
        'fecha_inicio_soldadura',
        'fecha_finalizacion_soldadura',
        'estado',
    ];

    // Relaciones
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'etiqueta_id', 'id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

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
        return optional($this->ensamblador1)->name ?? 'N/A';
    }

    public function getUser2NameAttribute()
    {
        return optional($this->ensamblador2)->name ?? 'N/A';
    }

    public function getSoldNameAttribute()
    {
        return optional($this->soldador1)->name ?? 'N/A';
    }

    public function getSold2NameAttribute()
    {
        return optional($this->soldador2)->name ?? 'N/A';
    }

    public function operario1()
    {
        return $this->belongsTo(User::class);
    }

    public function operario2()
    {
        return $this->belongsTo(User::class);
    }

    public function soldador1()
    {
        return $this->belongsTo(User::class);
    }

    public function soldador2()
    {
        return $this->belongsTo(User::class);
    }

    public function ensamblador1()
    {
        return $this->belongsTo(User::class);
    }

    public function ensamblador2()
    {
        return $this->belongsTo(User::class);
    }
}
