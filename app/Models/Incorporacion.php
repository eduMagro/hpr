<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Incorporacion extends Model
{
    protected $table = 'incorporaciones';

    protected $fillable = [
        'token',
        'estado',
        'empresa_destino',
        'puesto',
        'nombre_provisional',
        'email_provisional',
        'telefono_provisional',
        'dni',
        'numero_afiliacion_ss',
        'email',
        'telefono',
        'certificado_bancario',
        'datos_completados_at',
        'enlace_enviado_at',
        'recordatorio_enviado_at',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;

    protected $casts = [
        'datos_completados_at' => 'datetime',
        'enlace_enviado_at' => 'datetime',
        'recordatorio_enviado_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Estados posibles
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_DATOS_RECIBIDOS = 'datos_recibidos';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_CANCELADA = 'cancelada';

    // Empresas
    const EMPRESA_HPR = 'hpr_servicios';
    const EMPRESA_HIERROS = 'hierros_paco_reyes';

    // Tipos de documentos post-incorporación
    const DOCUMENTOS_POST = [
        'info_preventiva' => 'Información materia preventiva',
        'art_19' => 'Art. 19 específica del puesto',
        'aptitud_medica' => 'Certificado de aptitud médica',
        'entrega_epis' => 'Documento entrega EPIs',
        'contrato_trabajo' => 'Contrato de trabajo',
        'idc' => 'IDC',
        'huella_sepe' => 'Huella contrato (SEPE)',
        'ta2' => 'TA2 (Alta trabajador)',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = Str::random(64);
            }
        });
    }

    // Relaciones
    public function formaciones()
    {
        return $this->hasMany(IncorporacionFormacion::class);
    }

    public function documentos()
    {
        return $this->hasMany(IncorporacionDocumento::class);
    }

    public function logs()
    {
        return $this->hasMany(IncorporacionLog::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getNombreCompletoAttribute()
    {
        return $this->dni
            ? ($this->nombre_provisional ?: 'Sin nombre') . ' (' . $this->dni . ')'
            : ($this->nombre_provisional ?: 'Sin nombre');
    }

    public function getEmpresaNombreAttribute()
    {
        return $this->empresa_destino === self::EMPRESA_HPR
            ? 'HPR Servicios'
            : 'Hierros Paco Reyes';
    }

    public function getEstadoBadgeAttribute()
    {
        return match($this->estado) {
            self::ESTADO_PENDIENTE => ['color' => 'yellow', 'texto' => 'Pendiente'],
            self::ESTADO_DATOS_RECIBIDOS => ['color' => 'orange', 'texto' => 'Datos recibidos'],
            self::ESTADO_EN_PROCESO => ['color' => 'blue', 'texto' => 'En proceso'],
            self::ESTADO_COMPLETADA => ['color' => 'green', 'texto' => 'Completada'],
            self::ESTADO_CANCELADA => ['color' => 'red', 'texto' => 'Cancelada'],
            default => ['color' => 'gray', 'texto' => 'Desconocido'],
        };
    }

    public function getUrlFormularioAttribute()
    {
        if (!$this->token) {
            return null;
        }
        return route('incorporacion.publica', ['token' => $this->token]);
    }

    // Métodos
    public function estaCompleta()
    {
        return $this->estado === self::ESTADO_COMPLETADA;
    }

    public function tieneDocumentosFormacion()
    {
        return $this->formaciones()->count() > 0;
    }

    public function porcentajeDocumentosPost()
    {
        $total = count(self::DOCUMENTOS_POST);
        $completados = $this->documentos()->where('completado', true)->count();
        return round(($completados / $total) * 100);
    }

    public function documentosFaltantes()
    {
        $subidos = $this->documentos()->pluck('tipo')->toArray();
        return array_diff(array_keys(self::DOCUMENTOS_POST), $subidos);
    }

    public function registrarLog($accion, $descripcion = null, $datosAnteriores = null, $datosNuevos = null)
    {
        return $this->logs()->create([
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
    }
}
