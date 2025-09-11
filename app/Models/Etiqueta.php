<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Etiqueta extends Model
{
    use HasFactory;


    protected $table = 'etiquetas';
    protected $appends = ['peso_kg'];
    protected $fillable = [
        'codigo',
        'etiqueta_sub_id', // Para el caso en que todos los elementos tienen la misma máquina
        'planilla_id',
        'producto_id',
        'producto_id_2',
        'ubicacion_id',
        'operario1_id',
        'operario2_id',
        'soldador1_id',
        'soldador2_id',
        'ensamblador1_id',
        'ensamblador2_id',
        'nombre',
        'marca',
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
            $prefijo = 'ETQ' . now()->format('ym');   // p.ej. ETQ2506

            // 1️⃣  Solo filas PADRE (etiqueta_sub_id IS NULL)
            $ultimo = self::where('codigo', 'like', "$prefijo%")
                ->whereNull('etiqueta_sub_id')        // ← clave del arreglo
                ->lockForUpdate()
                ->orderByDesc(DB::raw(
                    "CAST(SUBSTRING(codigo, LENGTH('$prefijo') + 1) AS UNSIGNED)"
                ))
                ->value('codigo');

            $numero = $ultimo
                ? (int) substr($ultimo, strlen($prefijo)) + 1
                : 1;

            return sprintf('%s%03d', $prefijo, $numero); // ETQ2506005, ETQ2506006…
        });
    }

    public static function generarCodigoSubEtiqueta(string $codigoPadre): string
    {
        return DB::transaction(function () use ($codigoPadre) {
            // Prefijo de subetiquetas del padre, p.ej. "ETQ2508007."
            $prefijo = $codigoPadre . '.';

            // Bloqueamos fila/índice para evitar carreras y obtenemos el mayor sufijo existente
            $maxIndice = self::where('etiqueta_sub_id', 'like', $prefijo . '%')
                ->lockForUpdate()
                ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)) as maxnum"))
                ->value('maxnum');

            $siguiente = ($maxIndice ? ((int)$maxIndice) : 0) + 1;

            return $codigoPadre . '.' . str_pad($siguiente, 2, '0', STR_PAD_LEFT);
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
