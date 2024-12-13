<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $table = 'movimientos';

    protected $fillable = [
        'producto_id',
        'ubicacion_origen',
        'ubicacion_destino',
        'maquina_id',
        'users_id',
    ];
    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
