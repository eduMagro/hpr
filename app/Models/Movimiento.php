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

        if ($d !== strip_tags($d)) {
            return $d; // ya viene con HTML
        }

        // Helper: formatea "Almacén 0A, Sector 1, Ubicación 3"
        // → "Almacén [<strong>0A</strong>] Sector [<strong>1</strong>] Ubicación [<strong>3</strong>]"
        $fmtUbicacion = function (string $txt): string {
            $out = e(trim($txt));

            // Almacén
            $out = preg_replace_callback(
                '/\b(Almac[eé]n)\s+([A-Za-z0-9\-]+)/u',
                fn($m) => e($m[1]) . ' <strong>' . e($m[2]) . '</strong>',
                $out
            );

            // Sector
            $out = preg_replace_callback(
                '/\b(Sector)\s+([A-Za-z0-9\-]+)/u',
                fn($m) => e($m[1]) . ' <strong>' . e($m[2]) . '</strong>',
                $out
            );

            // Ubicación
            $out = preg_replace_callback(
                '/\b(Ubicaci[oó]n)\s+([A-Za-z0-9\-]+)/u',
                fn($m) => e($m[1]) . ' <strong>' . e($m[2]) . '</strong>',
                $out
            );

            return $out;
        };

        // ----------------- PRODUCTO -----------------
        $reProducto = '/^(Pasamos|Movemos)\s+(.+?)\s+\(Código:\s*([^)]+)\)\s+Ø(\d+)\s*mm(?:\s+L:\s*(\d+)\s*mm)?\s+de\s+(.+?)\s+a\s+(máquina|maquina|ubicación|ubicacion)\s+(.+)$/u';
        if (preg_match($reProducto, $d, $m)) {
            [, $verbo, $tipo, $codigo, $diam, $long, $origen, $destTipo, $destNombre] = $m;

            $tipoLower       = mb_strtolower(trim($tipo), 'UTF-8');
            $tipoUpper       = mb_strtoupper($tipoLower, 'UTF-8'); // ← para mostrar
            $esEncarretado   = preg_match('/\bencarretado\b/u', $tipoLower) === 1;



            // Chips
            $chips = [];
            $chips[] = '<span style="background:#e5e7eb;border-radius:9999px;padding:2px 10px;display:inline-block;margin-right:6px;">' . e($codigo) . '</span>';
            $chips[] = '<span style="background:#eef;border-radius:6px;padding:2px 8px;display:inline-block;margin-right:6px;">Ø' . e($diam) . ' mm</span>';
            if (!$esEncarretado && !empty($long)) {
                $chips[] = '<span style="background:#eef;border-radius:6px;padding:2px 8px;display:inline-block;margin-right:6px;">L:' . e($long) . ' mm</span>';
            }

            // 🔸 Origen formateado (si tiene patrón de Almacén/Sector/Ubicación)
            $origenHtml = '<span style="color:#b45309;">' . $fmtUbicacion($origen) . '</span>';

            // 🔹 Destino: si es máquina, normal; si es ubicación, aplicar formateo al nombre
            $destTipoNorm = mb_strtolower($destTipo, 'UTF-8');
            $esMaquina    = in_array($destTipoNorm, ['máquina', 'maquina'], true);
            $destColor    = $esMaquina ? '#1d4ed8' : '#047857';
            $destNombreHtml = $esMaquina
                ? '<strong>' . e($destNombre) . '</strong>'
                : $fmtUbicacion($destNombre);

            $destinoHtml = '<span style="color:' . $destColor . ';"> ' . $destNombreHtml . '</span>';

            $materialHtml = e($verbo) . ' <strong>' . e($tipoUpper) . '</strong> ' . implode(' ', $chips);

            return $materialHtml . ' ' . $origenHtml . ' <span style="opacity:.6;">➜</span> ' . $destinoHtml;
        }

        // ----------------- PAQUETE -----------------
        $rePaquete = '/^(Pasamos|Movemos)\s+paquete\s+\(Código:\s*([^)]+)\)\s+de\s+(.+?)\s+a\s+(máquina|maquina|ubicación|ubicacion)\s+(.+)$/u';
        if (preg_match($rePaquete, $d, $m)) {
            [, $verbo, $codigo, $origen, $destTipo, $destNombre] = $m;

            $chips = [];
            $chips[] = '<span style="background:#e5e7eb;border-radius:9999px;padding:2px 10px;display:inline-block;margin-right:6px;">' . e($codigo) . '</span>';

            $origenHtml = '<span style="color:#b45309;">' . $fmtUbicacion($origen) . '</span>';

            $destTipoNorm = mb_strtolower($destTipo, 'UTF-8');
            $esMaquina    = in_array($destTipoNorm, ['máquina', 'maquina'], true);
            $destColor    = $esMaquina ? '#1d4ed8' : '#047857';
            $destNombreHtml = $esMaquina ? '<strong>' . e($destNombre) . '</strong>' : $fmtUbicacion($destNombre);

            $destinoHtml = '<span style="color:' . $destColor . ';">' . $destNombreHtml . '</span>';

            $materialHtml = e($verbo) . ' <strong>paquete</strong> ' . implode(' ', $chips);

            return $materialHtml . ' ' . $origenHtml . ' <span style="opacity:.6;">➜</span> ' . $destinoHtml;
        }

        // Fallback
        return e($d);
    }
}
