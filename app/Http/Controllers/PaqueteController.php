<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Elemento;
use App\Models\Maquina;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PaqueteController extends Controller
{
    public function index(Request $request)
    {
        $query = Paquete::with([
            'planilla',
            'ubicacion',
            'elementos' // Cargamos la relación sin modificar la consulta
        ])->orderBy('created_at', 'desc'); // Ordenar paquetes por fecha de creación descendente

        // Aplicar filtro por ID si se proporciona
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }
        if ($request->filled('planilla')) {
            $planillaInput = $request->input('planilla');

            $query->where(function ($q) use ($planillaInput) {
                $q->where('planilla_id', $planillaInput) // Buscar por ID en la tabla paquetes
                    ->orWhereHas('planilla', function ($subQuery) use ($planillaInput) {
                        $subQuery->where('codigo', 'like', '%' . $planillaInput . '%'); // Buscar por código en planillas
                    });
            });
        }


        $paquetes = $query->paginate(10)->appends($request->query());

        // Ordenar manualmente los elementos dentro de cada paquete después de la consulta
        foreach ($paquetes as $paquete) {
            $paquete->elementos = $paquete->elementos->sortBy('id')->values();
        }

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
            'items.*.type' => 'required|in:etiqueta,elemento', // Tipos permitidos
            'maquina_id' => 'required|integer|exists:maquinas,id'
        ]);

        try {
            DB::beginTransaction();

            // Separar los elementos por tipo
            $etiquetasIds = collect($request->items)->where('type', 'etiqueta')->pluck('id')->toArray();
            $elementosIds = collect($request->items)->where('type', 'elemento')->pluck('id')->toArray();


            $maquinaId = $request->input('maquina_id');
            $maquina = Maquina::where('id', $maquinaId)->first();
            $nombreMaquina = $maquina->nombre; // ✅ Obtener el nombre de la máquina
            $codigoMaquina = $maquina->codigo; // ✅ Obtener el nombre de la máquina

            // Obtener los elementos y subpaquetes asociados
            $etiquetas = Etiqueta::whereIn('id', $etiquetasIds)->with(['elementos', 'planilla'])->get();
            $elementos = Elemento::whereIn('id', $elementosIds)->get();


            // Incluir elementos de etiquetas en la lista de elementos a empaquetar
            // $elementosIdsDesdeEtiquetas = $etiquetas->flatMap(function ($etiqueta) use ($maquinaId) {
            //     return $etiqueta->elementos->filter(fn($elemento) => $elemento->maquina_id == $maquinaId)
            //         ->pluck('id')
            //         ->toArray();
            // })->toArray();
            // Obtener elementos que pertenecen a la máquina seleccionada
            $elementosIdsDesdeEtiquetas = $etiquetas->flatMap(function ($etiqueta) use ($maquinaId) {
                return $etiqueta->elementos->where('maquina_id', $maquinaId)->pluck('id');
            })->unique()->values()->toArray(); // ✅ Elimina duplicados y reindexa el array

            if ($etiquetas->isEmpty() && $elementos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete..'
                ], 400);
            }
            // **Siempre** añadir los elementos directos a `$elementosIdsDesdeEtiquetas`
            $elementosIdsDesdeEtiquetas = array_unique(array_merge($elementosIdsDesdeEtiquetas, $elementosIds));

            // Verificar disponibilidad de etiquetas, elementos
            if ($mensajeError = $this->verificarDisponibilidad($elementosIdsDesdeEtiquetas)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $mensajeError
                ], 400);
            }

            // Obtener la lista de todos los elementos a procesar
            $todosElementos = Elemento::whereIn('id', $elementosIdsDesdeEtiquetas)->get();

            // Si no hay datos válidos, devolver error
            if ($todosElementos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete.'
                ], 400);
            }

            // Obtener la planilla desde etiquetas, elementos 
            $planilla = $etiquetas->first()->planilla ?? $todosElementos->first()->planilla ?? null;

            // Si no hay planilla, error
            if (!$planilla) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una planilla válida para las etiquetas o los elementos.'
                ], 400);
            }

            $codigo_planilla = $planilla->codigo_limpio;

            // Calcular el peso total recorriendo $todosElementos
            $pesoTotal = $todosElementos->sum('peso');

            // Verificar si el peso total supera el límite
            if ($pesoTotal > 1300) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "El peso total del paquete ($pesoTotal kg) supera el límite permitido de 1200 kg."
                ], 400);
            }


            // Obtener ubicación
            $ubicacion = Ubicacion::where('descripcion', 'LIKE', "%$codigoMaquina%")->first();

            if (!$ubicacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró una ubicación con el nombre de la máquina: $codigoMaquina."
                ], 400);
            }
            // Crear el paquete
            $paquete = $this->crearPaquete($planilla->id, $ubicacion->id, $pesoTotal);

            // Asociar elementos al paquete
            $this->asignarItemsAPaquete($elementosIdsDesdeEtiquetas, $subpaquetesIds, $paquete->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete creado correctamente.',
                'paquete_id' => $paquete->id,
                'codigo_planilla' => $codigo_planilla
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error en el controlador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica si los elementos están disponibles para ser empaquetados.
     */
    private function verificarDisponibilidad($elementos)
    {
        try {
            $elementosOcupados = Elemento::whereIn('id', $elementos)->whereNotNull('paquete_id')->pluck('id');

            if ($elementosOcupados->isNotEmpty()) {
                return [
                    'message' => 'Algunos elementos ya están asignados a otro paquete.',
                    'elementos_ocupados' => $elementosOcupados->toArray()
                ];
            }

            return null; // <- Importante: Devuelve NULL si no hay problemas
        } catch (Exception $e) {
            // No devolvemos response()->json(), solo un array de error
            return [
                'message' => 'Error al verificar disponibilidad: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crea un nuevo paquete.
     */
    private function crearPaquete($planillaId, $ubicacionId, $pesoTotal)
    {
        try {
            return Paquete::create([
                'planilla_id' => $planillaId,
                'ubicacion_id' => $ubicacionId,
                'peso' => $pesoTotal ?? 0, // ✅ Usa el peso correcto pasado como parámetro
            ]);
        } catch (Exception $e) {
            Log::error('Error al crear paquete: ' . $e->getMessage()); // ✅ Guarda el error en logs

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el paquete: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Asigna etiquetas, elementos al paquete.
     */
    private function asignarItemsAPaquete($elementos, $paqueteId)
    {
        try {
            Elemento::whereIn('id', $elementos)->update(['paquete_id' => $paqueteId]);
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
                    'elementos_incompletos' => [],
                    'subpaquetes_incompletos' => []
                ], 400);
            }

            $elementosIds = collect($items)->where('type', 'elemento')->pluck('id')->toArray();

            // // Buscar elementos incompletos
            $elementosIncompletos = Elemento::whereIn('id', $elementosIds)
                ->where('estado', '!=', 'completado')
                ->pluck('id')
                ->toArray();


            return response()->json([
                'success' => empty($elementosIncompletos) && empty($subpaquetesIncompletos),
                'message' => empty($elementosIncompletos) && empty($subpaquetesIncompletos) ?
                    'Todos los ítems están completos.' : 'Algunos ítems no están completos.',
                'elementos_incompletos' => $elementosIncompletos
            ], 200);
        } catch (Exception $e) {
            Log::error('Error en verificarItems(): ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar los ítems.',
                'error' => $e->getMessage(),
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
            Elemento::where('paquete_id', $paquete->id)->update(['paquete_id' => null]);

            // Eliminar el paquete
            $paquete->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Paquete eliminado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al eliminar el paquete: ' . $e->getMessage());
        }
    }
}
