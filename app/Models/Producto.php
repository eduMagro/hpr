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
    ];

    public function movimientos()
    {
        return $this->belongsTo(Entrada::class, 'entrada_id');
    }
      // Relación con el modelo Ubicacion
      public function ubicacion()
      {
          return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
      }
}
