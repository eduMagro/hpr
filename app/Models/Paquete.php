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
        'nave_id',
        'planilla_id',
        'ubicacion_id',
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

    /**
     * Calcula el tamaño del paquete basándose en las longitudes máximas
     * de cada elemento (extraídas del campo dimensiones de cada elemento).
     * El ancho es fijo: 1 metro.
     * Las dimensiones almacenadas están en cm, convertimos a metros.
     */
    public function getTamañoAttribute()
    {
        $maxLongitudCm = 0;

        foreach ($this->etiquetas as $etiqueta) {
            foreach ($etiqueta->elementos as $elemento) {
                $maxLongitudElemento = $this->extraerMaxLongitudDeDimensiones($elemento->dimensiones);

                if ($maxLongitudElemento > $maxLongitudCm) {
                    $maxLongitudCm = $maxLongitudElemento;
                }
            }
        }

        // ✅ Convertimos de cm a metros (corregido)
        $maxLongitudM = $maxLongitudCm / 100;

        return [
            'ancho'    => 1,
            'longitud' => $maxLongitudM
        ];
    }


    /**
     * Extrae la longitud máxima de un string de dimensiones en cm.
     * Ejemplo: "10 90d 200 90d" => devuelve 200 (cm).
     */
    protected function extraerMaxLongitudDeDimensiones($dimensiones)
    {
        if (!$dimensiones) return 0;

        $partes = preg_split('/\s+/', trim($dimensiones));
        $max = 0;

        foreach ($partes as $parte) {
            // ignoramos las partes que contengan 'd' (grados)
            if (strpos($parte, 'd') === false && is_numeric($parte)) {
                $valor = floatval($parte);
                if ($valor > $max) {
                    $max = $valor;
                }
            }
        }

        return $max; // en cm
    }

    public function nave()
    {
        return $this->belongsTo(Obra::class, 'nave_id');
    }
}
