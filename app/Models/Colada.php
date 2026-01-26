<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colada extends Model
{
    protected $table = 'coladas';

    protected $fillable = [
        'numero_colada',
        'producto_base_id',
        'fabricante_id',
        'documento',
        'codigo_adherencia',
        'observaciones',
        'dio_de_alta',
        'ultima_modificacion',
    ];

    public function dioDeAltaPor()
    {
        return $this->belongsTo(User::class, 'dio_de_alta');
    }

    public function ultimoModificadoPor()
    {
        return $this->belongsTo(User::class, 'ultima_modificacion');
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class);
    }

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function pedidoProductoColadas()
    {
        return $this->hasMany(PedidoProductoColada::class);
    }
}
