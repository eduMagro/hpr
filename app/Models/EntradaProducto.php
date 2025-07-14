<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaProducto extends Model
{
    use HasFactory;

    protected $table = 'entrada_producto';

    protected $fillable = [
        'entrada_id',     // ID de la entrada asociada
        'producto_id',    // ID del producto asociado
        'ubicacion_id',   // ID de la ubicación asociada
        'users_id',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    // Definir las relaciones
    public function entry()
    {
        return $this->belongsTo(Entrada::class, 'entrada_id');
    }

    public function product()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function pedidoProducto()
    {
        return $this->belongsTo(PedidoProducto::class, 'pedido_producto_id');
    }
}
