<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Elemento extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'elementos';
    protected $appends = ['estado_icon']; // Agregamos el atributo calculado
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'planilla_id',
        'users_id',
        'users_id_2',
        'etiqueta_id',
        'nombre',
        'maquina_id',
        'producto_id',
        'producto_id_2',
        'paquete_id',
        'figura',
        'fila',
        'descripcion_fila',
        'marca',
        'etiqueta',
        'barras',
        'dobles_barra',
        'peso',
        'dimensiones',
        'diametro',
        'longitud',
        'fecha_inicio',
        'fecha_finalizacion',
        'tiempo_fabricacion',
        'estado',

    ];
    public function getIdElAttribute()
    {
        return 'EL' . $this->id;
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

    public function subpaquetes()
    {
        return $this->hasMany(Subpaquete::class, 'elemento_id');
    }

    public function etiquetaRelacion()
    {
        return $this->belongsTo(Etiqueta::class, 'etiqueta_id');
    }


    // Relación con el modelo Maquina (si existe)
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
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
    /**
     * Relación con la tabla 'users'
     * Un elemento pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');  // Relación con User
    }
    // Relación con el segundo usuario
    public function user2()
    {
        return $this->belongsTo(User::class, 'users_id_2');
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
    // Estado Icono
    public function getEstadoIconAttribute()
    {
        return match ($this->estado) {
            'completado' => 'Completado ✔', // Check verde ✅
            'fabricando' => 'Fabricando 🕒', // Engranaje amarillo ⚙
            'pendiente' => 'Pendiente 🔜', // X roja ❌ para pendientes o desconocidos
        };
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
    public function getSueltaAttribute($value)
    {
        return $value ? 'Si' : 'No';
    }
}
