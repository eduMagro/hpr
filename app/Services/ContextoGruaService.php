<?php
// app/Services/ContextoGruaService.php
namespace App\Services;

use App\Models\Maquina;

class ContextoGruaService
{
    public function maquinasDisponiblesPorObraId(int $obraId)
    {
        return Maquina::select('id', 'nombre', 'codigo', 'diametro_min', 'diametro_max', 'obra_id')
            ->where('obra_id', $obraId)
            ->where('tipo', '!=', 'grua')
            ->orderBy('nombre')
            ->get();
    }
}
