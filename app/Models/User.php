<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    protected $fillable = [
        'name',
        'email',
        'password',
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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
}
