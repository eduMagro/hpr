<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Planilla extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'planillas';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'fecha_estimada_entrega' => 'datetime',
        'fecha_inicio' => 'datetime',
        'revisada' => 'boolean',
        'revisada_at' => 'datetime',
        'fecha_creacion_ferrawin' => 'datetime',
        'aprobada' => 'boolean',
        'aprobada_at' => 'datetime',
        'automatizacion_salidas_activa' => 'boolean',
    ];


    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'codigo',
        'users_id',
        'cliente_id',
        'obra_id',
        'seccion',
        'descripcion',
        'ensamblado',
        'comentario',
        'peso_total',
        'estado',
        'fecha_inicio',
        'fecha_finalizacion',
        'tiempo_fabricacion',
        'fecha_estimada_entrega',
        'revisada',
        'revisada_por_id',
        'revisada_at',
        'fecha_creacion_ferrawin',
        'aprobada',
        'aprobada_por_id',
        'aprobada_at',
        'automatizacion_salidas_activa',
    ];

    /**
     * Indica si el modelo debe gestionar las marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $appends = ['codigo_limpio', 'peso_total_kg'];
    public function ordenProduccion()
    {
        return $this->hasOne(OrdenPlanilla::class);
    }

    public function getFechaEstimadaEntregaAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return Carbon::parse($value)->format('d/m/Y H:i');
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
        return $this->belongsTo(Obra::class, 'obra_id');
    }
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
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

    /**
     * Relación con las entidades/ensamblajes de la planilla.
     */
    public function entidades()
    {
        return $this->hasMany(PlanillaEntidad::class, 'planilla_id');
    }

    /**
     * Relación con las etiquetas de ensamblaje.
     */
    public function etiquetasEnsamblaje()
    {
        return $this->hasMany(EtiquetaEnsamblaje::class, 'planilla_id');
    }

    /**
     * Determina si la planilla tiene ensamblaje en taller.
     * Se basa en el campo 'ensamblado' y la existencia de entidades con etiquetas.
     */
    public function tieneEnsamblajeTaller(): bool
    {
        // Verificar campo ensamblado (puede contener "TALLER", "ENSAMBLADO TALLER", etc.)
        $ensamblado = strtoupper(trim($this->ensamblado ?? ''));

        if (empty($ensamblado)) {
            return false;
        }

        // Palabras clave que indican ensamblaje en taller
        $palabrasTaller = ['TALLER', 'FABRICA', 'PLANTA'];

        foreach ($palabrasTaller as $palabra) {
            if (str_contains($ensamblado, $palabra)) {
                // Además debe tener entidades con etiquetas de ensamblaje
                return $this->entidades()->exists() && $this->etiquetasEnsamblaje()->exists();
            }
        }

        return false;
    }

    /**
     * Verifica si todas las etiquetas de ensamblaje están completadas.
     */
    public function ensamblajeCompletado(): bool
    {
        if (!$this->tieneEnsamblajeTaller()) {
            return true; // Sin ensamblaje en taller, se considera completado
        }

        $totalEtiquetas = $this->etiquetasEnsamblaje()->count();

        if ($totalEtiquetas === 0) {
            return true;
        }

        $completadas = $this->etiquetasEnsamblaje()
            ->where('estado', 'completada')
            ->count();

        return $completadas >= $totalEtiquetas;
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

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisada_por_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobada_por_id');
    }

    /**
     * Calcula la fecha estimada de entrega basándose en la fecha de aprobación.
     * Si está aprobada, es aprobada_at + 7 días.
     * Si no está aprobada, devuelve null.
     */
    public function getFechaEstimadaEntregaCalculadaAttribute()
    {
        if ($this->aprobada && $this->aprobada_at) {
            return Carbon::parse($this->aprobada_at)->addDays(7);
        }
        return null;
    }

    /**
     * Formatea la fecha de creación en Ferrawin.
     */
    public function getFechaCreacionFerrawinFormateadaAttribute()
    {
        if (!$this->fecha_creacion_ferrawin) {
            return null;
        }
        return Carbon::parse($this->fecha_creacion_ferrawin)->format('d/m/Y');
    }
}
