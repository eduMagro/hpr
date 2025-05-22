<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Movimiento;
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
     * - Verifica la integridad de los items y si alguno ya pertenece a un paquete.
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
            'items.*.id' => 'required|string',
            'items.*.type' => 'required|in:etiqueta,elemento',
            'maquina_id' => 'required|integer|exists:maquinas,id'
        ]);

        // Verificar la integridad de los items (completitud y asignación previa)
        $warnings = $this->verificarItems($request->items, $request->input('maquina_id'));
        if ($warnings !== null && !$request->boolean('confirmar')) {
            return response()->json([
                'success' => false,
                'warning' => $warnings
            ], 200);
        }

        try {
            DB::beginTransaction();

            // Separar items según su tipo
            $etiquetasSubIds = collect($request->items)
                ->where('type', 'etiqueta')
                ->pluck('id')
                ->toArray();

            $elementosIds = collect($request->items)
                ->where('type', 'elemento')
                ->pluck('id')
                ->toArray();

            // Obtener los elementos de etiquetas (por sub_id) y elementos directos
            $elementosDesdeEtiquetas = Elemento::whereIn('etiqueta_sub_id', $etiquetasSubIds)->get();
            $elementosDirectos = Elemento::whereIn('id', $elementosIds)->get();

            // Unir todos los elementos
            $todosElementos = $elementosDesdeEtiquetas->merge($elementosDirectos);

            if ($todosElementos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete.'
                ], 400);
            }

            // Obtener la máquina
            $maquinaId = $request->input('maquina_id');
            $maquina = Maquina::findOrFail($maquinaId);
            $codigoMaquina = $maquina->codigo;

            // Obtener la planilla
            $planilla = $todosElementos->first()->planilla ?? null;
            if (!$planilla) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una planilla válida para las etiquetas o los elementos.'
                ], 400);
            }
            $codigo_planilla = $planilla->codigo_limpio;

            // Calcular peso total
            $pesoTotal = $todosElementos->sum('peso');
            if ($pesoTotal > 1300) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "El peso total del paquete ($pesoTotal kg) supera el límite permitido de 1200 kg."
                ], 400);
            }

            // Obtener la ubicación
            if (stripos($maquina->nombre, 'idea 5') !== false) {
                $ubicacion = Ubicacion::where('descripcion', 'LIKE', '%Sector Final%')->first();
            } else {
                $ubicacion = Ubicacion::where('descripcion', 'LIKE', "%$codigoMaquina%")->first();
            }

            if (!$ubicacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró una ubicación con el nombre de la máquina: $codigoMaquina."
                ], 400);
            }

            // Crear el paquete
            $paquete = $this->crearPaquete($planilla->id, $ubicacion->id, $pesoTotal);

            // Crear movimiento de paquete pendiente
            Movimiento::create([
                'tipo' => 'Bajada de paquete',
                'paquete_id'         => $paquete->id,
                'solicitado_por'         => auth()->id(),
                'descripcion'        => "Se solicita bajar del carro el paquete #{$paquete->id} de la máquina {$maquina->nombre}",
                'ubicacion_origen'   => $ubicacion->id,
                'maquina_origen'     => $maquina->id,
                'estado'             => 'pendiente'
            ]);

            // Asignar los elementos al paquete
            $this->asignarItemsAPaquete($todosElementos->pluck('id')->toArray(), $paquete->id);
            session(['elementos_reempaquetados' => $todosElementos->pluck('id')->toArray()]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete creado correctamente.',
                'paquete_id' => $paquete->id,
                'codigo_planilla' => $codigo_planilla,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en el controlador store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Verifica que los items enviados estén completos y que ninguno
     * de los elementos ya pertenezca a otro paquete.
     *
     * - Para etiquetas: se verifica que exista la planilla y que tenga
     *   elementos asociados o un pesoTotal definido.
     * - Para elementos: se comprueba que tenga un valor de peso definido.
     * - Además, se valida que ninguno de los elementos (ya sea de una etiqueta
     *   o ingresado directamente) tenga asignado un paquete.
     *
     * @param array $items
     * @param int $maquinaId
     * @return array|null   Retorna un arreglo de warnings si existen inconsistencias, o null si todo es correcto.
     */
    private function verificarItems($items, $maquinaId)
    {
        $warnings = [];

        // Separar IDs según tipo
        $etiquetasSubIds = collect($items)->where('type', 'etiqueta')->pluck('id')->toArray();
        $elementosIds = collect($items)->where('type', 'elemento')->pluck('id')->toArray();

        // Buscar las etiquetas por su sub_id (no por id normal)
        $etiquetas = Etiqueta::whereIn('etiqueta_sub_id', $etiquetasSubIds)
            ->with(['planilla', 'elementos'])
            ->get();

        if (count($etiquetas) < count($etiquetasSubIds)) {
            $subIdsEncontrados = $etiquetas->pluck('etiqueta_sub_id')->toArray();
            $faltantes = array_diff($etiquetasSubIds, $subIdsEncontrados);
            $warnings['etiquetas_no_encontradas'] = array_values($faltantes);
        }

        // Verificar existencia de los elementos
        $elementos = Elemento::whereIn('id', $elementosIds)->get();
        if (count($elementos) < count($elementosIds)) {
            $idsEncontrados = $elementos->pluck('id')->toArray();
            $faltantes = array_diff($elementosIds, $idsEncontrados);
            $warnings['elementos_no_encontrados'] = array_values($faltantes);
        }

        // Validar que las etiquetas estén completas
        foreach ($etiquetas as $etiqueta) {
            if (!$etiqueta->planilla) {
                $warnings['etiquetas_incompletas'][] = $etiqueta->etiqueta_sub_id;
            }
            if ($etiqueta->elementos->isEmpty() && !$etiqueta->pesoTotal) {
                $warnings['etiquetas_incompletas'][] = $etiqueta->etiqueta_sub_id;
            }
        }

        // Validar que los elementos estén completos
        foreach ($elementos as $elemento) {
            if (is_null($elemento->peso)) {
                $warnings['elementos_incompletos'][] = $elemento->id;
            }
        }

        // Obtener los elementos asociados a las etiquetas filtrados por máquina
        $elementosIdsDesdeEtiquetas = $etiquetas->flatMap(function ($etiqueta) use ($maquinaId) {
            return $etiqueta->elementos
                ->where('maquina_id', $maquinaId)
                ->pluck('id');
        })->toArray();

        // Combinar
        $todosElementosIds = array_unique(array_merge($elementosIdsDesdeEtiquetas, $elementosIds));

        // Verificar si ya tienen paquete
        $elementosOcupados = Elemento::whereIn('id', $todosElementosIds)
            ->whereNotNull('paquete_id')
            ->pluck('id')
            ->toArray();

        if (!empty($elementosOcupados)) {
            $warnings['elementos_ocupados'] = $elementosOcupados;
        }

        return !empty($warnings) ? $warnings : null;
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
            $elementosYaAsignados = Elemento::whereIn('id', $elementos)
                ->whereNotNull('paquete_id')
                ->pluck('id')
                ->toArray();

            if (count($elementosYaAsignados) > 0) {
                Log::warning('Elementos ya asignados a otro paquete: ' . implode(', ', $elementosYaAsignados));
            }

            Elemento::whereIn('id', $elementos)->update(['paquete_id' => $paqueteId]);
        } catch (Exception $e) {
            Log::error('Error al asignar paquete: ' . $e->getMessage());
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
