<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion_id',  // Relación con la ubicación
    ];

    /**
     * Relación con la tabla 'entradas'
     * Un producto pertenece a una única entrada (relación uno a uno)
     */
    public function entrada()
    {
        return $this->belongsTo(Entrada::class, 'producto_id');  // Un producto pertenece a una entrada
    }

    /**
     * Relación con la tabla 'ubicaciones'
     * Un producto pertenece a una ubicación
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
}
