<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaAlmacen extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'codigo_sage',
        'user_id',
        'camion_id',
        'empresa_id',
        'horas_paralizacion',
        'importe_paralizacion',
        'horas_grua',
        'importe_grua',
        'horas_almacen',
        'importe',
        'estado',
        'fecha_salida',
        'observaciones'
    ];
    // app/Models/SalidaAlmacen.php
    protected $table = 'salidas_almacen';

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Una salida pertenece a un camión.
     */
    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }

    /**
     * Relación: Una salida pertenece a una empresa de transporte.
     */
    public function empresaTransporte()
    {
        return $this->belongsTo(EmpresaTransporte::class, 'empresa_id');
    }

    /**
     * Relación: Una salida tiene muchos paquetes.
     */
    public function productos()
    {
        return $this->belongsToMany(Paquete::class, 'salidas_almacen_productos', 'salida_almacen_id', 'producto_id');
    }
    public function salidasPaquetes()
    {
        return $this->hasMany(SalidaPaquete::class);
    }

    public function obras()
    {
        return $this->belongsToMany(Obra::class, 'salida_cliente', 'salida_id', 'obra_id');
    }
    public function obraDestino()
    {
        return $this->belongsTo(Obra::class, 'obra_id_destino');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }

    public function clientes()
    {
        // solo para listar clientes vinculados (sin campos extra del pivote)
        return $this->belongsToMany(
            Cliente::class,
            'salidas_almacen_clientes',   // tabla pivote
            'salida_almacen_id',          // FK local en la pivote
            'cliente_id'                  // FK remota
        );
    }

    public function salidasClientes()
    {
        // aquí gestionas los campos extra (horas, importes, obra_id…)
        return $this->hasMany(SalidaAlmacenCliente::class, 'salida_almacen_id');
    }

    public static function generarCodigo()
    {
        $año = now()->format('y'); // ejemplo: '25' para 2025
        $prefijo = "AS{$año}/";

        // Buscar el último código generado con ese prefijo
        $ultimoCodigo = self::where('codigo', 'like', "{$prefijo}%")
            ->orderBy('codigo', 'desc')
            ->value('codigo');

        $siguiente = 1;

        if ($ultimoCodigo) {
            $partes = explode('/', $ultimoCodigo);
            $numeroActual = intval($partes[1]);
            $siguiente = $numeroActual + 1;
        }

        $numeroFormateado = str_pad($siguiente, 4, '0', STR_PAD_LEFT);

        return $prefijo . $numeroFormateado; // ejemplo: SA25/0004
    }
}
