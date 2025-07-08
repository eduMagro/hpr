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
        'diametro_min',
        'diametro_max',
        'peso_min',
        'peso_max',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
}
