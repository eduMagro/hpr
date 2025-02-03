<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Elemento;
use App\Models\Subpaquete;

use Illuminate\Support\Facades\DB;

class PaqueteController extends Controller
{
    public function index(Request $request)
    {
        $query = Paquete::with([
            'planilla',
            'etiquetas.elementos',
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
            'items' => 'required|array|min:1', // Debe recibir un array con al menos un item
            'items.*.id' => 'integer|required', // Asegurar que los IDs existen
            'items.*.type' => 'required|in:etiqueta,elemento,subpaquete' // Tipos permitidos
        ]);

        try {
            DB::beginTransaction();

            // Separar los elementos por tipo
            $etiquetasIds = collect($request->items)->where('type', 'etiqueta')->pluck('id')->toArray();
            $elementosIds = collect($request->items)->where('type', 'elemento')->pluck('id')->toArray();
            $subpaquetesIds = collect($request->items)->where('type', 'subpaquete')->pluck('id')->toArray();

            // Verificar disponibilidad de etiquetas, elementos y subpaquetes
         if ($mensajeError = $this->verificarDisponibilidad($etiquetasIds, $elementosIds, $subpaquetesIds)) {
    DB::rollBack();
    return response()->json(array_merge(['success' => false], $mensajeError), 400);
}


            // Obtener los elementos y subpaquetes asociados
            $etiquetas = Etiqueta::whereIn('id', $etiquetasIds)->with(['elementos', 'planilla'])->get();
            $elementos = Elemento::whereIn('id', $elementosIds)->get();
            $subpaquetes = Subpaquete::whereIn('id', $subpaquetesIds)->get();

            if ($etiquetas->isEmpty() && $elementos->isEmpty() && $subpaquetes->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete.'
                ], 400);
            }

           // Obtener la planilla desde las etiquetas (si existen)
$planilla = $etiquetas->first()->planilla ?? null;

// Si no hay planilla y hay elementos, buscar la planilla desde los elementos
if (!$planilla && $elementos->isNotEmpty()) {
    $planilla = $elementos->first()->planilla ?? null;
}

// Si no se encontró una planilla en etiquetas ni en elementos, error
if (!$planilla) {
    DB::rollBack();
    return response()->json([
        'success' => false,
        'message' => 'No se encontró una planilla válida para las etiquetas o los elementos.'
    ], 400);
}
            $codigo_planilla = $planilla->codigo_limpio;

            // Verificar código de máquina y ubicación
            $codigoMaquina = $elementos->pluck('codigo_maquina')->unique()->first();
            $ubicacion = $this->obtenerUbicacionPorCodigoMaquina($codigoMaquina);

            if (!$ubicacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró una ubicación para la máquina con código $codigoMaquina"
                ], 400);
            }

            // Crear el paquete
            $paquete = $this->crearPaquete($planilla->id, $ubicacion->id);

            // Asociar elementos al paquete
            $this->asignarItemsAPaquete($etiquetasIds, $elementosIds, $subpaquetesIds, $paquete->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete creado correctamente.',
                'paquete_id' => $paquete->id,
                'codigo_planilla' => $codigo_planilla
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica si los elementos están disponibles para ser empaquetados.
     */
    private function verificarDisponibilidad($etiquetas, $elementos, $subpaquetes)
	{
		try{
        $ocupados = Etiqueta::whereIn('id', $etiquetas)->whereNotNull('paquete_id')->pluck('id');
        $elementosOcupados = Elemento::whereIn('id', $elementos)->whereNotNull('paquete_id')->pluck('id');
        $subpaquetesOcupados = Subpaquete::whereIn('id', $subpaquetes)->whereNotNull('paquete_id')->pluck('id');

        if ($ocupados->isNotEmpty() || $elementosOcupados->isNotEmpty() || $subpaquetesOcupados->isNotEmpty()) {
            return [
                'message' => 'Algunos elementos ya están asignados a otro paquete.',
                'etiquetas_ocupadas' => $ocupados->toArray(),
                'elementos_ocupados' => $elementosOcupados->toArray(),
                'subpaquetes_ocupados' => $subpaquetesOcupados->toArray()
            ];
        }

         return null; // <- Importante: Devuelve NULL si no hay problemas
    } catch (\Exception $e) {
        // No devolvemos response()->json(), solo un array de error
        return [
            'message' => 'Error al verificar disponibilidad: ' . $e->getMessage()
        ];
    }
    }

    /**
     * Obtiene la ubicación según el código de máquina.
     */
    private function obtenerUbicacionPorCodigoMaquina($codigoMaquina)
    {
		try{
        return Ubicacion::where('descripcion', 'LIKE', "%$codigoMaquina%")->first();
		 } catch (\Exception $e) {
               return response()->json([
            'success' => false,
            'message' => 'Error al obtener ubicación de la máquina: ' . $e->getMessage()
        ], 500);
    }
    }

    /**
     * Crea un nuevo paquete.
     */
    private function crearPaquete($planillaId, $ubicacionId)
    {
		 try {
        return Paquete::create([
            'planilla_id' => $planillaId,
            'ubicacion_id' => $ubicacionId,
        ]);
		   

    } catch (\Exception $e) {
               return response()->json([
            'success' => false,
            'message' => 'Error al crear paquete: ' . $e->getMessage()
        ], 500);
    }
    }

    /**
     * Asigna etiquetas, elementos y subpaquetes al paquete.
     */
    private function asignarItemsAPaquete($etiquetas, $elementos, $subpaquetes, $paqueteId)
    {
		try{
        Etiqueta::whereIn('id', $etiquetas)->update(['paquete_id' => $paqueteId]);
        Elemento::whereIn('id', $elementos)->update(['paquete_id' => $paqueteId]);
        Subpaquete::whereIn('id', $subpaquetes)->update(['paquete_id' => $paqueteId]);
		 } catch (\Exception $e) {
               return response()->json([
            'success' => false,
            'message' => 'Error al asignar items a paquete: ' . $e->getMessage()
        ], 500);
    }
    }

public function verificarItems(Request $request)
{
    try {
        $items = $request->input('items', []);

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron ítems para verificar.',
                'etiquetas_incompletas' => [],
                'elementos_incompletos' => [],
                'subpaquetes_incompletos' => []
            ], 400);
        }

        // Separar por tipo
        $etiquetasIds = collect($items)->where('type', 'etiqueta')->pluck('id')->toArray();
        $elementosIds = collect($items)->where('type', 'elemento')->pluck('id')->toArray();
        $subpaquetesIds = collect($items)->where('type', 'subpaquete')->pluck('id')->toArray();

        // Buscar etiquetas incompletas
        $etiquetasIncompletas = Etiqueta::whereIn('id', $etiquetasIds)
            ->where('estado', '!=', 'completado')
            ->pluck('id')
            ->toArray();

        // Buscar elementos incompletos
        $elementosIncompletos = Elemento::whereIn('id', $elementosIds)
            ->where('estado', '!=', 'completado')
            ->pluck('id')
            ->toArray();

     $subpaquetesIncompletos = []; // No es necesario filtrar, si existe, está completo.


        return response()->json([
            'success' => empty($etiquetasIncompletas) && empty($elementosIncompletos) && empty($subpaquetesIncompletos),
            'message' => empty($etiquetasIncompletas) && empty($elementosIncompletos) && empty($subpaquetesIncompletos) ? 
                'Todos los ítems están completos.' : 'Algunos ítems no están completos.',
            'etiquetas_incompletas' => $etiquetasIncompletas,
            'elementos_incompletos' => $elementosIncompletos,
            'subpaquetes_incompletos' => $subpaquetesIncompletos
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Error en verificarItems(): ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al verificar los ítems.',
            'error' => $e->getMessage(),
            'etiquetas_incompletas' => [],
            'elementos_incompletos' => [],
            'subpaquetes_incompletos' => []
        ], 500);
    }
}


    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $paquete = Paquete::findOrFail($id);

            // Desasociar las etiquetas (poner paquete_id en NULL)
            Etiqueta::where('paquete_id', $paquete->id)->update(['paquete_id' => null]);

            // Eliminar el paquete
            $paquete->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Paquete eliminado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al eliminar el paquete: ' . $e->getMessage());
        }
    }
}
