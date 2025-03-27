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
    /**
     * Muestra la lista de paquetes.
     *
     * - Incluye relaciones: planilla, ubicación y elementos.
     * - Permite filtrar por id y por planilla.
     * - Ordena los paquetes por fecha de creación descendente.
     */
    public function index(Request $request)
    {
        $query = Paquete::with(['planilla', 'ubicacion', 'elementos'])
            ->orderBy('created_at', 'desc');

        // Filtro por ID
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }
        // Filtro por planilla (ID o código)
        if ($request->filled('planilla')) {
            $planillaInput = $request->input('planilla');
            $query->where(function ($q) use ($planillaInput) {
                $q->where('planilla_id', $planillaInput)
                    ->orWhereHas('planilla', function ($subQuery) use ($planillaInput) {
                        $subQuery->where('codigo', 'like', '%' . $planillaInput . '%');
                    });
            });
        }

        // Obtener paquetes con paginación
        $paquetes = $query->paginate(10)->appends($request->query());

        // Ordenar manualmente los elementos de cada paquete
        foreach ($paquetes as $paquete) {
            $paquete->elementos = $paquete->elementos->sortBy('id')->values();
        }

        return view('paquetes.index', compact('paquetes'));
    }

    /**
     * Crea un nuevo paquete y asocia las etiquetas y elementos.
     *
     * - Valida la entrada.
     * - Separa los items por tipo (etiqueta o elemento).
     * - Verifica la disponibilidad de los elementos.
     * - Calcula el peso total y valida que no supere el límite permitido.
     * - Obtiene la ubicación asociada a la máquina.
     * - Crea el paquete y asigna los elementos.
     * - Retorna la respuesta en JSON.
     */
    public function store(Request $request)
    {
        // Validación de entrada
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'integer|required',
            'items.*.type' => 'required|in:etiqueta,elemento',
            'maquina_id' => 'required|integer|exists:maquinas,id'
        ]);

        try {
            DB::beginTransaction();

            // Separar items según su tipo
            $etiquetasIds = collect($request->items)
                ->where('type', 'etiqueta')
                ->pluck('id')
                ->toArray();
            $elementosIds = collect($request->items)
                ->where('type', 'elemento')
                ->pluck('id')
                ->toArray();

            // Obtener la máquina seleccionada y sus datos
            $maquinaId = $request->input('maquina_id');
            $maquina = Maquina::findOrFail($maquinaId);
            $codigoMaquina = $maquina->codigo;

            // Obtener las etiquetas y elementos de la base de datos
            $etiquetas = Etiqueta::whereIn('id', $etiquetasIds)
                ->with(['elementos', 'planilla'])
                ->get();
            $elementos = Elemento::whereIn('id', $elementosIds)->get();

            // Obtener los IDs de los elementos asociados a las etiquetas de la máquina seleccionada
            $elementosIdsDesdeEtiquetas = $etiquetas->flatMap(function ($etiqueta) use ($maquinaId) {
                return $etiqueta->elementos
                    ->where('maquina_id', $maquinaId)
                    ->pluck('id');
            })->unique()->values()->toArray();

            // Validar que existan datos válidos para crear el paquete
            if ($etiquetas->isEmpty() && $elementos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete.'
                ], 400);
            }

            // Combinar los IDs directos con los obtenidos de las etiquetas
            $elementosIdsDesdeEtiquetas = array_unique(array_merge($elementosIdsDesdeEtiquetas, $elementosIds));

            // Verificar la disponibilidad de los elementos
            $disponibilidad = $this->verificarDisponibilidad($elementosIdsDesdeEtiquetas);
            if ($disponibilidad !== null && !$request->has('confirmar')) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'warning' => $disponibilidad // contiene 'message' y 'elementos_ocupados'
                ], 400);
            }

            // Obtener todos los elementos a procesar
            $todosElementos = Elemento::whereIn('id', $elementosIdsDesdeEtiquetas)->get();
            if ($todosElementos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete.'
                ], 400);
            }

            // Obtener la planilla a partir de las etiquetas o, en su defecto, de los elementos
            $planilla = $etiquetas->first()->planilla ?? $todosElementos->first()->planilla ?? null;
            if (!$planilla) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una planilla válida para las etiquetas o los elementos.'
                ], 400);
            }
            $codigo_planilla = $planilla->codigo_limpio;

            // Calcular el peso total y validar que no supere el límite permitido
            $pesoTotal = $todosElementos->sum('peso');
            if ($pesoTotal > 1300) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "El peso total del paquete ($pesoTotal kg) supera el límite permitido de 1200 kg."
                ], 400);
            }

            // Obtener la ubicación asociada a la máquina
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

            // Asignar los elementos al paquete
            $this->asignarItemsAPaquete($elementosIdsDesdeEtiquetas, $paquete->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete creado correctamente.',
                'paquete_id' => $paquete->id,
                'codigo_planilla' => $codigo_planilla
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en el controlador store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en el controlador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica que los ítems enviados estén disponibles.
     *
     * - Valida la entrada.
     * - Retorna un mensaje de error completo si algunos elementos ya están asignados.
     */
    public function verificarItems(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'integer|required',
            'items.*.type' => 'required|in:etiqueta,elemento'
        ]);

        $elementosIds = collect($request->items)->pluck('id')->toArray();
        $disponibilidad = $this->verificarDisponibilidad($elementosIds);

        if ($disponibilidad !== null) {
            return response()->json([
                'success' => false,
                'elementos_incompletos' => $disponibilidad['elementos_ocupados'] ?? [],
                'message' => $disponibilidad['message'] ?? 'Verificación de ítems fallida.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Todos los ítems están disponibles.'
        ], 200);
    }

    /**
     * Verifica la disponibilidad de los elementos para ser empaquetados.
     *
     * @param array $elementos
     * @return array|null
     */
    private function verificarDisponibilidad($elementos)
    {
        try {
            $elementosOcupados = Elemento::whereIn('id', $elementos)
                ->whereNotNull('paquete_id')
                ->pluck('id');
            if ($elementosOcupados->isNotEmpty()) {
                return [
                    'message' => 'Algunos elementos ya están asignados a otro paquete. ¿Desea continuar y reasignarlos?',
                    'elementos_ocupados' => $elementosOcupados->toArray()
                ];
            }
            return null;
        } catch (Exception $e) {
            return [
                'message' => 'Error al verificar disponibilidad: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crea un nuevo paquete.
     *
     * @param int $planillaId
     * @param int $ubicacionId
     * @param float $pesoTotal
     * @return Paquete
     * @throws Exception
     */
    private function crearPaquete($planillaId, $ubicacionId, $pesoTotal)
    {
        try {
            return Paquete::create([
                'planilla_id' => $planillaId,
                'ubicacion_id' => $ubicacionId,
                'peso' => $pesoTotal ?? 0,
            ]);
        } catch (Exception $e) {
            Log::error('Error al crear paquete: ' . $e->getMessage());
            throw new Exception('No se pudo crear el paquete: ' . $e->getMessage());
        }
    }

    /**
     * Asigna los elementos al paquete.
     *
     * @param array $elementos
     * @param int $paqueteId
     * @throws Exception
     */
    private function asignarItemsAPaquete($elementos, $paqueteId)
    {
        try {
            Elemento::whereIn('id', $elementos)->update(['paquete_id' => $paqueteId]);
        } catch (Exception $e) {
            throw new Exception('Error al asignar items a paquete: ' . $e->getMessage());
        }
    }

    /**
     * Elimina un paquete y desasocia sus elementos.
     *
     * - Realiza la transacción y devuelve un mensaje completo según el resultado.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $paquete = Paquete::findOrFail($id);
            // Desasociar los elementos del paquete
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
