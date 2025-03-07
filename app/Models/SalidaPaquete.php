<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaPaquete extends Model
{
    use HasFactory;

    // Si el nombre de la tabla no es el plural del nombre del modelo, puedes especificarlo
    protected $table = 'salidas_paquetes';

    // Si esta tabla es de relación muchos a muchos, no necesitamos definir "fillable" si no se insertan directamente
    protected $fillable = [
        'salida_id',
        'paquete_id',
    ];

    // Definimos las relaciones
    public function salida()
    {
        return $this->belongsTo(Salida::class);
    }

    public function paquete()
    {
        return $this->belongsTo(Paquete::class);
    }
}
