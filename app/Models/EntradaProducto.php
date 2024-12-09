<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaProducto extends Model
{
    use HasFactory;

    protected $table = 'entradas_productos';

    protected $fillable = [
        'albaran',
        'entrada_id',     // ID de la entrada asociada
        'producto_id',    // ID del producto asociado
        'qr',             // Código QR generado
        'ubicacion_id',   // ID de la ubicación asociada
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
}
