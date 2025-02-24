<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


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
        'email',
        'password',
        'rol',
        'categoria',
        'turno',
        'dias_vacaciones',
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
    
    // Relación: Un usuario tiene muchas entradas
    public function entradas()
    {
        return $this->hasMany(Entrada::class, 'users_id'); // 'users_id' es la clave foránea en la tabla `entradas`
    }

    // Relación: Un usuario tiene muchos movimientos
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'users_id'); // 'user_id' es la clave foránea en la tabla `movimientos`
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
        return $this->hasMany(Etiqueta::class, 'soldador1');
    }

    public function etiquetasComoSoldador2()
    {
        return $this->hasMany(Etiqueta::class, 'soldador2');
    }

    public function etiquetasComoEnsamblador1()
    {
        return $this->hasMany(Etiqueta::class, 'ensamblador1');
    }

    public function etiquetasComoEnsamblador2()
    {
        return $this->hasMany(Etiqueta::class, 'ensamblador2');
    }

    /**
     * Relación con `RegistroFichaje` (Un usuario tiene muchos registros de fichajes)
     */
    public function registrosFichajes()
    {
        return $this->hasMany(RegistroFichaje::class, 'user_id');
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
    public function vacaciones()
    {
        return $this->hasMany(Vacaciones::class);
    }
}
