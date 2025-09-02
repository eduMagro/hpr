<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    use HasFactory;

    protected $table = 'maquinas';
    protected $fillable = [
        'codigo',
        'nombre',
        'estado',
        'tipo',
        'obra_id',
        'diametro_min',
        'diametro_max',
        'peso_min',
        'peso_max',
        'ancho_m',
        'largo_m',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tiene_carro' => 'boolean',
        'ancho_m' => 'float',
        'largo_m' => 'float',

    ];
    // Celdas que ocupará según el tamaño de celda del almacén
    public function getCeldasAttribute(): array
    {
        $cell = (float) config('almacen.tamano_celda_m', 0.50);
        $ancho = max(0.0, (float) $this->ancho_m);
        $largo = max(0.0, (float) $this->largo_m);

        return [
            'ancho' => $cell > 0 ? (int) ceil($ancho / $cell) : 0,
            'largo' => $cell > 0 ? (int) ceil($largo / $cell) : 0,
        ];
    }
    public function usuarios()
    {
        return $this->hasMany(User::class, 'especialidad');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'maquina_id');
    }

    // Elementos que tienen esta máquina como principal
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'maquina_id');
    }

    // Elementos que tienen esta máquina como secundaria
    public function elementosSecundarios()
    {
        return $this->hasMany(Elemento::class, 'maquina_id_2');
    }
    // Elementos que tienen esta máquina como terciaria
    public function elementosTerciarios()
    {
        return $this->hasMany(Elemento::class, 'maquina_id_3');
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public static function naveA()
    {
        return self::whereHas('obra', fn($q) => $q->where('obra', 'Nave A'));
    }

    public function localizacion()
    {
        return $this->hasOne(Localizacion::class, 'maquina_id');
    }
}
