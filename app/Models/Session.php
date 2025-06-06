<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';
    public $timestamps = false; // Laravel no gestiona created_at/updated_at aquí
    protected $primaryKey = 'id';
    public $incrementing = false; // El ID es un string (el session ID)
}
