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

        /* ── Filtro por código de planilla ──────── */
        if ($request->filled('planilla')) {
            $input = trim($request->planilla);

            $query->whereHas('planilla', function ($q) use ($input) {

                // Caso 1: formato completo tipo 2025-4512  → se normaliza a 2025-004512
                if (preg_match('/^(\d{4})-(\d{1,6})$/', $input, $m)) {
                    $anio = $m[1];
                    $num  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                    $codigoFormateado = "{$anio}-{$num}";
                    $q->where('planillas.codigo', 'like', "%{$codigoFormateado}%");
                    return;
                }

                // Caso 2: solo número final (ej. "4512") → busca cualquier código que lo contenga
                if (preg_match('/^\d{1,6}$/', $input)) {
                    $q->where('planillas.codigo', 'like', "%{$input}%");
                    return;
                }

                // Caso general: texto libre
                $q->where('planillas.codigo', 'like', "%{$input}%");
            });
        }



        /* ── Nave (obra) ───────────────────────────── */
        if ($request->filled('nave')) {
            $texto = $request->nave;
            $query->whereHas('nave', function ($q) use ($texto) {
                $q->where('obra', 'like', '%' . $texto . '%');
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
            'nave', // 👈 añadimos para ordenar por obra
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

        // Caso especial: obra (en obras)
        if ($sort === 'nave') {
            return $query
                ->leftJoin('obras', 'paquetes.nave_id', '=', 'obras.id')
                ->orderBy('obras.obra', $order)
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
        if ($request->filled('nave')) {
            $filtros[] = 'Nave: <strong>' . e($request->nave) . '</strong>';
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
        $paquetesAll = Paquete::with('etiquetas:id,paquete_id,etiqueta_sub_id,nombre,codigo,peso')
            ->select('id', 'codigo')
            ->latest()
            ->take(100) // 🔸 solo los 100 últimos, ajusta según lo que necesites
            ->get();

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

        $elementosAgrupadosScript = Etiqueta::with(['elementos:id,etiqueta_id,dimensiones,barras,peso,diametro'])
            ->select('id', 'etiqueta_sub_id')
            ->latest()
            ->take(100) // igual, solo los últimos
            ->get()
            ->map(fn($et) => [
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
            'nave'                 => $this->getOrdenamiento('nave', 'Nave'), // 👈 nuevo
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
        // 1) Validación
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|string',
            'items.*.type' => 'required|in:etiqueta,elemento',
            'maquina_id' => 'required|integer|exists:maquinas,id'
        ]);

        // 2) Verificación previa
        $warnings = $this->verificarItems($request->items, $request->input('maquina_id'));
        if ($warnings !== null && !$request->boolean('confirmar')) {
            return response()->json([
                'success' => false,
                'warning' => $warnings
            ], 200);
        }

        try {
            DB::beginTransaction();

            // 3) Separar items
            $etiquetasSubIds = collect($request->items)
                ->where('type', 'etiqueta')
                ->pluck('id')
                ->toArray();

            $elementosIds = collect($request->items)
                ->where('type', 'elemento')
                ->pluck('id')
                ->toArray();

            $elementosDesdeEtiquetas = Elemento::whereIn('etiqueta_sub_id', $etiquetasSubIds)->get();
            $elementosDirectos       = Elemento::whereIn('id', $elementosIds)->get();
            $todosElementos          = $elementosDesdeEtiquetas->merge($elementosDirectos);

            if ($todosElementos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete.'
                ], 400);
            }

            // 4) Máquina y planilla
            $maquinaId     = $request->input('maquina_id');
            $maquina       = Maquina::findOrFail($maquinaId);
            $codigoMaquina = $maquina->codigo;

            $planilla = $todosElementos->first()->planilla ?? null;
            if (!$planilla) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una planilla válida para las etiquetas o los elementos.'
                ], 400);
            }
            $codigo_planilla = $planilla->codigo_limpio;

            // 5) Peso total
            $pesoTotal = $todosElementos->sum('peso');
            if ($pesoTotal > 1300) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "El peso total del paquete ({$pesoTotal} kg) supera el límite permitido de 1300 kg."
                ], 400);
            }

            // 6) Ubicación
            if (stripos($maquina->nombre, 'idea 5') !== false) {
                $ubicacion = Ubicacion::where('descripcion', 'LIKE', '%Sector Final%')->first();
            } else {
                $ubicacion = Ubicacion::where('descripcion', 'LIKE', "%{$codigoMaquina}%")->first();
            }

            if (!$ubicacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró una ubicación con el nombre de la máquina: {$codigoMaquina}."
                ], 400);
            }

            // 7) ⬅️ Capturamos los paquetes ANTERIORES de esas subetiquetas (antes de reasignar)
            $paquetesPrevios = DB::table('etiquetas')
                ->whereIn('etiqueta_sub_id', $etiquetasSubIds)
                ->whereNotNull('paquete_id')
                ->pluck('paquete_id')
                ->unique()
                ->values()
                ->all();

            // 8) Crear paquete NUEVO
            $codigo  = Paquete::generarCodigo();
            $paquete = $this->crearPaquete($planilla->id, $ubicacion->id, $pesoTotal, $codigo, $maquina->obra_id);

            // 9) Reasignar etiquetas al NUEVO paquete
            $this->asignarEtiquetasAPaquete($etiquetasSubIds, $paquete->id);

            // 10) Check de seguridad: ¿el nuevo paquete quedó vacío?
            $etiquetasAsignadasNuevo = DB::table('etiquetas')
                ->where('paquete_id', $paquete->id)
                ->count();

            if ((int)$etiquetasAsignadasNuevo === 0) {
                Log::info('Paquete nuevo eliminado por quedar sin etiquetas asignadas', [
                    'paquete_id'  => $paquete->id,
                    'planilla_id' => $planilla->id,
                    'maquina_id'  => $maquina->id,
                    'items_input' => $etiquetasSubIds,
                ]);

                $paquete->delete();
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No se reasignó ninguna etiqueta al nuevo paquete.'
                ], 400);
            }

            // 11) ✅ Borrar paquetes ANTERIORES que hayan quedado vacíos tras la reasignación
            foreach ($paquetesPrevios as $paqueteAnteriorId) {
                // por seguridad, evita tocar el recién creado (no debería estar en la lista)
                if ((int)$paqueteAnteriorId === (int)$paquete->id) {
                    continue;
                }

                $restantes = DB::table('etiquetas')
                    ->where('paquete_id', $paqueteAnteriorId)
                    ->count();

                if ((int)$restantes === 0) {
                    Paquete::where('id', $paqueteAnteriorId)->delete();
                    Log::info('Paquete anterior eliminado por quedar vacío', [
                        'paquete_id'  => $paqueteAnteriorId,
                        'nuevo_id'    => $paquete->id,
                        'planilla_id' => $planilla->id,
                    ]);
                }
            }

            // 12) Retirar de la cola de ESTA máquina si ya no quedan etiquetas en ESTA máquina
            app(PlanillaColaService::class)
                ->retirarSiPlanillaCompletamentePaquetizadaYCompletada($planilla, $maquina);

            // // 13) Movimiento solo si tiene carro
            // if ($maquina->tiene_carro) {
            //     Movimiento::create([
            //         'tipo'             => 'Bajada de paquete',
            //         'paquete_id'       => $paquete->id,
            //         'solicitado_por'   => auth()->id(),
            //         'descripcion'      => "Se solicita bajar del carro el paquete {$paquete->codigo} de la máquina {$maquina->nombre}",
            //         'ubicacion_origen' => $ubicacion->id,
            //         'maquina_origen'   => $maquina->id,
            //         'estado'           => 'pendiente',
            //         'prioridad'        => 3,
            //         'fecha_solicitud'  => now(),
            //     ]);
            // }

            // 14) Sesión de reempaquetados
            session(['elementos_reempaquetados' => $todosElementos->pluck('id')->toArray()]);

            DB::commit();

            return response()->json([
                'success'         => true,
                'message'         => 'Paquete creado correctamente.',
                'paquete_id'      => $paquete->id,
                'codigo_paquete'  => $codigo,
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

    private function crearPaquete($planillaId, $ubicacionId, $pesoTotal, $codigo, $obraId)
    {
        try {
            return Paquete::create([
                'planilla_id'   => $planillaId,
                'ubicacion_id'  => $ubicacionId,
                'peso'          => $pesoTotal ?? 0,
                'codigo'        => $codigo,
                'nave_id'     => $obraId,
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
            \Log::info('Borrando paquete ' . ($paquete->codigo ?? ('ID ' . $paquete->id)) . ' por el usuario ' . (auth()->user()->nombre_completo ?? 'desconocido'));

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

    public function tamaño(Request $request)
    {
        // 1) Validación
        $validated = $request->validate([
            'codigo' => 'required|string|max:100',
        ], [
            'codigo.required' => 'Debes indicar el etiqueta_sub_id.',
        ]);

        $codigo = trim($validated['codigo']);

        // 2) Buscar etiqueta directamente
        $etiqueta = Etiqueta::where('etiqueta_sub_id', $codigo)->first();

        // 3) Fallback: buscar en elementos
        if (!$etiqueta) {
            $elemento = Elemento::with('etiqueta')
                ->where('etiqueta_sub_id', $codigo)
                ->first();
            if ($elemento) {
                $etiqueta = $elemento->etiqueta;
            }
        }

        if (!$etiqueta || !$etiqueta->paquete_id) {
            return response()->json(['error' => 'Etiqueta no asociada a ningún paquete.'], 404);
        }

        // 4) Cargar paquete con todas sus etiquetas y elementos
        $paquete = Paquete::with(['etiquetas.elementos'])->find($etiqueta->paquete_id);
        if (!$paquete) {
            return response()->json(['error' => 'Paquete no encontrado.'], 404);
        }

        // 5) Tamaño (usa accessor getTamañoAttribute o alias getTamanoAttribute)
        $tamano = $paquete->tamaño ?? $paquete->tamano ?? ['ancho' => 1, 'longitud' => 0];

        // 6) Métricas adicionales
        $etiquetasCount = $paquete->etiquetas->count();
        $elementosCount = $paquete->etiquetas->flatMap->elementos->count();

        $celdaM = 0.5;
        $celdasLargo = max(1, (int) ceil(($tamano['longitud'] ?? 0) / $celdaM));

        // 7) Respuesta JSON
        return response()->json([
            'codigo'          => $paquete->codigo,
            'paquete_id'      => $paquete->id,
            'ancho'           => (float) $tamano['ancho'],
            'longitud'        => (float) $tamano['longitud'],
            'celdas_largo'    => $celdasLargo,
            'etiqueta_sub_id' => $codigo,
            'etiquetas_count' => $etiquetasCount,
            'elementos_count' => $elementosCount,
        ]);
    }

    public function storePaquete(Request $request)
    {
        $data = $request->validate([
            'nave_id'     => 'required|exists:obras,id',
            'tipo'        => 'required|in:paquete', // si prefieres otro tipo, ajusta
            'nombre'      => 'required|string|max:100',
            'paquete_id'  => 'nullable|exists:paquetes,id',
            'x1'          => 'required|integer|min:1',
            'y1'          => 'required|integer|min:1',
            'x2'          => 'required|integer|min:1',
            'y2'          => 'required|integer|min:1',
        ]);

        // Normaliza rect
        $x1 = min($data['x1'], $data['x2']);
        $y1 = min($data['y1'], $data['y2']);
        $x2 = max($data['x1'], $data['x2']);
        $y2 = max($data['y1'], $data['y2']);

        // (Opcional) aquí puedes validar colisiones si procede

        $loc = \App\Models\Localizacion::create([
            'nave_id' => $data['nave_id'],
            'tipo'    => $data['tipo'],        // 'paquete'
            'nombre'  => $data['nombre'],      // código del paquete
            'paquete_id' => $data['paquete_id'] ?? null,
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $loc->id,
        ]);
    }
}
