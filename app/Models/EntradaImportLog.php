<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaImportLog extends Model
{
    use HasFactory;

    protected $table = 'entrada_import_logs';

    protected $fillable = [
        'user_id',
        'entrada_id',
        'file_path',
        'raw_text',
        'parsed_payload',
        'applied_payload',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'parsed_payload'  => 'array',
        'applied_payload' => 'array',
        'reviewed_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entrada()
    {
        return $this->belongsTo(Entrada::class);
    }
}
