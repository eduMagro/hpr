<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Elemento;
use Illuminate\Support\Facades\DB;

class PaqueteController extends Controller
{
    public function index(Request $request)
    {
        $query = Paquete::with([
            'planilla',
            'etiquetas',
            'ubicacion'
        ])
            ->orderBy('created_at', 'desc'); // Ordenar por fecha de creación descendente

        // Aplicar filtro por ID si se proporciona
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }

        $paquetes = $query->paginate(10);

        return view('paquetes.index', compact('paquetes'));
    }
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
                ->whereNotNull('paquete_id')
                ->pluck('id');

            if ($etiquetasOcupadas->isNotEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Al menos una de las etiquetas ya está asignada a un paquete. Verifique los IDs.',
                    'etiquetas_ocupadas' => $etiquetasOcupadas->toArray()
                ], 400);
            }

            $etiquetas = Etiqueta::whereIn('id', $request->etiquetas)
                ->where('estado', 'completado')
                ->get();

            $etiquetas_incompletas = Etiqueta::whereIn('id', $request->etiquetas)
                ->where('estado', '!=', 'completado') // Filtrar los elementos que no están completados
                ->select('id', 'id', 'estado') // Seleccionar solo los campos relevantes
                ->get();

            if ($etiquetas->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron elementos completados para estas etiquetas.',
                    'elementos_incompletos' => $etiquetas_incompletas->toArray()
                ], 400);
            }


            // Crear el paquete con la ubicación encontrada
            $paquete = Paquete::create([
                'ubicacion_id' => 1,
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
