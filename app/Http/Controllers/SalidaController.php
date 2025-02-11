<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;

class SalidaController extends Controller
{
    public function index()
    {
        $planillas = Planilla::with([
            'paquetes:id,planilla_id,peso,ubicacion_id',
            'paquetes.ubicacion:id,nombre', // Cargar la ubicación de cada paquete
            'etiquetas:id,planilla_id,estado,peso,paquete_id',
            'elementos:id,planilla_id,estado,peso,ubicacion_id,etiqueta_id,paquete_id,maquina_id',
            'elementos.ubicacion:id,nombre', // Cargar la ubicación de cada elemento
            'elementos.maquina:id,nombre' // Cargar la máquina de cada elemento
        ])->get();

        // Función para asignar color de fondo según estado
        $getColor = function ($estado, $tipo) {
            $estado = strtolower($estado ?? 'desconocido');

            if ($tipo === 'etiqueta' && $estado === 'completada') {
                $estado = 'completado';
            }

            return match ($estado) {
                'completado' => 'bg-green-200',
                'pendiente' => 'bg-red-200',
                'fabricando' => 'bg-blue-200',
                default => 'bg-gray-200'
            };
        };

        // Procesar cada planilla
        $planillasCalculadas = $planillas->map(function ($planilla) use ($getColor) {
            $pesoAcumulado = $planilla->elementos->where('estado', 'completado')->sum('peso');
            $pesoTotal = max(1, $planilla->peso_total ?? 1);

            $progreso = min(100, ($pesoAcumulado / $pesoTotal) * 100);

            $paquetes = $planilla->paquetes->map(fn($paquete) => tap($paquete, fn($p) => $p->color = $getColor($p->estado, 'paquete')));

            $etiquetas = $planilla->etiquetas->map(function ($etiqueta) use ($getColor, $planilla) {
                $etiqueta->color = $getColor($etiqueta->estado, 'etiqueta');
                $etiqueta->elementos = $planilla->elementos->where('etiqueta_id', $etiqueta->id)
                    ->map(fn($elemento) => tap($elemento, fn($e) => $e->color = $getColor($e->estado, 'elemento')));
                return $etiqueta;
            });

            return [
                'planilla' => $planilla,
                'pesoAcumulado' => $pesoAcumulado,
                'pesoRestante' => max(0, $pesoTotal - $pesoAcumulado),
                'progreso' => round($progreso, 2),
                'paquetes' => $paquetes,
                'etiquetas' => $etiquetas,
                'elementos' => $elementos,
                'subpaquetes' => $subpaquetes,
                'etiquetasSinPaquete' => $etiquetas->whereNull('paquete_id')
            ];
        });

        return view('salidas.index', compact('planillasCalculadas'));
    }
}
