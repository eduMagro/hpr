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
        // Opcional: si guardas un cache del acumulado en una columna 'peso_acumulado'
        $sum = (float) $this->pedidos()
            // si quieres ser ultra-estricto: ->whereRaw('LOWER(estado) != ?', ['cancelado'])
            ->sum('peso_total');

        $this->peso_acumulado = $sum; // quita esta línea si no tienes la columna

        $objetivo = (float) ($this->cantidad_total ?? 0);
        $epsilon  = 0.001; // tolerancia por decimales (kg)

        $nuevoEstado = $this->estado; // por defecto, mantener

        if ($objetivo <= 0) {
            // Sin objetivo no se puede completar: considera 'en curso' si hay algo, si no 'pendiente'
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

        // Solo persistir si cambió algo relevante
        $dirty = false;

        if (property_exists($this, 'peso_acumulado') && $this->isDirty('peso_acumulado')) {
            $dirty = true;
        }

        if ($this->estado !== $nuevoEstado) {
            $this->estado = $nuevoEstado;
            $dirty = true;
        }

        if ($dirty) {
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
    public function distribuidor()
    {
        return $this->belongsTo(Distribuidor::class, 'distribuidor_id');
    }

    // Accesor para calcular la cantidad restante
    public function getCantidadRestanteAttribute()
    {
        // Sumar la cantidad de todas las líneas de pedido (pedidoProductos) de los pedidos hijos
        $cantidadPedida = $this->pedidos()
            ->with('pedidoProductos') // usamos la relación correcta
            ->get()
            ->flatMap->pedidoProductos
            ->sum('cantidad');

        return max(0, $this->cantidad_total - $cantidadPedida);
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

    // Accesor para calcular el % de avance
    public function getProgresoAttribute()
    {
        if ($this->cantidad_total == 0) {
            return 0;
        }

        // Sumar la cantidad recepcionada de todas las líneas de pedido (pedidoProductos)
        $acumulado = $this->pedidos()
            ->with('pedidoProductos')
            ->get()
            ->flatMap->pedidoProductos
            ->sum('cantidad');

        return round(($acumulado / $this->cantidad_total) * 100, 2);
    }
}
