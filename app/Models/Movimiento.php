<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $table = 'movimientos';

    protected $fillable = [
        'tipo',
        'producto_id',
        'producto_base_id',
        'paquete_id',
        'pedido_id',
        'pedido_producto_id',
        'salida_id',
        'salida_almacen_id',
        'ubicacion_origen',
        'ubicacion_destino',
        'maquina_origen',
        'maquina_destino',
        'estado',
        'prioridad',
        'descripcion',
        'nave_id',
        'fecha_solicitud',
        'fecha_ejecucion',
        'solicitado_por',
        'ejecutado_por',
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
    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class, 'producto_base_id');
    }
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
    public function pedidoProducto()
    {
        return $this->belongsTo(PedidoProducto::class, 'pedido_producto_id');
    }

    public function salida()
    {
        return $this->belongsTo(Salida::class);
    }
    public function salidaAlmacen()
    {
        return $this->belongsTo(SalidaAlmacen::class);
    }


    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino');
    }
    // En Movimiento.php
    public function maquinaDestino()
    {
        return $this->belongsTo(Maquina::class, 'maquina_destino');
    }

    // Nueva relación para maquina_origen
    public function maquinaOrigen()
    {
        return $this->belongsTo(Maquina::class, 'maquina_origen');
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function ejecutadoPor()
    {
        return $this->belongsTo(User::class, 'ejecutado_por');
    }

    public function nave()
    {
        return $this->belongsTo(Obra::class, 'nave_id');
    }

    // Devuelve la descripción “maquillada” para la vista.
    // Si la descripción ya viene con HTML (registros nuevos), la deja tal cual.
    // En app/Models/Movimiento.php
    public function getDescripcionHtmlAttribute(): string
    {
        $d = (string) $this->descripcion;

        // Si ya trae HTML, no tocar
        if ($d !== strip_tags($d)) {
            return $d;
        }

        // ---------- PRODUCTO ----------
        // Ej: "Movemos barra (Código: MP123) Ø20 mm L:14 mm de ORIGEN a máquina|ubicación DESTINO"
        $reProducto = '/^(Pasamos|Movemos)\s+(.+?)\s+\(Código:\s*([^)]+)\)\s+Ø(\d+)\s*mm(?:\s+L:\s*(\d+)\s*mm)?\s+de\s+(.+?)\s+a\s+(máquina|maquina|ubicación|ubicacion)\s+(.+)$/u';

        if (preg_match($reProducto, $d, $m)) {
            [, $verbo, $tipo, $codigo, $diam, $long, $origen, $destTipo, $destNombre] = $m;

            $tipoNorm        = mb_strtolower(trim($tipo), 'UTF-8');
            $esEncarretado   = preg_match('/\bencarretado\b/u', $tipoNorm) === 1;

            // Chips
            $chips = [];
            // Código (pill)
            $chips[] = '<span style="background:#e5e7eb;border-radius:9999px;padding:2px 10px;display:inline-block;margin-right:6px;">' . e($codigo) . '</span>';
            // Ø siempre
            $chips[] = '<span style="background:#eef;border-radius:6px;padding:2px 8px;display:inline-block;margin-right:6px;">Ø' . e($diam) . ' mm</span>';
            // L solo si NO es encarretado y hay valor
            if (!$esEncarretado && !empty($long)) {
                $chips[] = '<span style="background:#eef;border-radius:6px;padding:2px 8px;display:inline-block;margin-right:6px;">L:' . e($long) . ' mm</span>';
            }

            $materialHtml = e($verbo) . ' <strong>' . e($tipoNorm) . '</strong> ' . implode(' ', $chips);
            $origenHtml   = '<span style="color:#b45309;">' . e($origen) . '</span>';
            $destTipoNorm = mb_strtolower($destTipo, 'UTF-8');
            $esMaquina    = in_array($destTipoNorm, ['máquina', 'maquina'], true);
            $destColor    = $esMaquina ? '#1d4ed8' : '#047857';
            $destinoHtml  = '<span style="color:' . $destColor . ';">' . e($destTipo . ' ' . $destNombre) . '</span>';

            return $materialHtml . ' ' . $origenHtml . ' <span style="opacity:.6;">➜</span> ' . $destinoHtml;
        }

        // ---------- PAQUETE ----------
        // Ej: "Movemos paquete (Código: P123) de ORIGEN a máquina|ubicación DESTINO"
        $rePaquete = '/^(Pasamos|Movemos)\s+paquete\s+\(Código:\s*([^)]+)\)\s+de\s+(.+?)\s+a\s+(máquina|maquina|ubicación|ubicacion)\s+(.+)$/u';

        if (preg_match($rePaquete, $d, $m)) {
            [, $verbo, $codigo, $origen, $destTipo, $destNombre] = $m;

            $chips = [];
            // Código (pill)
            $chips[] = '<span style="background:#e5e7eb;border-radius:9999px;padding:2px 10px;display:inline-block;margin-right:6px;">' . e($codigo) . '</span>';

            $materialHtml = e($verbo) . ' <strong>paquete</strong> ' . implode(' ', $chips);
            $origenHtml   = '<span style="color:#b45309;">' . e($origen) . '</span>';
            $destTipoNorm = mb_strtolower($destTipo, 'UTF-8');
            $esMaquina    = in_array($destTipoNorm, ['máquina', 'maquina'], true);
            $destColor    = $esMaquina ? '#1d4ed8' : '#047857';
            $destinoHtml  = '<span style="color:' . $destColor . ';">' . e($destTipo . ' ' . $destNombre) . '</span>';

            return $materialHtml . ' ' . $origenHtml . ' <span style="opacity:.6;">➜</span> ' . $destinoHtml;
        }

        // Si no casa ningún formato, devuelve texto escapado
        return e($d);
    }
}
