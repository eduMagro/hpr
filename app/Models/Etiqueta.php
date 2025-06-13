<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Etiqueta extends Model
{
    use HasFactory;


    protected $table = 'etiquetas';

    protected $fillable = [
        'codigo',
        'etiqueta_sub_id', // Para el caso en que todos los elementos tienen la misma máquina
        'planilla_id',
        'producto_id',
        'producto_id_2',
        'ubicacion_id',
        'operario1',
        'operario2',
        'soldador1',
        'soldador2',
        'ensamblador1',
        'ensamblador2',
        'nombre',
        'paquete_id',
        'numero_etiqueta',
        'peso',
        'fecha_inicio',
        'fecha_finalizacion',
        'fecha_inicio_ensamblado',
        'fecha_finalizacion_ensamblado',
        'fecha_inicio_soldadura',
        'fecha_finalizacion_soldadura',
        'estado',
    ];

    public function getIdEtAttribute()
    {
        return 'ET' . $this->id;
    }

    public static function generarCodigoEtiqueta(): string
    {
        return DB::transaction(function () {
            // Prefijo por año+mes, p. ej. ETQ2506
            $prefijo = 'ET' . now()->format('ym');

            $ultimo = self::where('codigo', 'like', "$prefijo%")
                ->where('codigo', 'not like', "$prefijo%.%") // ⚠️ ignora subetiquetas como ET2506999.1
                ->lockForUpdate()
                ->orderByDesc(DB::raw(
                    "CAST(SUBSTRING(codigo, LENGTH('$prefijo') + 1) AS UNSIGNED)"
                ))
                ->value('codigo');


            $siguiente = $ultimo
                ? (int)substr($ultimo, strlen($prefijo)) + 1
                : 1;

            // ➜ sin str_pad → sufijo indefinido: 1…999, 1000, 1001, ...
            return $prefijo . $siguiente;
        });
    }
    public static function generarCodigoSubEtiqueta(string $codigoPadre): string
    {
        return DB::transaction(function () use ($codigoPadre) {

            // Lock rows that start with "ET2506999."
            $existentes = self::where('codigo', 'like', $codigoPadre . '.%')
                ->lockForUpdate()
                ->pluck('codigo')
                ->map(function ($c) use ($codigoPadre) {
                    // Extrae solo la parte numérica después del punto
                    return (int) substr($c, strlen($codigoPadre) + 1);
                })
                ->toArray();

            $contador = empty($existentes) ? 1 : max($existentes) + 1;

            return $codigoPadre . '.' . $contador;   // ET2506999.2, .3, .4…
        });
    }



    // Relaciones
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'etiqueta_id', 'id');
    }
    // Relación con el modelo Producto (si existe)
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    // Relación con el modelo Producto (si existe)
    public function producto2()
    {
        return $this->belongsTo(Producto::class, 'producto_id_2');
    }
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'ensamblador1');
    }

    // Accessors
    public function getPesoKgAttribute()
    {
        if (is_null($this->peso)) {
            return 'No asignado';
        }

        return number_format((float) $this->peso, 2, ',', '.') . ' kg';
    }

    public function getUserNameAttribute()
    {
        return optional($this->ensamblador1)->name ?? 'N/A';
    }

    public function getUser2NameAttribute()
    {
        return optional($this->ensamblador2)->name ?? 'N/A';
    }

    public function getSoldNameAttribute()
    {
        return optional($this->soldador1)->name ?? 'N/A';
    }

    public function getSold2NameAttribute()
    {
        return optional($this->soldador2)->name ?? 'N/A';
    }
    public function operario1()
    {
        return $this->belongsTo(User::class);  // Relación con User
    }
    // Relación con el segundo usuario
    public function operario2()
    {
        return $this->belongsTo(User::class);
    }
    // Relación con el modelo User
    public function soldador1()
    {
        return $this->belongsTo(User::class);
    }

    public function soldador2()
    {
        return $this->belongsTo(User::class);
    }

    public function ensamblador1()
    {
        return $this->belongsTo(User::class);
    }

    public function ensamblador2()
    {
        return $this->belongsTo(User::class);
    }
}
