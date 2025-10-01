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
        'precio_referencia',
        'fabricante_id',
        'estado',

    ];
    protected $appends = ['precio_referencia_euro'];

    protected $casts = [

        'precio_referencia' => 'decimal:2',
    ];
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_CURSO = 'en curso';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_CANCELADO = 'cancelado';
    public function getPrecioReferenciaEuroAttribute(): ?string
    {
        if ($this->precio_referencia === null) return null;

        return number_format($this->precio_referencia, 2, ',', '') . ' €';
    }
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
    // En App\Models\PedidoGlobal

    public function actualizarEstadoSegunProgreso(): void
    {
        $sum = (float) $this->pedidos()
            // ->whereRaw('LOWER(estado) != ?', ['cancelado']) // si te interesa
            ->sum('peso_total');

        $objetivo = (float) ($this->cantidad_total ?? 0);
        $epsilon  = 0.001;

        $nuevoEstado = $this->estado;

        if ($objetivo <= 0) {
            $nuevoEstado = ($sum > $epsilon) ? self::ESTADO_EN_CURSO : self::ESTADO_PENDIENTE;
        } else {
            if ($sum + $epsilon >= $objetivo) {
                $nuevoEstado = self::ESTADO_COMPLETADO;
            } elseif ($sum > $epsilon) {
                $nuevoEstado = self::ESTADO_EN_CURSO;
            } else {
                $nuevoEstado = self::ESTADO_PENDIENTE;
            }
        }

        if ($this->estado !== $nuevoEstado) {
            $this->estado = $nuevoEstado;
            $this->save();
        }
    }

    public function pedidoProductos()
    {
        return $this->hasMany(PedidoProducto::class, 'pedido_global_id');
    }

    public function pedidos()
    {
        return $this->hasManyThrough(
            Pedido::class,
            PedidoProducto::class,
            'pedido_global_id', // FK en pedido_productos
            'id',               // FK en pedidos
            'id',               // local key en pedido_global
            'pedido_id'         // local key en pedido_productos
        )->distinct();
    }

    // Relación con fabricante
    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class, 'fabricante_id');
    }
    public function distribuidor()
    {
        return $this->belongsTo(Distribuidor::class, 'distribuidor_id');
    }

    public function getCantidadRestanteAttribute(): float
    {
        // Suma de cantidades en pedido_productos con este pedido_global_id (no canceladas)
        $cantidadPedida = (float) PedidoProducto::where('pedido_global_id', $this->id)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '!=', 'cancelado');
            })
            ->sum('cantidad');

        $objetivo = (float) ($this->cantidad_total ?? 0);

        return max(0.0, $objetivo - $cantidadPedida);
    }

    public function getProgresoAttribute(): float
    {
        $objetivo = (float) ($this->cantidad_total ?? 0);
        if ($objetivo <= 0) {
            return 0.0;
        }

        $acumulado = (float) PedidoProducto::where('pedido_global_id', $this->id)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '!=', 'cancelado');
            })
            ->sum('cantidad');

        return round(($acumulado / $objetivo) * 100, 2);
    }




    public function entradas()
    {
        return $this->hasManyThrough(
            Entrada::class,   // Modelo destino
            Pedido::class,    // Modelo intermedio
            'pedido_global_id',           // Foreign key en pedidos
            'pedido_id',                  // Foreign key en entradas
            'id',                         // Local key en pedido_global
            'id'                          // Local key en pedidos
        );
    }
}
