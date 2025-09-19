<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteAlmacen extends Model
{
    protected $table = 'clientes_almacen'; // nombre en plural (puedes cambiarlo si usas otro)

    protected $fillable = [
        'nombre',
        'cif',
    ];

    public function pedidos()
    {
        return $this->hasMany(PedidoAlmacenVenta::class, 'cliente_id');
    }
}
