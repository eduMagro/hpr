<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'fabricante',
        'nombre',
        'tipo',
        'diametro',
        'longitud',
        'n_colada',
        'n_paquete',
        'peso_inicial',
        'peso_stock',
        'ubicacion_id',  // Relación con la ubicación
        'maquina_id',
        'estado',
        'otros',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /**
     * Relación con la tabla 'entradas'
     * Un producto pertenece a una única entrada (relación uno a uno)
     */
    public function entrada()
    {
        return $this->belongsTo(Entrada::class, 'entrada_producto');  // Un producto pertenece a una entrada
    }

    /**
     * Relación con la tabla 'ubicaciones'
     * Un producto pertenece a una ubicación
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
    public function buscaAlmacenados($query)
    {
        return $query->where('estado', 'almacenado')->whereNull('maquina_id');
    }

    public function buscaConsumiendo($query)
    {
        return $query->where('estado', 'consumiendo')->whereNotNull('maquina_id');
    }
    // Relación con los elementos que están asociados a este producto
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'producto_id');
    }
}
