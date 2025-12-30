<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioBackup extends Model
{
    protected $fillable = ['user_id', 'almacen_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
