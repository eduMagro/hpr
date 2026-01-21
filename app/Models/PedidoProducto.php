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
        'cantidad_recepcionada',
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

    // ===================================================
    // CÁLCULO DE COSTE ESTIMADO
    // ===================================================

    /**
     * Calcula el coste estimado de esta línea de pedido.
     * Fórmula: (precio_referencia + incremento_diametro + incremento_formato) × toneladas
     */
    public function getCosteEstimadoAttribute(): ?float
    {
        // Obtener precio de referencia del PedidoGlobal
        $precioReferencia = $this->pedidoGlobal?->precio_referencia;
        if ($precioReferencia === null) {
            return null;
        }

        // Obtener datos del producto
        $diametro = (int) ($this->productoBase?->diametro ?? 0);
        $longitud = $this->productoBase?->longitud; // en metros
        $esEncarretado = strtolower($this->productoBase?->tipo ?? '') === 'encarretado';

        // Obtener incremento por diámetro
        $incrementoDiametro = PrecioMaterialDiametro::getIncremento($diametro);

        // Determinar formato según longitud y tipo
        $formato = PrecioMaterialFormato::determinarFormato($longitud, $esEncarretado);
        $formatoCodigo = $formato?->codigo ?? 'estandar_12m';

        // Buscar excepción por fabricante/distribuidor o usar formato base
        $fabricanteId = $this->pedido?->fabricante_id;
        $distribuidorId = $this->pedido?->distribuidor_id;

        $incrementoFormato = 0;
        $excepcion = PrecioMaterialExcepcion::buscar($distribuidorId, $fabricanteId, $formatoCodigo);

        if ($excepcion) {
            $incrementoFormato = (float) $excepcion->incremento;
        } elseif ($formato) {
            $incrementoFormato = (float) $formato->incremento;
        }

        // Calcular toneladas (basado en cantidad recepcionada)
        $cantidadRecepcionada = $this->cantidad_recepcionada ?? 0;
        if ($cantidadRecepcionada <= 0) {
            return null; // Sin recepción, no hay coste
        }
        $toneladas = $cantidadRecepcionada / 1000;

        // Calcular coste
        $precioTonelada = $precioReferencia + $incrementoDiametro + $incrementoFormato;
        $coste = $precioTonelada * $toneladas;

        return round($coste, 2);
    }

    /**
     * Devuelve el coste formateado en euros.
     */
    public function getCosteEstimadoFormateadoAttribute(): string
    {
        $coste = $this->coste_estimado;
        if ($coste === null) {
            return '—';
        }
        return number_format($coste, 2, ',', '.') . ' €';
    }

    /**
     * Devuelve el desglose del cálculo del coste.
     */
    public function getCosteDesglose(): array
    {
        $precioReferencia = $this->pedidoGlobal?->precio_referencia;
        if ($precioReferencia === null) {
            return ['error' => 'Sin precio de referencia'];
        }

        $diametro = (int) ($this->productoBase?->diametro ?? 0);
        $longitud = $this->productoBase?->longitud;
        $esEncarretado = strtolower($this->productoBase?->tipo ?? '') === 'encarretado';

        $incrementoDiametro = PrecioMaterialDiametro::getIncremento($diametro);

        // Determinar formato según longitud y tipo
        $formato = PrecioMaterialFormato::determinarFormato($longitud, $esEncarretado);
        $formatoCodigo = $formato?->codigo ?? 'estandar_12m';

        $fabricanteId = $this->pedido?->fabricante_id;
        $distribuidorId = $this->pedido?->distribuidor_id;

        $incrementoFormato = 0;
        $origenFormato = 'base';
        $excepcion = PrecioMaterialExcepcion::buscar($distribuidorId, $fabricanteId, $formatoCodigo);

        if ($excepcion) {
            $incrementoFormato = (float) $excepcion->incremento;
            $origenFormato = $excepcion->distribuidor_id ? 'excepcion_especifica' : 'excepcion_fabricante';
        } elseif ($formato) {
            $incrementoFormato = (float) $formato->incremento;
        }

        // Usar cantidad recepcionada para el cálculo del coste
        $cantidadRecepcionada = $this->cantidad_recepcionada ?? 0;
        $toneladas = $cantidadRecepcionada / 1000;
        $precioTonelada = $precioReferencia + $incrementoDiametro + $incrementoFormato;

        return [
            'precio_referencia' => $precioReferencia,
            'incremento_diametro' => $incrementoDiametro,
            'incremento_formato' => $incrementoFormato,
            'precio_tonelada' => round($precioTonelada, 2),
            'toneladas' => round($toneladas, 4),
            'coste_total' => $cantidadRecepcionada > 0 ? round($precioTonelada * $toneladas, 2) : null,
            'cantidad_recepcionada' => $cantidadRecepcionada,
            'diametro' => $diametro,
            'longitud' => $longitud,
            'formato' => $formatoCodigo,
            'origen_formato' => $origenFormato,
        ];
    }
}
