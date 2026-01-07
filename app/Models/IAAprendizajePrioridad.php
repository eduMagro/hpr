<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IAAprendizajePrioridad extends Model
{
    use HasFactory;

    protected $table = 'ia_aprendizaje_prioridad';

    protected $fillable = [
        'entrada_import_log_id',
        'payload_ocr',
        'recomendaciones_ia',
        'pedido_seleccionado_id',
        'es_discrepancia',
        'motivo_usuario',
        'contexto_sistema',
    ];

    protected $casts = [
        'payload_ocr' => 'array',
        'recomendaciones_ia' => 'array',
        'contexto_sistema' => 'array',
        'es_discrepancia' => 'boolean',
    ];

    public function logImportacion()
    {
        return $this->belongsTo(EntradaImportLog::class, 'entrada_import_log_id');
    }

    public function pedidoSeleccionado()
    {
        return $this->belongsTo(Pedido::class, 'pedido_seleccionado_id');
    }
}
