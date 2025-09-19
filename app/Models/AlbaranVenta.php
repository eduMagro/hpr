<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlbaranVenta extends Model
{
    protected $table = 'albaranes_venta';

    protected $fillable = [
        'salida_id',
        'cliente_id',
        'codigo',
        'fecha',
        'estado',
        'created_by',
        'updated_by',
    ];

    // Relaciones
    public function salida()
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_id');
    }

    public function cliente()
    {
        return $this->belongsTo(ClienteAlmacen::class, 'cliente_id');
    }

    public function lineas()
    {
        return $this->hasMany(AlbaranVentaLinea::class, 'albaran_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
