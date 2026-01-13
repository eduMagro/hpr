<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Elemento extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'elementos';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'id',
        'codigo',
        'planilla_id',
        'ferrawin_id',
        'planilla_entidad_id',
        'etiqueta_ensamblaje_id',
        'elaborado',
        'orden_planilla_id',
        'etiqueta_id',
        'etiqueta_sub_id',
        'maquina_id',
        'maquina_id_2',
        'maquina_id_3',
        'producto_id',
        'producto_id_2',
        'producto_id_3',
        'paquete_id',
        'figura',
        'descripcion_fila',
        'fila',
        'marca',
        'etiqueta',
        'diametro',
        'longitud',
        'barras',
        'dobles_barra',
        'peso',
        'dimensiones',
        'tiempo_fabricacion',
        'estado',
        'fecha_entrega'
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
    ];

    protected $appends = ['longitud_cm', 'longitud_m', 'peso_kg', 'diametro_mm'];

    public static function generarCodigo(): string
    {
        return DB::transaction(function () {
            $prefijo = 'EL' . now()->format('ym'); // EL2512

            // Obtener el número mayor ya usado después del prefijo
            $ultimo = self::where('codigo', 'like', "$prefijo%")
                ->lockForUpdate()
                ->orderByDesc(DB::raw("CAST(SUBSTRING(codigo, LENGTH('$prefijo') + 1) AS UNSIGNED)"))
                ->value('codigo');

            $siguiente = 1;

            if ($ultimo) {
                $numero = (int)substr($ultimo, strlen($prefijo));
                $siguiente = $numero + 1;
            }

            return $prefijo . $siguiente; // Sin límite de dígitos
        });
    }

    /**
     * Indica si el modelo debe gestionar las marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Obtiene el conjunto al que pertenece el elemento.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    // Relación con el modelo Planilla
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Relación con la entidad de ensamblaje (pilar, viga, etc.)
     */
    public function entidad()
    {
        return $this->belongsTo(PlanillaEntidad::class, 'planilla_entidad_id');
    }

    /**
     * Relación con la etiqueta de ensamblaje específica (unidad)
     */
    public function etiquetaEnsamblaje()
    {
        return $this->belongsTo(EtiquetaEnsamblaje::class, 'etiqueta_ensamblaje_id');
    }



    public function etiquetaRelacion()
    {
        return $this->belongsTo(Etiqueta::class, 'etiqueta_sub_id', 'etiqueta_sub_id');
    }
    // En Etiqueta.php
    public function subetiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'etiqueta_sub_id', 'etiqueta_sub_id');
    }

    // Acceso directo a subetiqueta virtual
    public function getSubetiquetaAttribute()
    {
        return $this->etiqueta_sub_id;
    }
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }


    // Relación con el modelo Maquina (si existe)
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
    // Relación con el modelo Maquina (si existe)
    public function maquina_2()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id_2');
    }
    public function maquina_3()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id_3');
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
    public function producto3()
    {
        return $this->belongsTo(Producto::class, 'producto_id_3');
    }


    // Longitudes
    public function getLongitudCmAttribute()
    {
        return $this->longitud ? number_format($this->longitud, 2) . ' cm' : 'No asignado';
    }
    public function getLongitudMAttribute()
    {
        return $this->longitud ? number_format($this->longitud / 100, 2) . ' m' : 'No asignado';
    }
    // Peso
    public function getPesoKgAttribute()
    {
        return $this->peso ? number_format($this->peso, 2) . ' kg' : 'No asignado';
    }

    // Diámetro (mm)
    public function getDiametroMmAttribute()
    {
        return $this->diametro ? number_format($this->diametro, 2) . ' mm' : 'No asignado';
    }

    // Tiempos
    public function getTiempoFabricacionFormatoAttribute()
    {
        if (!$this->tiempo_fabricacion || $this->tiempo_fabricacion <= 0) {
            return 'No asignado';
        }

        $horas = floor($this->tiempo_fabricacion / 3600);
        $minutos = floor(($this->tiempo_fabricacion % 3600) / 60);
        $segundos = $this->tiempo_fabricacion % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }
}
