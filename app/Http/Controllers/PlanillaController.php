<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Etiqueta;
use App\Models\Obra;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use App\Imports\PlanillaImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Services\AsignacionMaquinaIAService;
use App\Models\OrdenPlanilla;
use App\Services\PlanillaService;
use ZipArchive;
use DOMDocument;
use App\Services\ColaPlanillasService;
use App\Services\AsignarMaquinaService;
use App\Services\PlanillaImport\PlanillaImportService;
use App\Services\OrdenPlanillaService;
use Illuminate\Support\Facades\Schema;
use App\Services\PlanillaColaService;
use App\Services\ImportProgress;

class PlanillaController extends Controller
{
    private AsignarMaquinaService $asignador;
    private OrdenPlanillaService $ordenService; // ✅ Nueva inyección

    public function __construct(
        AsignarMaquinaService $asignador,
        OrdenPlanillaService $ordenService // ✅ Nueva inyección
    ) {
        $this->asignador = $asignador;
        $this->ordenService = $ordenService;
    }
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('nombre_completo')) {
            $usuario = User::where(DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"), 'like', '%' . $request->nombre_completo . '%')->first();

            $filtros[] = 'Responsable: <strong>' . ($usuario?->nombre_completo ?? $request->nombre_completo) . '</strong>';
        }


        if ($request->filled('codigo')) {
            $filtros[] = 'Código Planilla: <strong>' . $request->codigo . '</strong>';
        }

        if ($request->filled('codigo_cliente')) {
            $filtros[] = 'Código cliente: <strong>' . $request->codigo_cliente . '</strong>';
        }

        if ($request->filled('cliente')) {
            $filtros[] = 'Cliente: <strong>' . $request->cliente . '</strong>';
        }

        if ($request->filled('cod_obra')) {
            $filtros[] = 'Código obra: <strong>' . $request->cod_obra . '</strong>';
        }

        if ($request->filled('nom_obra')) {
            $filtros[] = 'Obra: <strong>' . $request->nom_obra . '</strong>';
        }


        if ($request->filled('seccion')) {
            $filtros[] = 'Sección: <strong>' . $request->seccion . '</strong>';
        }

        if ($request->filled('descripcion')) {
            $filtros[] = 'Descripción: <strong>' . $request->descripcion . '</strong>';
        }

        if ($request->filled('ensamblado')) {
            $filtros[] = 'Ensamblado: <strong>' . $request->ensamblado . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }

        if ($request->filled('fecha_finalizacion')) {
            $filtros[] = 'Fecha finalización: <strong>' . $request->fecha_finalizacion . '</strong>';
        }
        if ($request->filled('fecha_importacion')) {
            $filtros[] = 'Fecha importación: <strong>' . $request->fecha_importacion . '</strong>';
        }
        if ($request->filled('fecha_estimada_entrega')) {
            $filtros[] = 'Fecha estimada entrega: <strong>' . $request->fecha_estimada_entrega . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'fecha_estimada_entrega' => 'Entrega estimada',
                'estado' => 'Estado',
                'seccion' => 'Sección',
                'peso_total' => 'Peso total',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }

        return $filtros;
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
        $sortBy = $request->input('sort', 'created_at');
        $order  = $request->input('order', 'desc');

        // aplicarOrdenamiento(): columnasPermitidas
        $columnasPermitidas = [
            'codigo',
            'codigo_cliente',
            'cliente',
            'cod_obra',
            'nom_obra',
            'seccion',
            'descripcion',
            'ensamblado',
            'comentario',
            'peso_fabricado',
            'peso_total',
            'estado',
            'revisada',
            'fecha_inicio',
            'fecha_finalizacion',
            'fecha_importacion',
            'fecha_estimada_entrega',
            'nombre_completo',
            'created_at',
        ];


        if (!in_array($sortBy, $columnasPermitidas, true)) {
            $sortBy = 'fecha_estimada_entrega'; // Fallback seguro
        }

        // Mapear fecha_importacion a created_at (la columna real en la BD)
        if ($sortBy === 'fecha_importacion') {
            $sortBy = 'created_at';
        }

        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $order);
    }

    //------------------------------------------------------------------------------------ FILTROS
    public function aplicarFiltros($query, Request $request)
    {
        // Filtro por usuario
        if ($request->filled('nombre_completo')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(users.name, ' ', users.primer_apellido, ' ', users.segundo_apellido)"), 'like', '%' . $request->nombre_completo . '%');
            });
        }


        // Filtro por códigos parciales separados por comas
        if ($request->filled('codigo')) {
            $codigos = array_filter(
                array_map('trim', explode(',', $request->codigo))
            );

            $query->where(function ($q) use ($codigos) {
                foreach ($codigos as $codigo) {
                    $codigo = trim($codigo);

                    // 1) Solo dígitos -> contains
                    if (preg_match('/^\d+$/', $codigo)) {
                        $q->orWhere('codigo', 'like', "%{$codigo}%");
                        continue;
                    }

                    // 2) Dígitos seguidos de guion (prefijo tipo "2025-") -> empieza por ese bloque + guion (pero con % por si hay prefijo como MP-)
                    if (preg_match('/^(\d+)-$/', $codigo, $m)) {
                        $izq = $m[1];
                        $q->orWhere('codigo', 'like', "%{$izq}-%");
                        continue;
                    }

                    // 3) Dígitos-guion-dígitos -> pad a 6 el bloque derecho
                    if (preg_match('/^(\d+)-(\d+)$/', $codigo, $m)) {
                        $izq = $m[1];
                        $derPadded = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                        // Usamos %...% para que matchee también códigos con prefijo: p.ej. MP-2024-008094
                        $q->orWhere('codigo', 'like', "%{$izq}-{$derPadded}%");
                        continue;
                    }

                    // 4) Cualquier otra cosa -> contains
                    $q->orWhere('codigo', 'like', '%' . $codigo . '%');
                }
            });
        }


        // Filtro por código del cliente
        if ($request->filled('codigo_cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->codigo_cliente . '%');
            });
        }

        // Filtro por nombre de cliente (empresa)
        if ($request->filled('cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('empresa', 'like', '%' . $request->cliente . '%');
            });
        }

        // Filtro por código de obra
        if ($request->filled('cod_obra')) {
            $query->whereHas('obra', function ($q) use ($request) {
                $q->where('cod_obra', 'like', '%' . $request->cod_obra . '%');
            });
        }

        // Filtro por nombre de obra
        if ($request->filled('nom_obra')) {
            $query->whereHas('obra', function ($q) use ($request) {
                $q->where('obra', 'like', '%' . $request->nom_obra . '%');
            });
        }

        // Filtros directos sobre columnas de la tabla planillas
        $query->when(
            $request->filled('seccion'),
            fn($q) =>
            $q->where('seccion', 'like', '%' . $request->seccion . '%')
        );

        $query->when(
            $request->filled('descripcion'),
            fn($q) =>
            $q->where('descripcion', 'like', '%' . $request->descripcion . '%')
        );

        $query->when(
            $request->filled('ensamblado'),
            fn($q) =>
            $q->where('ensamblado', 'like', '%' . $request->ensamblado . '%')
        );

        $query->when(
            $request->filled('estado'),
            fn($q) =>
            $q->where('estado', $request->estado)
        );

        // Fechas
        if ($request->filled('fecha_finalizacion')) {
            $query->whereDate('fecha_finalizacion', Carbon::parse($request->fecha_finalizacion)->format('Y-m-d'));
        }

        if ($request->filled('fecha_importacion')) {
            $query->whereDate('created_at', Carbon::parse($request->fecha_importacion)->format('Y-m-d'));
        }

        if ($request->filled('fecha_estimada_entrega')) {
            $query->whereDate(
                'fecha_estimada_entrega',
                Carbon::parse($request->fecha_estimada_entrega)->format('Y-m-d')
            );
        }

        // --- Revisada: whitelisting; no filtrar en "todas"/"seleccionar"
        if ($request->has('revisada')) {
            $raw = trim((string) $request->input('revisada'));

            // Normaliza acentos y mayúsculas
            $val = mb_strtolower($raw, 'UTF-8');

            // Acepta equivalentes
            $mapTrue  = ['1', 'si', 'sí', 'true', 'on'];
            $mapFalse = ['0', 'no', 'false', 'off'];

            if (in_array($val, $mapTrue, true)) {
                $request->merge(['revisada' => '1']);
                $query->where('revisada', 1);
            } elseif (in_array($val, $mapFalse, true)) {
                $request->merge(['revisada' => '0']);
                $query->where('revisada', 0);
            } else {
                // "todas", "seleccionar", vacío, etc. -> NO filtrar
                $request->request->remove('revisada');
            }
        }





        return $query;
    }

    //------------------------------------------------------------------------------------ INDEX()
    public function index(Request $request)
    {
        // Retornar vista Livewire
        return view('planillas.index');
    }

    /**
     * Marca una planilla como revisada
     */
    public function marcarRevisada(Request $request, Planilla $planilla)
    {
        $planilla->update([
            'revisada' => true,
            'revisada_por_id' => auth()->id(),
            'revisada_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Planilla marcada como revisada correctamente',
                'revisada_por' => auth()->user()->name,
                'revisada_at' => now()->format('d/m/Y H:i'),
            ]);
        }

        return redirect()->back()->with('success', 'Planilla marcada como revisada correctamente');
    }

    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        $planilla = Planilla::with([
            'obra:id,obra',
            'cliente:id,empresa',
            // No restringimos columnas de etiquetas/elementos para evitar problemas con accessors
            'etiquetas',
            'elementos.maquina:id,nombre,tipo',
            'entidades',
            'etiquetasEnsamblaje.entidad', // Etiquetas de ensamblaje con su entidad
        ])->findOrFail($id);

        // Generar etiquetas de ensamblaje automáticamente si hay entidades pero no etiquetas
        if ($planilla->entidades->isNotEmpty() && $planilla->etiquetasEnsamblaje->isEmpty()) {
            $service = app(\App\Services\EtiquetaEnsamblajeService::class);
            $service->generarParaPlanilla($planilla);
            // Recargar la relación
            $planilla->load('etiquetasEnsamblaje.entidad');
        }

        // ------ Color por estado (igual que antes)
        $getColor = fn($s) => match (strtolower(trim($s ?? ''))) {
            'fabricado'  => 'bg-green-200',
            'pendiente'  => 'bg-red-200',
            'fabricando' => 'bg-blue-200',
            default      => 'bg-gray-200',
        };

        // Clonamos elementos y añadimos color
        $elementos = $planilla->elementos->map(function ($e) use ($getColor) {
            $e->color = $getColor($e->estado);
            return $e;
        });

        // ------ Progreso
        $progreso = round(
            min(100, ($elementos->where('estado', 'fabricado')->sum('peso') / max(1, ($planilla->peso_total ?? 0))) * 100),
            2
        );

        // =========================================================
        // A) Agrupación para la vista: máquinas => etiquetas (sub_id)
        // =========================================================
        $porMaquina = $elementos->groupBy(fn($e) => $e->maquina_id ?? 'sin');

        $etiquetasPorMaquina = $porMaquina->map(function ($elemsDeMaquina) use ($planilla) {
            return $elemsDeMaquina
                ->groupBy('etiqueta_sub_id')
                ->map(function ($grupo, $subId) use ($planilla) {
                    // Buscamos la etiqueta real por sub_id
                    $etiqueta = $planilla->etiquetas->firstWhere('etiqueta_sub_id', $subId)
                        ?? (object)[
                            'id'              => null,              // en la vista se ignoran sin id
                            'etiqueta_sub_id' => $subId,
                            'estado'          => $grupo->first()?->estado ?? 'pendiente',
                            // NO es columna; solo info de ayuda si se usa:
                            'peso_kg'         => (float) $grupo->sum('peso'),
                            'nombre'          => null,
                            'codigo'          => null,
                        ];

                    return [
                        'etiqueta'  => $etiqueta,
                        'elementos' => $grupo->values(),
                    ];
                })
                ->values();
        });

        // Mapa de máquinas para render (incluye “Sin máquina” como cabecera)
        $maquinas = collect([
            'sin' => (object) ['id' => null, 'nombre' => 'Sin máquina', 'tipo' => 'normal'],
        ])->merge(
            $elementos->pluck('maquina')->filter()->keyBy('id') // solo máquinas que realmente aparecen
        );

        // =========================================================
        // B) Datasets “como máquinas” para canvasMaquina.js
        // =========================================================
        // Pesos por elemento (plano)
        $pesosElementos = $elementos
            ->map(fn($e) => ['id' => $e->id, 'peso' => $e->peso])
            ->values()
            ->toArray();

        // Orden natural por etiqueta_sub_id
        $ordenSub = function ($grupo, $subId) {
            if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                return sprintf('%s-%010d', $m[1], (int) $m[2]);
            }
            return $subId . '-0000000000';
        };

        // Dataset compacto por subetiqueta (el que suelen leer tus scripts)
        $etiquetasData = $elementos
            ->filter(fn($e) => !empty($e->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub)
            ->map(fn($grupo, $subId) => [
                'codigo'    => (string) $subId,                  // subId (ej: ETQ25090001.01)
                'elementos' => $grupo->pluck('id')->toArray(),   // ids de elementos
                'pesoTotal' => $grupo->sum('peso'),              // sumatorio
            ])
            ->values();

        // Colección agrupada (por si la usas en Blade o en otros JS)
        // Colección agrupada (por si la usas en Blade o en otros JS)
        $elementosAgrupados = $elementos
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub);

        // Payload “rico” para scripts (idéntico al de máquinas)
        $elementosAgrupadosScript = $elementosAgrupados->map(fn($grupo) => [
            'etiqueta'  => $planilla->etiquetas->firstWhere('etiqueta_sub_id', $grupo->first()?->etiqueta_sub_id),
            'planilla'  => $planilla,
            'elementos' => $grupo->map(fn($e) => [
                'id'          => $e->id,
                'codigo'      => $e->codigo ?? null,
                'dimensiones' => $e->dimensiones ?? null,
                'estado'      => $e->estado,
                'peso'        => $e->peso_kg ?? $e->peso,       // usa accessor si existe
                'diametro'    => $e->diametro_mm ?? $e->diametro,
                'longitud'    => $e->longitud_cm ?? ($e->longitud ?? null),
                'barras'      => $e->barras ?? null,
                'figura'      => $e->figura ?? null,
            ])->values(),
        ])->values();

        // Etiquetas de ensamblaje para la sección de ensamblajes
        $etiquetasEns = $planilla->etiquetasEnsamblaje;

        return view('planillas.show', compact(
            'planilla',
            'progreso',
            'maquinas',
            'etiquetasPorMaquina',
            'etiquetasEns',
            // datasets para canvas/JS (mismo shape que en máquinas)
            'pesosElementos',
            'etiquetasData',
            'elementosAgrupados',
            'elementosAgrupadosScript',
        ));
    }

    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        return view('planillas.create');
    }

    //------------------------------------------------------------------------------------ IMPORT()

    // Método import() actualizado con nombre de archivo
    public function import(Request $request, PlanillaImportService $importService)
    {
        abort_unless(auth()->check() && auth()->user()->rol === 'oficina', 403);

        try {
            $validated = $request->validate([
                'file'             => 'required|file|mimes:xlsx,xls',
                'fecha_aprobacion' => 'required|date',
                'import_id'        => 'required|string',
            ]);

            $file            = $validated['file'];
            $nombreArchivo   = $file->getClientOriginalName();
            $fechaAprobacion = \Illuminate\Support\Carbon::parse($validated['fecha_aprobacion'])->startOfDay();
            $importId        = $validated['import_id'];

            ImportProgress::init($importId, 1, 'Leyendo archivo...');

            $resultado = $importService->importar($file, fechaAprobacion: $fechaAprobacion, importId: $importId);

            if ($request->ajax()) {
                // Preparar respuesta detallada con información de errores
                $responseData = [
                    'success' => $resultado->esExitoso(),
                    'message' => $resultado->mensaje(),
                    'errors' => [],
                    'warnings' => [],
                    'statistics' => [
                        'exitosas' => 0,
                        'fallidas' => 0,
                        'elementos_creados' => 0,
                        'etiquetas_creadas' => 0,
                    ],
                    'nombre_archivo' => $nombreArchivo
                ];

                // Usar los métodos getter de ImportResult
                $exitosas = $resultado->exitosas();
                $fallidas = $resultado->fallidas();
                $advertencias = $resultado->advertencias();
                $estadisticas = $resultado->estadisticas();

                // Si hubo fallos, incluir detalles completos
                if (!empty($fallidas)) {
                    $responseData['errors'] = array_map(function ($fallida) {
                        return [
                            'codigo' => is_array($fallida) ? ($fallida['codigo'] ?? 'Desconocido') : 'Desconocido',
                            'error' => is_array($fallida) ? ($fallida['error'] ?? 'Error no especificado') : $fallida
                        ];
                    }, $fallidas);
                }

                // Incluir advertencias si las hay
                if (!empty($advertencias)) {
                    $responseData['warnings'] = $advertencias;
                }

                // Incluir estadísticas
                $responseData['statistics'] = [
                    'exitosas' => is_array($exitosas) ? count($exitosas) : 0,
                    'fallidas' => is_array($fallidas) ? count($fallidas) : 0,
                    'elementos_creados' => isset($estadisticas['elementos_creados']) ? $estadisticas['elementos_creados'] : 0,
                    'etiquetas_creadas' => isset($estadisticas['etiquetas_creadas']) ? $estadisticas['etiquetas_creadas'] : 0,
                ];

                // Log para debug
                Log::info('Import response data:', [
                    'statistics' => $responseData['statistics'],
                    'exitosas_array' => $exitosas,
                    'estadisticas_raw' => $estadisticas
                ]);

                return response()->json($responseData, $resultado->esExitoso() ? 200 : 422);
            }

            // (opcional) camino de fallback para navegadores sin fetch
            if ($resultado->esExitoso()) {
                $redirect = redirect()
                    ->route('planillas.index')
                    ->with('success', $resultado->mensaje())
                    ->with('import_report', true)
                    ->with('nombre_archivo', $nombreArchivo);

                if ($resultado->tieneAdvertencias()) {
                    $redirect->with('tiene_advertencias', true);
                }

                return $redirect;
            }

            return back()->with('error', $resultado->mensaje())->with('nombre_archivo', $nombreArchivo);
        } catch (\Throwable $e) {
            // ¡Muy útil en dev para ver el motivo del 500!
            ImportProgress::setError($request->input('import_id', 'n/a'), $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(), // en prod puedes poner mensaje genérico
                    'errors' => [['codigo' => 'SISTEMA', 'error' => $e->getMessage()]]
                ], 500);
            }

            return back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }


    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    /**
     * Reimporta una planilla existente eliminando solo elementos pendientes.
     * 
     * - Usa el sistema de importación moderno (ExcelReader, PlanillaProcessor)
     * - Mantiene fecha de entrega y valores originales de la planilla
     * - Solo elimina elementos en estado 'pendiente'
     *
     * @param Request $request
     * @param Planilla $planilla
     * @param PlanillaImportService $importService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reimportar(Request $request, Planilla $planilla, PlanillaImportService $importService)
    {
        // 1️⃣ Seguridad
        abort_unless(auth()->check() && auth()->user()->rol === 'oficina', 403);

        // 2️⃣ Validación del archivo
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.mimes'    => 'El archivo debe ser .xlsx o .xls',
        ]);

        $file = $request->file('archivo');
        $nombreArchivo = $file->getClientOriginalName();

        // 3️⃣ Delegar todo al servicio de reimportación
        try {
            $resultado = $importService->reimportar($file, $planilla);

            if ($resultado->esExitoso()) {
                $redirect = redirect()
                    ->route('planillas.index')
                    ->with('success', $resultado->mensaje())
                    ->with('import_report', true)
                    ->with('nombre_archivo', $nombreArchivo);

                if ($resultado->tieneAdvertencias()) {
                    $redirect->with('tiene_advertencias', true);
                }

                return $redirect;
            } else {
                return redirect()
                    ->back()
                    ->with('error', $resultado->mensaje())
                    ->with('nombre_archivo', $nombreArchivo);
            }
        } catch (\Throwable $e) {
            Log::error('❌ Error en reimportación de planilla', [
                'planilla' => $planilla->codigo,
                'archivo' => $nombreArchivo,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error durante la reimportación: ' . $e->getMessage())
                ->with('nombre_archivo', $nombreArchivo);
        }
    }

    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'cod_obra' => 'required|string|max:255',
                'cod_cliente' => 'required|string|max:255',
                'cliente' => 'required|string|max:255',
                'nom_obra' => 'required|string|max:255',
                'seccion' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'ensamblado' => 'required|string|max:255',
                'codigo' => 'required|string|max:255',
                'peso_total' => 'required|numeric|min:0',
            ]);

            Planilla::create($validated);

            return redirect()->route('planillas.index')->with('success', 'Planilla creada exitosamente.');
        } catch (ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ EDIT()
    public function edit($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $planilla = Planilla::findOrFail($id);  // Encuentra la planilla por su ID

        return view('planillas.edit', compact('planilla'));
    }

    public function updateX(Request $request, $id)
    {

        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            $planilla = Planilla::findOrFail($id);

            // Actualizar la planilla con datos limpios
            $planilla->update([
                'cod_obra' => $request->cod_obra,
                'cod_cliente' => $request->cod_cliente,
                'cliente' => $request->cliente,
                'nom_obra' => $request->nom_obra,
                'seccion' => $request->seccion,
                'descripcion' => $request->descripcion,
                'ensamblado' => $request->ensamblado,
                'codigo' => $request->codigo,
                'peso_total' => $request->peso_total,
                'tiempo_fabricacion' => 0,
            ]);

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('planillas.index')->with('success', 'Planilla actualizada');
        } catch (ValidationException $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $planilla = Planilla::findOrFail($id);

            // Fechas vacías -> null
            $request->merge([
                'fecha_inicio'           => $request->fecha_inicio ?: null,
                'fecha_estimada_entrega' => $request->fecha_estimada_entrega ?: null,
                'fecha_finalizacion'     => $request->fecha_finalizacion ?: null,
                'fecha_importacion'      => $request->fecha_importacion ?: null,
            ]);

            // ✅ Nuevo: normalizar checkbox "revisada" (acepta on/true/1)
            if ($request->has('revisada')) {
                $request->merge([
                    'revisada' => filter_var($request->input('revisada'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }

            $validatedData = $request->validate([
                'codigo'                 => 'required|string|max:50',
                'cliente_id'             => 'nullable|integer|exists:clientes,id',
                'obra_id'                => 'nullable|integer|exists:obras,id',
                'seccion'                => 'nullable|string|max:100',
                'descripcion'            => 'nullable|string',
                'ensamblado'             => 'nullable|string|max:100',
                'comentario'             => 'nullable|string|max:255',
                'peso_fabricado'         => 'nullable|numeric',
                'peso_total'             => 'nullable|numeric',
                'estado'                 => 'nullable|string|in:pendiente,fabricando,completada',
                'fecha_inicio'           => 'nullable|date_format:d/m/Y H:i',
                'fecha_finalizacion'     => 'nullable|date_format:d/m/Y H:i',
                'fecha_estimada_entrega' => 'nullable|date_format:d/m/Y H:i',
                'fecha_importacion'      => 'nullable|date_format:d/m/Y',
                'usuario'                => 'nullable|string|max:100',
                // ✅ Nuevo:
                'revisada'               => 'nullable|boolean',
            ], [
                'codigo.required'     => 'El campo Código es obligatorio.',
                'codigo.string'       => 'El campo Código debe ser una cadena de texto.',
                'codigo.max'          => 'El campo Código no debe exceder 50 caracteres.',
                'cliente_id.integer'  => 'El campo cliente_id debe ser un número entero.',
                'cliente_id.exists'   => 'El cliente especificaoa en cliente_id no existe.',
                'obra_id.integer'     => 'El campo obra_id debe ser un número entero.',
                'obra_id.exists'      => 'La obra especificada en obra_id no existe.',
                'seccion.string'      => 'El campo Sección debe ser una cadena de texto.',
                'seccion.max'         => 'El campo Sección no debe exceder 100 caracteres.',
                'descripcion.string'  => 'El campo Descripción debe ser una cadena de texto.',
                'ensamblado.string'   => 'El campo Ensamblado debe ser una cadena de texto.',
                'ensamblado.max'      => 'El campo Ensamblado no debe exceder 100 caracteres.',
                'comentario.string'   => 'El campo Comentario debe ser una cadena de texto.',
                'comentario.max'      => 'El campo Comentario no debe exceder 255 caracteres.',
                'peso_fabricado.numeric' => 'El campo Peso Fabricado debe ser un número.',
                'peso_total.numeric'     => 'El campo Peso Total debe ser un número.',
                'estado.in'              => 'El campo Estado debe ser: pendiente, fabricando o completada.',
                'fecha_inicio.date_format'           => 'El campo Fecha Inicio no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_finalizacion.date_format'     => 'El campo Fecha Finalización no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_estimada_entrega.date_format' => 'El campo Fecha Estimada de Entrega no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_importacion.date_format'      => 'El campo Fecha de Importación no corresponde al formato DD/MM/YYYY.',
                'usuario.string'        => 'El campo Usuario debe ser una cadena de texto.',
                'usuario.max'           => 'El campo Usuario no debe exceder 100 caracteres.',
                // ✅ Nuevo:
                'revisada.boolean'      => 'El campo Revisada debe ser booleano.',
            ]);

            // Validación: obra pertenece al cliente
            if (!empty($validatedData['obra_id']) && !empty($validatedData['cliente_id'])) {
                $obra = Obra::find($validatedData['obra_id']);
                if ($obra && $obra->cliente_id != $validatedData['cliente_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La obra seleccionada no pertenece al cliente seleccionado.'
                    ], 422);
                }
            }

            // Fechas -> formato BD
            if (!empty($validatedData['fecha_inicio'])) {
                $validatedData['fecha_inicio'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_inicio'])->format('Y-m-d H:i:s');
            }
            if (!empty($validatedData['fecha_finalizacion'])) {
                $validatedData['fecha_finalizacion'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_finalizacion'])->format('Y-m-d H:i:s');
            }
            if (!empty($validatedData['fecha_estimada_entrega'])) {
                $validatedData['fecha_estimada_entrega'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_estimada_entrega'])->format('Y-m-d H:i:s');
            }

            // ✅ Lógica de revisión (quién/cuándo)
            if (array_key_exists('revisada', $validatedData)) {
                $revisada = (bool)$validatedData['revisada'];

                if ($revisada) {
                    $validatedData['revisada_por_id'] = auth()->id();
                    $validatedData['revisada_at']     = now();
                } else {
                    $validatedData['revisada_por_id'] = null;
                    $validatedData['revisada_at']     = null;
                }
            }

            $planilla->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Planilla actualizada correctamente',
                'data'    => $planilla->codigo_limpio
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Planilla no encontrada'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la planilla. Intente nuevamente. ' . $e->getMessage()
            ], 500);
        }
    }

    //------------------------------------------------------------------------------------ DESTROY()

    public function destroy($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')
                ->with('abort', 'No tienes los permisos necesarios.');
        }

        DB::beginTransaction();
        try {
            $planilla = Planilla::with('elementos')->findOrFail($id);

            // 1) Máquinas afectadas antes de borrar ordenes
            $maquinasAfectadas = $this->ordenService->obtenerMaquinasAfectadas([$planilla->id]);

            // 2) Borrar orden_planillas de esta planilla
            $this->ordenService->eliminarOrdenDePlanilla($planilla->id);

            // 3) Borrar elementos asociados (si no hay ON DELETE CASCADE)
            $planilla->elementos()->delete();

            // (Opcional recomendado) borrar etiquetas huérfanas de la planilla
            if (method_exists($planilla, 'etiquetas')) {
                $planilla->etiquetas()->delete();
            }

            // 4) Borrar la planilla
            $planilla->delete();

            // 5) Recalcular posiciones por máquina afectada
            $this->ordenService->recalcularOrdenDeMaquinas($maquinasAfectadas);

            DB::commit();
            return redirect()->route('planillas.index')
                ->with('success', 'Planilla eliminada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Ocurrió un error al eliminar la planilla: ' . $e->getMessage());
        }
    }

    // ✅ REEMPLAZAR método destroyMultiple
    public function destroyMultiple(Request $request)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')
                ->with('abort', 'No tienes los permisos necesarios.');
        }

        $ids = $request->input('seleccionados', []);
        Log::info('🗑️ IDs recibidos para eliminar múltiples:', $ids);

        if (empty($ids)) {
            return redirect()->route('planillas.index')
                ->with('error', 'No seleccionaste ninguna planilla.');
        }

        DB::beginTransaction();
        try {
            $planillas = Planilla::with('elementos')->whereIn('id', $ids)->get();

            if ($planillas->isEmpty()) {
                DB::rollBack();
                return redirect()->route('planillas.index')
                    ->with('error', 'No se encontraron planillas para eliminar.');
            }

            // 1) Máquinas afectadas antes de borrar ordenes
            $maquinasAfectadas = $this->ordenService->obtenerMaquinasAfectadas(
                $planillas->pluck('id')->all()
            );

            // 2) Borrar orden_planillas de todas esas planillas
            foreach ($planillas->pluck('id') as $planillaId) {
                $this->ordenService->eliminarOrdenDePlanilla($planillaId);
            }

            // 3) Borrar elementos en bloque
            Elemento::whereIn('planilla_id', $planillas->pluck('id'))->delete();

            // (Opcional recomendado) borrar etiquetas de esas planillas
            if (class_exists(\App\Models\Etiqueta::class)) {
                Etiqueta::whereIn('planilla_id', $planillas->pluck('id'))->delete();
            }

            // 4) Borrar las planillas
            Planilla::whereIn('id', $planillas->pluck('id'))->delete();

            // 5) Recalcular posiciones por máquina afectada
            $this->ordenService->recalcularOrdenDeMaquinas($maquinasAfectadas);

            DB::commit();
            return redirect()->route('planillas.index')
                ->with('success', 'Planillas eliminadas correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Ocurrió un error al eliminar las planillas: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ COMPLETAR PLANILLA()
    public function completar(Request $request, PlanillaService $ordenPlanillaService)
    {
        // ✅ Validamos que exista la planilla
        $request->validate([
            'id' => 'required|integer|exists:planillas,id',
        ]);

        // ✅ Llamamos al service
        $resultado = $ordenPlanillaService->completarPlanilla($request->id);

        // ✅ Respondemos según resultado
        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'message' => $resultado['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultado['message'] ?? 'Error desconocido',
        ], 422);
    }

    public function completarTodas(Request $request, PlanillaService $svc)
    {
        $resultado = $svc->completarTodasPlanillas();
        $mensaje = "Procesadas OK: {$resultado['procesadas_ok']} | Omitidas por fecha: {$resultado['omitidas_fecha']} | Fallidas: {$resultado['fallidas']}";

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success'  => $resultado['success'],
                'message'  => $mensaje,
                'detalles' => $resultado,
            ], $resultado['success'] ? 200 : 422);
        }

        return back()->with(
            $resultado['success'] ? 'success' : 'error',
            $mensaje
        );
    }

    public function informacionMasiva(Request $request)
    {
        try {
            $ids = collect(explode(',', (string) $request->query('ids', '')))
                ->map(fn($x) => (int) trim($x))
                ->filter();

            if ($ids->isEmpty()) {
                return response()->json(['planillas' => []]);
            }

            // No incluimos codigo_limpio en el select porque es un accessor
            $planillas = Planilla::query()
                ->whereIn('id', $ids->all())
                ->select(['id', 'codigo', 'fecha_estimada_entrega', 'obra_id', 'seccion', 'descripcion', 'peso_total'])
                ->with([
                    'obra:id,cod_obra,obra',
                    'elementos:id,planilla_id,codigo,marca,diametro,longitud,barras,peso,dimensiones,fecha_entrega'
                ])
                ->get();

            $resultado = $planillas->map(function ($p) {
                // Fecha robusta (aunque sea string en DB)
                $fecha = null;
                if (!empty($p->fecha_estimada_entrega)) {
                    try {
                        // Forzar interpretación como DD/MM/YYYY en lugar de MM/DD/YYYY
                        $carbon = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $p->fecha_estimada_entrega);
                        $fecha = $carbon->format('Y-m-d');
                    } catch (\Throwable $e) {
                        // deja null si no se puede parsear
                    }
                }

                // Mapear obra usando los nombres reales
                $obra = null;
                if ($p->relationLoaded('obra') && $p->obra) {
                    $obra = [
                        'codigo' => $p->obra->cod_obra ?? null,
                        'nombre' => $p->obra->obra ?? null,
                    ];
                }

                // Mapear elementos
                $elementos = [];
                if ($p->relationLoaded('elementos')) {
                    $elementos = $p->elementos->map(function ($e) {
                        return [
                            'id' => $e->id,
                            'codigo' => $e->codigo,
                            'marca' => $e->marca,
                            'diametro' => $e->diametro,
                            'longitud' => $e->longitud,
                            'barras' => $e->barras,
                            'peso' => $e->peso,
                            'dimensiones' => $e->dimensiones,
                            'fecha_entrega' => $e->fecha_entrega ? $e->fecha_entrega->format('Y-m-d') : null,
                        ];
                    })->values()->all();
                }

                return [
                    'id'                         => $p->id,
                    // Usamos accessor codigo_limpio
                    'codigo'                     => $p->codigo_limpio ?? ('Planilla ' . $p->id),
                    'fecha_estimada_entrega'     => $fecha,
                    'obra'                       => $obra,
                    'seccion'                    => $p->seccion ?? null,
                    'descripcion'                => $p->descripcion ?? null,
                    'peso_total'                 => $p->peso_total ?? null,
                    'elementos'                  => $elementos,
                ];
            });

            return response()->json(['planillas' => $resultado]);
        } catch (\Throwable $e) {
            Log::error('[informacionMasiva] 500: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'Error interno'], 500);
        }
    }

    public function actualizarFechasMasiva(Request $request)
    {
        try {
            $data = $request->validate([
                'planillas' => ['required', 'array', 'min:1'],
                'planillas.*.id' => ['required', 'integer', 'exists:planillas,id'],
                'planillas.*.fecha_estimada_entrega' => ['nullable', 'date_format:Y-m-d'],
                'planillas.*.elementos' => ['nullable', 'array'],
                'planillas.*.elementos.*.id' => ['required_with:planillas.*.elementos', 'integer', 'exists:elementos,id'],
                'planillas.*.elementos.*.fecha_entrega' => ['nullable', 'date_format:Y-m-d'],
            ]);

            Log::info('[Planillas actualizarFechasMasiva] payload=', $data['planillas']);

            DB::transaction(function () use ($data) {
                foreach ($data['planillas'] as $fila) {
                    // Actualizar fecha de la planilla si se proporciona
                    if (!empty($fila['fecha_estimada_entrega'])) {
                        $planilla = Planilla::find($fila['id']);
                        $planilla->fecha_estimada_entrega = Carbon::createFromFormat('Y-m-d', $fila['fecha_estimada_entrega'])->startOfDay();
                        $planilla->save();
                    }

                    // Actualizar fechas de elementos si se proporcionan
                    if (!empty($fila['elementos']) && is_array($fila['elementos'])) {
                        foreach ($fila['elementos'] as $elementoData) {
                            $elemento = Elemento::find($elementoData['id']);
                            if ($elemento) {
                                $elemento->fecha_entrega = !empty($elementoData['fecha_entrega'])
                                    ? Carbon::createFromFormat('Y-m-d', $elementoData['fecha_entrega'])->startOfDay()
                                    : null;
                                $elemento->save();
                            }
                        }
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Fechas de entrega actualizadas correctamente.',
            ]);
        } catch (ValidationException $ve) {
            Log::warning('[Planillas actualizarFechasMasiva] validation:', $ve->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[Planillas actualizarFechasMasiva] error: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno',
            ], 500);
        }
    }

    public function importProgress(string $id)
    {
        return response()->json(ImportProgress::get($id));
    }

    /**
     * Resetear una planilla a su estado inicial
     * - Planilla: estado = pendiente, fechas = null, revisada = false
     * - Etiquetas: estado = pendiente, fechas = null, operarios = null
     * - Elementos: estado = pendiente, fechas = null, operarios = null, maquina_id = null
     * - Paquetes: eliminar todos
     * - OrdenPlanillas: eliminar y recrear usando los servicios
     */
    public function resetearPlanilla(Request $request, $id)
    {
        $planilla = Planilla::with(['elementos', 'etiquetas', 'paquetes'])->findOrFail($id);

        DB::beginTransaction();

        try {
            // Instanciar servicios
            $asignarMaquinaService = new \App\Services\AsignarMaquinaService();
            $ordenPlanillaService = new \App\Services\OrdenPlanillaService();

            // 1. Eliminar todos los paquetes de esta planilla
            $paquetesEliminados = $planilla->paquetes()->count();
            $planilla->paquetes()->delete();

            // 2. Resetear etiquetas
            $etiquetasReseteadas = $planilla->etiquetas()->count();
            $planilla->etiquetas()->update([
                'estado' => 'pendiente',
                'fecha_inicio' => null,
                'fecha_finalizacion' => null,
                'fecha_inicio_ensamblado' => null,
                'fecha_finalizacion_ensamblado' => null,
                'fecha_inicio_soldadura' => null,
                'fecha_finalizacion_soldadura' => null,
                'operario1_id' => null,
                'operario2_id' => null,
                'soldador1_id' => null,
                'soldador2_id' => null,
                'ensamblador1_id' => null,
                'ensamblador2_id' => null,
                'paquete_id' => null,
            ]);

            // 3. Eliminar orden_planillas existente (esto también limpia orden_planilla_id de elementos)
            $ordenPlanillaService->eliminarOrdenDePlanilla($planilla->id);

            // 4. Resetear elementos (incluyendo maquina_id para que el servicio los reasigne)
            // Nota: elementos NO tiene fecha_inicio/fecha_finalizacion ni operarios - eso está en etiquetas
            // Nota: etiqueta_id NO se resetea porque es la relación estructural con la etiqueta padre
            $elementosReseteados = $planilla->elementos()->count();
            $planilla->elementos()->update([
                'estado' => 'pendiente',
                'paquete_id' => null,
                'producto_id' => null,
                'producto_id_2' => null,
                'maquina_id' => null,
                'orden_planilla_id' => null,
            ]);

            // 5. Resetear la planilla
            $planilla->update([
                'estado' => 'pendiente',
                'fecha_inicio' => null,
                'fecha_finalizacion' => null,
                'revisada' => false,
                'revisada_por_id' => null,
                'revisada_at' => null,
            ]);

            // 6. Reasignar máquinas a los elementos usando AsignarMaquinaService
            $asignarMaquinaService->repartirPlanilla($planilla->id);

            // 7. Crear orden_planillas y asignar orden_planilla_id a elementos
            $ordenesCreadas = $ordenPlanillaService->crearOrdenParaPlanilla($planilla->id);

            // Obtener las máquinas asignadas para el resumen
            $maquinasAsignadas = OrdenPlanilla::where('planilla_id', $planilla->id)
                ->with('maquina:id,codigo')
                ->get()
                ->pluck('maquina.codigo')
                ->filter()
                ->toArray();

            DB::commit();

            Log::info('Planilla reseteada', [
                'planilla_id' => $planilla->id,
                'codigo' => $planilla->codigo,
                'paquetes_eliminados' => $paquetesEliminados,
                'etiquetas_reseteadas' => $etiquetasReseteadas,
                'elementos_reseteados' => $elementosReseteados,
                'ordenes_creadas' => $ordenesCreadas,
                'maquinas_asignadas' => $maquinasAsignadas,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Planilla {$planilla->codigo} reseteada correctamente",
                'detalles' => [
                    'paquetes_eliminados' => $paquetesEliminados,
                    'etiquetas_reseteadas' => $etiquetasReseteadas,
                    'elementos_reseteados' => $elementosReseteados,
                    'maquinas_asignadas' => $maquinasAsignadas,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al resetear planilla', [
                'planilla_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al resetear la planilla: ' . $e->getMessage()
            ], 500);
        }
    }
}
