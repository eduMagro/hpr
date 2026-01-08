<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallaTrabajador extends Model
{
    use HasFactory;

    protected $table = 'tallas_trabajadores';

    protected $fillable = [
        'user_id',
        'talla_guante',
        'talla_zapato',
        'talla_pantalon',
        'talla_chaqueta',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
