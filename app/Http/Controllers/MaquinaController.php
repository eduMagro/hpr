<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Salida;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\Producto;
use App\Models\Obra;
use App\Models\Cliente;
use App\Models\ProductoBase;
use App\Models\Pedido;
use App\Models\Planilla;
use App\Models\OrdenPlanilla;
use App\Models\AsignacionTurno;
use App\Models\User;
use App\Models\Ubicacion;
use App\Models\Movimiento;
use App\Models\Localizacion;
use App\Models\Paquete;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage; // ✅ Añadir esta línea
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Services\SugeridorProductoBaseService;
use Illuminate\Support\Facades\Log;
use App\Services\ProgressBVBSService;
use App\Services\AsignarMaquinaService;
use App\Services\PlanillaColaService;
use App\Services\ActionLoggerService;
use App\Models\GrupoResumen;
use App\Models\EtiquetaEnsamblaje;
use App\Models\PlanillaEntidad;
use App\Models\OrdenPlanillaEnsamblaje;

class MaquinaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = auth()->user();

        /* ───────────────────────────────────────────
         * 1️⃣  RUTA OPERARIO (igual que la tuya)
         * ─────────────────────────────────────────── */
        if ($usuario->rol === 'operario') {
            $hoy = Carbon::today();
            $maniana = Carbon::tomorrow();

            // Primero buscar asignación CON máquina asignada
            $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', $hoy)
                ->whereNotNull('maquina_id')
                ->whereNotNull('turno_id')
                ->first();

            if (!$asignacion) {
                // Probar para mañana con máquina asignada
                $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                    ->whereDate('fecha', $maniana)
                    ->whereNotNull('maquina_id')
                    ->whereNotNull('turno_id')
                    ->first();
            }

            // Si tiene máquina asignada, redirigir a ella
            if ($asignacion) {
                $maquinaId = $asignacion->maquina_id;
                $turnoId = $asignacion->turno_id;

                // Buscar compañero
                $compañero = AsignacionTurno::where('maquina_id', $maquinaId)
                    ->where('turno_id', $turnoId)
                    ->where('user_id', '!=', $usuario->id)
                    ->latest()
                    ->first();

                session(['compañero_id' => optional($compañero)->user_id]);

                return redirect()->route('maquinas.show', ['maquina' => $maquinaId]);
            }

            // Buscar asignación SIN máquina (para que pueda elegir una)
            $asignacionSinMaquina = AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', $hoy)
                ->whereNull('maquina_id')
                ->first();

            if (!$asignacionSinMaquina) {
                $asignacionSinMaquina = AsignacionTurno::where('user_id', $usuario->id)
                    ->whereDate('fecha', $maniana)
                    ->whereNull('maquina_id')
                    ->first();
            }

            // Si no tiene ninguna asignación, no ha fichado
            if (!$asignacionSinMaquina) {
                return back()->with('error', 'No has fichado entrada. Debes fichar antes de acceder.');
            }

            // Tiene asignación pero sin máquina: mostrar selector de máquinas
            $maquinasDisponibles = Maquina::orderBy('nombre')->get(['id', 'codigo', 'nombre']);
            return view('maquinas.seleccionar-maquina', [
                'maquinas' => $maquinasDisponibles
            ]);
        }

        /* ───────────────────────────────────────────
         * 2️⃣  RESTO DE USUARIOS
         * ─────────────────────────────────────────── */

        // ▸ 2.1 Consulta de máquinas (simplificada)
        $query = Maquina::with('productos');

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->input('nombre') . '%');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        if (Schema::hasColumn('maquinas', $sortBy)) {
            $query->orderBy($sortBy, $order);
        }

        $perPage = $request->input('per_page', 30);
        $registrosMaquina = $query->paginate($perPage)
            ->appends($request->except('page'));

        // ▸ 2.2 Operarios (deshabilitado para mejorar rendimiento)
        $usuariosPorMaquina = collect();
        $obras = Obra::whereHas('cliente', function ($query) {
            $query->where('empresa', 'like', '%Hierros Paco Reyes%');
        })
            ->orderBy('obra')
            ->get();

        // ▸ 2.4 Incidencias (para la pestaña de incidencias)
        $incidenciasQuery = \App\Models\Incidencia::with(['maquina', 'user'])->latest('fecha_reporte');

        if (!$request->has('ver_inactivas')) {
            $incidenciasQuery->where('estado', '!=', 'resuelta');
        }

        $incidencias = $incidenciasQuery->paginate(10, ['*'], 'incidencias_page');
        $activasCount = \App\Models\Incidencia::where('estado', '!=', 'resuelta')->count();

        // ▸ 2.3 Render vista
        return view('maquinas.index', compact(
            'registrosMaquina',
            'usuariosPorMaquina',
            'obras',
            'incidencias',
            'activasCount'
        ));
    }

    public function showJson($id)
    {
        $maquina = Maquina::with('obra:id,obra')->findOrFail($id);
        return response()->json($maquina);
    }

    //------------------------------------------------------------------------------------ SHOW

    public function show($id)
    {
        $t0 = microtime(true);

        // 0) Primero cargar solo la máquina para verificar el tipo
        $maquina = Maquina::findOrFail($id);
        // 1) Rama GRÚA: cargar contexto mínimo y devolver pronto
        if ($this->esGrua($maquina)) {
            $base = $this->cargarContextoBase($maquina);
            // ⚠️ IMPORTANTE: Activar movimientos ANTES de cargar el contexto
            // para que los nuevos movimientos aparezcan en la primera carga
            //$this->activarMovimientosSalidasHoy();
            //$this->activarMovimientosSalidasAlmacenHoy();
            //$this->activarMovimientosPreparacionPaquete($maquina);

            // 🔧 MODO FABRICACIÓN: Si viene el parámetro fabricar_planilla, mostrar vista de fabricación
            $fabricarPlanillaId = request('fabricar_planilla');
            if ($fabricarPlanillaId) {
                return $this->mostrarGruaComoMaquinaFabricacion($maquina, $fabricarPlanillaId, $base);
            }

            // Ahora sí cargar el contexto con los movimientos ya creados
            $grua = $this->cargarContextoGrua($maquina);
            return view('maquinas.show', array_merge(
                $base,
                ['maquina' => $maquina],
                $grua
            ));
        }

        // 1.5) Rama ENSAMBLADORA: cargar contexto específico de ensamblaje
        if ($this->esEnsambladora($maquina)) {
            $base = $this->cargarContextoBase($maquina);
            $ensambladora = $this->cargarContextoEnsambladora($maquina);
            return view('maquinas.show', array_merge(
                $base,
                ['maquina' => $maquina],
                $ensambladora
            ));
        }

        // 2) MÁQUINAS NORMALES: Cargar solo productos (no elementos - se cargan filtrados después)
        $t1 = microtime(true);
        $maquina->load('productos');
        $base = $this->cargarContextoBase($maquina);

        // 3) Cola de planillas - solo planillas revisadas
        // ⚠️ LÓGICA ESTRICTA: Solo planillas revisadas entran en planificación
        $t2 = microtime(true);
        $ordenesPlanillas = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->with('planilla')
            ->orderBy('posicion', 'asc')
            ->get();

        // Obtener IDs de planillas revisadas en la cola
        $planillasRevisadasIds = $ordenesPlanillas
            ->filter(fn($orden) => $orden->planilla && $orden->planilla->revisada)
            ->pluck('planilla_id')
            ->toArray();

        // Para dobladora manual: incluir también planillas con elementos donde maquina_id_2 = esta máquina
        if ($this->esDobladoraManual($maquina)) {
            $planillasConMaquina2 = Elemento::where('maquina_id_2', $maquina->id)
                ->whereHas('planilla', fn($q) => $q->where('revisada', true))
                ->distinct()
                ->pluck('planilla_id')
                ->toArray();

            $planillasRevisadasIds = array_unique(array_merge($planillasRevisadasIds, $planillasConMaquina2));
        }

        // 4) Cargar SOLO elementos de planillas revisadas en cola (no todos los 30k+)
        // Buscar en cualquiera de los campos de máquina (maquina_id, maquina_id_2)
        // Para dobladora_manual: filtrar por estado pendiente según corresponda
        $t3 = microtime(true);
        $esDobladoraManual = $this->esDobladoraManual($maquina);
        // Estados válidos para etiqueta en máquina principal
        $estadosEtiquetaPrincipal = ['pendiente', 'fabricando', 'fabricada'];
        // Estados válidos para etiqueta.estado2 en máquina secundaria
        $estadosEtiquetaSecundaria = ['pendiente', 'doblando'];

        $elementosMaquina = Elemento::with(['planilla', 'etiquetaRelacion', 'subetiquetas', 'maquina', 'maquina_2', 'producto', 'producto2', 'producto3'])
            ->where(function ($query) use ($maquina, $esDobladoraManual, $estadosEtiquetaPrincipal, $estadosEtiquetaSecundaria) {
                // Elementos donde esta máquina es la principal
                $query->where(function ($q) use ($maquina, $esDobladoraManual, $estadosEtiquetaPrincipal) {
                    $q->where('maquina_id', $maquina->id);
                    // Para dobladora_manual: solo elementos cuya etiqueta tenga estado válido
                    if ($esDobladoraManual) {
                        $q->whereHas('etiquetaRelacion', function ($subQ) use ($estadosEtiquetaPrincipal) {
                            $subQ->whereIn('estado', $estadosEtiquetaPrincipal);
                        });
                    }
                });
                // Elementos donde esta máquina es la secundaria
                $query->orWhere(function ($q) use ($maquina, $esDobladoraManual, $estadosEtiquetaSecundaria) {
                    $q->where('maquina_id_2', $maquina->id);
                    // Para dobladora_manual: solo elementos cuya etiqueta tenga estado2 pendiente/doblando
                    if ($esDobladoraManual) {
                        $q->whereHas('etiquetaRelacion', function ($subQ) use ($estadosEtiquetaSecundaria) {
                            $subQ->whereIn('estado2', $estadosEtiquetaSecundaria);
                        });
                    }
                });
            })
            ->whereIn('planilla_id', $planillasRevisadasIds)
            ->get();

        // Obtener posiciones del request o calcular automáticamente
        $posicion1 = request('posicion_1');
        $posicion2 = request('posicion_2');

        // Si no hay posiciones en el request, buscar solo la primera posición con planilla revisada
        if (is_null($posicion1) && is_null($posicion2)) {
            foreach ($ordenesPlanillas as $orden) {
                if ($orden->planilla && $orden->planilla->revisada) {
                    $posicion1 = $orden->posicion;
                    break; // Solo la primera posición revisada por defecto
                }
            }
        }

        // Filtrar posiciones válidas (mayores a 0)
        $posiciones = collect([$posicion1, $posicion2])
            ->filter(fn($p) => !is_null($p) && (int) $p > 0)
            ->map(fn($p) => (int) $p)
            ->unique()
            ->values();

        // Cola de planillas con posiciones específicas
        // OPTIMIZADO: Pasamos $ordenesPlanillas para evitar consultas duplicadas
        $t4 = microtime(true);
        [$planillasActivas, $elementosFiltrados, $ordenManual, $posicionesDisponibles, $codigosPorPosicion, $planillaIdsPorPosicion] =
            $this->aplicarColaPlanillasPorPosicion($maquina, $elementosMaquina, $posiciones, $ordenesPlanillas);

        // 5) Datasets filtrados por planilla
        $elementosPorPlanilla = $elementosFiltrados->groupBy('planilla_id');

        // 6) Datasets para UI (canvas/tabla)
        $pesosElementos = $elementosFiltrados
            ->map(fn($e) => ['id' => $e->id, 'peso' => $e->peso])
            ->values()
            ->toArray();

        $ordenSub = function ($grupo, $subId) {
            if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                return sprintf('%s-%010d', $m[1], (int) $m[2]);
            }
            return $subId . '-0000000000';
        };

        // Obtener etiqueta_sub_ids que están en grupos de resumen (resumidas)
        // OPTIMIZADO: Solo de las planillas activas, no de toda la BD
        $planillaIdsActivos = collect($planillasActivas)->pluck('id')->toArray();
        $etiquetasResumidas = $planillaIdsActivos
            ? Etiqueta::whereNotNull('grupo_resumen_id')
            ->whereIn('planilla_id', $planillaIdsActivos)
            ->pluck('etiqueta_sub_id')
            ->toArray()
            : [];

        $etiquetasData = $elementosFiltrados
            ->filter(fn($e) => !empty($e->etiqueta_sub_id) && !in_array($e->etiqueta_sub_id, $etiquetasResumidas))
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub)
            ->map(fn($grupo, $subId) => [
                'codigo' => (string) $subId,
                'elementos' => $grupo->pluck('id')->toArray(),
                'pesoTotal' => $grupo->sum('peso'),
            ])
            ->values();

        $elementosReempaquetados = session('elementos_reempaquetados', []);

        $elementosAgrupados = $elementosFiltrados
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub);

        $elementosAgrupadosScript = $elementosAgrupados->map(function ($grupo) {
            $etiqueta = $grupo->first()->etiquetaRelacion;
            return [
                'etiqueta' => $etiqueta,
                'planilla' => $grupo->first()->planilla,
                // Coladas de la etiqueta (asignadas en primer y segundo clic)
                'colada_etiqueta' => $etiqueta?->producto?->n_colada,
                'colada_etiqueta_2' => $etiqueta?->producto2?->n_colada,
                'elementos' => $grupo->map(fn($e) => [
                    'id' => $e->id,
                    'codigo' => $e->codigo,
                    'dimensiones' => $e->dimensiones,
                    'estado' => $etiqueta?->estado ?? 'pendiente',
                    'peso' => $e->peso_kg,
                    'peso_numerico' => (float) $e->peso,
                    'diametro' => $e->diametro_mm,
                    'longitud' => $e->longitud_cm,
                    'barras' => $e->barras,
                    'figura' => $e->figura,
                    'paquete_id' => $e->paquete_id,
                    'etiqueta_sub_id' => $e->etiqueta_sub_id,
                    // Incluimos las coladas para mostrarlas en la leyenda del SVG
                    'coladas' => [
                        'colada1' => $e->producto ? $e->producto->n_colada : null,
                        'colada2' => $e->producto2 ? $e->producto2->n_colada : null,
                        'colada3' => $e->producto3 ? $e->producto3->n_colada : null,
                    ],
                ])->values(),
            ];
        })->values();

        // 7) Turno, movimientos y otros contextos
        $turnoHoy = AsignacionTurno::where('user_id', auth()->id())
            ->whereDate('fecha', now())
            ->with('maquina')
            ->first();

        $movimientosPendientes = collect();
        $movimientosCompletados = collect();
        $ubicacionesDisponiblesPorProductoBase = [];
        $pedidosActivos = collect();

        $productoBaseSolicitados = Movimiento::where('tipo', 'recarga materia prima')
            ->where('estado', 'pendiente')
            ->where('maquina_destino', $maquina->id)
            ->pluck('producto_base_id')
            ->unique() ?? collect();

        // 8) Productos base compatibles con la máquina (solo para máquinas normales)
        $tipoMaterial = strtolower($maquina->tipo_material ?? '');
        $queryPB = ProductoBase::whereBetween('diametro', [$maquina->diametro_min ?? 0, $maquina->diametro_max ?? 100])
            ->orderBy('diametro');
        if (!empty($tipoMaterial)) {
            $queryPB->whereRaw('LOWER(tipo) = ?', [$tipoMaterial]);
        }
        $productosBaseCompatibles = $queryPB->get();

        // 9) Sugerencias de corte (sobre elementos filtrados y PB barra)
        $productosBarra = $productosBaseCompatibles->filter(function ($pb) {
            $tipo = strtolower((string) ($pb->tipo ?? ''));
            if (str_contains($tipo, 'barra') || str_contains($tipo, 'varilla') || str_contains($tipo, 'corrug')) {
                return true;
            }
            $v = (float) str_replace(',', '.', (string) ($pb->longitud ?? 0));
            return $v > 1 && $v < 50; // metros razonables
        })->values();
        if ($productosBarra->isEmpty()) {
            $productosBarra = $productosBaseCompatibles;
        }

        $planillasOrden = $elementosFiltrados->pluck('planilla_id')->unique()->values();
        $idxPlanilla = $planillasOrden->flip();
        $colegasDe = function ($el) use ($elementosFiltrados, $planillasOrden, $idxPlanilla) {
            $i = (int) ($idxPlanilla[$el->planilla_id] ?? 0);
            $ids = $planillasOrden->slice($i, 3); // actual + 2 siguientes
            return $elementosFiltrados->whereIn('planilla_id', $ids)->values();
        };

        $t5 = microtime(true);
        $sugeridor = app(\App\Services\SugeridorProductoBaseService::class);
        $sugerenciasPorElemento = [];
        foreach ($elementosFiltrados as $el) {
            $colegas = $colegasDe($el);
            $sugerenciasPorElemento[$el->id] = $sugeridor->sugerirParaElemento($el, $productosBarra, $colegas);
        }

        // 9) Longitudes por diámetro (solo si la máquina es de barras)
        $esBarra = strcasecmp($maquina->tipo_material, 'barra') === 0;

        $longitudesPorDiametro = $esBarra
            ? $productosBaseCompatibles
            ->filter(fn($pb) => strtoupper($pb->tipo) === 'BARRA')
            ->groupBy(fn($pb) => (int) $pb->diametro)
            ->map(
                fn($g) => $g->pluck('longitud')
                    ->filter(fn($L) => is_numeric($L) && $L > 0)
                    ->map(fn($L) => (float) $L)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all()
            )
            ->toArray()
            : [];

        // 10) Diámetro por subetiqueta (desde elementos FILTRADOS)
        //     (usamos la moda por si existieran mezclas)
        $diametroPorEtiqueta = $elementosFiltrados
            ->filter(fn($e) => !empty($e->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->map(function ($els) {
                $c = collect($els)->pluck('diametro')->filter()->map(fn($d) => (int) $d);
                return (int) $c->countBy()->sortDesc()->keys()->first();
            })
            ->toArray();

        // 11) AUTO-RESUMEN: Solo procesa etiquetas NO resumidas de planillas en orden_planillas
        $resumenService = app(\App\Services\ResumenEtiquetaService::class);
        $resumenService->resumirMultiplanilla($maquina->id, auth()->id());

        // 12) Grupos de resumen activos para esta máquina
        // Incluye tanto grupos de planilla individual como grupos multi-planilla (planilla_id = null)
        $t6 = microtime(true);
        $planillaIds = collect($planillasActivas)->pluck('id')->toArray();

        // OPTIMIZADO: Solo cargar grupos si hay planillas activas
        // Grupos multi-planilla solo se cargan si hay planillas activas (sus etiquetas deben pertenecer a alguna)
        $gruposResumen = empty($planillaIds)
            ? collect() // Sin planillas activas = sin grupos
            : GrupoResumen::where('activo', true)
                ->where('maquina_id', $maquina->id)
                ->where(function ($query) use ($planillaIds) {
                    $query->whereIn('planilla_id', $planillaIds)
                        ->orWhereNull('planilla_id'); // Grupos multi-planilla
                })
                ->with(['etiquetas.planilla', 'planilla'])
                ->get()
                // Filtrar grupos multi-planilla: solo los que tienen etiquetas de planillas activas
                ->filter(function ($grupo) use ($planillaIds) {
                    if (!is_null($grupo->planilla_id)) {
                        return true; // Grupos con planilla específica ya están filtrados
                    }
                    // Para multi-planilla: verificar que al menos una etiqueta pertenezca a planilla activa
                    return $grupo->etiquetas->contains(fn($et) => in_array($et->planilla_id, $planillaIds));
                });

        // OPTIMIZADO: Cargar todos los elementos de grupos en UNA sola consulta (evita N+1)
        $todosEtiquetaIds = $gruposResumen->flatMap(fn($g) => $g->etiquetas->pluck('id'))->unique()->toArray();
        $elementosDeGrupos = $todosEtiquetaIds
            ? Elemento::with(['producto', 'producto2', 'producto3', 'etiquetaRelacion'])
            ->whereIn('etiqueta_id', $todosEtiquetaIds)
            ->where(function ($query) use ($maquina) {
                // CORREGIDO: Buscar en cualquier campo de máquina
                $query->where('maquina_id', $maquina->id)
                    ->orWhere('maquina_id_2', $maquina->id);
            })
            ->get()
            ->groupBy('etiqueta_id')
            : collect();

        // Pre-cargar etiquetas de grupos (solo IDs y planilla, sin elementos para evitar memoria)
        $etiquetasPreCargadas = collect();

        // Preparar datos de grupos para la vista (con elementos de cada etiqueta)
        $gruposResumenData = $gruposResumen->map(function ($grupo) use ($elementosDeGrupos) {
            // Obtener los IDs de etiquetas del grupo
            $etiquetaIds = $grupo->etiquetas->pluck('id')->toArray();

            // OPTIMIZADO: Filtrar de la colección pre-cargada (no consulta adicional)
            $elementosGrupo = collect($etiquetaIds)
                ->flatMap(fn($id) => $elementosDeGrupos->get($id, collect()));

            // Para grupos multi-planilla, obtener códigos de planillas involucradas
            $esMultiplanilla = is_null($grupo->planilla_id);
            $codigosPlanillas = [];
            if ($esMultiplanilla) {
                $codigosPlanillas = $grupo->etiquetas
                    ->pluck('planilla')
                    ->filter() // Filtrar nulls
                    ->unique('id')
                    ->map(fn($p) => $p->codigo_limpio ?? $p->codigo)
                    ->values()
                    ->toArray();
            }

            return [
                'id' => $grupo->id,
                'codigo' => $grupo->codigo,
                'diametro' => (int) $grupo->diametro,
                'dimensiones' => $grupo->dimensiones,
                'total_elementos' => $grupo->total_elementos,
                'peso_total' => round($grupo->peso_total, 2),
                'total_etiquetas' => $grupo->total_etiquetas,
                'planilla_id' => $grupo->planilla_id,
                'planilla_codigo' => $grupo->planilla->codigo ?? '',
                'es_multiplanilla' => $esMultiplanilla,
                'codigos_planillas' => $codigosPlanillas,
                'estado' => $grupo->estado_predominante,
                'etiquetas' => $grupo->etiquetas->map(fn($et) => [
                    'id' => $et->id,
                    'etiqueta_sub_id' => $et->etiqueta_sub_id,
                    'estado' => $et->estado,
                    'planilla_codigo' => $et->planilla?->codigo_limpio ?? $et->planilla?->codigo ?? '',
                ])->values()->toArray(),
                'elementos' => $elementosGrupo->map(fn($e) => [
                    'id' => $e->id,
                    'codigo' => $e->codigo,
                    'dimensiones' => $e->dimensiones,
                    'estado' => $e->etiquetaRelacion?->estado ?? 'pendiente',
                    'peso' => $e->peso_kg,
                    'peso_numerico' => (float) $e->peso,
                    'diametro' => $e->diametro_mm,
                    'longitud' => $e->longitud_cm,
                    'barras' => $e->barras,
                    'figura' => $e->figura,
                    'etiqueta_sub_id' => $e->etiqueta_sub_id,
                    'paquete_id' => $e->paquete_id,
                    'coladas' => [
                        'colada1' => $e->producto ? $e->producto->n_colada : null,
                        'colada2' => $e->producto2 ? $e->producto2->n_colada : null,
                        'colada3' => $e->producto3 ? $e->producto3->n_colada : null,
                    ],
                ])->values()->toArray(),
            ];
        })->values();

        // Obtener etiqueta_sub_ids que están en grupos (para excluirlos de la vista individual)
        $etiquetasEnGrupos = $gruposResumen->flatMap(fn($g) => $g->etiquetas->pluck('etiqueta_sub_id'))->toArray();

        // Filtrar elementosAgrupados para excluir etiquetas que están en grupos
        $elementosAgrupadosSinGrupos = $elementosAgrupados->filter(
            fn($grupo, $subId) => !in_array($subId, $etiquetasEnGrupos)
        );

        // Actualizar elementosAgrupadosScript sin grupos
        $elementosAgrupadosScriptSinGrupos = $elementosAgrupadosSinGrupos->map(function ($grupo) {
            $etiqueta = $grupo->first()->etiquetaRelacion;
            return [
                'etiqueta' => $etiqueta,
                'planilla' => $grupo->first()->planilla,
                // Coladas de la etiqueta (asignadas en primer y segundo clic)
                'colada_etiqueta' => $etiqueta?->producto?->n_colada,
                'colada_etiqueta_2' => $etiqueta?->producto2?->n_colada,
                'elementos' => $grupo->map(fn($e) => [
                    'id' => $e->id,
                    'codigo' => $e->codigo,
                    'dimensiones' => $e->dimensiones,
                    'estado' => $etiqueta?->estado ?? 'pendiente',
                    'peso' => $e->peso_kg,
                    'peso_numerico' => (float) $e->peso,
                    'diametro' => $e->diametro_mm,
                    'longitud' => $e->longitud_cm,
                    'barras' => $e->barras,
                    'figura' => $e->figura,
                    'paquete_id' => $e->paquete_id,
                    'etiqueta_sub_id' => $e->etiqueta_sub_id,
                    'coladas' => [
                        'colada1' => $e->producto ? $e->producto->n_colada : null,
                        'colada2' => $e->producto2 ? $e->producto2->n_colada : null,
                        'colada3' => $e->producto3 ? $e->producto3->n_colada : null,
                    ],
                ])->values(),
            ];
        })->values();

        // 12) Devolver vista
        return view('maquinas.show', array_merge($base, [
            // base
            'maquina' => $maquina,

            // cola / filtrados
            'planillasActivas' => $planillasActivas,
            'elementosFiltrados' => $elementosFiltrados,
            'elementosPorPlanilla' => $elementosPorPlanilla,
            'posicionesDisponibles' => $posicionesDisponibles,
            'codigosPorPosicion' => $codigosPorPosicion,
            'planillaIdsPorPosicion' => $planillaIdsPorPosicion,
            'posicion1' => $posicion1,
            'posicion2' => $posicion2,
            // datasets UI
            'elementosMaquina' => $elementosMaquina,
            'pesosElementos' => $pesosElementos,
            'etiquetasData' => $etiquetasData,
            'elementosReempaquetados' => $elementosReempaquetados,
            'elementosAgrupados' => $elementosAgrupadosSinGrupos,
            'elementosAgrupadosScript' => $elementosAgrupadosScriptSinGrupos,
            'sugerenciasPorElemento' => $sugerenciasPorElemento,

            // grupos de resumen
            'gruposResumen' => $gruposResumenData,
            'etiquetasEnGrupos' => $etiquetasEnGrupos,
            'etiquetasPreCargadas' => $etiquetasPreCargadas, // ✅ Para evitar N+1

            // extra contexto
            'turnoHoy' => $turnoHoy,
            'movimientosPendientes' => $movimientosPendientes,
            'movimientosCompletados' => $movimientosCompletados,
            'ubicacionesDisponiblesPorProductoBase' => $ubicacionesDisponiblesPorProductoBase,
            'pedidosActivos' => $pedidosActivos,
            'productoBaseSolicitados' => $productoBaseSolicitados,

            // barra
            'esBarra' => $esBarra,
            'longitudesPorDiametro' => $longitudesPorDiametro,
            'diametroPorEtiqueta' => $diametroPorEtiqueta,

            // productos base compatibles
            'productosBaseCompatibles' => $productosBaseCompatibles,
        ]));
    }

    /* =========================
   HELPERS PRIVADOS
   ========================= */

    /**
     * 🔥 Obtiene planillas según posiciones específicas
     * OPTIMIZADO: Recibe $ordenesPlanillas para evitar consultas duplicadas
     *
     * @param Maquina $maquina
     * @param Collection $elementos
     * @param Collection $posiciones - Collection de posiciones a mostrar [1, 3, 5...]
     * @param Collection $ordenesPlanillas - Órdenes ya cargadas con relación planilla
     * @return array [$planillasActivas, $elementosFiltrados, $ordenManual, $posicionesDisponibles, $codigosPorPosicion]
     */
    private function aplicarColaPlanillasPorPosicion(Maquina $maquina, Collection $elementos, Collection $posiciones, Collection $ordenesPlanillas)
    {
        // 1) Agrupar elementos por planilla
        $porPlanilla = $elementos->groupBy('planilla_id');

        // 2) OPTIMIZADO: Usar $ordenesPlanillas ya cargada (evita consulta duplicada)
        $ordenManual = $ordenesPlanillas->pluck('posicion', 'planilla_id');

        // 3) Crear un mapa inverso [posicion => planilla_id] para búsqueda rápida
        $posicionAPlanilla = $ordenManual->flip();

        // 4) OPTIMIZADO: Filtrar directamente de $ordenesPlanillas (evita consulta duplicada)
        $ordenesConPlanilla = $ordenesPlanillas->filter(function ($orden) use ($porPlanilla) {
            // Incluir solo si:
            // 1. La planilla existe y está revisada
            // 2. Y tiene elementos en esta máquina (está en $porPlanilla)
            return $orden->planilla
                && $orden->planilla->revisada
                && $porPlanilla->has($orden->planilla_id);
        });

        $posicionesDisponibles = $ordenesConPlanilla
            ->pluck('posicion')
            ->values()
            ->toArray();

        // 4b) Crear mapeo de posicion => codigo_limpio
        $codigosPorPosicion = $ordenesConPlanilla
            ->mapWithKeys(fn($orden) => [$orden->posicion => $orden->planilla->codigo_limpio])
            ->toArray();

        // 4c) Crear mapeo de posicion => planilla_id
        $planillaIdsPorPosicion = $ordenesConPlanilla
            ->mapWithKeys(fn($orden) => [$orden->posicion => $orden->planilla_id])
            ->toArray();

        // 5) Seleccionar planillas según las posiciones solicitadas
        // ⚠️ SOLO planillas REVISADAS pueden ser mostradas
        $planillasActivas = [];
        foreach ($posiciones as $pos) {
            if ($posicionAPlanilla->has($pos)) {
                $planillaId = $posicionAPlanilla[$pos];

                // Buscar la planilla en los elementos agrupados
                if ($porPlanilla->has($planillaId)) {
                    $planilla = $porPlanilla[$planillaId]->first()->planilla;

                    // ✅ VALIDACIÓN CRÍTICA: Solo mostrar planillas revisadas
                    if ($planilla && $planilla->revisada) {
                        $planillasActivas[] = $planilla;
                    }
                }
            }
        }

        // 6) Filtrar elementos a las planillas activas
        $idsActivos = collect($planillasActivas)->pluck('id');
        $elementosFiltrados = $idsActivos->isNotEmpty()
            ? $elementos->whereIn('planilla_id', $idsActivos)->values()
            : collect();

        return [$planillasActivas, $elementosFiltrados, $ordenManual, $posicionesDisponibles, $codigosPorPosicion, $planillaIdsPorPosicion];
    }

    public static function productosSolicitadosParaMaquina($maquinaId)
    {
        return Movimiento::where('tipo', 'recarga_materia_prima')
            ->where('estado', 'pendiente')
            ->where('maquina_id', $maquinaId)
            ->pluck('producto_base_id')
            ->unique()
            ->toArray();
    }

    private function esGrua(Maquina $m): bool
    {
        return stripos((string) $m->tipo, 'grua') !== false || stripos((string) $m->nombre, 'grua') !== false;
    }

    private function esEnsambladora(Maquina $m): bool
    {
        return stripos((string)$m->tipo, 'ensambladora') !== false || stripos((string)$m->nombre, 'ensambladora') !== false;
    }

    private function esDobladoraManual(Maquina $m): bool
    {
        return stripos((string)$m->tipo, 'dobladora_manual') !== false
            || stripos((string)$m->tipo, 'dobladora manual') !== false;
    }

    // Si tienes un campo explícito para "segunda" úsalo aquí.
    // Por defecto asumo “segunda” = máquinas que trabajan como post-proceso, p.ej. ensambladora.
    private function esSegundaMaquina(Maquina $m): bool
    {
        $tipo = strtolower((string) $m->tipo);

        return str_contains($tipo, 'ensambladora')
            || str_contains($tipo, 'dobladora manual')   // 👈 añade esto
            || (property_exists($m, 'orden') && (int) $m->orden === 2);
    }


    private function cargarContextoBase(Maquina $maquina): array
    {
        // OPTIMIZADO: Buscar por código exacto primero, luego LIKE solo si es necesario
        $ubicacion = Ubicacion::where('descripcion', $maquina->codigo)->first()
            ?? Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();

        // OPTIMIZADO: Solo cargar máquinas de la misma nave
        $maquinas = Maquina::where('obra_id', $maquina->obra_id)->orderBy('nombre')->get();

        $usuario1 = auth()->user();
        $usuario1->name = html_entity_decode($usuario1->name, ENT_QUOTES, 'UTF-8');

        $usuario2 = null;
        if (Session::has('compañero_id')) {
            $usuario2 = User::find(Session::get('compañero_id'));
            if ($usuario2)
                $usuario2->name = html_entity_decode($usuario2->name, ENT_QUOTES, 'UTF-8');
        }

        // ✅ turnoHoy común a todos los flujos (incluida grúa)
        $turnoHoy = AsignacionTurno::where('user_id', auth()->id())
            ->whereDate('fecha', now())
            ->with('maquina')
            ->first();

        return compact('ubicacion', 'maquinas', 'usuario1', 'usuario2', 'turnoHoy');
    }


    private function cargarContextoGrua(Maquina $maquina): array
    {
        $ubicacionesDisponiblesPorProductoBase = [];

        // Nave (obra) de esta máquina
        $obraId = $maquina->obra_id;

        // 🟢 Máquinas de la misma nave
        $maquinasDisponibles = Maquina::select('id', 'nombre', 'codigo', 'diametro_min', 'diametro_max', 'obra_id')
            ->where('obra_id', $obraId)
            ->where('tipo', '!=', 'grua')   // 👈 fuera las grúas
            ->orderBy('nombre')
            ->get();


        // PENDIENTES: eager load estrecho + columns mínimos + misma nave + coladas
        $movimientosPendientes = Movimiento::with([
            'solicitadoPor:id,name',
            'producto.ubicacion:id,nombre',
            'productoBase:id,tipo,diametro,longitud',
            'pedido:id,codigo,peso_total,fabricante_id,distribuidor_id',
            'pedido.fabricante:id,nombre',
            'pedido.distribuidor:id,nombre',
            'pedidoProducto:id,pedido_id,codigo,producto_base_id,cantidad,cantidad_recepcionada,obra_id,estado,fecha_estimada_entrega',
            'pedidoProducto.coladas', // ✅ Cargar las coladas asociadas a la línea de pedido
        ])
            ->where('estado', 'pendiente')
            ->where('nave_id', $obraId)              // ⬅️ solo movimientos de la misma nave
            ->orderBy('prioridad', 'asc')
            ->get();


        // COMPLETADOS (últimos 20 ejecutados por mí) + misma nave
        $movimientosCompletados = Movimiento::with([
            'solicitadoPor:id,name',
            'ejecutadoPor:id,name',
            'producto.ubicacion:id,nombre',
            'productoBase:id,tipo,diametro,longitud',
            'pedidoProducto:id,pedido_id,producto_base_id,cantidad,cantidad_recepcionada,estado,fecha_estimada_entrega',
            'productoConsumido:id,codigo', // Para mostrar producto consumido en movimientos completados
        ])
            ->where('estado', 'completado')
            ->where('ejecutado_por', auth()->id())
            ->where('nave_id', $obraId)
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        // Ubicaciones disponibles por producto base - OPTIMIZADO: una sola query
        $productosBaseIds = $movimientosPendientes
            ->pluck('producto_base_id')
            ->filter()
            ->unique()
            ->values();

        if ($productosBaseIds->isNotEmpty()) {
            $productosCompatibles = Producto::with('ubicacion:id,nombre')
                ->whereIn('producto_base_id', $productosBaseIds)
                ->where('estado', 'almacenado')
                ->get()
                ->groupBy('producto_base_id');

            foreach ($productosCompatibles as $productoBaseId => $productos) {
                $ubicaciones = $productos->filter(fn($p) => $p->ubicacion)
                    ->map(fn($p) => [
                        'id' => $p->ubicacion->id,
                        'nombre' => $p->ubicacion->nombre,
                        'producto_id' => $p->id,
                        'codigo' => $p->codigo,
                    ])->unique('id')->values()->toArray();

                $ubicacionesDisponiblesPorProductoBase[$productoBaseId] = $ubicaciones;
            }
        }

        // Datos del mapa para la nave
        $mapaData = $this->obtenerDatosMapaParaNave($obraId);

        // ✅ Ubicaciones por sector para el modal de movimiento libre
        $nave = Obra::find($obraId);
        $codigoAlmacen = $nave ? Ubicacion::codigoDesdeNombreNave($nave->obra) : null;

        $ubicacionesPorSector = collect();
        $sectores = [];
        $sectorPorDefecto = null;

        if ($codigoAlmacen) {
            $ubicacionesPorSector = Ubicacion::where('almacen', $codigoAlmacen)
                ->orderBy('sector', 'asc')
                ->orderBy('ubicacion', 'asc')
                ->get()
                ->map(function ($ubicacion) {
                    $ubicacion->nombre_sin_prefijo = Str::after($ubicacion->nombre, 'Almacén ');
                    return $ubicacion;
                })
                ->groupBy('sector');

            $sectores = $ubicacionesPorSector->keys()->toArray();
            $sectorPorDefecto = !empty($sectores) ? $sectores[0] : null;
        }

        return [
            'movimientosPendientes' => $movimientosPendientes,
            'movimientosCompletados' => $movimientosCompletados,
            'ubicacionesDisponiblesPorProductoBase' => $ubicacionesDisponiblesPorProductoBase,
            'maquinasDisponibles' => $maquinasDisponibles,
            'mapaData' => $mapaData,
            'ubicacionesPorSector' => $ubicacionesPorSector,
            'sectores' => $sectores,
            'sectorPorDefecto' => $sectorPorDefecto,
        ];
    }

    /**
     * Carga el contexto específico para máquinas ensambladoras.
     * Usa la tabla orden_planillas_ensamblaje que ordena entidades individuales.
     */
    private function cargarContextoEnsambladora(Maquina $maquina): array
    {
        // Obtener entidades de la cola de ensamblaje (ordenadas por posición)
        $ordenesEnsamblaje = OrdenPlanillaEnsamblaje::with([
            'entidad.planilla.obra',
            'entidad.planilla.cliente',
            'entidad.etiquetasEnsamblaje.operario',
            'entidad.elementos'
        ])
            ->where('maquina_id', $maquina->id)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->orderBy('posicion', 'asc')
            ->take(5) // Máximo 5 entidades en vista
            ->get();

        // Filtrar solo entidades con todos elementos fabricados
        $ordenesListos = $ordenesEnsamblaje->filter(function ($orden) {
            return $orden->entidad && $orden->entidad->listaParaEnsamblaje();
        });

        // Obtener las entidades
        $entidadesActivas = $ordenesListos->map(fn($orden) => $orden->entidad)->values();

        // Obtener planillas únicas de las entidades activas
        $planillasActivas = $entidadesActivas
            ->pluck('planilla')
            ->filter()
            ->unique('id')
            ->values();

        // Obtener etiquetas de ensamblaje de las entidades activas
        $entidadIds = $entidadesActivas->pluck('id');
        $etiquetasEnsamblaje = EtiquetaEnsamblaje::with(['entidad', 'planilla', 'operario'])
            ->whereIn('planilla_entidad_id', $entidadIds)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->orderBy('planilla_entidad_id')
            ->orderBy('numero_unidad')
            ->get();

        // Agrupar etiquetas
        $etiquetasPorPlanilla = $etiquetasEnsamblaje->groupBy('planilla_id');
        $etiquetasPorEntidad = $etiquetasEnsamblaje->groupBy('planilla_entidad_id');

        // Estadísticas
        $totalEtiquetasPendientes = $etiquetasEnsamblaje->where('estado', 'pendiente')->count();
        $totalEtiquetasEnProceso = $etiquetasEnsamblaje->where('estado', 'en_proceso')->count();
        $totalCompletadas = EtiquetaEnsamblaje::whereIn('planilla_entidad_id', $entidadIds)
            ->where('estado', 'completada')
            ->count();

        // Elementos fabricados de las entidades activas
        $elementosFabricados = Elemento::with(['entidad', 'planilla'])
            ->whereIn('planilla_entidad_id', $entidadIds)
            ->where('elaborado', 1)
            ->get();

        $elementosPorDiametro = $elementosFabricados->groupBy('diametro')->sortKeys();

        // Órdenes con estado de elementos para mostrar progreso
        $ordenesConEstado = $ordenesEnsamblaje->map(function ($orden) {
            $orden->estado_elementos = $orden->entidad?->elementos_fabricados ?? [];
            return $orden;
        });

        return [
            'planillasActivas'          => $planillasActivas,
            'entidadesActivas'          => $entidadesActivas,
            'ordenesEnsamblaje'         => $ordenesConEstado,
            'etiquetasEnsamblaje'       => $etiquetasEnsamblaje,
            'etiquetasPorPlanilla'      => $etiquetasPorPlanilla,
            'etiquetasPorEntidad'       => $etiquetasPorEntidad,
            'elementosFabricados'       => $elementosFabricados,
            'elementosPorDiametro'      => $elementosPorDiametro,
            'totalEtiquetasPendientes'  => $totalEtiquetasPendientes,
            'totalEtiquetasEnProceso'   => $totalEtiquetasEnProceso,
            'totalCompletadas'          => $totalCompletadas,
        ];
    }

    private function obtenerDatosMapaParaNave(?int $naveId): array
    {
        if (!$naveId) {
            return [];
        }

        $obra = Obra::find($naveId);
        if (!$obra) {
            return [];
        }

        $anchoM = max(1, (int) ($obra->ancho_m ?? 22));
        $largoM = max(1, (int) ($obra->largo_m ?? 115));
        $columnasReales = $anchoM * 2;
        $filasReales = $largoM * 2;

        $ctx = [
            'naveId' => $naveId,
            'columnasReales' => $columnasReales,
            'filasReales' => $filasReales,
            'estaGirado' => false,
            'columnasVista' => $columnasReales,
            'filasVista' => $filasReales,
        ];

        $localizaciones = Localizacion::with('maquina:id,nombre')
            ->where('nave_id', $naveId)
            ->get();

        $localizacionesMaquinas = $localizaciones
            ->where('tipo', 'maquina')
            ->whereNotNull('maquina_id')
            ->filter(fn($loc) => $loc->maquina)
            ->map(function ($loc) {
                return [
                    'id' => (int) $loc->id,
                    'x1' => (int) $loc->x1,
                    'y1' => (int) $loc->y1,
                    'x2' => (int) $loc->x2,
                    'y2' => (int) $loc->y2,
                    'maquina_id' => (int) $loc->maquina_id,
                    'nombre' => (string) ($loc->nombre ?: $loc->maquina->nombre),
                ];
            })->values()->toArray();

        $localizacionesZonas = $localizaciones
            ->where('tipo', '!=', 'maquina')
            ->map(function ($loc) {
                $tipo = str_replace('-', '_', $loc->tipo ?? 'transitable');
                return [
                    'id' => (int) $loc->id,
                    'x1' => (int) $loc->x1,
                    'y1' => (int) $loc->y1,
                    'x2' => (int) $loc->x2,
                    'y2' => (int) $loc->y2,
                    'tipo' => $loc->tipo ?? 'transitable',
                    'nombre' => (string) $loc->nombre,
                ];
            })->values()->toArray();

        // OPTIMIZADO: join + eager load etiquetas.elementos para evitar N+1 en getTipoContenido()
        $paquetesConLocalizacion = Paquete::select('paquetes.*')
            ->join('localizaciones_paquetes', 'paquetes.id', '=', 'localizaciones_paquetes.paquete_id')
            ->where('paquetes.nave_id', $naveId)
            ->with(['localizacionPaquete', 'etiquetas.elementos'])
            ->get()
            ->map(function ($paquete) {
                $loc = $paquete->localizacionPaquete;
                return [
                    'id' => (int) $paquete->id,
                    'codigo' => (string) $paquete->codigo,
                    'x1' => (int) $loc->x1,
                    'y1' => (int) $loc->y1,
                    'x2' => (int) $loc->x2,
                    'y2' => (int) $loc->y2,
                    'tipo_contenido' => $paquete->getTipoContenido(),
                    'orientacion' => $paquete->orientacion ?? 'I',
                ];
            })->values()->toArray();

        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra' => $obra->obra,
        ];

        return [
            'ctx' => $ctx,
            'localizacionesZonas' => $localizacionesZonas,
            'localizacionesMaquinas' => $localizacionesMaquinas,
            'paquetesConLocalizacion' => $paquetesConLocalizacion,
            'dimensiones' => $dimensiones,
            'obraActualId' => $naveId,
            'mapaId' => 'mapa-modal-paquete-' . $naveId,
        ];
    }



    /**
     * Devuelve [planillaActiva, elementosFiltradosAPlanillaActiva]
     * según el orden manual (OrdenPlanilla) de esta máquina.
     */
    private function aplicarColaPlanillas(Maquina $maquina, Collection $elementos, int $cuantas = 1)
    {
        // 1) Agrupar por planilla
        $porPlanilla = $elementos->groupBy('planilla_id');

        // 2) Traer orden manual: [planilla_id => posicion]
        $ordenManual = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->pluck('posicion', 'planilla_id');

        // 3) Ordenar grupos por posición (los que no estén en la cola los manda al final)
        $porPlanillaOrdenado = $porPlanilla->sortBy(function ($grupo, $planillaId) use ($ordenManual) {
            return $ordenManual[$planillaId] ?? PHP_INT_MAX;
        });

        // 4) Seleccionar las N planillas activas (que estén en el orden manual)
        $planillasActivas = [];
        foreach ($porPlanillaOrdenado as $grupo) {
            $planilla = $grupo->first()->planilla;
            if (!$planilla || !$ordenManual->has($planilla->id)) {
                continue;
            }
            $planillasActivas[] = $planilla;
            if (count($planillasActivas) >= $cuantas) {
                break;
            }
        }

        // 5) Filtrar elementos a las planillas activas
        $idsActivos = collect($planillasActivas)->pluck('id');
        $elementosFiltrados = $idsActivos->isNotEmpty()
            ? $elementos->whereIn('planilla_id', $idsActivos)->values()
            : collect();

        // 6) (opcional) devolver también el mapa planilla_id => posición, por si quieres pintar la posición
        return [$planillasActivas, $elementosFiltrados, $ordenManual];
    }

    private function activarMovimientosSalidasHoy(): void
    {
        // 👉 Fecha actual (sin hora)
        $hoy = Carbon::today();

        // 🔎 Buscar todas las salidas programadas para hoy con sus paquetes
        $salidasHoy = Salida::with(['paquetes', 'camion', 'empresaTransporte', 'salidaClientes.obra', 'salidaClientes.cliente'])
            ->whereDate('fecha_salida', $hoy)
            ->get();

        foreach ($salidasHoy as $salida) {
            // 👉 Agrupar paquetes por nave_id
            $paquetesPorNave = $salida->paquetes->groupBy('nave_id')->filter(function ($grupo, $naveId) {
                return !empty($naveId); // Solo naves válidas (excluye null y strings vacíos)
            });

            // Si no hay paquetes con nave, no crear movimiento
            if ($paquetesPorNave->isEmpty()) {
                continue;
            }

            // 👉 Datos básicos de la salida (comunes para todos los movimientos)
            $camion = optional($salida->camion)->modelo ?? 'Sin modelo';
            $empresaTransporte = optional($salida->empresaTransporte)->nombre ?? 'Sin empresa';
            $horaSalida = \Carbon\Carbon::parse($salida->fecha_salida)->format('H:i');
            $codigoSalida = $salida->codigo_salida;

            // 👉 Armar listado de obras y clientes relacionados
            $obrasClientes = $salida->salidaClientes->map(function ($sc) {
                $obra = optional($sc->obra)->obra ?? 'Sin obra';
                $cliente = optional($sc->cliente)->empresa ?? 'Sin cliente';
                return "$obra - $cliente";
            })->filter()->implode(', ');

            // 👉 Crear un movimiento por cada nave donde haya paquetes
            foreach ($paquetesPorNave as $naveId => $paquetesEnNave) {
                // 🔎 Comprobar si ya existe un movimiento para esta salida Y esta nave
                $existeMovimiento = Movimiento::where('salida_id', $salida->id)
                    ->where('tipo', 'salida')
                    ->where('nave_id', $naveId)
                    ->exists();

                if (!$existeMovimiento) {
                    // 👉 Obtener nombre de la nave para la descripción
                    $nave = Obra::find($naveId);
                    $nombreNave = $nave->obra ?? 'Nave desconocida';
                    $numPaquetes = $paquetesEnNave->count();
                    $pesoTotal = $paquetesEnNave->sum('peso');

                    // 👉 Construir la descripción con info de la nave
                    $descripcion = "$codigoSalida. [$nombreNave] Cargar $numPaquetes paquete(s) (" . number_format($pesoTotal, 0) . " kg) - Camión ($camion) - ($empresaTransporte) para [$obrasClientes], listo a las $horaSalida";

                    // ⚡ Crear movimiento para esta nave
                    Movimiento::create([
                        'tipo' => 'salida',
                        'salida_id' => $salida->id,
                        'nave_id' => $naveId,
                        'estado' => 'pendiente',
                        'fecha_solicitud' => now(),
                        'solicitado_por' => null,
                        'prioridad' => 2,
                        'descripcion' => $descripcion,
                    ]);

                    Log::info("✅ Movimiento de salida creado para nave $nombreNave", [
                        'salida_id' => $salida->id,
                        'nave_id' => $naveId,
                        'paquetes' => $numPaquetes,
                        'peso' => $pesoTotal,
                    ]);
                }
            }
        }
    }

    private function activarMovimientosSalidasAlmacenHoy(): void
    {
        // 👉 Fecha actual (sin hora)
        $hoy = \Carbon\Carbon::today();

        // 🔎 Buscar todas las salidas de almacén programadas para hoy
        $salidasHoy = \App\Models\SalidaAlmacen::with(['camionero', 'albaranes.cliente'])
            ->whereDate('fecha', $hoy)
            ->get();

        $almacen = Obra::buscarDeCliente('Paco Reyes', 'Almacén');
        foreach ($salidasHoy as $salida) {
            // 🔎 Comprobar si ya existe un movimiento asociado a esta salida
            $existeMovimiento = Movimiento::where('salida_almacen_id', $salida->id)
                ->where('tipo', 'Salida Almacén')
                ->exists();

            if (!$existeMovimiento) {
                // 👉 Datos básicos
                $camionero = optional($salida->camionero)->name ?? 'Sin camionero';
                $horaSalida = $salida->fecha->format('H:i');
                $codigoSalida = $salida->codigo;

                // 👉 Clientes relacionados desde los albaranes
                $clientes = $salida->albaranes
                    ->map(fn($av) => optional($av->cliente)->nombre ?? 'Sin cliente')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                // 👉 Construir descripción
                $descripcion = "$codigoSalida. Se solicita carga de almacén (camionero: $camionero) "
                    . "para [$clientes], tiene que estar listo a las $horaSalida";

                // ⚡ Crear movimiento nuevo
                Movimiento::create([
                    'tipo' => 'Salida Almacén',
                    'salida_almacen_id' => $salida->id,
                    'nave_id' => $almacen?->id,
                    'estado' => 'pendiente',
                    'fecha_solicitud' => now(),
                    'solicitado_por' => null,
                    'prioridad' => 2,
                    'descripcion' => $descripcion,
                ]);
            }
        }
    }

    /**
     * Busca elementos con elaborado=0 que necesitan ser fabricados para mañana.
     *
     * Lógica:
     * 1. Buscar elementos con elaborado=0 cuya fecha de entrega es mañana
     * 2. La fecha de entrega puede venir del propio elemento (fecha_entrega) o de su planilla (fecha_estimada_entrega)
     * 3. Si el elemento tiene fecha_entrega, usar esa. Si no, usar la de la planilla.
     * 4. Crear movimientos agrupados por planilla para que se preparen esos elementos
     */
    private function activarMovimientosPreparacionPaquete(Maquina $grua): void
    {
        $manana = Carbon::tomorrow();

        Log::info("🔎 [Grúa] Buscando elementos sin elaborar (elaborado=0) con fecha de entrega para mañana ({$manana->format('d/m/Y')})");

        // 1. Buscar elementos con elaborado=0 que tienen fecha de entrega para mañana
        // Puede ser por fecha_entrega del elemento o por fecha_estimada_entrega de la planilla
        $elementosSinElaborar = Elemento::with(['planilla.cliente', 'planilla.obra', 'maquina'])
            ->where('elaborado', 0)
            ->where('longitud', '!=', 600) // ❌ excluir 6 metros
            ->where(function ($query) use ($manana) {
                $query->whereDate('fecha_entrega', $manana)
                    ->orWhere(function ($q) use ($manana) {
                        $q->whereNull('fecha_entrega')
                            ->whereHas('planilla', function ($planillaQuery) use ($manana) {
                                $planillaQuery->whereDate('fecha_estimada_entrega', $manana);
                            });
                    });
            })
            ->get();


        if ($elementosSinElaborar->isEmpty()) {
            // No hay elementos sin elaborar para mañana
            return;
        }

        Log::info("📦 Encontrados {$elementosSinElaborar->count()} elementos sin elaborar para mañana");

        // 2. Agrupar por planilla para crear movimientos más específicos
        $elementosPorPlanilla = $elementosSinElaborar->groupBy('planilla_id');

        foreach ($elementosPorPlanilla as $planillaId => $elementos) {
            // Verificar si ya existe un movimiento pendiente para esta planilla
            $existeMovimiento = Movimiento::where('tipo', 'Preparación elementos')
                ->where('estado', 'pendiente')
                ->whereRaw("descripcion LIKE ?", ["%planilla_id:{$planillaId}%"])
                ->exists();

            if ($existeMovimiento) {
                Log::info("⏭️ Ya existe movimiento pendiente para planilla {$planillaId}");
                continue;
            }

            $planilla = $elementos->first()->planilla;

            if (!$planilla) {
                Log::warning("⚠️ Elemento sin planilla asociada, planilla_id: {$planillaId}");
                continue;
            }

            $cliente = $planilla->cliente?->empresa ?? 'Cliente desconocido';
            $obra = $planilla->obra?->obra ?? 'Obra desconocida';
            $codigoPlanilla = $planilla->codigo ?? $planillaId;
            $numElementos = $elementos->count();
            $pesoTotal = $elementos->sum('peso');

            // Determinar la fecha de entrega efectiva (la más temprana de los elementos)
            $fechaEntregaEfectiva = $elementos->map(function ($elemento) {
                return $elemento->fecha_entrega ?? $elemento->planilla?->getRawOriginal('fecha_estimada_entrega');
            })->filter()->min();

            // Resumen de diámetros
            $resumenDiametros = $elementos
                ->groupBy('diametro')
                ->map(fn($grupo, $diametro) => $grupo->count() . "xØ{$diametro}")
                ->implode(', ');

            // Obtener máquinas donde están asignados estos elementos
            $maquinasAsignadas = $elementos
                ->filter(fn($e) => $e->maquina_id)
                ->pluck('maquina.nombre')
                ->unique()
                ->implode(', ') ?: 'Sin asignar';

            $descripcion = sprintf(
                "⚠️ URGENTE: Fabricar %d elementos (%.1f kg) de planilla %s [%s / %s]. Diámetros: %s. Máquinas: %s. [planilla_id:%d]",
                $numElementos,
                $pesoTotal,
                $codigoPlanilla,
                $cliente,
                $obra,
                $resumenDiametros,
                $maquinasAsignadas,
                $planillaId
            );

            // Crear movimiento
            Movimiento::create([
                'tipo' => 'Preparación elementos',
                'nave_id' => $grua->obra_id,
                'estado' => 'pendiente',
                'prioridad' => 1, // Alta prioridad porque es para mañana
                'fecha_solicitud' => now(),
                'descripcion' => $descripcion,
            ]);

            Log::info("✅ Movimiento 'Preparación elementos' creado", [
                'planilla_id' => $planillaId,
                'planilla_codigo' => $codigoPlanilla,
                'elementos_sin_elaborar' => $numElementos,
                'peso_total' => $pesoTotal,
                'maquinas' => $maquinasAsignadas,
                'fecha_entrega_efectiva' => $fechaEntregaEfectiva,
            ]);
        }
    }


    /**
     * Muestra la grúa en modo fabricación, cargando los elementos de una planilla específica
     * como si fuera una máquina normal de producción.
     */
    private function mostrarGruaComoMaquinaFabricacion(Maquina $maquina, int $planillaId, array $base)
    {
        $planilla = Planilla::with(['cliente', 'obra'])->find($planillaId);

        if (!$planilla) {
            return redirect()->route('maquinas.show', $maquina->id)
                ->with('error', 'Planilla no encontrada.');
        }

        // Obtener elementos de la planilla con elaborado=0
        $elementosFiltrados = Elemento::with([
            'planilla',
            'etiquetaRelacion',
            'subetiquetas',
            'maquina',
            'producto',
            'producto2',
            'producto3'
        ])
            ->where('planilla_id', $planillaId)
            ->where('elaborado', 0)
            ->get();

        if ($elementosFiltrados->isEmpty()) {
            return redirect()->route('maquinas.show', $maquina->id)
                ->with('info', 'No hay elementos pendientes de fabricar en esta planilla.');
        }

        // Construir datasets para la vista (mismo formato que máquinas normales)
        $pesosElementos = $elementosFiltrados
            ->map(fn($e) => ['id' => $e->id, 'peso' => $e->peso])
            ->values()
            ->toArray();

        $ordenSub = function ($grupo, $subId) {
            if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                return sprintf('%s-%010d', $m[1], (int) $m[2]);
            }
            return $subId . '-0000000000';
        };

        // Obtener etiqueta_sub_ids que están en grupos de resumen (resumidas)
        // OPTIMIZADO: Solo de esta planilla específica
        $etiquetasResumidas = Etiqueta::whereNotNull('grupo_resumen_id')
            ->where('planilla_id', $planillaId)
            ->pluck('etiqueta_sub_id')
            ->toArray();

        $etiquetasData = $elementosFiltrados
            ->filter(fn($e) => !empty($e->etiqueta_sub_id) && !in_array($e->etiqueta_sub_id, $etiquetasResumidas))
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub)
            ->map(fn($grupo, $subId) => [
                'codigo' => (string) $subId,
                'elementos' => $grupo->pluck('id')->toArray(),
                'pesoTotal' => $grupo->sum('peso'),
            ])
            ->values();

        $elementosAgrupados = $elementosFiltrados
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub);

        $elementosAgrupadosScript = $elementosAgrupados->map(function ($grupo) {
            $etiqueta = $grupo->first()->etiquetaRelacion;
            return [
                'etiqueta' => $etiqueta,
                'planilla' => $grupo->first()->planilla,
                // Coladas de la etiqueta (asignadas en primer y segundo clic)
                'colada_etiqueta' => $etiqueta?->producto?->n_colada,
                'colada_etiqueta_2' => $etiqueta?->producto2?->n_colada,
                'elementos' => $grupo->map(fn($e) => [
                    'id' => $e->id,
                    'codigo' => $e->codigo,
                    'dimensiones' => $e->dimensiones,
                    'estado' => $etiqueta?->estado ?? 'pendiente',
                    'peso' => $e->peso_kg,
                    'peso_numerico' => (float) $e->peso,
                    'diametro' => $e->diametro_mm,
                    'longitud' => $e->longitud_cm,
                    'barras' => $e->barras,
                    'figura' => $e->figura,
                    'paquete_id' => $e->paquete_id,
                    'etiqueta_sub_id' => $e->etiqueta_sub_id,
                    'coladas' => [
                        'colada1' => $e->producto ? $e->producto->n_colada : null,
                        'colada2' => $e->producto2 ? $e->producto2->n_colada : null,
                        'colada3' => $e->producto3 ? $e->producto3->n_colada : null,
                    ],
                ])->values(),
            ];
        })->values();

        // Sugerencias de productos base (vacías para grúa)
        $sugerenciasPorElemento = [];

        // Turno de hoy
        $turnoHoy = AsignacionTurno::where('user_id', auth()->id())
            ->whereDate('fecha', now())
            ->with('maquina')
            ->first();

        // Movimientos pendientes y completados
        $movimientosPendientes = collect();
        $movimientosCompletados = collect();

        // Máquinas disponibles de la misma nave (excluyendo grúas)
        $maquinasDisponibles = Maquina::select('id', 'nombre', 'codigo', 'diametro_min', 'diametro_max', 'obra_id')
            ->where('obra_id', $maquina->obra_id)
            ->where('tipo', '!=', 'grua')
            ->orderBy('nombre')
            ->get();

        // Variables adicionales para tipo-normal
        $elementosPorPlanilla = $elementosFiltrados->groupBy('planilla_id');
        $esBarra = strcasecmp($maquina->tipo_material ?? '', 'barra') === 0;

        $longitudesPorDiametro = $esBarra
            ? $elementosFiltrados->groupBy('diametro')->map(fn($g) => $g->pluck('longitud')->unique()->sort()->values())
            : collect();

        $diametroPorEtiqueta = $elementosFiltrados
            ->filter(fn($e) => !empty($e->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->map(fn($g) => $g->first()->diametro);

        return view('maquinas.show', array_merge(
            $base,
            [
                'maquina' => $maquina,
                'elementosMaquina' => $elementosFiltrados,
                'pesosElementos' => $pesosElementos,
                'etiquetasData' => $etiquetasData,
                'elementosAgrupados' => $elementosAgrupados,
                'elementosAgrupadosScript' => $elementosAgrupadosScript,
                'sugerenciasPorElemento' => $sugerenciasPorElemento,
                'planillasActivas' => collect([$planilla]),
                'turnoHoy' => $turnoHoy,
                'movimientosPendientes' => $movimientosPendientes,
                'movimientosCompletados' => $movimientosCompletados,
                'ubicacionesDisponiblesPorProductoBase' => [],
                'pedidosActivos' => collect(),
                'ordenManual' => collect(),
                'posicionesDisponibles' => collect(),
                'maquinasDisponibles' => $maquinasDisponibles,
                // Variables adicionales para tipo-normal
                'productoBaseSolicitados' => collect(),
                'elementosPorPlanilla' => $elementosPorPlanilla,
                'esBarra' => $esBarra,
                'longitudesPorDiametro' => $longitudesPorDiametro,
                'diametroPorEtiqueta' => $diametroPorEtiqueta,
                'posicion1' => null,
                'posicion2' => null,
                // Indicador de modo fabricación en grúa
                'modoFabricacionGrua' => true,
                'planillaFabricacion' => $planilla,
                // Ubicación (null para grúa, se asigna después en el mapa)
                'ubicacion' => null,
                // Productos base compatibles (vacío para grúa en modo fabricación)
                'productosBaseCompatibles' => collect(),
                // Grupos de resumen (vacíos para modo fabricación)
                'gruposResumen' => collect(),
                'etiquetasEnGrupos' => [],
                'etiquetasPreCargadas' => collect(), // Para evitar error de variable indefinida
            ]
        ));
    }

    public function create()
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }

        $clienteId = Cliente::where('empresa', 'Hierros Paco Reyes')->value('id');

        $obras = Obra::where('cliente_id', $clienteId)
            ->orderBy('obra')
            ->get();

        return view('maquinas.create', compact('obras'));
    }

    // Método para guardar la ubicación en la base de datos
    public function store(Request $request, ActionLoggerService $logger)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            // Validación de los datos del formulario
            $request->validate([
                'codigo' => 'required|string|unique:maquinas,codigo',
                'nombre' => 'required|string|max:40|unique:maquinas,nombre',
                'tipo' => 'nullable|string|max:50|in:grua,cortadora_dobladora,ensambladora,soldadora,cortadora_manual,dobladora_manual',
                'obra_id' => 'nullable|exists:obras,id',
                'diametro_min' => 'nullable|integer',
                'diametro_max' => 'nullable|integer',
                'peso_min' => 'nullable|integer',
                'peso_max' => 'nullable|integer',
                'ancho_m' => 'nullable|numeric|min:0.01',
                'largo_m' => 'nullable|numeric|min:0.01',


            ], [
                // Mensajes personalizados
                'codigo.required' => 'El campo "código" es obligatorio.',
                'codigo.string' => 'El campo "código" debe ser una cadena de texto.',
                'codigo.unique' => 'Ya existe una máquina con el mismo código.',

                'nombre.required' => 'El campo "nombre" es obligatorio.',
                'nombre.string' => 'El campo "nombre" debe ser una cadena de texto.',
                'nombre.max' => 'El campo "nombre" no puede tener más de 40 caracteres.',
                'nombre.unique' => 'Ya existe una máquina con el mismo nombre.',

                'tipo.string' => 'El campo "tipo" debe ser una cadena de texto.',
                'tipo.max' => 'El campo "tipo" no puede tener más de 50 caracteres.',
                'tipo.in' => 'El tipo no está entre los posibles.',

                'diametro_min.integer' => 'El campo "diámetro mínimo" debe ser un número entero.',
                'diametro_max.integer' => 'El campo "diámetro máximo" debe ser un número entero.',
                'peso_min.integer' => 'El campo "peso mínimo" debe ser un número entero.',
                'peso_max.integer' => 'El campo "peso máximo" debe ser un número entero.',
                'obra_id.exists' => 'La obra seleccionada no es válida.',

                'ancho_m.numeric' => 'El ancho debe ser un número.',
                'ancho_m.min' => 'El ancho debe ser mayor que cero.',
                'largo_m.numeric' => 'El largo debe ser un número.',
                'largo_m.min' => 'El largo debe ser mayor que cero.',

            ]);

            // Crear la nueva máquina en la base de datos
            $maquina = Maquina::create([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'tipo' => $request->tipo,
                'obra_id' => $request->obra_id,
                'diametro_min' => $request->diametro_min,
                'diametro_max' => $request->diametro_max,
                'peso_min' => $request->peso_min,
                'peso_max' => $request->peso_max,
                'ancho_m' => $request->ancho_m,
                'largo_m' => $request->largo_m,
            ]);

            $obra = $request->obra_id ? Obra::find($request->obra_id) : null;

            $logger->logMaquinas('maquina_creada', [
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'tipo' => $request->tipo ?? 'N/A',
                'obra' => $obra ? $obra->obra : 'N/A',
            ]);

            DB::commit();  // Confirmamos la transacción

            return redirect()->route('maquinas.index')->with('success', 'Máquina creada con éxito.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();  // Revertimos la transacción si hay error de validación
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();  // Revertimos la transacción si hay error general
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    public function guardarSesion(Request $request)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'users_id_2' => 'nullable|exists:users,id' // Ahora puede ser null
        ]);

        // Guardar el nuevo compañero en la sesión (o eliminar si es null)
        session(['compañero_id' => $request->users_id_2]);

        return response()->json(['success' => true]);
    }

    // TurnoController.php
    public function cambiarMaquina(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'nullable|exists:asignaciones_turnos,id',
            'nueva_maquina_id' => 'required|exists:maquinas,id',
        ]);

        // Si hay asignación de turno, actualizarla
        if ($request->asignacion_id) {
            $asignacion = AsignacionTurno::findOrFail($request->asignacion_id);
            $asignacion->maquina_id = $request->nueva_maquina_id;
            $asignacion->save();
        }

        // Redirigir a la nueva máquina seleccionada
        return redirect()->route('maquinas.show', $request->nueva_maquina_id);
    }

    public function cambiarEstado(Request $request, $id, ActionLoggerService $logger)
    {
        // Validar el estado recibido (puede ser nulo o string corto)
        $request->validate([
            'estado' => 'nullable|string|max:50',
        ]);

        // Buscar la máquina y actualizar estado
        $maquina = Maquina::findOrFail($id);
        $estadoAnterior = $maquina->estado;
        $maquina->estado = $request->input('estado', 'activa');
        $maquina->save();

        $logger->logMaquinas('estado_cambiado', [
            'codigo' => $maquina->codigo,
            'nombre' => $maquina->nombre,
            'estado_anterior' => $estadoAnterior ?? 'N/A',
            'estado_nuevo' => $maquina->estado,
        ]);

        // 🧠 Detectar si se espera una respuesta JSON (Ajax, fetch, etc.)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'estado' => $maquina->estado,
                'mensaje' => 'Estado actualizado correctamente.'
            ]);
        }

        // 🌐 Si no se espera JSON, redirigir normalmente
        return redirect()->back()->with('success', 'Estado de la máquina actualizado correctamente.');
    }
    public function edit($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        // Buscar la máquina por su ID
        $maquina = Maquina::findOrFail($id);

        // Retornar la vista con los datos de la máquina
        return view('maquinas.edit', compact('maquina'));
    }

    public function update(Request $request, $id, ActionLoggerService $logger)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'codigo' => 'required|string|unique:maquinas,codigo,' . $id,
            'nombre' => 'required|string|max:40',
            'tipo' => 'nullable|string|max:50|in:cortadora_dobladora,ensambladora,soldadora,cortadora manual,dobladora_manual,grua',
            'obra_id' => 'nullable|exists:obras,id',
            'diametro_min' => 'nullable|integer',
            'diametro_max' => 'nullable|integer',
            'peso_min' => 'nullable|integer',
            'peso_max' => 'nullable|integer',
            'ancho_m' => 'nullable|numeric|min:0.01',
            'largo_m' => 'nullable|numeric|min:0.01',
            'estado' => 'nullable|string|in:activa,en mantenimiento,inactiva',
        ], [
            'codigo.required' => 'El campo "código" es obligatorio.',
            'codigo.string' => 'El campo "código" debe ser una cadena de texto.',
            'codigo.unique' => 'El código ya existe, por favor ingrese otro diferente.',

            'nombre.required' => 'El campo "nombre" es obligatorio.',
            'nombre.string' => 'El campo "nombre" debe ser una cadena de texto.',
            'nombre.max' => 'El campo "nombre" no puede tener más de 40 caracteres.',

            'tipo.string' => 'El campo "tipo" debe ser texto.',
            'tipo.max' => 'El campo "tipo" no puede tener más de 50 caracteres.',
            'tipo.in' => 'El tipo no está entre los posibles.',

            'obra_id.exists' => 'La obra seleccionada no es válida.',

            'diametro_min.integer' => 'El "diámetro mínimo" debe ser un número entero.',
            'diametro_max.integer' => 'El "diámetro máximo" debe ser un número entero.',
            'peso_min.integer' => 'El "peso mínimo" debe ser un número entero.',
            'peso_max.integer' => 'El "peso máximo" debe ser un número entero.',

            'ancho_m.numeric' => 'El ancho debe ser un número.',
            'ancho_m.min' => 'El ancho debe ser mayor que cero.',
            'largo_m.numeric' => 'El largo debe ser un número.',
            'largo_m.min' => 'El largo debe ser mayor que cero.',

            'estado.in' => 'El estado debe ser: activa, en mantenimiento o inactiva.',
        ]);

        DB::beginTransaction();

        try {
            $maquina = Maquina::findOrFail($id);
            $maquina->update($validatedData);

            $obra = $validatedData['obra_id'] ? Obra::find($validatedData['obra_id']) : null;

            $logger->logMaquinas('maquina_actualizada', [
                'codigo' => $validatedData['codigo'],
                'nombre' => $validatedData['nombre'],
                'tipo' => $validatedData['tipo'] ?? 'N/A',
                'estado' => $validatedData['estado'] ?? 'N/A',
                'obra' => $obra ? $obra->obra : 'N/A',
            ]);

            DB::commit();

            return redirect()
                ->route('maquinas.index')
                ->with('success', 'La máquina se actualizó correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Hubo un problema al actualizar la máquina. Intenta nuevamente. Error: ' . $e->getMessage());
        }
    }



    public function actualizarImagen(Request $request, Maquina $maquina)
    {
        $request->validate([
            'imagen' => 'required|image|max:2048',
        ]);

        $nombreOriginal = $request->file('imagen')->getClientOriginalName();
        $nombreLimpio = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME));
        $extension = $request->file('imagen')->getClientOriginalExtension();
        $nombreFinal = $nombreLimpio . '.' . $extension;
        $directorio = public_path('maquinasImagenes');
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // ✅ Guardamos directamente en public/maquinasImagenes (evita conflicto con /maquinas)
        $request->file('imagen')->move(public_path('maquinasImagenes'), $nombreFinal);

        $maquina->imagen = 'maquinasImagenes/' . $nombreFinal;
        $maquina->save();

        return back()->with('success', 'Imagen actualizada correctamente.');
    }


    public function destroy($id, ActionLoggerService $logger)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('maquinas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        DB::beginTransaction();
        try {
            // Buscar la maquina a eliminar
            $maquina = Maquina::findOrFail($id);

            // Store data for logging before deletion
            $codigo = $maquina->codigo;
            $nombre = $maquina->nombre;
            $tipo = $maquina->tipo ?? 'N/A';

            // Eliminar la entrada
            $maquina->delete();

            $logger->logMaquinas('maquina_eliminada', [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo' => $tipo,
            ]);

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('maquinas.index')->with('success', 'Máquina eliminada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la entrada: ' . $e->getMessage());
        }
    }


    public function exportarBVBS(Request $request, Maquina $maquina, ProgressBVBSService $bvbs)
    {
        // 1️⃣ Obtener la posición del parámetro (por defecto 1)
        $posicion = (int) $request->query('posicion', 1);

        // 1️⃣ Obtener las planillas de la posición seleccionada para esta máquina
        $planillaIdsActivas = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->where('posicion', $posicion)
            ->pluck('planilla_id');
        $codigoProyecto = null;

        // Validar que hay planillas en esa posición
        if ($planillaIdsActivas->isEmpty()) {
            return redirect()->back()->with('error', "No hay planillas en la posición {$posicion} para esta máquina.");
        }

        if ($planillaIdsActivas->isNotEmpty()) {
            $planilla = Planilla::find($planillaIdsActivas->first());
            if ($planilla && preg_match('/-(\d{6})$/', $planilla->codigo, $m)) {
                $codigoProyecto = ltrim(substr($m[1], -4), '0'); // '006651' → '6651'
            }
        }

        // 2️⃣ Obtener los elementos de esas planillas y de esa máquina
        $elementos = Elemento::with(['planilla.obra', 'etiquetaRelacion'])
            ->whereIn('planilla_id', $planillaIdsActivas)
            ->where(function ($q) use ($maquina) {
                $q->where('maquina_id', $maquina->id)
                    ->orWhere('maquina_id_2', $maquina->id);
            })
            ->get();

        // Validar que hay elementos para exportar
        if ($elementos->isEmpty()) {
            return redirect()->back()->with('error', 'No hay elementos para exportar en las planillas activas de esta máquina.');
        }

        // 3️⃣ Mapear cada elemento a los campos que necesita el servicio BVBS
        $datos = $elementos->map(fn($el) => [
            'proyecto' => $codigoProyecto,
            'plano' => optional($el->etiquetaRelacion)->nombre,
            'indice' => $el->indice,
            'marca' => $el->etiqueta,
            'barras' => (int) $el->barras,
            'diametro' => (int) $el->diametro,
            'dimensiones' => (string) $el->dimensiones,
            'longitud' => $el->longitud_total_cm,
            'peso' => $el->peso,
            'mandril_mm' => $el->mandril_mm, // opcional
            'calidad' => 'B500SD',
            'capa' => $el->capa,
            'box' => $el->box,
        ])->all();

        // 4) Generar BVBS
        $contenido = $bvbs->exportarLote($datos);

        // 5) Nombre de archivo decente
        $timestamp = now()->format('Ymd_His');
        $maquinaTag = Str::upper(trim($maquina->codigo ?? $maquina->nombre ?? 'MAQUINA'));
        $maquinaTag = Str::of($maquinaTag)->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_');
        $proyectoTag = $codigoProyecto ? 'PRJ' . $codigoProyecto : 'SINPRJ';
        $filename = "BVBS_{$maquinaTag}_{$proyectoTag}_{$timestamp}.bvbs";

        // 6) Intentar guardar en carpeta compartida MSR20 (ruta principal)
        // IMPORTANTE: Solo intentar rutas de red en Windows (local). En Linux/producción las rutas UNC no funcionan.
        $esWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $rutasAIntentar = $esWindows ? [
            '\\\\192.168.0.10\\Datos\\Compartido\\COMPARTIDO_MAQUINA_MSR\\',  // Ruta UNC directa (más fiable)
            'M:\\COMPARTIDO_MAQUINA_MSR\\',                                     // Unidad mapeada (solo funciona si Apache corre como usuario)
        ] : [];
        $guardadoEnRed = false;
        $rutaFinal = null;
        $errores = [];

        $usuarioNombre = auth()->user()?->nombre_completo ?? 'Usuario desconocido';
        $maquinaNombre = $maquina->nombre ?? $maquina->codigo ?? 'Máquina sin nombre';

        foreach ($rutasAIntentar as $rutaBase) {
            $rutaCompleta = $rutaBase . $filename;

            // Intentar escribir directamente (a veces is_dir falla pero la escritura funciona)
            $resultado = @file_put_contents($rutaCompleta, $contenido);

            // Verificar que el archivo realmente se escribió (importante para rutas de red)
            if ($resultado !== false) {
                // Forzar flush y verificar que el archivo existe con contenido
                clearstatcache(true, $rutaCompleta);

                if (file_exists($rutaCompleta) && filesize($rutaCompleta) > 0) {
                    $guardadoEnRed = true;
                    $rutaFinal = $rutaCompleta;
                    Log::channel('produccion_piezas')->info("Export BVBS: {$usuarioNombre} exportó {$maquinaNombre} a {$rutaFinal}");
                    break;
                } else {
                    $errores[] = "{$rutaBase}: file_put_contents OK pero archivo no verificado (existe: " . (file_exists($rutaCompleta) ? 'sí' : 'no') . ")";
                    continue;
                }
            } else {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Error desconocido';
                $errores[] = "{$rutaBase}: {$errorMsg}";
            }
        }

        // Si se guardó en red, devolver respuesta de éxito sin descarga
        if ($guardadoEnRed) {
            return redirect()->back()->with('success', "Archivo BVBS exportado correctamente a: {$rutaFinal}");
        }

        // 7) Fallback: guardar en storage local y devolver descarga
        $path = "exports/bvbs/{$filename}";
        Storage::disk('local')->put($path, $contenido);

        Log::channel('produccion_piezas')->info("Export BVBS: {$usuarioNombre} exportó {$maquinaNombre} (descarga: {$filename})");

        return response()->download(
            Storage::disk('local')->path($path),
            $filename,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    /**
     * Redistribuye los elementos pendientes de una máquina en otras máquinas disponibles
     *
     * @param Request $request
     * @param int $id ID de la máquina
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Obtener elementos pendientes de una máquina (para previsualizar antes de redistribuir).
     */
    public function elementosPendientes(Request $request, $id)
    {
        $tipo = $request->query('tipo', 'todos');
        $soloRevisadas = $request->boolean('solo_revisadas', false);
        $limite = $request->query('limite') ? (int) $request->query('limite') : null;

        try {
            $maquinaOrigen = Maquina::findOrFail($id);

            // Obtener elementos pendientes de esta máquina con su planilla
            $elementosQuery = Elemento::with(['planilla'])
                ->where('maquina_id', $id)
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'asc');

            // Filtrar solo planillas revisadas si se solicita
            if ($soloRevisadas) {
                $elementosQuery->whereHas('planilla', function ($q) {
                    $q->where('revisada', true);
                });
            }

            // Aplicar límite personalizado o por tipo
            if ($limite) {
                $elementosQuery->limit($limite);
            } elseif ($tipo === 'primeros') {
                $elementosQuery->limit(50);
            }

            $elementos = $elementosQuery->get();

            // Obtener todas las máquinas disponibles (excluyendo la actual)
            $maquinasDisponibles = Maquina::where('id', '!=', $id)
                ->where('estado', '!=', 'fuera_servicio')
                ->select('id', 'nombre', 'tipo')
                ->orderBy('nombre')
                ->get();

            // Calcular a qué máquina iría cada elemento automáticamente
            // Usando transacción manual para simular sin persistir cambios
            $elementosConDestino = [];

            DB::beginTransaction();

            try {
                $asignarMaquinaService = new \App\Services\AsignarMaquinaService();
                // Excluir la máquina origen para que no se reasignen a la misma
                $asignarMaquinaService->excluirMaquinas([(int) $id]);

                // Guardar IDs originales
                $elementosIds = $elementos->pluck('id')->toArray();

                // Quitar asignación de máquina temporalmente
                Elemento::whereIn('id', $elementosIds)->update(['maquina_id' => null]);

                // Agrupar por planilla y redistribuir
                $elementosPorPlanilla = $elementos->groupBy('planilla_id');

                foreach ($elementosPorPlanilla as $planillaId => $grupoElementos) {
                    try {
                        $asignarMaquinaService->repartirPlanilla($planillaId);
                    } catch (\Exception $e) {
                        Log::warning("Error simulando redistribución de planilla {$planillaId}: " . $e->getMessage());
                    }
                }

                // Obtener las nuevas asignaciones calculadas
                $elementosActualizados = Elemento::whereIn('id', $elementosIds)
                    ->with('maquina')
                    ->get()
                    ->keyBy('id');

                // Construir array con destinos
                foreach ($elementos as $elemento) {
                    $elementoActualizado = $elementosActualizados->get($elemento->id);
                    $maquinaDestino = $elementoActualizado && $elementoActualizado->maquina
                        ? $elementoActualizado->maquina
                        : null;

                    $elementosConDestino[] = [
                        'id' => $elemento->id,
                        'codigo' => $elemento->codigo,
                        'dimensiones' => $elemento->dimensiones,
                        'diametro' => $elemento->diametro,
                        'peso' => $elemento->peso,
                        'barras' => $elemento->barras,
                        'figura' => $elemento->figura,
                        'maquina_destino_id' => $maquinaDestino ? $maquinaDestino->id : null,
                        'maquina_destino_nombre' => $maquinaDestino ? $maquinaDestino->nombre : 'Sin asignar'
                    ];
                }
            } finally {
                // IMPORTANTE: siempre hacer rollback para que no se guarden los cambios
                DB::rollBack();
            }

            return response()->json([
                'success' => true,
                'elementos' => $elementosConDestino,
                'total' => count($elementosConDestino),
                'maquina_origen' => [
                    'id' => $maquinaOrigen->id,
                    'nombre' => $maquinaOrigen->nombre
                ],
                'maquinas_disponibles' => $maquinasDisponibles
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener elementos pendientes de máquina {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al obtener elementos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function redistribuir(Request $request, $id)
    {
        $request->validate([
            'tipo' => 'required|in:primeros,todos,limitado',
            'solo_revisadas' => 'sometimes|boolean',
            'limite' => 'sometimes|integer|min:1',
        ]);

        $maquina = Maquina::findOrFail($id);
        $tipo = $request->input('tipo');
        $soloRevisadas = $request->boolean('solo_revisadas', false);
        $limite = $request->input('limite') ? (int) $request->input('limite') : null;

        try {
            // Obtener elementos pendientes de esta máquina
            $elementosQuery = Elemento::with(['planilla'])
                ->where('maquina_id', $id)
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'asc');

            // Filtrar solo planillas revisadas si se solicita
            if ($soloRevisadas) {
                $elementosQuery->whereHas('planilla', function ($q) {
                    $q->where('revisada', true);
                });
            }

            // Aplicar límite personalizado o por tipo
            if ($limite) {
                $elementosQuery->limit($limite);
            } elseif ($tipo === 'primeros') {
                $elementosQuery->limit(50);
            }

            $elementos = $elementosQuery->get();

            if ($elementos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No hay elementos pendientes para redistribuir en esta máquina.',
                ]);
            }

            // Guardar información original de cada elemento
            $detallesOriginales = [];
            foreach ($elementos as $elemento) {
                $detallesOriginales[$elemento->id] = [
                    'id' => $elemento->id,
                    'marca' => $elemento->marca,
                    'diametro' => $elemento->diametro,
                    'peso' => $elemento->peso,
                    'planilla_codigo' => $elemento->planilla ? $elemento->planilla->codigo : 'N/A',
                    'maquina_anterior' => $maquina->nombre,
                ];
            }

            // Quitar la asignación de máquina a estos elementos
            $elementosIds = $elementos->pluck('id')->toArray();
            Elemento::whereIn('id', $elementosIds)->update(['maquina_id' => null]);

            // Agrupar elementos por planilla
            $elementosPorPlanilla = $elementos->groupBy('planilla_id');

            $asignarMaquinaService = new AsignarMaquinaService();
            // Excluir la máquina origen para que no se reasignen a la misma
            $asignarMaquinaService->excluirMaquinas([(int) $id]);
            $redistribuidos = 0;

            // Redistribuir cada grupo de elementos usando el servicio de asignación
            foreach ($elementosPorPlanilla as $planillaId => $grupoElementos) {
                try {
                    // Repartir la planilla completa (solo reasignará los elementos sin máquina)
                    $asignarMaquinaService->repartirPlanilla($planillaId);
                    $redistribuidos += $grupoElementos->count();
                } catch (\Exception $e) {
                    Log::error("Error redistribuyendo planilla {$planillaId}: " . $e->getMessage());
                }
            }

            // Obtener las nuevas asignaciones
            $elementosActualizados = Elemento::with(['maquina'])
                ->whereIn('id', $elementosIds)
                ->get()
                ->keyBy('id');

            // Crear el detalle de la redistribución
            $detalles = [];
            $resumen = [];

            foreach ($detallesOriginales as $elementoId => $original) {
                $elementoActualizado = $elementosActualizados->get($elementoId);
                $nuevaMaquina = $elementoActualizado && $elementoActualizado->maquina
                    ? $elementoActualizado->maquina->nombre
                    : 'Sin asignar';

                $detalles[] = [
                    'elemento_id' => $elementoId,
                    'marca' => $original['marca'],
                    'diametro' => $original['diametro'],
                    'peso' => $original['peso'],
                    'planilla' => $original['planilla_codigo'],
                    'maquina_anterior' => $original['maquina_anterior'],
                    'maquina_nueva' => $nuevaMaquina,
                ];

                // Crear resumen por máquina
                if (!isset($resumen[$nuevaMaquina])) {
                    $resumen[$nuevaMaquina] = [
                        'nombre' => $nuevaMaquina,
                        'cantidad' => 0,
                        'peso_total' => 0,
                    ];
                }
                $resumen[$nuevaMaquina]['cantidad']++;
                $resumen[$nuevaMaquina]['peso_total'] += (float) $original['peso'];
            }

            // Convertir resumen a array de valores
            $resumen = array_values($resumen);

            // Consolidar posiciones adyacentes en todas las máquinas afectadas
            $maquinasAfectadas = $elementosActualizados->pluck('maquina_id')->unique()->filter()->toArray();
            $maquinasAfectadas[] = $id; // Incluir la máquina origen
            foreach (array_unique($maquinasAfectadas) as $maquinaAfectadaId) {
                $this->consolidarPosicionesAdyacentes($maquinaAfectadaId);
            }

            $mensaje = $tipo === 'todos'
                ? "Se redistribuyeron {$redistribuidos} elementos de toda la cola de trabajo."
                : "Se redistribuyeron los primeros {$redistribuidos} elementos de la cola.";

            Log::info("Máquina {$maquina->id} ({$maquina->nombre}): {$mensaje}");

            return response()->json([
                'success' => true,
                'mensaje' => $mensaje,
                'redistribuidos' => $redistribuidos,
                'detalles' => $detalles,
                'resumen' => $resumen,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en redistribuir máquina {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al redistribuir elementos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Completa manualmente la planilla actual de una máquina
     * Verifica que todas las etiquetas estén en paquetes y elimina la planilla de la cola
     */
    public function completarPlanillaManual(Request $request, $id)
    {
        try {
            $maquina = Maquina::findOrFail($id);

            // Compatibilidad: acepta formato nuevo (posicion) o viejo (posicion_1/posicion_2)
            $posicion = $request->input('posicion');
            $planillaIdEnviado = $request->input('planilla_id');
            $planillaCodigoEnviado = $request->input('planilla_codigo');

            // Si viene formato viejo, convertir
            if (!$posicion) {
                $pos1 = $request->input('posicion_1');
                $pos2 = $request->input('posicion_2');

                // Determinar cuál es válida
                $pos1Valida = $pos1 && $pos1 !== '0' && $pos1 !== 0;
                $pos2Valida = $pos2 && $pos2 !== '0' && $pos2 !== 0;

                if (!$pos1Valida && !$pos2Valida) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Debes seleccionar una planilla (posición diferente de 0)'
                    ], 400);
                }

                if ($pos1Valida && $pos2Valida) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para completar, debes tener solo UNA planilla seleccionada. Cambia una de las posiciones a 0 y recarga la página.'
                    ], 400);
                }

                $posicion = $pos1Valida ? $pos1 : $pos2;
            }

            if (!$posicion || $posicion === '0' || $posicion === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes seleccionar una planilla (posición diferente de 0)'
                ], 400);
            }

            DB::beginTransaction();

            // Buscar la planilla en esa posición para esta máquina
            $ordenPlanilla = OrdenPlanilla::where('maquina_id', $maquina->id)
                ->where('posicion', $posicion)
                ->lockForUpdate()
                ->first();

            if (!$ordenPlanilla) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró planilla en la posición {$posicion}"
                ], 400);
            }

            $planilla = $ordenPlanilla->planilla;

            // VALIDACIÓN DE SEGURIDAD: Verificar que la planilla en esa posición coincide con la enviada
            if ($planillaIdEnviado && (int)$planillaIdEnviado !== $planilla->id) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Error de sincronización: La planilla en posición {$posicion} es '{$planilla->codigo}', pero se esperaba '{$planillaCodigoEnviado}'. Por favor, recarga la página."
                ], 400);
            }

            // Verificar que todas las etiquetas de esa planilla EN ESTA MÁQUINA tengan paquete asignado
            // Usamos elementosPorId para relacionar por etiqueta_id (FK directa)
            $etiquetasSinPaquete = $planilla->etiquetas()
                ->whereDoesntHave('paquete')
                ->whereHas('elementosPorId', function ($q) use ($maquina) {
                    $q->where('maquina_id', $maquina->id)
                        ->orWhere('maquina_id_2', $maquina->id);
                })
                ->count();

            if ($etiquetasSinPaquete > 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "La planilla {$planilla->codigo} (Pos. {$posicion}) aún tiene {$etiquetasSinPaquete} etiqueta(s) sin paquete asignado en esta máquina"
                ], 400);
            }

            // Guardar posición para reordenar
            $posicionEliminada = $ordenPlanilla->posicion;

            // Eliminar de la cola
            $ordenPlanilla->delete();

            // Reordenar posiciones posteriores
            OrdenPlanilla::where('maquina_id', $maquina->id)
                ->where('posicion', '>', $posicionEliminada)
                ->decrement('posicion');

            // Si hay elementos con maquina_id_2 sin procesar,
            // cambiar etiquetas a 'fabricada' para que aparezcan en cola de la dobladora
            $etiquetasConPendienteEnMaquina2 = $planilla->etiquetas()
                ->whereHas('elementosPorId', function ($q) {
                    $q->whereNotNull('maquina_id_2')
                      ->where(function ($subQ) {
                          $subQ->whereNull('estado2')
                               ->orWhere('estado2', 'pendiente');
                      });
                })
                ->whereNotIn('estado', ['fabricada', 'pendiente'])
                ->get();

            foreach ($etiquetasConPendienteEnMaquina2 as $etiquetaPendiente) {
                $etiquetaPendiente->estado = 'fabricada';
                $etiquetaPendiente->save();
            }

            DB::commit();

            Log::info("Planilla {$planilla->codigo} completada manualmente en máquina {$maquina->nombre} (Pos. {$posicion})");

            return response()->json([
                'success' => true,
                'message' => "Planilla {$planilla->codigo} completada correctamente",
                'planilla_completada' => $planilla->codigo
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al completar planilla manual en máquina {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al completar planilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comprimir etiquetas: agrupa elementos hermanos en mismas subetiquetas (máx 5 por sub).
     * Solo para MSR20 o máquinas tipo encarretado.
     * Filtra por las posiciones de planilla seleccionadas.
     */
    public function comprimirEtiquetas(Request $request, $id)
    {
        $maquina = Maquina::findOrFail($id);

        // Obtener posiciones del request (array de enteros)
        $posiciones = collect($request->input('posiciones', []))
            ->filter(fn($p) => is_numeric($p) && (int) $p > 0)
            ->map(fn($p) => (int) $p)
            ->values()
            ->toArray();

        /** @var \App\Services\SubEtiquetaService $svc */
        $svc = app(\App\Services\SubEtiquetaService::class);
        $resultado = $svc->comprimirEtiquetasPorMaquina((int) $id, $posiciones);

        return response()->json($resultado);
    }

    /**
     * Descomprimir etiquetas: separa elementos en subetiquetas individuales (1 elemento = 1 sub).
     * Filtra por las posiciones de planilla seleccionadas.
     */
    public function descomprimirEtiquetas(Request $request, $id)
    {
        $maquina = Maquina::findOrFail($id);

        // Obtener posiciones del request (array de enteros)
        $posiciones = collect($request->input('posiciones', []))
            ->filter(fn($p) => is_numeric($p) && (int) $p > 0)
            ->map(fn($p) => (int) $p)
            ->values()
            ->toArray();

        /** @var \App\Services\SubEtiquetaService $svc */
        $svc = app(\App\Services\SubEtiquetaService::class);
        $resultado = $svc->descomprimirEtiquetasPorMaquina((int) $id, $posiciones);

        return response()->json($resultado);
    }

    /**
     * Obtener movimientos completados para una nave (AJAX)
     */
    public function getMovimientosCompletados($naveId)
    {
        $movimientosCompletados = Movimiento::with([
            'solicitadoPor:id,name,primer_apellido',
            'ejecutadoPor:id,name,primer_apellido',
        ])
            ->where('estado', 'completado')
            ->where('nave_id', $naveId)
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'tipo' => ucfirst($m->tipo),
                    'descripcion_html' => $m->descripcion_html,
                    'solicitado_por' => $m->solicitadoPor
                        ? trim($m->solicitadoPor->name . ' ' . ($m->solicitadoPor->primer_apellido ?? ''))
                        : 'N/A',
                    'ejecutado_por' => $m->ejecutadoPor
                        ? trim($m->ejecutadoPor->name . ' ' . ($m->ejecutadoPor->primer_apellido ?? ''))
                        : 'N/A',
                    'fecha_completado' => $m->updated_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'movimientos' => $movimientosCompletados
        ]);
    }

    /**
     * Consolida posiciones adyacentes de la misma planilla en una máquina.
     * Si dos posiciones consecutivas tienen la misma planilla_id, elimina la duplicada
     * y recompacta las posiciones.
     */
    private function consolidarPosicionesAdyacentes(int $maquinaId): void
    {
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->orderBy('posicion')
            ->get();

        if ($ordenes->count() < 2) {
            return;
        }

        $ordenesAEliminar = [];
        $ordenAnterior = null;

        foreach ($ordenes as $orden) {
            if ($ordenAnterior && $ordenAnterior->planilla_id === $orden->planilla_id) {
                // Posiciones adyacentes con la misma planilla: marcar para eliminar la segunda
                $ordenesAEliminar[] = $orden->id;
                Log::info("🔗 Consolidando posiciones adyacentes", [
                    'maquina_id' => $maquinaId,
                    'planilla_id' => $orden->planilla_id,
                    'posicion_eliminada' => $orden->posicion,
                    'posicion_mantenida' => $ordenAnterior->posicion,
                ]);
            } else {
                $ordenAnterior = $orden;
            }
        }

        if (!empty($ordenesAEliminar)) {
            // Eliminar los duplicados
            OrdenPlanilla::whereIn('id', $ordenesAEliminar)->delete();

            // Recompactar posiciones
            $this->recompactarPosiciones($maquinaId);

            Log::info("✅ Posiciones consolidadas y recompactadas", [
                'maquina_id' => $maquinaId,
                'ordenes_eliminados' => count($ordenesAEliminar),
            ]);
        }
    }

    /**
     * Recompacta las posiciones de una máquina para que sean consecutivas (1, 2, 3, ...)
     */
    private function recompactarPosiciones(int $maquinaId): void
    {
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->orderBy('posicion')
            ->get();

        $posicion = 1;
        foreach ($ordenes as $orden) {
            if ($orden->posicion !== $posicion) {
                $orden->update(['posicion' => $posicion]);
            }
            $posicion++;
        }
    }
}
