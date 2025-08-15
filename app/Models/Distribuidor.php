<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Distribuidor extends Model
{
    use HasFactory;

    protected $table = 'distribuidores';

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
    public function pedidosGlobalesComoDistribuidor()
    {
        return $this->hasMany(PedidoGlobal::class, 'distribuidor_id');
    }
}
