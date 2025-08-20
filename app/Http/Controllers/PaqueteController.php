<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\LocalizacionPaquete;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Movimiento;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Services\PlanillaColaService;

class PaqueteController extends Controller
{

    private function aplicarFiltros(Request $request, $query)
    {
        /* ── Filtro por ID ─────────────────────────────── */
        if ($request->filled('id') && is_numeric($request->id)) {
            $query->where('id', (int) $request->id);
        }

        /* ── Filtro por código limpio de planilla ──────── */
        if ($request->filled('planilla')) {
            $input = $request->planilla;
            $query->whereHas('planilla', function ($q) use ($input) {
                $q->where('codigo', 'like', "%{$input}%");
            });
        }

        /* ── Ubicación ─────────────────────────────────── */
        if ($request->filled('ubicacion')) {
            $query->whereHas('ubicacion', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->ubicacion . '%');
            });
        }

        /* ── Peso mínimo ───────────────────────────────── */
        if ($request->filled('peso') && is_numeric($request->peso)) {
            $query->where('peso', '>=', (float) $request->peso);
        }

        /* ── Fechas ───────────────────────────────────── */
        if ($request->filled('created_at_from')) {
            $query->whereDate('created_at', $request->created_at_from);
        }

        if ($request->filled('fecha_limite_reparto_from')) {
            $query->whereHas('planilla', function ($q) use ($request) {
                $q->whereDate('fecha_estimada_entrega', $request->fecha_limite_reparto_from);
            });
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
    private function aplicarOrdenamiento($query, Request $request)
    {
        $sortAllowed = [
            'id',
            'planilla_id',
            'paquete',
            'peso',
            'created_at',
            'fecha_limite_reparto',
        ];

        $sort  = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!in_array($sort, $sortAllowed, true)) {
            $sort = 'created_at';
        }

        // Caso especial: fecha límite (en planillas)
        if ($sort === 'fecha_limite_reparto') {
            return $query
                ->leftJoin('planillas', 'paquetes.planilla_id', '=', 'planillas.id')
                ->orderBy('planillas.fecha_estimada_entrega', $order)
                ->select('paquetes.*');
        }

        return $query->orderBy($sort, $order);
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

    public function index(Request $request)
    {
        // Base query + relaciones
        $query = Paquete::with(['planilla', 'ubicacion', 'etiquetas.elementos']);

        // Filtros
        $query = $this->aplicarFiltros($request, $query);

        // Ordenamiento
        $query = $this->aplicarOrdenamiento($query, $request);

        /* ── Paginación (LengthAwarePaginator manual) ── */
        $perPage      = 10;
        $currentPage  = $request->input('page', 1);
        $paquetesPage = $query->paginate($perPage)->appends($request->query());

        /* ── Para el JSON y scripts auxiliares (sin paginar) ── */
        $paquetesAll = Paquete::with('etiquetas.elementos', 'planilla', 'ubicacion')->get();
        $paquetesConEtiquetas = $paquetesAll->mapWithKeys(
            fn($p) =>
            [$p->codigo => $p->etiquetas->pluck('etiqueta_sub_id')]
        );

        $paquetesJson = $paquetesAll->map(fn($p) => [
            'id'     => $p->id,
            'codigo' => $p->codigo,
            'etiquetas' => $p->etiquetas->map(fn($e) => [
                'id'             => $e->id,
                'etiqueta_sub_id' => $e->etiqueta_sub_id,
                'nombre'         => $e->nombre,
                'codigo'         => $e->codigo,
                'peso_kg'        => $e->peso_kg,
            ]),
        ]);

        $elementosAgrupadosScript = Etiqueta::with('elementos')->get()->map(fn($et) => [
            'etiqueta'  => ['id' => $et->id, 'etiqueta_sub_id' => $et->etiqueta_sub_id],
            'elementos' => $et->elementos->map(fn($e) => [
                'id'         => $e->id,
                'dimensiones' => $e->dimensiones,
                'barras'     => $e->barras,
                'peso'       => $e->peso_kg,
                'diametro'   => $e->diametro,
            ]),
        ]);

        /* ── Ordenables para la cabecera ── */
        $ordenables = [
            'id'                   => $this->getOrdenamiento('id', 'ID'),
            'planilla_id'          => $this->getOrdenamiento('planilla_id', 'Planilla'),
            'peso'                 => $this->getOrdenamiento('peso', 'Peso (Kg)'),
            'created_at'           => $this->getOrdenamiento('created_at', 'Fecha Creación'),
            'fecha_limite_reparto' => $this->getOrdenamiento('fecha_limite_reparto', 'Fecha Límite Reparto'),
        ];

        return view('paquetes.index', [
            'paquetes'                => $paquetesPage,
            'paquetesJson'            => $paquetesJson,
            'ordenables'              => $ordenables,
            'filtrosActivos'          => $this->filtrosActivos($request),
            'paquetesConEtiquetas'    => $paquetesConEtiquetas,
            'elementosAgrupadosScript' => $elementosAgrupadosScript,
        ]);
    }

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
            (new PlanillaColaService)
                ->retirarPlanillaDeColaSiNoQuedanEtiquetasEnMaquina($planilla, $maquina);

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

    public function update(Request $request, $codigo)
    {
        $paquete = Paquete::where('codigo', $codigo)->first();
        if (!$paquete) {
            return response()->json(['error' => 'Paquete no encontrado'], 404);
        }

        $validated = $request->validate([
            'x1' => 'required|integer|min:1',
            'y1' => 'required|integer|min:1',
            'x2' => 'required|integer|min:1',
            'y2' => 'required|integer|min:1',
        ]);

        LocalizacionPaquete::updateOrCreate(
            ['paquete_id' => $paquete->id],
            $validated
        );

        return response()->json(['message' => 'Localización guardada correctamente']);
    }
}
