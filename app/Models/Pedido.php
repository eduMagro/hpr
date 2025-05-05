<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'codigo',
        'proveedor_id',
        'fecha_pedido',
        'fecha_estimada',
        'estado',
        'observaciones',
    ];
    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_estimada' => 'datetime',
    ];
    public static function generarCodigo()
    {
        $año = now()->format('y');
        $último = self::whereYear('created_at', now()->year)->count();
        $número = str_pad($último + 1, 4, '0', STR_PAD_LEFT);

        return "PC{$año}/{$número}";
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function productos()
    {
        return $this->belongsToMany(ProductoBase::class, 'pedido_productos')
            ->withPivot('cantidad', 'observaciones')
            ->withTimestamps();
    }
}
