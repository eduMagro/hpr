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
use App\Services\AutoReordenadorService;

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

        if ($request->filled('id')) {
            $filtros[] = 'ID: <strong>' . $request->id . '</strong>';
        }

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
        // Filtro por ID exacto
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

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
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No tienes los permisos necesarios.'], 403);
            }
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

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Planilla eliminada correctamente.']);
            }
            return redirect()->route('planillas.index')
                ->with('success', 'Planilla eliminada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
            }
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
        // Fecha de corte opcional (si no se proporciona, el servicio usa hoy)
        $fechaCorte = $request->input('fecha_corte');

        // Planilla específica opcional (si se proporciona, solo procesa esa)
        $planillaId = $request->input('planilla_id');
        $planillaIds = $planillaId ? [(int) $planillaId] : null;

        $resultado = $svc->completarTodasPlanillas($planillaIds, $fechaCorte);
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

    /**
     * Buscar planillas para autocompletado (modal completar planillas)
     */
    public function buscarParaCompletar(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $query = Planilla::query()
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->where('aprobada', true)
            ->with(['obra:id,obra', 'cliente:id,empresa']);

        // Solo aplicar filtro de búsqueda si hay texto
        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('codigo', 'like', "%{$busqueda}%")
                    ->orWhereHas('obra', function ($q2) use ($busqueda) {
                        $q2->where('obra', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('cliente', function ($q2) use ($busqueda) {
                        $q2->where('empresa', 'like', "%{$busqueda}%");
                    });
            });
        }

        $planillas = $query
            ->orderBy('fecha_estimada_entrega')
            ->limit(30)
            ->get(['id', 'codigo', 'obra_id', 'cliente_id', 'fecha_estimada_entrega', 'estado']);

        return response()->json([
            'planillas' => $planillas->map(fn($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'obra' => $p->obra->obra ?? '',
                'cliente' => $p->cliente->empresa ?? '',
                'fecha_entrega' => $p->fecha_estimada_entrega,
                'estado' => $p->estado,
            ])
        ]);
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

            $logsRegistrados = [];
            $eventosAEliminar = [];
            $fechasAfectadas = [];
            $planillasIds = collect($data['planillas'])->pluck('id')->toArray();

            // 1. Recopilar información ANTES de actualizar para saber qué eventos eliminar
            $planillasAntes = Planilla::with([
                    'obra:id,obra,cod_obra',
                    'elementos:id,planilla_id,fecha_entrega',
                    'paquetes:id,planilla_id',
                    'paquetes.salidas:id,fecha_salida'
                ])
                ->whereIn('id', $planillasIds)
                ->get();

            // Crear un mapa de planilla_id -> obra_id para uso posterior
            $planillaObraMap = [];
            foreach ($planillasAntes as $planilla) {
                $planillaObraMap[$planilla->id] = $planilla->obra_id;
            }

            foreach ($planillasAntes as $planilla) {
                $fechaPlanillaAntes = $planilla->getRawOriginal('fecha_estimada_entrega');
                $obraId = $planilla->obra_id;

                // SIEMPRE añadir el evento de la planilla (sus valores pueden cambiar al mover elementos)
                if ($fechaPlanillaAntes) {
                    $fechaStr = Carbon::parse($fechaPlanillaAntes)->format('Y-m-d');
                    $eventosAEliminar[] = "planillas-{$obraId}-{$fechaStr}-sin-salida";
                    $fechasAfectadas[] = $fechaStr;

                    // También añadir eventos con salida (si existen)
                    foreach ($planilla->paquetes as $paquete) {
                        foreach ($paquete->salidas as $salida) {
                            $eventosAEliminar[] = "planillas-{$obraId}-{$fechaStr}-salida-{$salida->id}";
                        }
                    }
                }

                // Recopilar fechas de elementos con fecha_entrega propia (estado ACTUAL en BD)
                foreach ($planilla->elementos->whereNotNull('fecha_entrega') as $elemento) {
                    $fechaElStr = Carbon::parse($elemento->fecha_entrega)->format('Y-m-d');
                    $eventosAEliminar[] = "planillas-{$obraId}-{$fechaElStr}-sin-salida";
                    $fechasAfectadas[] = $fechaElStr;
                }
            }

            // También añadir eventos basados en las fechas NUEVAS del payload (lo que el usuario está enviando)
            foreach ($data['planillas'] as $fila) {
                $obraId = $planillaObraMap[$fila['id']] ?? null;
                if (!$obraId) continue;

                // Fecha nueva de la planilla
                if (!empty($fila['fecha_estimada_entrega'])) {
                    $eventosAEliminar[] = "planillas-{$obraId}-{$fila['fecha_estimada_entrega']}-sin-salida";
                    $fechasAfectadas[] = $fila['fecha_estimada_entrega'];
                }

                // Fechas nuevas de elementos
                if (!empty($fila['elementos']) && is_array($fila['elementos'])) {
                    foreach ($fila['elementos'] as $elementoData) {
                        if (!empty($elementoData['fecha_entrega'])) {
                            $eventosAEliminar[] = "planillas-{$obraId}-{$elementoData['fecha_entrega']}-sin-salida";
                            $fechasAfectadas[] = $elementoData['fecha_entrega'];
                        }
                    }
                }
            }

            // 2. Realizar las actualizaciones
            DB::transaction(function () use ($data, &$logsRegistrados) {
                foreach ($data['planillas'] as $fila) {
                    // Actualizar fecha de la planilla (preservando la hora existente)
                    if (!empty($fila['fecha_estimada_entrega'])) {
                        $planilla = Planilla::find($fila['id']);
                        $fechaAnterior = $planilla->getRawOriginal('fecha_estimada_entrega');
                        $fechaNueva = Carbon::createFromFormat('Y-m-d', $fila['fecha_estimada_entrega']);

                        // Preservar la hora existente de la planilla
                        if ($fechaAnterior) {
                            $horaExistente = Carbon::parse($fechaAnterior);
                            $fechaNueva->setTime($horaExistente->hour, $horaExistente->minute, 0);
                        } else {
                            $fechaNueva->setTime(7, 0, 0); // Hora por defecto si no tenía
                        }

                        $planilla->fecha_estimada_entrega = $fechaNueva;
                        $planilla->save();

                        // Registrar log si la fecha cambió
                        $fechaAnteriorStr = $fechaAnterior ? Carbon::parse($fechaAnterior)->format('d/m/Y') : 'S/F';
                        $fechaNuevaStr = $fechaNueva->format('d/m/Y');
                        if ($fechaAnteriorStr !== $fechaNuevaStr) {
                            $logsRegistrados[] = [
                                'tipo' => 'planilla',
                                'codigo' => $planilla->codigo,
                                'fecha_anterior' => $fechaAnteriorStr,
                                'fecha_nueva' => $fechaNuevaStr,
                                'planilla_id' => $planilla->id,
                            ];
                        }
                    }

                    // Actualizar fechas de elementos si se proporcionan
                    if (!empty($fila['elementos']) && is_array($fila['elementos'])) {
                        $elementosActualizados = 0;

                        // Usar la fecha de la planilla (puede haber sido actualizada arriba en esta misma request)
                        // Si se actualizó la planilla, usar la nueva fecha; sino, obtener la actual
                        $fechaPlanillaStr = !empty($fila['fecha_estimada_entrega'])
                            ? $fila['fecha_estimada_entrega']  // Fecha nueva del request
                            : (Planilla::find($fila['id'])?->getRawOriginal('fecha_estimada_entrega')
                                ? Carbon::parse(Planilla::find($fila['id'])->getRawOriginal('fecha_estimada_entrega'))->format('Y-m-d')
                                : null);
                        $planillaParaElementos = Planilla::find($fila['id']);

                        foreach ($fila['elementos'] as $elementoData) {
                            $elemento = Elemento::find($elementoData['id']);
                            if ($elemento) {
                                $fechaAnteriorEl = $elemento->fecha_entrega;

                                // Determinar la nueva fecha
                                $nuevaFechaElemento = !empty($elementoData['fecha_entrega'])
                                    ? Carbon::createFromFormat('Y-m-d', $elementoData['fecha_entrega'])->startOfDay()
                                    : null;

                                // Si la fecha del elemento coincide con la de la planilla, fusionar (null)
                                if ($nuevaFechaElemento && $fechaPlanillaStr && $nuevaFechaElemento->format('Y-m-d') === $fechaPlanillaStr) {
                                    $elemento->fecha_entrega = null; // Fusionar con planilla
                                } else {
                                    $elemento->fecha_entrega = $nuevaFechaElemento;
                                }

                                $elemento->save();

                                // Contar si hubo cambio
                                $fechaAnteriorElStr = $fechaAnteriorEl ? Carbon::parse($fechaAnteriorEl)->format('Y-m-d') : null;
                                $fechaNuevaElStr = $elemento->fecha_entrega ? $elemento->fecha_entrega->format('Y-m-d') : null;
                                if ($fechaAnteriorElStr !== $fechaNuevaElStr) {
                                    $elementosActualizados++;
                                }
                            }
                        }

                        if ($elementosActualizados > 0) {
                            $logsRegistrados[] = [
                                'tipo' => 'elementos',
                                'codigo_planilla' => $planillaParaElementos?->codigo ?? 'N/A',
                                'cantidad' => $elementosActualizados,
                                'planilla_id' => $fila['id'],
                            ];
                        }
                    }
                }
            });

            // 3. Generar eventos nuevos DESPUÉS de actualizar
            $eventosNuevos = $this->generarEventosPlanillas($planillasIds);

            // Añadir las nuevas fechas a las afectadas
            foreach ($eventosNuevos as $evento) {
                $fechasAfectadas[] = Carbon::parse($evento['start'])->format('Y-m-d');
            }

            // 3.5 Reordenar colas de máquinas afectadas por el cambio de fechas
            $ordenPlanillaService = app(\App\Services\OrdenPlanillaService::class);
            $maquinasAfectadas = $ordenPlanillaService->obtenerMaquinasAfectadas($planillasIds);
            $cambiosReorden = $ordenPlanillaService->reordenarColasDeMaquinas($maquinasAfectadas);

            // 4. Calcular resúmenes de días afectados
            $fechasAfectadas = array_unique($fechasAfectadas);
            $resumenesDias = $this->calcularResumenesDias($fechasAfectadas);

            // Registrar logs después de la transacción exitosa
            foreach ($logsRegistrados as $log) {
                if ($log['tipo'] === 'planilla') {
                    \App\Models\LogPlanificacionSalidas::registrar(
                        'mover_planilla',
                        "ha cambiado la fecha de la planilla {$log['codigo']} de {$log['fecha_anterior']} a {$log['fecha_nueva']} (modal)",
                        [
                            'planilla_id' => $log['planilla_id'],
                            'codigo' => $log['codigo'],
                            'fecha_anterior' => $log['fecha_anterior'],
                            'fecha_nueva' => $log['fecha_nueva'],
                            'origen' => 'modal_fechas',
                        ],
                        ['planilla_id' => $log['planilla_id']]
                    );
                } elseif ($log['tipo'] === 'elementos') {
                    \App\Models\LogPlanificacionSalidas::registrar(
                        'mover_planilla',
                        "ha actualizado fechas de {$log['cantidad']} elemento(s) de la planilla {$log['codigo_planilla']} (modal)",
                        [
                            'planilla_id' => $log['planilla_id'],
                            'codigo_planilla' => $log['codigo_planilla'],
                            'elementos_actualizados' => $log['cantidad'],
                            'origen' => 'modal_fechas',
                        ],
                        ['planilla_id' => $log['planilla_id']]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Fechas de entrega actualizadas correctamente.',
                'eventos_eliminar' => array_values(array_unique($eventosAEliminar)),
                'eventos_nuevos' => $eventosNuevos,
                'resumenes_dias' => $resumenesDias,
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

    /**
     * Genera los eventos de calendario para un conjunto de planillas.
     * Busca TODAS las planillas de las mismas obras en las fechas afectadas.
     */
    private function generarEventosPlanillas(array $planillasIds): array
    {
        // Primero obtener las planillas modificadas para saber obras y fechas afectadas
        $planillasModificadas = Planilla::whereIn('id', $planillasIds)->get(['id', 'obra_id', 'fecha_estimada_entrega']);

        // Obtener obras y fechas únicas
        $obrasIds = $planillasModificadas->pluck('obra_id')->unique()->toArray();
        $fechas = $planillasModificadas
            ->map(fn($p) => $p->getRawOriginal('fecha_estimada_entrega') ? Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->toDateString() : null)
            ->filter()
            ->unique()
            ->toArray();

        // También incluir fechas de elementos con fecha_entrega propia
        $fechasElementos = Elemento::whereIn('planilla_id', $planillasIds)
            ->whereNotNull('fecha_entrega')
            ->pluck('fecha_entrega')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->unique()
            ->toArray();

        $todasFechas = array_unique(array_merge($fechas, $fechasElementos));

        // Buscar TODAS las planillas de esas obras que tengan fechas en el rango
        $planillas = Planilla::with([
            'obra:id,obra,cod_obra,cliente_id',
            'obra.cliente:id,empresa,codigo',
            'elementos:id,planilla_id,peso,fecha_entrega,longitud,barras,diametro',
        ])
        ->whereIn('obra_id', $obrasIds)
        ->where(function ($q) use ($todasFechas) {
            // Planillas con fecha_estimada_entrega en las fechas afectadas
            $q->whereIn(DB::raw('DATE(fecha_estimada_entrega)'), $todasFechas);
            // O planillas con elementos que tienen fecha_entrega en esas fechas
            $q->orWhereHas('elementos', function ($eq) use ($todasFechas) {
                $eq->whereIn(DB::raw('DATE(fecha_entrega)'), $todasFechas);
            });
        })
        ->get();

        $eventosFinales = [];

        // Agrupar planillas por obra y fecha_estimada_entrega
        $grupos = $planillas->groupBy(function ($p) {
            $fechaRaw = $p->getRawOriginal('fecha_estimada_entrega');
            return $p->obra_id . '|' . ($fechaRaw ? Carbon::parse($fechaRaw)->toDateString() : 'sin-fecha');
        });

        foreach ($grupos as $key => $grupo) {
            if (str_contains($key, 'sin-fecha')) continue;

            $obraId = $grupo->first()->obra_id;
            $obra = $grupo->first()->obra;
            $nombreObra = $obra?->obra ?? 'Obra desconocida';
            $codObra = $obra?->cod_obra ?? 'Código desconocido';
            $clienteNombre = $obra?->cliente?->empresa ?? 'Sin cliente';
            $codCliente = $obra?->cliente?->codigo ?? '';
            $fechaBase = Carbon::parse($grupo->first()->getRawOriginal('fecha_estimada_entrega'));

            $planillasIdsGrupo = $grupo->pluck('id')->toArray();
            $planillasCodigos = $grupo->pluck('codigo')->filter()->toArray();

            // Calcular datos de elementos SIN fecha_entrega propia
            $elementosSinFecha = $grupo->flatMap(fn($p) => $p->elementos->whereNull('fecha_entrega'));
            $pesoElementosSinFecha = $elementosSinFecha->sum('peso');
            $longitudElementosSinFecha = $elementosSinFecha->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0));
            $diametroElementosSinFecha = $elementosSinFecha->avg('diametro');
            $elementosIdsSinFecha = $elementosSinFecha->pluck('id')->toArray();

            // Solo crear evento si hay elementos sin fecha propia
            if ($pesoElementosSinFecha > 0) {
                $eventosFinales[] = $this->crearEventoCalendario(
                    $grupo,
                    $obraId,
                    $codObra,
                    $nombreObra,
                    $clienteNombre,
                    $codCliente,
                    $fechaBase,
                    $pesoElementosSinFecha,
                    $longitudElementosSinFecha,
                    $diametroElementosSinFecha,
                    $elementosIdsSinFecha
                );
            }

            // Crear eventos separados para elementos CON fecha_entrega propia
            $elementosConFechaPropia = $grupo->flatMap(fn($p) => $p->elementos->whereNotNull('fecha_entrega'));
            $elementosPorFecha = $elementosConFechaPropia->groupBy(fn($e) => Carbon::parse($e->fecha_entrega)->toDateString());

            foreach ($elementosPorFecha as $fecha => $elementos) {
                $pesoFecha = $elementos->sum('peso');
                $longitudFecha = $elementos->sum(fn($e) => ($e->longitud ?? 0) * ($e->barras ?? 0));
                $diametroFecha = $elementos->avg('diametro');
                $elementosIdsFecha = $elementos->pluck('id')->toArray();

                $eventosFinales[] = $this->crearEventoCalendario(
                    $grupo,
                    $obraId,
                    $codObra,
                    $nombreObra,
                    $clienteNombre,
                    $codCliente,
                    Carbon::parse($fecha),
                    $pesoFecha,
                    $longitudFecha,
                    $diametroFecha,
                    $elementosIdsFecha
                );
            }
        }

        return $eventosFinales;
    }

    /**
     * Crea un evento de calendario para planillas.
     */
    private function crearEventoCalendario(
        $planillas,
        int $obraId,
        string $codObra,
        string $nombreObra,
        string $clienteNombre,
        string $codCliente,
        Carbon $fechaBase,
        float $pesoTotal,
        float $longitudTotal,
        ?float $diametroMedio,
        array $elementosIds
    ): array {
        // Usar la hora más temprana de las planillas del grupo (o 07:00 por defecto)
        $horaMinima = $planillas
            ->map(fn($p) => $p->getRawOriginal('fecha_estimada_entrega') ? Carbon::parse($p->getRawOriginal('fecha_estimada_entrega'))->format('H:i') : '07:00')
            ->filter(fn($h) => $h !== '00:00')
            ->sort()
            ->first() ?? '07:00';

        [$hora, $minuto] = explode(':', $horaMinima);
        $fechaInicio = $fechaBase->copy()->setTime((int)$hora, (int)$minuto, 0);
        $planillasIds = $planillas->pluck('id')->toArray();
        $planillasCodigos = $planillas->pluck('codigo')->filter()->toArray();

        // Formatear diámetro medio
        $diametroMedioFormateado = $diametroMedio !== null
            ? number_format($diametroMedio, 2, '.', '')
            : null;

        // Color según estado
        $alMenosUnaFabricando = $planillas->contains(fn($p) => $p->estado === 'fabricando');
        $todasCompletadas = $planillas->every(fn($p) => $p->estado === 'completada');

        if ($todasCompletadas) {
            $color = '#22c55e'; // verde
        } elseif ($alMenosUnaFabricando) {
            $color = '#facc15'; // amarillo
        } else {
            $color = '#9CA3AF'; // gris
        }

        // Título compacto (para vista no diaria)
        $titulo = $codObra . ' - ' . $nombreObra . " - " . number_format($pesoTotal, 0) . " kg";

        // ID único del evento
        $eventoId = 'planillas-' . $obraId . '-' . $fechaBase->format('Y-m-d') . '-sin-salida';

        return [
            'title' => $titulo,
            'id' => $eventoId,
            'start' => $fechaInicio->toIso8601String(),
            'end' => $fechaInicio->copy()->addHours(2)->toIso8601String(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'tipo' => 'planilla',
            'resourceId' => (string) $obraId,
            'extendedProps' => [
                'tipo' => 'planilla',
                'obra_id' => $obraId,
                'cod_obra' => $codObra,
                'nombre_obra' => $nombreObra,
                'cliente' => $clienteNombre,
                'cod_cliente' => $codCliente,
                'pesoTotal' => $pesoTotal,
                'longitudTotal' => $longitudTotal,
                'planillas_ids' => $planillasIds,
                'planillas_codigos' => $planillasCodigos,
                'elementos_ids' => $elementosIds,
                'diametroMedio' => $diametroMedioFormateado,
                'tieneSalidas' => false,
                'salida_id' => null,
                'salida_codigo' => null,
                'hora_entrega' => $horaMinima,
            ],
        ];
    }

    /**
     * Calcula los resúmenes diarios para fechas específicas.
     */
    private function calcularResumenesDias(array $fechas): array
    {
        $resumenes = [];

        foreach ($fechas as $fechaStr) {
            $datosPlanillas = Planilla::whereDate('fecha_estimada_entrega', $fechaStr)
                ->selectRaw('COALESCE(SUM(peso_total), 0) as peso_total')
                ->first();

            $datosElementos = Elemento::whereHas('planilla', function ($q) use ($fechaStr) {
                    $q->whereDate('fecha_estimada_entrega', $fechaStr);
                })
                ->selectRaw('COALESCE(SUM(longitud * barras), 0) as longitud_total, AVG(diametro) as diametro_medio')
                ->first();

            $datosElementosPropios = Elemento::whereDate('fecha_entrega', $fechaStr)
                ->selectRaw('COALESCE(SUM(peso), 0) as peso_total, COALESCE(SUM(longitud * barras), 0) as longitud_total, AVG(diametro) as diametro_medio')
                ->first();

            $pesoTotal = ($datosPlanillas->peso_total ?? 0) + ($datosElementosPropios->peso_total ?? 0);
            $longitudTotal = ($datosElementos->longitud_total ?? 0) + ($datosElementosPropios->longitud_total ?? 0);

            $diametroMedio = 0;
            $countDiametros = 0;
            if ($datosElementos->diametro_medio) {
                $diametroMedio += $datosElementos->diametro_medio;
                $countDiametros++;
            }
            if ($datosElementosPropios->diametro_medio) {
                $diametroMedio += $datosElementosPropios->diametro_medio;
                $countDiametros++;
            }
            if ($countDiametros > 0) {
                $diametroMedio = $diametroMedio / $countDiametros;
            }

            $resumenes[$fechaStr] = [
                'fecha' => $fechaStr,
                'pesoTotal' => round($pesoTotal, 2),
                'longitudTotal' => round($longitudTotal, 2),
                'diametroMedio' => round($diametroMedio, 2),
            ];
        }

        return $resumenes;
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
     *
     * Sistema robusto con reintentos automáticos y transacciones optimizadas.
     */
    public function resetearPlanilla(Request $request, $id)
    {
        $planilla = Planilla::findOrFail($id);
        $maxReintentos = 3;
        $intento = 0;
        $ultimoError = null;

        // Verificar que la planilla no esté siendo procesada activamente
        $etiquetasEnProceso = \App\Models\Etiqueta::where('planilla_id', $id)
            ->whereIn('estado', ['cortando', 'procesando'])
            ->count();

        if ($etiquetasEnProceso > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede resetear: hay {$etiquetasEnProceso} etiquetas siendo procesadas activamente. Espere a que terminen."
            ], 409);
        }

        while ($intento < $maxReintentos) {
            $intento++;

            try {
                $resultado = $this->ejecutarResetPlanilla($planilla);

                Log::info('Planilla reseteada exitosamente', [
                    'planilla_id' => $planilla->id,
                    'codigo' => $planilla->codigo,
                    'intento' => $intento,
                    'detalles' => $resultado,
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Planilla {$planilla->codigo} reseteada correctamente",
                    'detalles' => $resultado,
                    'intentos' => $intento,
                ]);

            } catch (\Illuminate\Database\QueryException $e) {
                $ultimoError = $e;

                // Detectar errores de bloqueo/deadlock que pueden reintentarse
                $esErrorReintentable = str_contains($e->getMessage(), 'Lock wait timeout')
                    || str_contains($e->getMessage(), 'Deadlock')
                    || $e->getCode() == 1205
                    || $e->getCode() == 1213;

                if ($esErrorReintentable && $intento < $maxReintentos) {
                    Log::warning("Reset planilla: reintentando por bloqueo", [
                        'planilla_id' => $id,
                        'intento' => $intento,
                        'error' => $e->getMessage(),
                    ]);

                    // Esperar antes de reintentar (backoff exponencial)
                    sleep(pow(2, $intento)); // 2s, 4s, 8s
                    continue;
                }

                throw $e;
            }
        }

        // Si llegamos aquí, agotamos los reintentos
        Log::error('Error al resetear planilla después de todos los reintentos', [
            'planilla_id' => $id,
            'intentos' => $intento,
            'error' => $ultimoError?->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al resetear la planilla después de ' . $maxReintentos . ' intentos. Por favor, inténtelo de nuevo en unos minutos.',
            'error_tecnico' => $ultimoError?->getMessage(),
        ], 500);
    }

    /**
     * Ejecuta el reset de una planilla en transacciones optimizadas.
     */
    private function ejecutarResetPlanilla(Planilla $planilla): array
    {
        // Configurar timeout de sesión
        DB::statement('SET SESSION innodb_lock_wait_timeout = 60');

        $resultado = [
            'paquetes_eliminados' => 0,
            'etiquetas_reseteadas' => 0,
            'elementos_reseteados' => 0,
            'maquinas_asignadas' => [],
        ];

        // Obtener IDs fuera de la transacción para minimizar tiempo de bloqueo
        $etiquetaIds = $planilla->etiquetas()->pluck('id')->toArray();
        $elementoIds = $planilla->elementos()->pluck('id')->toArray();
        $paquetesCount = $planilla->paquetes()->count();

        // FASE 1: Limpiar datos (transacción separada para minimizar bloqueos)
        DB::transaction(function () use ($planilla, $etiquetaIds, $elementoIds, &$resultado, $paquetesCount) {
            // Bloquear filas al inicio de la transacción
            if (!empty($etiquetaIds)) {
                DB::table('etiquetas')->whereIn('id', array_slice($etiquetaIds, 0, 1000))
                    ->lockForUpdate()->get(['id']);
            }

            // 1. Eliminar paquetes
            $planilla->paquetes()->delete();
            $resultado['paquetes_eliminados'] = $paquetesCount;

            // 2. Resetear etiquetas en chunks
            $resultado['etiquetas_reseteadas'] = count($etiquetaIds);
            foreach (array_chunk($etiquetaIds, 200) as $chunk) {
                \App\Models\Etiqueta::whereIn('id', $chunk)
                    ->update([
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
            }

            // 3. Eliminar orden_planillas
            $ordenPlanillaService = new \App\Services\OrdenPlanillaService();
            $ordenPlanillaService->eliminarOrdenDePlanilla($planilla->id);

            // 4. Resetear elementos en chunks
            $resultado['elementos_reseteados'] = count($elementoIds);
            foreach (array_chunk($elementoIds, 200) as $chunk) {
                \App\Models\Elemento::whereIn('id', $chunk)
                    ->update([
                        'estado' => 'pendiente',
                        'paquete_id' => null,
                        'producto_id' => null,
                        'producto_id_2' => null,
                        'maquina_id' => null,
                        'orden_planilla_id' => null,
                    ]);
            }

            // 5. Resetear la planilla
            $planilla->update([
                'estado' => 'pendiente',
                'fecha_inicio' => null,
                'fecha_finalizacion' => null,
                'revisada' => false,
                'revisada_por_id' => null,
                'revisada_at' => null,
            ]);
        }, 3); // 3 reintentos automáticos en deadlock

        // FASE 2: Reasignar máquinas y crear órdenes (transacción separada)
        DB::transaction(function () use ($planilla, &$resultado) {
            $asignarMaquinaService = new \App\Services\AsignarMaquinaService();
            $ordenPlanillaService = new \App\Services\OrdenPlanillaService();

            // Reasignar máquinas
            $asignarMaquinaService->repartirPlanilla($planilla->id);

            // Crear órdenes
            $ordenPlanillaService->crearOrdenParaPlanilla($planilla->id);

            // Obtener máquinas asignadas
            $resultado['maquinas_asignadas'] = OrdenPlanilla::where('planilla_id', $planilla->id)
                ->with('maquina:id,codigo')
                ->get()
                ->pluck('maquina.codigo')
                ->filter()
                ->values()
                ->toArray();
        }, 3);

        return $resultado;
    }

    /**
     * Muestra la información de ensamblaje de una planilla.
     */
    public function ensamblaje(Planilla $planilla)
    {
        $planilla->load([
            'cliente',
            'obra',
            'entidades' => function ($query) {
                $query->orderBy('linea');
            },
            'entidades.elementos' => function ($query) {
                $query->orderBy('marca');
            }
        ]);

        return view('planillas.ensamblaje', compact('planilla'));
    }
}
