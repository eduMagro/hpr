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

    private function aplicarFiltros(Request $request, $query)
    {
        if ($request->filled('id') && is_numeric($request->id)) {
            $query->where('id', intval($request->id));
        }
        // Filtro por código_limpio (accesor en planilla)
        if ($request->filled('planilla')) {
            $input = $request->planilla;
            $paquetes = $paquetes->filter(function ($paquete) use ($input) {
                return str_contains($paquete->planilla?->codigo_limpio ?? '', $input);
            });
        }

        if ($request->filled('ubicacion')) {
            $query->whereHas(
                'ubicacion',
                fn($q) => $q->where('nombre', 'like', '%' . $request->ubicacion . '%')
            );
        }

        if ($request->filled('peso')) {
            $query->where('peso', '>=', $request->peso);
        }

        if ($request->filled('created_at_from')) {
            $query->whereDate('created_at', '=', $request->created_at_from);
        }

        if ($request->filled('fecha_limite_reparto_from')) {
            $query->whereHas(
                'planilla',
                fn($q) => $q->whereDate('fecha_estimada_entrega', '=', $request->fecha_limite_reparto_from)
            );
        }

        return $query;
    }
    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = '';
        if ($isSorted) {
            $icon = $currentOrder === 'asc'
                ? '▲' // flecha hacia arriba
                : '▼'; // flecha hacia abajo
        } else {
            $icon = '⇅'; // símbolo de orden genérico
        }

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id')) {
            $filtros[] = 'ID: <strong>' . e($request->id) . '</strong>';
        }

        if ($request->filled('planilla')) {
            $filtros[] = 'Planilla: <strong>' . e($request->planilla) . '</strong>';
        }

        if ($request->filled('ubicacion')) {
            $filtros[] = 'Ubicación: <strong>' . e($request->ubicacion) . '</strong>';
        }

        if ($request->filled('peso')) {
            $filtros[] = 'Peso ≥ <strong>' . e($request->peso) . ' kg</strong>';
        }

        if ($request->filled('created_at_from')) {
            $filtros[] = 'Desde creación: <strong>' . e($request->created_at_from) . '</strong>';
        }

        if ($request->filled('fecha_limite_reparto_from')) {
            $filtros[] = 'Desde entrega estimada: <strong>' . e($request->fecha_limite_reparto_from) . '</strong>';
        }

        if ($request->filled('sort')) {
            $orden = $request->order === 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . e($request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        return $filtros;
    }


    /**
     * Muestra la lista de paquetes.
     *
     * - Incluye relaciones: planilla, ubicación y elementos.
     * - Permite filtrar por id y por planilla.
     * - Ordena los paquetes por fecha de creación descendente.
     */
    public function index(Request $request)
    {
        // Carga las relaciones necesarias, incluyendo etiquetas y sus elementos
        $query = Paquete::with(['planilla', 'ubicacion', 'etiquetas.elementos'])
            ->orderBy('created_at', 'desc');

        // Aplicar filtros
        $query = $this->aplicarFiltros($request, $query);

        // Ordenamiento dinámico
        $sortBy = $request->input('sort');
        $order = $request->input('order', 'asc');
        $allowedSorts = ['id', 'planilla_id', 'peso', 'created_at', 'fecha_limite_reparto'];

        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'fecha_limite_reparto') {
                $query->join('planillas', 'paquetes.planilla_id', '=', 'planillas.id')
                    ->orderBy('planillas.fecha_estimada_entrega', $order)
                    ->select('paquetes.*');
            } else {
                $query->orderBy($sortBy, $order);
            }
        }

        $paquetes = $query->get();

        // Paginación manual
        $currentPage = request()->get('page', 1);
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        $paginados = new \Illuminate\Pagination\LengthAwarePaginator(
            $paquetes->slice($offset, $perPage)->values(),
            $paquetes->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $paquetesConEtiquetas = Paquete::with('etiquetas')->get()->mapWithKeys(function ($paquete) {
            return [$paquete->codigo => $paquete->etiquetas->pluck('etiqueta_sub_id')];
        });
        $paquetes = Paquete::with('etiquetas.elementos', 'planilla', 'ubicacion')->get();

        $paquetesJson = $paquetes->map(function ($p) {
            return [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'etiquetas' => $p->etiquetas->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'etiqueta_sub_id' => $e->etiqueta_sub_id,
                        'nombre' => $e->nombre,
                        'codigo' => $e->codigo,
                        'peso_kg' => $e->peso_kg,
                    ];
                }),
            ];
        });

        $elementosAgrupadosScript = Etiqueta::with('elementos')->get()->map(function ($etiqueta) {
            return [
                'etiqueta' => [
                    'id' => $etiqueta->id,
                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                ],
                'elementos' => $etiqueta->elementos->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'dimensiones' => $e->dimensiones,
                        'barras' => $e->barras,
                        'peso' => $e->peso_kg,
                        'diametro' => $e->diametro,
                    ];
                }),
            ];
        });


        // Opciones de ordenamiento
        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'planilla_id' => $this->getOrdenamiento('planilla_id', 'Planilla'),
            'peso' => $this->getOrdenamiento('peso', 'Peso (Kg)'),
            'created_at' => $this->getOrdenamiento('created_at', 'Fecha Creación'),
            'fecha_limite_reparto' => $this->getOrdenamiento('fecha_limite_reparto', 'Fecha Límite Reparto'),
        ];

        $filtrosActivos = $this->filtrosActivos($request);

        return view('paquetes.index', [
            'paquetes' => $paginados,
            'paquetesJson' => $paquetesJson,
            'ordenables' => $ordenables,
            'filtrosActivos' => $filtrosActivos,
            'paquetesConEtiquetas' => $paquetesConEtiquetas,
            'elementosAgrupadosScript' => $elementosAgrupadosScript
        ]);
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

            $codigo = Paquete::generarCodigo(); // ← Aquí generas el código
            $paquete = $this->crearPaquete($planilla->id, $ubicacion->id, $pesoTotal, $codigo); // ← Se lo pasas


            // Crear movimiento de paquete pendiente
            Movimiento::create([
                'tipo' => 'Bajada de paquete',
                'paquete_id'         => $paquete->id,
                'solicitado_por'         => auth()->id(),
                'descripcion'        => "Se solicita bajar del carro el paquete {$paquete->codigo} de la máquina {$maquina->nombre}",
                'ubicacion_origen'   => $ubicacion->id,
                'maquina_origen'     => $maquina->id,
                'estado'             => 'pendiente',
                'prioridad'          => 3,
                'fecha_solicitud'    => now(),
            ]);

            // Asignar los elementos al paquete
            $this->asignarEtiquetasAPaquete($etiquetasSubIds, $paquete->id);

            session(['elementos_reempaquetados' => $todosElementos->pluck('id')->toArray()]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete creado correctamente.',
                'paquete_id' => $paquete->id,
                'codigo_paquete' => $codigo,
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

        $etiquetas = Etiqueta::whereIn('etiqueta_sub_id', $etiquetasSubIds)
            ->with(['planilla', 'elementos'])
            ->get();


        if (count($etiquetas) < count($etiquetasSubIds)) {
            $idsEncontrados = $etiquetas->pluck('etiqueta_sub_id')->toArray(); // ✅ pluck del mismo campo
            $faltantes = array_diff($etiquetasSubIds, $idsEncontrados);
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
                $warnings['etiquetas_incompletas'][] = $etiqueta->id;
            }
            if ($etiqueta->elementos->isEmpty() && !$etiqueta->pesoTotal) {
                $warnings['etiquetas_incompletas'][] = $etiqueta->id;
            }
        }

        // Validar que los elementos estén completos
        foreach ($elementos as $elemento) {
            if (is_null($elemento->peso)) {
                $warnings['elementos_incompletos'][] = $elemento->id;
            }
        }

        // Verificar si las etiquetas ya tienen paquete asignado
        $etiquetasConPaquete = $etiquetas->filter(function ($etiqueta) {
            return !is_null($etiqueta->paquete_id);
        })->pluck('id')->toArray();

        if (!empty($etiquetasConPaquete)) {
            $warnings['etiquetas_ocupadas'] = $etiquetasConPaquete;
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
    private function crearPaquete($planillaId, $ubicacionId, $pesoTotal, $codigo)
    {
        try {
            return Paquete::create([
                'planilla_id'   => $planillaId,
                'ubicacion_id'  => $ubicacionId,
                'peso'          => $pesoTotal ?? 0,
                'codigo'        => $codigo,
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
    private function asignarEtiquetasAPaquete(array $subIds, int $paqueteId)
    {
        try {
            // Verificar si alguna etiqueta ya tiene un paquete asignado
            $etiquetasYaAsignadas = Etiqueta::whereIn('etiqueta_sub_id', $subIds)
                ->whereNotNull('paquete_id')
                ->pluck('etiqueta_sub_id')
                ->toArray();

            if (count($etiquetasYaAsignadas) > 0) {
                Log::warning('Etiquetas ya asignadas a otro paquete: ' . implode(', ', $etiquetasYaAsignadas));
            }

            // Asignar el paquete a las etiquetas correctas
            Etiqueta::whereIn('etiqueta_sub_id', $subIds)
                ->update(['paquete_id' => $paqueteId]);
        } catch (Exception $e) {
            Log::error('Error al asignar paquete a etiquetas: ' . $e->getMessage());
            throw new Exception('Error al asignar paquete a etiquetas: ' . $e->getMessage());
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

            // 🔸 Eliminar movimientos pendientes asociados al paquete
            \App\Models\Movimiento::where('paquete_id', $paquete->id)
                ->where('estado', 'pendiente')
                ->delete();

            // 🔸 Desasociar los elementos del paquete
            $paquete->elementos()->update(['paquete_id' => null]);

            // 🔸 Eliminar el paquete
            $paquete->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Paquete eliminado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al eliminar el paquete: ' . $e->getMessage());
        }
    }
}
