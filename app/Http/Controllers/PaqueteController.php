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
use Illuminate\Http\JsonResponse;
use App\Services\LocalizacionPaqueteService;

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

    public function store(Request $request, LocalizacionPaqueteService $localizacionPaqueteService)
    {
        // 1) Validación de la petición
        //    - items: array de cosas a paquetizar (etiquetas / elementos)
        //    - items.*.id: identificador de la etiqueta_sub_id o del elemento
        //    - items.*.type: 'etiqueta' o 'elemento'
        //    - maquina_id: máquina desde la que se crea el paquete
        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.id'        => 'required|string',
            'items.*.type'      => 'required|in:etiqueta,elemento',
            'maquina_id'        => 'required|integer|exists:maquinas,id',
        ]);

        try {
            DB::beginTransaction();

            // 3) Separar items entre etiquetas y elementos
            //    - etiquetasSubIds: sub_id de las etiquetas (etiqueta_sub_id)
            //    - elementosIds: IDs de los elementos seleccionados directamente
            $etiquetasSubIds = collect($request->items)
                ->where('type', 'etiqueta')
                ->pluck('id')
                ->toArray();

            $elementosIds = collect($request->items)
                ->where('type', 'elemento')
                ->pluck('id')
                ->toArray();

            // Obtener elementos a partir de las etiquetas + elementos directos
            $elementosDesdeEtiquetas = Elemento::whereIn('etiqueta_sub_id', $etiquetasSubIds)->get();
            $elementosDirectos       = Elemento::whereIn('id', $elementosIds)->get();
            $todosElementos          = $elementosDesdeEtiquetas->merge($elementosDirectos);

            // Si no hay elementos, no se puede crear paquete
            if ($todosElementos->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos válidos para crear el paquete.',
                ], 400);
            }

            // 4) Máquina y planilla
            //    - Se usa la máquina para:
            //         * determinar ubicación (Ubicacion)
            //         * pasar su ID al servicio de localización de paquetes
            $maquinaId     = $request->input('maquina_id');
            $maquina       = Maquina::findOrFail($maquinaId);
            $codigoMaquina = $maquina->codigo;

            // La planilla se saca del primer elemento (todos deben ser compatibles)
            $planilla = $todosElementos->first()->planilla ?? null;
            if (!$planilla) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una planilla válida para las etiquetas o los elementos.',
                ], 400);
            }
            $codigo_planilla = $planilla->codigo_limpio;

            // 5) Calcular peso total del paquete (suma de peso de todos los elementos)
            $pesoTotal = $todosElementos->sum(function ($elemento) {
                return $elemento->peso ?? 0;
            });

            // 6) Ubicación: según el nombre/código de la máquina
            //    - Si contiene 'idea 5' en el nombre → Sector Final
            //    - Si no → ubicación que contenga el código de la máquina
            if (stripos($maquina->nombre, 'idea 5') !== false) {
                $ubicacion = Ubicacion::where('descripcion', 'LIKE', '%Sector Final%')->first();
            } else {
                $ubicacion = Ubicacion::where('descripcion', 'LIKE', "%{$codigoMaquina}%")->first();
            }

            if (!$ubicacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró una ubicación con el nombre de la máquina: {$codigoMaquina}.",
                ], 400);
            }

            // 7) Guardar los paquetes ANTERIORES de esas subetiquetas (para luego limpiar si quedan vacíos)
            $paquetesPrevios = DB::table('etiquetas')
                ->whereIn('etiqueta_sub_id', $etiquetasSubIds)
                ->whereNotNull('paquete_id')
                ->pluck('paquete_id')
                ->unique()
                ->values()
                ->all();

            // 8) Crear paquete NUEVO (en la tabla paquetes)
            $codigo  = Paquete::generarCodigo();
            $paquete = $this->crearPaquete(
                $planilla->id,   // planilla_id
                $ubicacion->id,  // ubicacion_id
                $pesoTotal,      // peso total del paquete
                $codigo,         // código generado
                $maquina->obra_id // nave/obra a la que pertenece
            );

            // 9) Reasignar etiquetas al NUEVO paquete
            $this->asignarEtiquetasAPaquete($etiquetasSubIds, $paquete->id);

            // 10) Check de seguridad: ¿el nuevo paquete quedó vacío?
            $etiquetasAsignadasNuevo = DB::table('etiquetas')
                ->where('paquete_id', $paquete->id)
                ->count();

            if ((int) $etiquetasAsignadasNuevo === 0) {
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
                    'message' => 'No se reasignó ninguna etiqueta al nuevo paquete.',
                ], 400);
            }

            // 10.1) 📍 Crear/actualizar la localización del paquete en el mapa
            //       Aquí es donde entramos al servicio LocalizacionPaqueteService,
            //       que:
            //          - Busca la localización de la máquina en `localizaciones`
            //          - Calcula el centro de esa máquina
            //          - Calcula el tamaño del paquete según sus elementos
            //          - Inserta/actualiza en `localizaciones_paquetes` una posición
            //            centrada encima del div de la máquina.
            $localizacionPaqueteService->asignarLocalizacionAutomatica(
                $paquete,          // paquete recién creado
                $maquina->id       // máquina desde la que se ha creado el paquete
            );

            // 11) Borrar paquetes ANTERIORES que hayan quedado vacíos tras la reasignación
            foreach ($paquetesPrevios as $paqueteAnteriorId) {
                // Seguridad: no tocar el recién creado (no debería estar en la lista)
                if ((int) $paqueteAnteriorId === (int) $paquete->id) {
                    continue;
                }

                $restantes = DB::table('etiquetas')
                    ->where('paquete_id', $paqueteAnteriorId)
                    ->count();

                if ((int) $restantes === 0) {
                    Paquete::where('id', $paqueteAnteriorId)->delete();
                    Log::info('Paquete anterior eliminado por quedar vacío', [
                        'paquete_id'  => $paqueteAnteriorId,
                        'nuevo_id'    => $paquete->id,
                        'planilla_id' => $planilla->id,
                    ]);
                }
            }

            // 12) Retirar de la cola de ESTA máquina si ya no quedan etiquetas pendientes en ella
            app(PlanillaColaService::class)
                ->retirarSiPlanillaCompletamentePaquetizadaYCompletada($planilla, $maquina);

            // 14) Guardar en sesión los IDs de elementos reempaquetados (para otras vistas/lógica)
            session(['elementos_reempaquetados' => $todosElementos->pluck('id')->toArray()]);

            // 📊 LOG DE PRODUCCIÓN EN CSV - Creación de paquete
            \App\Services\ProductionLogger::logCreacionPaquete(
                $paquete,
                $etiquetasSubIds,
                $maquina,
                \Illuminate\Support\Facades\Auth::user()
            );

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
                'message' => 'Error en el servidor: ' . $e->getMessage(),
            ], 500);
        }
    }



    public function validarParaPaquete(Request $request, string $etiquetaSubId): JsonResponse
    {
        $etiqueta = Etiqueta::with('elementos')
            ->where('etiqueta_sub_id', $etiquetaSubId)
            ->first();

        if (!$etiqueta) {
            return response()->json([
                'success' => false,
                'valida' => false,
                'message' => "Etiqueta {$etiquetaSubId} no encontrada.",
                'motivo'  => "No se ha encontrado la etiqueta.",
            ], 404);
        }

        $estado     = strtolower($etiqueta->estado ?? 'pendiente');
        $enPaquete  = !is_null($etiqueta->paquete_id);
        $estadosOK  = ['fabricada', 'completada', 'ensamblada', 'soldada'];
        $total      = $etiqueta->elementos->count();
        $fabricados = $etiqueta->elementos
            ->whereIn('estado', ['fabricado', 'completado', 'ensamblado', 'soldado'])
            ->count();

        $motivos = [];
        $valida = true;

        if ($enPaquete) {
            $valida = false;
            $motivos[] = 'La etiqueta ya está en un paquete.';
        }

        if (!in_array($estado, $estadosOK, true)) {
            $valida = false;
            $motivos[] = "El estado '{$estado}' no es válido para empaquetar.";
        }

        if ($fabricados < $total) {
            $valida = false;
            $motivos[] = "Hay elementos pendientes ({$fabricados}/{$total}).";
        }

        $pesoEtiqueta = $etiqueta->peso ?? 0;

        // Log para debug
        \Log::info("validarParaPaquete - Etiqueta: {$etiquetaSubId}, Peso: {$pesoEtiqueta}");

        return response()->json([
            'success'       => $valida,
            'valida'        => $valida,
            'message'       => $valida ? 'Etiqueta válida para empaquetar.' : implode(' ', $motivos),
            'motivo'        => $valida ? null : implode(' ', $motivos),
            'estado_actual' => $etiqueta->estado,
            'paquete_actual' => $etiqueta->paquete_id,
            'id'            => $etiqueta->etiqueta_sub_id,
            'nombre'        => $etiqueta->nombre,
            'peso_etiqueta' => $pesoEtiqueta,
            'estado'        => $etiqueta->estado,
        ]);
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

    // Otros métodos del controlador... Creacion de paquetes a traves de maquinas.show

    // ================================================================
    // MÉTODOS ADICIONALES PARA PaqueteController.php
    // Añadir estos métodos al controlador existente
    // ================================================================

    /**
     * Obtener paquetes de una planilla específica con sus etiquetas
     * 
     * GET /api/planillas/{planillaId}/paquetes
     */
    public function obtenerPaquetesPorPlanilla($planillaId, \Illuminate\Http\Request $request)
    {
        try {
            $planilla = \App\Models\Planilla::findOrFail($planillaId);

            // Obtener paquetes de esta planilla con sus etiquetas y elementos
            $query = Paquete::with(['etiquetas' => function ($query) {
                $query->select('id', 'etiqueta_sub_id', 'paquete_id', 'peso', 'estado')
                    ->withCount('elementos')
                    ->with(['elementos' => function ($q) {
                        $q->select('id', 'codigo', 'dimensiones', 'etiqueta_id');
                    }]);
            }, 'ubicacion:id,nombre'])
                ->where('planilla_id', $planillaId);

            // Filtrar por máquina si se proporciona el parámetro
            if ($request->has('maquina_id') && $request->maquina_id) {
                $maquinaId = $request->maquina_id;
                $query->where('ubicacion_id', $maquinaId);
            }

            $paquetes = $query->orderBy('created_at', 'desc')->get();

            $paquetesFormateados = $paquetes->map(function ($paquete) {
                return [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                    'peso' => number_format($paquete->peso, 2, '.', ''),
                    'cantidad_etiquetas' => $paquete->etiquetas->count(),
                    'ubicacion' => optional($paquete->ubicacion)->nombre ?? 'Sin ubicación',
                    'created_at' => $paquete->created_at->format('d/m/Y H:i'),
                    'etiquetas' => $paquete->etiquetas->map(function ($etiqueta) {
                        return [
                            'codigo' => $etiqueta->etiqueta_sub_id,
                            'peso' => number_format($etiqueta->peso ?? 0, 2, '.', ''),
                            'estado' => $etiqueta->estado,
                            'elementos_count' => $etiqueta->elementos_count ?? 0,
                            'elementos' => $etiqueta->elementos->map(function ($elemento) {
                                return [
                                    'id' => $elemento->id,
                                    'codigo' => $elemento->codigo,
                                    'dimensiones' => $elemento->dimensiones,
                                ];
                            })->values()->all()
                        ];
                    })->values()->all()
                ];
            });

            return response()->json([
                'success' => true,
                'planilla' => [
                    'id' => $planilla->id,
                    'codigo' => $planilla->codigo
                ],
                'paquetes' => $paquetesFormateados
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Planilla no encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al obtener paquetes de planilla', [
                'planilla_id' => $planillaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los paquetes'
            ], 500);
        }
    }

    /**
     * Añadir una etiqueta a un paquete existente
     * 
     * POST /api/paquetes/{paqueteId}/añadir-etiqueta
     * Body: { "etiqueta_codigo": "2025-004512.1.1" }
     */
    public function añadirEtiquetaAPaquete(Request $request, $paqueteId)
    {
        $request->validate([
            'etiqueta_codigo' => 'required|string'
        ]);

        DB::beginTransaction();

        try {
            $paquete = Paquete::findOrFail($paqueteId);
            $codigoEtiqueta = trim($request->etiqueta_codigo);

            // Buscar la etiqueta
            $etiqueta = Etiqueta::where('etiqueta_sub_id', $codigoEtiqueta)->first();

            if (!$etiqueta) {
                return response()->json([
                    'success' => false,
                    'message' => "Etiqueta {$codigoEtiqueta} no encontrada"
                ], 404);
            }

            // Validar que la etiqueta pertenezca a la misma planilla
            if ($etiqueta->planilla_id !== $paquete->planilla_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La etiqueta no pertenece a la misma planilla del paquete'
                ], 400);
            }

            // Validar que la etiqueta no esté ya en otro paquete
            if ($etiqueta->paquete_id && $etiqueta->paquete_id !== $paquete->id) {
                $paqueteActual = Paquete::find($etiqueta->paquete_id);

                // Si viene el parámetro forzar=true, permitir el movimiento
                if (!$request->boolean('forzar')) {
                    return response()->json([
                        'success' => false,
                        'requiere_confirmacion' => true,
                        'paquete_actual' => [
                            'id' => $paqueteActual->id,
                            'codigo' => $paqueteActual->codigo,
                        ],
                        'paquete_destino' => [
                            'id' => $paquete->id,
                            'codigo' => $paquete->codigo,
                        ],
                        'message' => "La etiqueta pertenece al paquete {$paqueteActual->codigo}. ¿Deseas moverla al paquete {$paquete->codigo}?"
                    ], 409); // 409 Conflict
                }

                // Si forzar=true, proceder a mover la etiqueta
                // Primero quitar del paquete anterior
                $pesoEtiqueta = $etiqueta->peso ?? 0;
                $pesoAnteriorPaqueteOrigen = $paqueteActual->peso;
                $paqueteActual->peso -= $pesoEtiqueta;
                $paqueteActual->save();

                // Registrar movimiento de salida
                \App\Models\Movimiento::create([
                    'tipo' => 'Movimiento paquete',
                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                    'paquete_id' => $paqueteActual->id,
                    'descripcion' => "Etiqueta {$codigoEtiqueta} removida del paquete {$paqueteActual->codigo} (movimiento a {$paquete->codigo})",
                    'estado' => 'completado',
                    'fecha_solicitud' => now(),
                    'ejecutado_por' => auth()->id(),
                ]);

                // 📊 LOG DE PRODUCCIÓN EN CSV - Remover etiqueta de paquete origen
                // Recargar paquete para obtener etiquetas restantes
                $paqueteActual->refresh();
                $etiquetasRestantes = $paqueteActual->etiquetas()->count() - 1; // -1 porque aún no se ha removido

                \App\Services\ProductionLogger::logEliminarEtiquetaPaquete(
                    $paqueteActual,
                    $etiqueta,
                    $pesoAnteriorPaqueteOrigen,
                    $etiquetasRestantes,
                    auth()->user()
                );
            }

            // Si ya está en este paquete, informar
            if ($etiqueta->paquete_id === $paquete->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La etiqueta ya está en este paquete'
                ], 400);
            }

            // Validar estado de la etiqueta
            if (in_array(strtolower($etiqueta->estado), ['pendiente'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede añadir una etiqueta completada a un paquete'
                ], 400);
            }

            // Guardar peso anterior para logs
            $pesoAnterior = $paquete->peso;

            // Asignar etiqueta al paquete
            $etiqueta->paquete_id = $paquete->id;
            $etiqueta->estado = 'en paquete';
            $etiqueta->save();

            // Actualizar peso del paquete
            $pesoEtiqueta = $etiqueta->peso ?? 0;
            $paquete->peso += $pesoEtiqueta;
            $paquete->save();

            // Registrar movimiento
            \App\Models\Movimiento::create([
                'tipo' => 'Movimiento paquete',
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                'paquete_id' => $paquete->id,
                'descripcion' => "Etiqueta {$codigoEtiqueta} añadida al paquete {$paquete->codigo}",
                'estado' => 'completado',
                'fecha_solicitud' => now(),
                'ejecutado_por' => auth()->id(),
            ]);

            // 📊 LOG DE PRODUCCIÓN EN CSV - Añadir etiqueta a paquete
            \App\Services\ProductionLogger::logAñadirEtiquetaPaquete(
                $paquete,
                $etiqueta,
                $pesoAnterior,
                auth()->user()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Etiqueta añadida correctamente al paquete {$paquete->codigo}",
                'paquete' => [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                    'peso' => $paquete->peso
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Paquete no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al añadir etiqueta a paquete', [
                'paquete_id' => $paqueteId,
                'etiqueta_codigo' => $request->etiqueta_codigo ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al añadir la etiqueta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una etiqueta de un paquete
     * 
     * DELETE /api/paquetes/{paqueteId}/eliminar-etiqueta
     * Body: { "etiqueta_codigo": "2025-004512.1.1" }
     */
    public function eliminarEtiquetaDePaquete(Request $request, $paqueteId)
    {
        $request->validate([
            'etiqueta_codigo' => 'required|string'
        ]);

        DB::beginTransaction();

        try {
            $paquete = Paquete::findOrFail($paqueteId);
            $codigoEtiqueta = trim($request->etiqueta_codigo);

            // Buscar la etiqueta
            $etiqueta = Etiqueta::where('etiqueta_sub_id', $codigoEtiqueta)
                ->where('paquete_id', $paquete->id)
                ->first();

            if (!$etiqueta) {
                return response()->json([
                    'success' => false,
                    'message' => 'La etiqueta no se encuentra en este paquete'
                ], 404);
            }

            // Guardar peso antes de desasociar
            $pesoEtiqueta = $etiqueta->peso ?? 0;

            // Desasociar etiqueta del paquete
            $etiqueta->paquete_id = null;
            $etiqueta->estado = 'pendiente'; // Volver a estado pendiente
            $etiqueta->save();

            // Actualizar peso del paquete
            $paquete->peso = max(0, $paquete->peso - $pesoEtiqueta);
            $paquete->save();

            // Verificar si el paquete quedó vacío
            $etiquetasRestantes = Etiqueta::where('paquete_id', $paquete->id)->count();

            if ($etiquetasRestantes === 0) {
                // Opcionalmente eliminar el paquete vacío
                Log::warning("Paquete {$paquete->codigo} quedó sin etiquetas después de eliminar {$codigoEtiqueta}");
            }

            // Registrar movimiento
            \App\Models\Movimiento::create([
                'tipo' => 'Movimiento paquete',
                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                'descripcion' => "Etiqueta {$codigoEtiqueta} eliminada del paquete {$paquete->codigo}",
                'estado' => 'completado',
                'fecha_solicitud' => now(),
                'ejecutado_por' => auth()->id(),
            ]);

            // 📊 LOG DE PRODUCCIÓN EN CSV - Eliminar etiqueta de paquete
            \App\Services\ProductionLogger::logEliminarEtiquetaPaquete(
                $paquete,
                $etiqueta,
                $pesoAnterior,
                $etiquetasRestantes,
                auth()->user()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Etiqueta eliminada correctamente del paquete",
                'paquete' => [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                    'peso' => $paquete->peso,
                    'etiquetas_restantes' => $etiquetasRestantes
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Paquete no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar etiqueta de paquete', [
                'paquete_id' => $paqueteId,
                'etiqueta_codigo' => $request->etiqueta_codigo ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la etiqueta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un paquete completo y libera sus etiquetas
     * API endpoint para eliminar desde la interfaz sin recargar
     */
    public function eliminarPaquete($paqueteId)
    {
        DB::beginTransaction();

        try {
            $paquete = Paquete::findOrFail($paqueteId);

            Log::info('Eliminando paquete completo', [
                'paquete_id' => $paquete->id,
                'codigo' => $paquete->codigo,
                'usuario' => auth()->user()->nombre_completo ?? 'desconocido'
            ]);

            // 📊 Obtener IDs de etiquetas para logs (antes de liberarlas)
            $etiquetasIds = Etiqueta::where('paquete_id', $paquete->id)
                ->pluck('etiqueta_sub_id')
                ->toArray();

            // Liberar todas las etiquetas del paquete (solo quitar paquete_id, mantener estado)
            $etiquetasLiberadas = Etiqueta::where('paquete_id', $paquete->id)
                ->update([
                    'paquete_id' => null
                ]);

            // Eliminar movimientos pendientes asociados al paquete
            \App\Models\Movimiento::where('paquete_id', $paquete->id)
                ->where('estado', 'pendiente')
                ->delete();

            // Registrar movimiento de eliminación
            \App\Models\Movimiento::create([
                'tipo' => 'Movimiento paquete',
                'descripcion' => "Paquete {$paquete->codigo} eliminado completamente. {$etiquetasLiberadas} etiquetas liberadas",
                'estado' => 'completado',
                'fecha_solicitud' => now(),
                'ejecutado_por' => auth()->id(),
                'paquete_id' => null, // Ya que el paquete va a ser eliminado
            ]);

            // Eliminar el paquete
            $codigoPaquete = $paquete->codigo;

            // 📊 LOG DE PRODUCCIÓN EN CSV - Eliminar paquete completo
            \App\Services\ProductionLogger::logEliminarPaquete(
                $paquete,
                $etiquetasLiberadas,
                $etiquetasIds,
                auth()->user()
            );

            $paquete->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Paquete {$codigoPaquete} eliminado correctamente. {$etiquetasLiberadas} etiquetas liberadas",
                'etiquetas_liberadas' => $etiquetasLiberadas
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Paquete no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar paquete completo', [
                'paquete_id' => $paqueteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el paquete: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene todos los elementos de un paquete con sus etiquetas
     * Para mostrar en el modal de "Ver elementos"
     */
    public function getElementos($paqueteId)
    {
        try {
            $paquete = \App\Models\Paquete::with([
                'etiquetas.elementos' => function ($query) {
                    $query->orderBy('id');
                }
            ])->findOrFail($paqueteId);

            // Agrupar por etiquetas
            $etiquetas = [];

            foreach ($paquete->etiquetas as $etiqueta) {
                $elementosEtiqueta = [];
                foreach ($etiqueta->elementos as $elemento) {
                    $elementosEtiqueta[] = [
                        'id' => $elemento->id,
                        'codigo' => $elemento->codigo,
                        'dimensiones' => $elemento->dimensiones,
                        'peso_kg' => $elemento->peso_kg,
                        'diametro' => $elemento->diametro,
                        'barras' => $elemento->barras,
                    ];
                }

                $etiquetas[] = [
                    'id' => $etiqueta->id,
                    'codigo' => $etiqueta->codigo ?? $etiqueta->etiqueta_sub_id,
                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                    'nombre' => $etiqueta->nombre,
                    'peso' => $etiqueta->peso,
                    'marca' => $etiqueta->marca,
                    'elementos' => $elementosEtiqueta,
                    'cantidad_elementos' => count($elementosEtiqueta),
                ];
            }

            return response()->json([
                'success' => true,
                'paquete' => [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                ],
                'etiquetas' => $etiquetas,
                'total_etiquetas' => count($etiquetas),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener etiquetas: ' . $e->getMessage()
            ], 500);
        }
    }
}
