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
            'etiquetas.*' => 'integer|exists:etiquetas,id|distinct' // Asegurar que los IDs existen y son únicos
        ]);

        try {
            DB::beginTransaction(); // Iniciar transacción

            // Verificar si alguna etiqueta ya tiene un paquete asignado
            $etiquetasOcupadas = Etiqueta::whereIn('id', $request->etiquetas)
                ->whereNotNull('paquete_id') // Filtrar etiquetas que YA tienen un paquete
                ->get();

            if ($etiquetasOcupadas->isNotEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Al menos una de las etiquetas ya está asignada a un paquete. Verifique los IDs.',
                    'etiquetas_ocupadas' => $etiquetasOcupadas->pluck('id')->toArray() // Asegurar que devuelva un array real
                ], 400);
            }


            // Crear el paquete sin código, solo con timestamps y ubicación_id opcional
            $paquete = Paquete::create([
                'ubicacion_id' => $request->ubicacion_id ?? null, // Si tiene una ubicación, se asigna
            ]);

            // Asignar las etiquetas al nuevo paquete
            Etiqueta::whereIn('id', $request->etiquetas)->update(['paquete_id' => $paquete->id]);

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
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Buscar el paquete
            $paquete = Paquete::findOrFail($id);

            // Desasociar las etiquetas (poner `paquete_id` en NULL en lugar de eliminarlas)
            Etiqueta::where('paquete_id', $paquete->id)->update(['paquete_id' => null]);

            // Eliminar el paquete
            $paquete->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete eliminado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el paquete: ' . $e->getMessage()
            ], 500);
        }
    }
}
