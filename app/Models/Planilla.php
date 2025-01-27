<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'planillas';

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

    ];

    /**
     * Indica si el modelo debe gestionar las marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Obtiene los conjuntos asociados a la planilla.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'planilla_id');
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
}
