<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Etiqueta;
use Illuminate\Support\Facades\DB;

class PaqueteController extends Controller
{
    /**
     * Crear un nuevo paquete y asociar etiquetas existentes.
     */
    public function store(Request $request)
    {
        $request->validate([
            'etiquetas' => 'required|array|min:1', // Debe recibir un array con al menos una etiqueta
            'etiquetas.*' => 'string|distinct' // Las etiquetas deben ser únicas dentro del array
        ]);

        try {
            DB::beginTransaction(); // Iniciar transacción

            // Crear el paquete sin código, solo con timestamps y ubicación_id opcional
            $paquete = Paquete::create([
                'ubicacion_id' => $request->ubicacion_id ?? null, // Si tiene una ubicación, se asigna
            ]);

            // Buscar etiquetas existentes y asignarlas al paquete
            $etiquetasActualizadas = Etiqueta::whereIn('nombre', $request->etiquetas)->update(['paquete_id' => $paquete->id]);

            if ($etiquetasActualizadas === 0) {
                throw new \Exception("Ninguna etiqueta encontrada. Verifique que las etiquetas existen en la base de datos.");
            }

            DB::commit(); // Confirmar transacción

            return response()->json([
                'success' => true,
                'message' => 'Paquete creado y etiquetas asociadas correctamente.',
                'paquete_id' => $paquete->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir transacción en caso de error
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
