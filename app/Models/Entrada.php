<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    use HasFactory;

    // El nombre de la tabla asociada
    protected $table = "entradas";

    // Los campos que son asignables masivamente
    protected $fillable = [
        'albaran',
        'pedido_id',
        'pedido_producto_id',
        'peso_total',
        'usuario_id',
        'estado',
        'otros',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /**
     * Relación con la tabla 'ubicaciones'
     * Una entrada pertenece a una ubicación
     */
    // En el modelo Entrada

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);  // Relación con Ubicacion
    }

    /**
     * Relación con la tabla 'users'
     * Una entrada pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');  // Relación con User
    }

    /**
     * Relación con la tabla 'productos'
     * Una entrada puede tener muchos productos
     */
    public function productos()
    {
        return $this->hasMany(Producto::class);   // 👈  ya no hay 2.º parámetro
    }
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
    public function pedidoProducto()
    {
        return $this->belongsTo(PedidoProducto::class, 'pedido_producto_id');
    }
}
