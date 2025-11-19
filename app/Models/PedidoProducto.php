<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PedidoProducto extends Model
{
    protected $table = 'pedido_productos';

    protected $fillable = [
        'pedido_id',
        'codigo',
        'pedido_global_id',
        'obra_id',
        'obra_manual',
        'producto_base_id',
        'cantidad',
        'fecha_estimada_entrega',
        'estado',
        'observaciones',
    ];
    protected $casts = [
        'fecha_estimada_entrega' => 'datetime',
    ];
    // ===================================================
    // ✅ MÉTODO PARA GENERAR CÓDIGO DE LÍNEA
    // ===================================================

    /**
     * Genera código automático al crear la línea
     * Se ejecuta automáticamente gracias al evento boot
     */
    protected static function boot()
    {
        parent::boot();

        // Cuando se crea una nueva línea, generar su código
        static::created(function ($linea) {
            if (empty($linea->codigo) && $linea->pedido) {
                $linea->generarCodigo();
            }
        });
    }

    /**
     * Genera y guarda el código de esta línea
     */
    public function generarCodigo()
    {
        if (!$this->pedido || !$this->pedido->codigo) {
            return false;
        }

        DB::transaction(function () {
            // Contar líneas existentes con código para este pedido
            $ultimoNumero = self::where('pedido_id', $this->pedido_id)
                ->whereNotNull('codigo')
                ->lockForUpdate()
                ->count();

            // Número siguiente
            $nuevoNumero = $ultimoNumero + 1;

            // Formatear: 001, 002, 003...
            $numeroFormateado = str_pad($nuevoNumero, 3, '0', STR_PAD_LEFT);

            // Código completo: PC25/0001–001
            $this->codigo = $this->pedido->codigo . '–' . $numeroFormateado;
            $this->saveQuietly(); // Guardar sin disparar eventos
        });

        return true;
    }
    // ===================================================
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class);
    }

    public function pedidoGlobal()
    {
        return $this->belongsTo(PedidoGlobal::class, 'pedido_global_id');
    }
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'pedido_producto_id');
    }

    public function entradas()
    {
        return $this->hasMany(Entrada::class, 'pedido_producto_id');
    }

    public function getTipoAttribute()
    {
        return $this->productoBase?->tipo ?? '—';
    }

    public function getDiametroAttribute()
    {
        return $this->productoBase?->diametro ?? '—';
    }

    public function getLongitudAttribute()
    {
        return $this->productoBase?->longitud ?? '—';
    }

    public function getFechaEstimadaEntregaFormateadaAttribute(): ?string
    {
        return $this->fecha_estimada_entrega
            ? $this->fecha_estimada_entrega->format('d-m-Y')
            : null;
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function coladas()
    {
        return $this->hasMany(PedidoProductoColada::class, 'pedido_producto_id');
    }
}
