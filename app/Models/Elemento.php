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

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
<<<<<<< HEAD
    protected $fillable = [
        'conjunto_id',
        'nombre',
        'cantidad',
        'diametro',
        'longitud',
        'peso',
=======
     // Campos que se pueden asignar masivamente
    protected $fillable = [
        'planilla_id',
		'users_id',
        'nombre',
        'maquina_id',
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
>>>>>>> 6fea693 (primercommit)
    ];

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
<<<<<<< HEAD
    public function conjunto()
    {
        return $this->belongsTo(Conjunto::class, 'conjunto_id', 'id');
    }

    /**
     * Definir cualquier casting de atributos si es necesario.
     *
     * @var array
     */
    protected $casts = [
        'cantidad' => 'integer',
        'diametro' => 'decimal:2',
        'longitud' => 'decimal:2',
        'peso' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
=======
    // Relación con el modelo Planilla
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    // Relación con el modelo Maquina (si existe)
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
	    /**
     * Relación con la tabla 'users'
     * Una planilla pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');  // Relación con User
    }
>>>>>>> 6fea693 (primercommit)
}
