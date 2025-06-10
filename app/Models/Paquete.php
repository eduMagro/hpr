<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paquete extends Model
{
    use HasFactory;

    protected $table = 'paquetes'; // Nombre de la tabla en la BD

    protected $fillable = [
        'codigo',
        'ubicacion_id',
        'planilla_id',
        'peso'
    ];
    public static function generarCodigo()
    {
        $year = now()->format('y');
        $month = now()->format('m');

        $ultimoCodigo = self::where('codigo', 'LIKE', "P{$year}{$month}%")
            ->orderBy('codigo', 'desc')
            ->value('codigo');

        if ($ultimoCodigo) {
            $ultimoNumero = intval(substr($ultimoCodigo, 5));
            $nuevoNumero = str_pad($ultimoNumero + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nuevoNumero = '0001';
        }

        return "P{$year}{$month}{$nuevoNumero}";
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Relación uno a muchos con Etiqueta (Un paquete puede tener muchas etiquetas)
     */
    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'paquete_id');
    }
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'paquete_id');
    }

    /**
     * Relación uno a uno con Ubicación (Un paquete tiene una única ubicación)
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    /**
     * Relación: Un paquete puede estar asociado a muchas salidas.
     */
    public function salidas()
    {
        return $this->belongsToMany(Salida::class, 'salidas_paquetes', 'paquete_id', 'salida_id');
    }
    public function salidasPaquetes()
    {
        return $this->hasMany(SalidaPaquete::class);
    }
}
