<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\AsignacionTurno;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     * 
     * 
     */
    public $timestamps = true;

    protected $table = 'users';


    protected $fillable = [
        'name',
        'primer_apellido',
        'segundo_apellido',
        'email',
        'imagen',
        'movil_personal',
        'movil_empresa',
        'empresa_id',
        'password',
        'dni',
        'rol',
        'categoria_id',
        'maquina_id',
        'turno',
        'turno_actual',
        'dias_vacaciones',
        'estado',
        'updated_by',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $appends = ['nombre_completo'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function getRutaImagenAttribute()
    {
        return $this->imagen
            ? route('perfil.imagen', $this->imagen)
            : null;
    }


    protected static function booted()
    {
        static::addGlobalScope('soloActivos', function ($query) {
            $query->where('estado', 'activo');
        });
    }
    public function alertasLeidas()
    {
        return $this->belongsToMany(Alerta::class, 'alertas_users')
            ->withPivot('leida_en')
            ->withTimestamps();
    }
    public function getNombreCompletoAttribute()
    {
        return trim("{$this->name} {$this->primer_apellido} {$this->segundo_apellido}");
    }


    // Relación: Un usuario tiene muchas entradas
    public function entradas()
    {
        return $this->hasMany(Entrada::class, 'usuario_id'); // 'users_id' es la clave foránea en la tabla `entradas`
    }
    public function salidas()
    {
        return $this->hasMany(Salida::class, 'user_id');
    }
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }


    // Relación: Un usuario tiene muchos movimientos
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'ejecutado_por'); // 'user_id' es la clave foránea en la tabla `movimientos`
    }

    // Relación con los elementos en los que este usuario es el usuario principal
    public function elementos1()
    {
        return $this->hasMany(Elemento::class, 'users_id');
    }

    // Relación con los elementos en los que este usuario es el usuario secundario
    public function elementos2()
    {
        return $this->hasMany(Elemento::class, 'users_id_2');
    }

    public function isOnline()
    {
        $resultado = DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>=', now()->subMinutes(1)->timestamp)
            ->exists();
        return $resultado;
    }

    public function etiquetasComoSoldador1()
    {
        return $this->hasMany(Etiqueta::class, 'soldador1_id');
    }

    public function etiquetasComoSoldador2()
    {
        return $this->hasMany(Etiqueta::class, 'soldador2_id');
    }

    public function etiquetasComoEnsamblador1()
    {
        return $this->hasMany(Etiqueta::class, 'ensamblador1_id');
    }

    public function etiquetasComoEnsamblador2()
    {
        return $this->hasMany(Etiqueta::class, 'ensamblador2_id');
    }


    /**
     * Relación con `AsignacionTurno` (Un usuario puede tener muchas asignaciones de turnos)
     */
    public function asignacionesTurnos()
    {
        return $this->hasMany(AsignacionTurno::class, 'user_id');
    }

    /**
     * Obtener el turno actual del usuario basado en la asignación más reciente.
     */
    public function turnoActual()
    {
        return $this->hasOne(AsignacionTurno::class, 'user_id')->latest('fecha')->with('turno');
    }
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
    public function convenio()
    {
        return $this->belongsTo(Convenio::class, 'categoria_id', 'categoria_id');
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
    public function modelo145()
    {
        return $this->hasOne(Modelo145::class);
    }
    public function esOperario()
    {
        return $this->rol === 'operario';
    }

    public function esOficina()
    {
        return $this->rol === 'oficina' || $this->rol === 'admin';
    }

    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class)
            ->withPivot('rol_departamental')
            ->withTimestamps();
    }

    public function esAdminDepartamento(): bool
    {
        return $this->departamentos()
            ->where(function ($q) {
                // Ejemplo 1 - por slug del departamento
                $q->where('nombre', 'administrador');

                // Ejemplo 2 - por nombre
                // $q->orWhere('nombre', 'Administrador');

                // Ejemplo 3 - por rol en el pivot
                // $q->orWherePivot('rol_departamental', 'administrador');
            })
            ->exists();
    }
    public function esProduccionDepartamento(): bool
    {
        return $this->departamentos()
            ->where(function ($q) {
                // Ejemplo 1 - por slug del departamento
                $q->where('nombre', 'produccion');

                // Ejemplo 2 - por nombre
                // $q->orWhere('nombre', 'Administrador');

                // Ejemplo 3 - por rol en el pivot
                // $q->orWherePivot('rol_departamental', 'administrador');
            })
            ->exists();
    }
    public function permisosAcceso()
    {
        return $this->hasMany(PermisoAcceso::class);
    }

    public function lugarActualTrabajador(): ?int
    {
        $ahora = Carbon::now();
        $hora = (int) $ahora->format('H');

        // Turno lógico y fecha de referencia
        if ($hora >= 22 || $hora < 6) {
            $turnoSlug = 'noche';
            $fechaRef = ($hora < 6) ? $ahora->copy()->subDay()->toDateString() : $ahora->toDateString();
        } elseif ($hora < 14) {
            $turnoSlug = 'manana';
            $fechaRef = $ahora->toDateString();
        } else {
            $turnoSlug = 'tarde';
            $fechaRef = $ahora->toDateString();
        }

        // Mapear nombres de turnos -> id (cache estática en memoria PHP)
        static $turnoMap = null;
        if ($turnoMap === null) {
            // crea un mapa: 'manana' => id, 'tarde' => id, 'noche' => id
            $turnoMap = Turno::pluck('id', 'nombre')
                ->mapWithKeys(fn($id, $nombre) => [Str::slug($nombre, '') => $id])
                ->all();
        }
        $turnoId = $turnoMap[$turnoSlug] ?? null;

        // Búsqueda exacta del día y turno (sin JOINs ni slug)
        $asig = AsignacionTurno::query()
            ->where('user_id', $this->id)
            ->whereDate('fecha', $fechaRef)
            ->when($turnoId, fn($q) => $q->where('turno_id', $turnoId))
            ->whereNotNull('obra_id')
            ->latest('id')
            ->first();

        if ($asig?->obra_id) {
            return (int) $asig->obra_id;
        }

        // Fallback: última asignación reciente con obra en los últimos 2 días
        $asigReciente = AsignacionTurno::query()
            ->where('user_id', $this->id)
            ->whereNotNull('obra_id')
            ->whereDate('fecha', '>=', Carbon::now()->subDays(2)->toDateString())
            ->orderByDesc('fecha')
            ->latest('id')
            ->first();

        return $asigReciente?->obra_id ?: null;
    }
}
