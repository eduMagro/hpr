<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Models\Entrada;

class PedidoGlobal extends Model
{
    use HasFactory;

    protected $table = 'pedidos_globales';

    protected $fillable = [
        'codigo',
        'descripcion',
        'cantidad_total',
        'fabricante_id',
        'estado'
    ];

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_CURSO = 'en curso';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_CANCELADO = 'cancelado';

    public static function generarCodigo()
    {
        $año = now()->format('y');
        $prefix = "PCG{$año}/";

        // Buscar el último código generado
        $últimoCodigo = self::where('codigo', 'like', "{$prefix}%")
            ->orderBy('codigo', 'desc')
            ->value('codigo');

        // Extraer el número y aumentarlo
        $siguiente = 1;

        if ($últimoCodigo) {
            $partes = explode('/', $últimoCodigo);
            $numeroActual = intval($partes[1]);
            $siguiente = $numeroActual + 1;
        }

        $númeroFormateado = str_pad($siguiente, 4, '0', STR_PAD_LEFT);

        return $prefix . $númeroFormateado;
    }

    public function getFechaCreacionFormateadaAttribute()
    {
        return Carbon::parse($this->created_at)->format('d-m-Y H:i');
    }
    public function actualizarEstadoSegunProgreso()
    {
        $pesoAcumulado = $this->pedidos()->sum('peso_total');

        if ($pesoAcumulado >= $this->cantidad_total) {
            $this->estado = self::ESTADO_COMPLETADO;
            $this->save();
        }
    }

    // Relación con pedidos individuales
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'pedido_global_id');
    }

    // Relación con fabricante
    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class, 'fabricante_id');
    }

    // Accesor para calcular la cantidad restante
    public function getCantidadRestanteAttribute()
    {
        // Sumar el peso_total de todos los pedidos asociados a este pedido global
        $cantidadPedida = $this->pedidos()->sum('peso_total');

        // Restar a la cantidad_total del pedido global
        return max(0, $this->cantidad_total - $cantidadPedida);
    }


    public function entradas()
    {
        return $this->hasManyThrough(
            \App\Models\Entrada::class,   // Modelo destino
            \App\Models\Pedido::class,    // Modelo intermedio
            'pedido_global_id',           // Foreign key en pedidos
            'pedido_id',                  // Foreign key en entradas
            'id',                         // Local key en pedido_global
            'id'                          // Local key en pedidos
        );
    }

    // Accesor para calcular el % de avance
    public function getProgresoAttribute()
    {
        if ($this->cantidad_total == 0) {
            return 0;
        }

        return round(($this->cantidad_acumulada / $this->cantidad_total) * 100, 2);
    }
}
