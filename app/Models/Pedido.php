<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'codigo',
        'pedido_global_id',
        'fabricante_id',
        'distribuidor_id',
        'obra_id',
        'fecha_pedido',
        'fecha_entrega',
        'estado',
        'observaciones',
    ];
    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_entrega' => 'date',
    ];
    protected $appends = ['fecha_pedido_formateada', 'fecha_entrega_formateada', 'peso_total_formateado'];

    public function getFechaCreacionFormateadaAttribute()
    {
        return Carbon::parse($this->created_at)->format('d-m-Y H:i');
    }
    public function pedidoGlobal()
    {
        return $this->belongsTo(PedidoGlobal::class, 'pedido_global_id');
    }


    public static function generarCodigo()
    {
        $año = now()->format('y');
        $prefix = "PC{$año}/";

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

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }
    public function distribuidor()
    {
        return $this->belongsTo(Distribuidor::class);
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function productos()
    {
        return $this->belongsToMany(ProductoBase::class, 'pedido_productos')
            ->withPivot([
                'id',
                'cantidad_recepcionada',
                'cantidad',
                'estado',
                'fecha_estimada_entrega',
                'observaciones',
            ])
            ->withTimestamps();
    }

    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }

    public function getFechaPedidoFormateadaAttribute()
    {
        return optional($this->fecha_pedido)->format('d-m-Y');
    }
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }

    public function getFechaEntregaFormateadaAttribute()
    {
        return optional($this->fecha_entrega)->format('d-m-Y');
    }
    public function getPesoTotalFormateadoAttribute()
    {
        if (is_null($this->peso_total)) {
            return 'N/A';
        }

        return number_format($this->peso_total, 2, ',', '.') . ' kg';
    }

    public function getCantidadRestanteAttribute()
    {
        $suministrado = $this->entradas()->sum('peso_total');
        return max(0, $this->peso_total - $suministrado);
    }
}
