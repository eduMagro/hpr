<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Planilla extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'planillas';
    protected $dates = ['created_at', 'updated_at']; // Asegura que las fechas sean tratadas correctamente
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [

        'users_id',
        'cod_obra',
        'cod_cliente',
        'cliente',
        'nom_obra',
        'seccion',
        'descripcion',
        'ensamblado',
        'codigo', // Asegúrate de que este campo esté aquí
        'peso_total',
        'fecha_inicio',
        'fecha_finalizacion',
        'tiempo_fabricacion',
        'fecha_estimada_entrega',

    ];

    /**
     * Indica si el modelo debe gestionar las marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $appends = ['codigo_limpio', 'peso_total_kg'];

    public function getFechaEstimadaEntregaAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
    }
    public function getFechaInicioAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y H:i') : null;
    }

    public function getFechaFinalizacionAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y H:i') : null;
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }
    public function paquetes()
    {
        return $this->hasMany(Paquete::class, 'planilla_id');
    }
    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'planilla_id');
    }


    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'planilla_id');
    }
    public function subpaquetes()
    {
        return $this->hasMany(Subpaquete::class, 'planilla_id');
    }

    /**
     * Relación con la tabla 'users'
     * Una planilla pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');  // Relación con User

    }

    /**
     * Definir cualquier casting de atributos si es necesario.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCodigoLimpioAttribute()
    {
        if (!$this->codigo) {
            return null;
        }

        // Dividir el código en dos partes usando "-"
        $partes = explode('-', $this->codigo);

        // Si hay al menos dos partes, eliminar los ceros de la segunda
        if (count($partes) === 2) {
            return $partes[0] . '-' . ltrim($partes[1], '0');
        }

        // Si el formato no es correcto, devolver el código original
        return $this->codigo;
    }
    // Tiempos
    public function getTiempoEstimadoFinalizacionFormatoAttribute()
    {
        if (!$this->tiempo_fabricacion || $this->tiempo_fabricacion <= 0) {
            return 'No asignado';
        }

        $horas = floor($this->tiempo_fabricacion / 3600);
        $minutos = floor(($this->tiempo_fabricacion % 3600) / 60);
        $segundos = $this->tiempo_fabricacion % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }
    public function getFechaInicioFormatoAttribute()
    {
        if (!$this->fecha_inicio || $this->fecha_inicio <= 0) {
            return 'No asignado';
        }

        $horas = floor($this->fecha_inicio / 3600);
        $minutos = floor(($this->fecha_inicio % 3600) / 60);
        $segundos = $this->fecha_inicio % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }
    public function getFechaFinalizacionFormatoAttribute()
    {
        if (!$this->fecha_finalizacion || $this->fecha_finalizacion <= 0) {
            return 'No asignado';
        }

        $horas = floor($this->fecha_finalizacion / 3600);
        $minutos = floor(($this->fecha_finalizacion % 3600) / 60);
        $segundos = $this->fecha_finalizacion % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }

    public function getPesoTotalKgAttribute()
    {
        if (!$this->peso_total) {
            return 'No asignado';
        }

        return number_format($this->peso_total, 2, ',', '.') . ' kg';
    }
    public function getFechaEstimadaRepartoAttribute()
    {
        if (!$this->created_at) {
            return null;
        }

        // Asegurar que created_at es un objeto Carbon y sumarle 6 días
        return Carbon::parse($this->created_at)->addDays(6);
    }
    public function salidas()
    {
        return $this->belongsToMany(Salida::class, 'planilla_salida');
    }
}
