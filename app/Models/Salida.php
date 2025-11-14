<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salida extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo_salida',
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
    public function paquetes()
    {
        return $this->belongsToMany(Paquete::class, 'salidas_paquetes', 'salida_id', 'paquete_id');
    }
    public function salidasPaquetes()
    {
        return $this->hasMany(SalidaPaquete::class);
    }
    public function salidaClientes()
    {
        return $this->hasMany(SalidaCliente::class);
    }
    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'salida_cliente', 'salida_id', 'cliente_id');
    }
    public function obras()
    {
        return $this->belongsToMany(Obra::class, 'salida_cliente', 'salida_id', 'obra_id');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }

    public static function generarCodigo()
    {
        $año = now()->format('y'); // ejemplo: '25' para 2025
        $prefijo = "SA{$año}/";

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
