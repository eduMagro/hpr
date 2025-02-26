<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    use HasFactory;

    protected $table = 'alertas'; // Nombre de la tabla

    protected $fillable = [
        'user_id_1', 
        'user_id_2', 
        'destino', 
        'destinatario', 
        'mensaje', 
        'leida', 
        'created_at', 
        'updated_at'
    ];

    protected $casts = [
        'leida' => 'boolean', // Para que Laravel lo maneje como booleano
    ];
    public function usuariosQueLeen()
    {
        return $this->belongsToMany(User::class, 'alertas_users')
                    ->withPivot('leida_en')
                    ->withTimestamps();
    }
    
    /**
     * Relación con el usuario que genera la alerta.
     */
    public function usuario1()
    {
        return $this->belongsTo(User::class, 'user_id_1');
    }

    /**
     * Relación con el usuario que recibe la alerta.
     */
    public function usuario2()
    {
        return $this->belongsTo(User::class, 'user_id_2');
    }

    /**
     * Scope para obtener solo las alertas no leídas.
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }
}
