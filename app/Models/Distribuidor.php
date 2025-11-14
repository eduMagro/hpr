<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Distribuidor extends Model
{
    use HasFactory, SoftDeletes;

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
