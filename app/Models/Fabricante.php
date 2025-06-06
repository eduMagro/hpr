<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fabricante extends Model
{
    protected $table = 'fabricantes';

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'direccion',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
