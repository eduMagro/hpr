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

            $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', $hoy)
                ->whereNotNull('maquina_id')
                ->whereNotNull('turno_id')
                ->first();

            if (!$asignacion) {
                // 👉 No encontró turno para hoy, probamos para mañana
                $asignacion = AsignacionTurno::where('user_id', $usuario->id)
                    ->whereDate('fecha', $maniana)
                    ->whereNotNull('maquina_id')
                    ->whereNotNull('turno_id')
                    ->first();
            }


            if (!$asignacion) {
                abort(403, 'No has fichado entrada');
            }

            $maquinaId = $asignacion->maquina_id;
            $turnoId   = $asignacion->turno_id;

            // Buscar compañero
            $compañero = AsignacionTurno::where('maquina_id', $maquinaId)
                ->where('turno_id', $turnoId)
                ->where('user_id', '!=', $usuario->id)
                ->latest()
                ->first();

            session(['compañero_id' => optional($compañero)->user_id]);

            return redirect()->route('maquinas.show', ['maquina' => $maquinaId]);
        }

        /* ───────────────────────────────────────────
     * 2️⃣  RESTO DE USUARIOS
     * ─────────────────────────────────────────── */

        // ▸ 2.1 Consulta de máquinas + conteos
        $query = Maquina::with('productos')
            ->selectRaw('maquinas.*, (
            SELECT COUNT(*) FROM elementos
            WHERE elementos.maquina_id_2 = maquinas.id
        ) as elementos_ensambladora')
            ->withCount([
                'elementos as elementos_count' => fn($q) =>
                $q->where('estado', '!=', 'fabricado')
            ]);

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->input('nombre') . '%');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $order  = $request->input('order', 'desc');
        if (Schema::hasColumn('maquinas', $sortBy)) {
            $query->orderBy($sortBy, $order);
        }

        $perPage = $request->input('per_page', 30);
        $registrosMaquina = $query->paginate($perPage)
            ->appends($request->except('page'));

        // ▸ 2.2 Operarios asignados hoy
        $hoy = Carbon::today();
        $usuariosPorMaquina = AsignacionTurno::with(['user', 'turno'])
            ->whereDate('fecha', $hoy)
            ->whereNotNull('maquina_id')
            ->get()
            ->groupBy('maquina_id');
        $obras = Obra::whereHas('cliente', function ($query) {
            $query->where('empresa', 'like', '%Hierros Paco Reyes%');
        })
            ->orderBy('obra')
            ->get();
        // ▸ 2.3 Render vista
        return view('maquinas.index', compact(
            'registrosMaquina',
            'usuariosPorMaquina',
            'obras'
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
        // 0) Máquina + relaciones base
        $maquina = Maquina::with([
            'elementos.planilla',
            'elementos.etiquetaRelacion',
            'elementos.subetiquetas',
            'elementos.maquina',
            'elementos.maquina_2',
            'elementos.maquina_3',
            'productos',
            // ✅ COLADAS: Cargar relaciones de productos en elementos (trazabilidad completa)
            'elementos.producto',
            'elementos.producto2',
            'elementos.producto3',
        ])->findOrFail($id);
        // 1) Contexto común (incluye productosBaseCompatibles en $base)
        $base = $this->cargarContextoBase($maquina);

        // 2) Rama GRÚA: devolver pronto con variables neutras de máquina
        if ($this->esGrua($maquina)) {
            $grua = $this->cargarContextoGrua($maquina);
            $this->activarMovimientosSalidasHoy();
            $this->activarMovimientosSalidasAlmacenHoy();
            return view('maquinas.show', array_merge(
                $base,
                [
                    'maquina'          => $maquina,
                    'elementosMaquina' => collect(),
                    'pesosElementos'   => [],
                    'etiquetasData'    => collect(),
                ],
                $grua // ← prioridad para movimientos* y demás de la grúa
            ));
        }

        // 3) Elementos de la máquina (primera o segunda)
        if ($this->esSegundaMaquina($maquina)) {
            $elementosMaquina = Elemento::with(['planilla', 'etiquetaRelacion', 'subetiquetas', 'maquina', 'maquina_2', 'producto', 'producto2', 'producto3'])
                ->where('maquina_id_2', $maquina->id)
                ->get();
        } else {
            $elementosMaquina = Elemento::with(['planilla', 'etiquetaRelacion', 'subetiquetas', 'maquina', 'producto', 'producto2', 'producto3'])
                ->where('maquina_id', $maquina->id)
                ->get();
        }

        // 4) Cola de planillas con lógica de salto de planillas sin revisar
        // ⚠️ LÓGICA ESTRICTA: Solo planillas revisadas entran en planificación
        $ordenesPlanillas = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->with('planilla')
            ->orderBy('posicion', 'asc')
            ->get();

        // Obtener posiciones del request o calcular automáticamente
        $posicion1 = request('posicion_1');
        $posicion2 = request('posicion_2');

        // Si no hay posiciones en el request, buscar las primeras dos posiciones con planillas revisadas
        if (is_null($posicion1) && is_null($posicion2)) {
            foreach ($ordenesPlanillas as $orden) {
                if ($orden->planilla && $orden->planilla->revisada) {
                    if (is_null($posicion1)) {
                        $posicion1 = $orden->posicion;
                    } elseif (is_null($posicion2)) {
                        $posicion2 = $orden->posicion;
                        break; // Ya tenemos las dos posiciones
                    }
                }
            }
        }

        // Filtrar posiciones válidas (mayores a 0)
        $posiciones = collect([$posicion1, $posicion2])
            ->filter(fn($p) => !is_null($p) && (int)$p > 0)
            ->map(fn($p) => (int)$p)
            ->unique()
            ->values();

        // Cola de planillas con posiciones específicas
        [$planillasActivas, $elementosFiltrados, $ordenManual, $posicionesDisponibles] =
            $this->aplicarColaPlanillasPorPosicion($maquina, $elementosMaquina, $posiciones);

        // 5) Datasets filtrados por planilla
        $elementosPorPlanilla = $elementosFiltrados->groupBy('planilla_id');

        // 6) Datasets para UI (canvas/tabla)
        $pesosElementos = $elementosFiltrados
            ->map(fn($e) => ['id' => $e->id, 'peso' => $e->peso])
            ->values()
            ->toArray();

        $ordenSub = function ($grupo, $subId) {
            if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                return sprintf('%s-%010d', $m[1], (int)$m[2]);
            }
            return $subId . '-0000000000';
        };

        $etiquetasData = $elementosFiltrados
            ->filter(fn($e) => !empty($e->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub)
            ->map(fn($grupo, $subId) => [
                'codigo'    => (string)$subId,
                'elementos' => $grupo->pluck('id')->toArray(),
                'pesoTotal' => $grupo->sum('peso'),
            ])
            ->values();

        $elementosReempaquetados = session('elementos_reempaquetados', []);

        $elementosAgrupados = $elementosFiltrados
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub);

        $elementosAgrupadosScript = $elementosAgrupados->map(fn($grupo) => [
            'etiqueta'  => $grupo->first()->etiquetaRelacion,
            'planilla'  => $grupo->first()->planilla,
            'elementos' => $grupo->map(fn($e) => [
                'id'          => $e->id,
                'codigo'      => $e->codigo,
                'dimensiones' => $e->dimensiones,
                'estado'      => $e->estado,
                'peso'        => $e->peso_kg,
                'diametro'    => $e->diametro_mm,
                'longitud'    => $e->longitud_cm,
                'barras'      => $e->barras,
                'figura'      => $e->figura,
                // Incluimos las coladas para mostrarlas en la leyenda del SVG
                'coladas'     => [
                    'colada1' => $e->producto ? $e->producto->n_colada : null,
                    'colada2' => $e->producto2 ? $e->producto2->n_colada : null,
                    'colada3' => $e->producto3 ? $e->producto3->n_colada : null,
                ],
            ])->values(),
        ])->values();

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

        // 8) Sugerencias de corte (sobre elementos filtrados y PB barra)
        $productosBaseCompatibles = collect($base['productosBaseCompatibles'] ?? []);
        $productosBarra = $productosBaseCompatibles->filter(function ($pb) {
            $tipo = strtolower((string)($pb->tipo ?? ''));
            if (str_contains($tipo, 'barra') || str_contains($tipo, 'varilla') || str_contains($tipo, 'corrug')) {
                return true;
            }
            $v = (float)str_replace(',', '.', (string)($pb->longitud ?? 0));
            return $v > 1 && $v < 50; // metros razonables
        })->values();
        if ($productosBarra->isEmpty()) {
            $productosBarra = $productosBaseCompatibles;
        }

        $planillasOrden = $elementosFiltrados->pluck('planilla_id')->unique()->values();
        $idxPlanilla    = $planillasOrden->flip();
        $colegasDe = function ($el) use ($elementosFiltrados, $planillasOrden, $idxPlanilla) {
            $i   = (int)($idxPlanilla[$el->planilla_id] ?? 0);
            $ids = $planillasOrden->slice($i, 3); // actual + 2 siguientes
            return $elementosFiltrados->whereIn('planilla_id', $ids)->values();
        };

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
            ->groupBy(fn($pb) => (int)$pb->diametro)
            ->map(
                fn($g) => $g->pluck('longitud')
                    ->filter(fn($L) => is_numeric($L) && $L > 0)
                    ->map(fn($L) => (float)$L)
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
                $c = collect($els)->pluck('diametro')->filter()->map(fn($d) => (int)$d);
                return (int) $c->countBy()->sortDesc()->keys()->first();
            })
            ->toArray();
        Log::info('[MaquinaController@show] 🧩 Planillas activas en cola:', collect($planillasActivas)->pluck('id')->toArray());

        Log::info('[MaquinaController@show] 🧩 Planillas presentes en elementosFiltrados:', $elementosFiltrados->pluck('planilla_id')->unique()->toArray());

        // 11) Devolver vista
        return view('maquinas.show', array_merge($base, [
            // base
            'maquina' => $maquina,

            // cola / filtrados
            'planillasActivas'      => $planillasActivas,
            'elementosFiltrados'    => $elementosFiltrados,
            'elementosPorPlanilla'  => $elementosPorPlanilla,
            'posicionesDisponibles' => $posicionesDisponibles,
            'posicion1'             => $posicion1,
            'posicion2'             => $posicion2,
            // datasets UI
            'elementosMaquina'         => $elementosMaquina,
            'pesosElementos'           => $pesosElementos,
            'etiquetasData'            => $etiquetasData,
            'elementosReempaquetados'  => $elementosReempaquetados,
            'elementosAgrupados'       => $elementosAgrupados,
            'elementosAgrupadosScript' => $elementosAgrupadosScript,
            'sugerenciasPorElemento'   => $sugerenciasPorElemento,

            // extra contexto
            'turnoHoy'                             => $turnoHoy,
            'movimientosPendientes'                => $movimientosPendientes,
            'movimientosCompletados'               => $movimientosCompletados,
            'ubicacionesDisponiblesPorProductoBase' => $ubicacionesDisponiblesPorProductoBase,
            'pedidosActivos'                       => $pedidosActivos,
            'productoBaseSolicitados'              => $productoBaseSolicitados,

            // barra
            'esBarra'               => $esBarra,
            'longitudesPorDiametro' => $longitudesPorDiametro,
            'diametroPorEtiqueta'   => $diametroPorEtiqueta,
        ]));
    }

    /* =========================
   HELPERS PRIVADOS
   ========================= */

    /**
     * 🔥 NUEVO MÉTODO: Obtiene planillas según posiciones específicas
     * 
     * @param Maquina $maquina
     * @param Collection $elementos
     * @param Collection $posiciones - Collection de posiciones a mostrar [1, 3, 5...]
     * @return array [$planillasActivas, $elementosFiltrados, $ordenManual, $posicionesDisponibles]
     */
    private function aplicarColaPlanillasPorPosicion(Maquina $maquina, Collection $elementos, Collection $posiciones)
    {
        // 1) Agrupar elementos por planilla
        $porPlanilla = $elementos->groupBy('planilla_id');

        // 2) Traer orden manual completo: [planilla_id => posicion]
        $ordenManual = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->orderBy('posicion', 'asc')
            ->get()
            ->pluck('posicion', 'planilla_id');

        // 3) Crear un mapa inverso [posicion => planilla_id] para búsqueda rápida
        $posicionAPlanilla = $ordenManual->flip();

        // 4) Obtener todas las posiciones disponibles (solo planillas REVISADAS)
        $posicionesDisponibles = [];
        foreach ($ordenManual as $planillaId => $posicion) {
            if ($porPlanilla->has($planillaId)) {
                $planilla = $porPlanilla[$planillaId]->first()->planilla;
                // Solo incluir posiciones con planillas revisadas
                if ($planilla && $planilla->revisada) {
                    $posicionesDisponibles[] = $posicion;
                }
            }
        }
        sort($posicionesDisponibles);

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
                        Log::info("✅ Planilla {$planilla->codigo} (posición {$pos}) añadida - REVISADA");
                    } else {
                        Log::warning("⚠️ Planilla en posición {$pos} OMITIDA - No está revisada");
                    }
                }
            }
        }

        // 6) Filtrar elementos a las planillas activas
        $idsActivos = collect($planillasActivas)->pluck('id');
        $elementosFiltrados = $idsActivos->isNotEmpty()
            ? $elementos->whereIn('planilla_id', $idsActivos)->values()
            : collect();

        return [$planillasActivas, $elementosFiltrados, $ordenManual, $posicionesDisponibles];
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
        return stripos((string)$m->tipo, 'grua') !== false || stripos((string)$m->nombre, 'grua') !== false;
    }

    // Si tienes un campo explícito para “segunda” úsalo aquí.
    // Por defecto asumo “segunda” = máquinas que trabajan como post-proceso, p.ej. ensambladora.
    private function esSegundaMaquina(Maquina $m): bool
    {
        $tipo = strtolower((string)$m->tipo);

        return str_contains($tipo, 'ensambladora')
            || str_contains($tipo, 'dobladora manual')   // 👈 añade esto
            || (property_exists($m, 'orden') && (int)$m->orden === 2);
    }


    private function cargarContextoBase(Maquina $maquina): array
    {
        $ubicacion = Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();
        $maquinas  = Maquina::orderBy('nombre')->get();

        $productosBaseCompatibles = ProductoBase::where('tipo', $maquina->tipo_material)
            ->whereBetween('diametro', [$maquina->diametro_min, $maquina->diametro_max])
            ->orderBy('diametro')
            ->get();

        $usuario1 = auth()->user();
        $usuario1->name = html_entity_decode($usuario1->name, ENT_QUOTES, 'UTF-8');

        $usuario2 = null;
        if (Session::has('compañero_id')) {
            $usuario2 = User::find(Session::get('compañero_id'));
            if ($usuario2) $usuario2->name = html_entity_decode($usuario2->name, ENT_QUOTES, 'UTF-8');
        }

        // ✅ turnoHoy común a todos los flujos (incluida grúa)
        $turnoHoy = AsignacionTurno::where('user_id', auth()->id())
            ->whereDate('fecha', now())
            ->with('maquina')
            ->first();

        return compact('ubicacion', 'maquinas', 'productosBaseCompatibles', 'usuario1', 'usuario2', 'turnoHoy');
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


        // PENDIENTES: eager load estrecho + columns mínimos + misma nave
        $movimientosPendientes = Movimiento::with([
            'solicitadoPor:id,name',
            'producto.ubicacion:id,nombre',
            'productoBase:id,tipo,diametro,longitud',
            'pedido:id,codigo,peso_total,fabricante_id,distribuidor_id',
            'pedido.fabricante:id,nombre',
            'pedido.distribuidor:id,nombre',
            'pedidoProducto:id,pedido_id,codigo,producto_base_id,cantidad,cantidad_recepcionada,obra_id,estado,fecha_estimada_entrega',
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
        ])
            ->where('estado', 'completado')
            ->where('ejecutado_por', auth()->id())
            ->where('nave_id', $obraId)              // ⬅️ misma nave
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        // JSON compacto para el front (incluye LA LÍNEA)
        $movsPendJson = $movimientosPendientes->map(function ($m) {
            $linea  = $m->pedidoProducto;
            $pedido = $m->pedido;
            $pb     = $m->productoBase;

            $cantidad = (float) ($linea->cantidad ?? 0);
            $recep    = (float) ($linea->cantidad_recepcionada ?? 0);
            $restante = max(0.0, $cantidad - $recep);

            return [
                'id'                 => $m->id,
                'tipo'               => $m->tipo,
                'estado'             => $m->estado,
                'prioridad'          => $m->prioridad,
                'pedido_id'          => $pedido?->id,
                'pedido_producto_id' => $linea?->id,
                'producto_base_id'   => $pb?->id,

                'pedido' => [
                    'id'            => $pedido?->id,
                    'codigo'        => $pedido?->codigo,
                    'peso_total'    => $pedido?->peso_total,
                    'fabricante_id' => $pedido?->fabricante_id,
                    'fabricante'    => ['nombre' => $pedido?->fabricante?->nombre],
                    'distribuidor'  => ['nombre' => $pedido?->distribuidor?->nombre],
                ],

                // 🔹 LÍNEA DE PEDIDO (lo que pide el modal)
                'pedido_producto' => [
                    'id'                     => $linea?->id,
                    'cantidad'               => $cantidad,
                    'cantidad_recepcionada'  => $recep,
                    'restante'               => $restante,
                    'estado'                 => $linea?->estado,
                    'fecha_estimada_entrega' => $linea?->fecha_estimada_entrega,
                ],

                // Producto base
                'producto_base' => [
                    'id'       => $pb?->id,
                    'tipo'     => $pb?->tipo,
                    'diametro' => $pb?->diametro,
                    'longitud' => $pb?->longitud, // en m si es barra en tu BD
                ],

                // Extras útiles para la vista grúa (opcionales)
                'solicitado_por' => [
                    'id'   => $m->solicitadoPor?->id,
                    'name' => $m->solicitadoPor?->name,
                ],
                'ubicacion_producto' => [
                    'nombre' => $m->producto?->ubicacion?->nombre,
                ],
            ];
        });

        $movsComplJson = $movimientosCompletados->map(function ($m) {
            $pb    = $m->productoBase;
            $linea = $m->pedidoProducto;

            return [
                'id'            => $m->id,
                'estado'        => $m->estado,
                'updated_at'    => $m->updated_at?->toIso8601String(),
                'pedido_producto' => [
                    'id'     => $linea?->id,
                    'estado' => $linea?->estado,
                ],
                'producto_base' => [
                    'id'       => $pb?->id,
                    'tipo'     => $pb?->tipo,
                    'diametro' => $pb?->diametro,
                    'longitud' => $pb?->longitud,
                ],
                'solicitado_por' => [
                    'id'   => $m->solicitadoPor?->id,
                    'name' => $m->solicitadoPor?->name,
                ],
                'ejecutado_por' => [
                    'id'   => $m->ejecutadoPor?->id,
                    'name' => $m->ejecutadoPor?->name,
                ],
                'ubicacion_producto' => [
                    'nombre' => $m->producto?->ubicacion?->nombre,
                ],
            ];
        });

        // Ubicaciones disponibles por producto base (como ya tenías)
        foreach ($movimientosPendientes as $mov) {
            if ($mov->producto_base_id) {
                $productosCompatibles = Producto::with('ubicacion:id,nombre')
                    ->where('producto_base_id', $mov->producto_base_id)
                    ->where('estado', 'almacenado')
                    ->get();

                $ubicaciones = $productosCompatibles->filter(fn($p) => $p->ubicacion)
                    ->map(fn($p) => [
                        'id'          => $p->ubicacion->id,
                        'nombre'      => $p->ubicacion->nombre,
                        'producto_id' => $p->id,
                        'codigo'      => $p->codigo,
                    ])->unique('id')->values()->toArray();

                $ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] = $ubicaciones;
            }
        }

        // Pedidos activos (de la misma nave)
        $pedidosActivos = Pedido::where('estado', 'activo')
            ->whereHas('pedidoProductos', function ($query) use ($obraId) {
                $query->where('obra_id', $obraId);
            })
            ->with(['pedidoProductos' => function ($query) use ($obraId) {
                $query->where('obra_id', $obraId);
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 👉 Devolvemos tanto las colecciones Eloquent (si las usas en Blade) como los JSON ligeros para JS
        $mapaData = $this->obtenerDatosMapaParaNave($obraId);

        return [
            'movimientosPendientes'                 => $movimientosPendientes,
            'movimientosCompletados'                => $movimientosCompletados,
            'movimientosPendientesJson'             => $movsPendJson->values(),
            'movimientosCompletadosJson'            => $movsComplJson->values(),
            'ubicacionesDisponiblesPorProductoBase' => $ubicacionesDisponiblesPorProductoBase,
            'pedidosActivos'                        => $pedidosActivos,
            'maquinasDisponibles'                   => $maquinasDisponibles,
            'mapaData'                              => $mapaData,
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
            'naveId'         => $naveId,
            'columnasReales' => $columnasReales,
            'filasReales'    => $filasReales,
            'estaGirado'     => false,
            'columnasVista'  => $columnasReales,
            'filasVista'     => $filasReales,
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
                    'id'         => (int) $loc->id,
                    'x1'         => (int) $loc->x1,
                    'y1'         => (int) $loc->y1,
                    'x2'         => (int) $loc->x2,
                    'y2'         => (int) $loc->y2,
                    'maquina_id' => (int) $loc->maquina_id,
                    'nombre'     => (string) ($loc->nombre ?: $loc->maquina->nombre),
                ];
            })->values()->toArray();

        $localizacionesZonas = $localizaciones
            ->where('tipo', '!=', 'maquina')
            ->map(function ($loc) {
                $tipo = str_replace('-', '_', $loc->tipo ?? 'transitable');
                return [
                    'id'    => (int) $loc->id,
                    'x1'    => (int) $loc->x1,
                    'y1'    => (int) $loc->y1,
                    'x2'    => (int) $loc->x2,
                    'y2'    => (int) $loc->y2,
                    'tipo'  => $loc->tipo ?? 'transitable',
                    'nombre'=> (string) $loc->nombre,
                ];
            })->values()->toArray();

        $paquetesConLocalizacion = Paquete::with('localizacionPaquete')
            ->where('nave_id', $naveId)
            ->whereHas('localizacionPaquete')
            ->get()
            ->map(function ($paquete) {
                $loc = $paquete->localizacionPaquete;
                return [
                    'id'             => (int) $paquete->id,
                    'codigo'         => (string) $paquete->codigo,
                    'x1'             => (int) $loc->x1,
                    'y1'             => (int) $loc->y1,
                    'x2'             => (int) $loc->x2,
                    'y2'             => (int) $loc->y2,
                    'tipo_contenido' => $paquete->getTipoContenido(),
                    'orientacion'    => $paquete->orientacion ?? 'I',
                ];
            })->values()->toArray();

        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra'  => $obra->obra,
        ];

        return [
            'ctx'                      => $ctx,
            'localizacionesZonas'     => $localizacionesZonas,
            'localizacionesMaquinas'   => $localizacionesMaquinas,
            'paquetesConLocalizacion'  => $paquetesConLocalizacion,
            'dimensiones'              => $dimensiones,
            'obraActualId'             => $naveId,
            'mapaId'                   => 'mapa-modal-paquete-' . $naveId,
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

        // 🔎 Buscar todas las salidas programadas para hoy
        $salidasHoy = Salida::whereDate('fecha_salida', $hoy)->get();
        $naveA = Obra::buscarDeCliente('Paco Reyes', 'Nave A');
        foreach ($salidasHoy as $salida) {
            // 🔎 Comprobar si ya existe un movimiento asociado a esta salida
            $existeMovimiento = Movimiento::where('salida_id', $salida->id)
                ->where('tipo', 'salida')
                ->exists();

            if (!$existeMovimiento) {

                // 👉 Datos básicos
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

                // 👉 Construir la descripción final (sin usar optional de nuevo)
                $descripcion = "$codigoSalida. Se solicita carga del camión ($camion) - ($empresaTransporte) para [$obrasClientes], tiene que estar listo a las $horaSalida";


                // ⚡ Crear movimiento nuevo
                Movimiento::create([
                    'tipo' => 'salida',
                    'salida_id' => $salida->id,
                    'nave_id'         => $naveA?->id,
                    'estado' => 'pendiente',
                    'fecha_solicitud' => now(),
                    'solicitado_por' => null,
                    'prioridad' => 2,
                    'descripcion' => $descripcion,
                    // 👉 Rellena otros campos si lo necesitas, por ejemplo prioridad o descripción
                ]);
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
                    'tipo'            => 'Salida Almacén',
                    'salida_almacen_id' => $salida->id,
                    'nave_id'         => $almacen?->id,
                    'estado'          => 'pendiente',
                    'fecha_solicitud' => now(),
                    'solicitado_por'  => null,
                    'prioridad'       => 2,
                    'descripcion'     => $descripcion,
                ]);
            }
        }
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
                'codigo'       => 'required|string|unique:maquinas,codigo',
                'nombre'       => 'required|string|max:40|unique:maquinas,nombre',
                'tipo'         => 'nullable|string|max:50|in:grua,cortadora_dobladora,ensambladora,soldadora,cortadora_manual,dobladora_manual',
                'obra_id'      => 'nullable|exists:obras,id',
                'diametro_min' => 'nullable|integer',
                'diametro_max' => 'nullable|integer',
                'peso_min'     => 'nullable|integer',
                'peso_max'     => 'nullable|integer',
                'ancho_m' => 'nullable|numeric|min:0.01',
                'largo_m' => 'nullable|numeric|min:0.01',


            ], [
                // Mensajes personalizados
                'codigo.required' => 'El campo "código" es obligatorio.',
                'codigo.string'   => 'El campo "código" debe ser una cadena de texto.',
                'codigo.unique'   => 'Ya existe una máquina con el mismo código.',

                'nombre.required' => 'El campo "nombre" es obligatorio.',
                'nombre.string'   => 'El campo "nombre" debe ser una cadena de texto.',
                'nombre.max'      => 'El campo "nombre" no puede tener más de 40 caracteres.',
                'nombre.unique'   => 'Ya existe una máquina con el mismo nombre.',

                'tipo.string'     => 'El campo "tipo" debe ser una cadena de texto.',
                'tipo.max'        => 'El campo "tipo" no puede tener más de 50 caracteres.',
                'tipo.in'         => 'El tipo no está entre los posibles.',

                'diametro_min.integer' => 'El campo "diámetro mínimo" debe ser un número entero.',
                'diametro_max.integer' => 'El campo "diámetro máximo" debe ser un número entero.',
                'peso_min.integer'     => 'El campo "peso mínimo" debe ser un número entero.',
                'peso_max.integer'     => 'El campo "peso máximo" debe ser un número entero.',
                'obra_id.exists'       => 'La obra seleccionada no es válida.',

                'ancho_m.numeric' => 'El ancho debe ser un número.',
                'ancho_m.min'     => 'El ancho debe ser mayor que cero.',
                'largo_m.numeric' => 'El largo debe ser un número.',
                'largo_m.min'     => 'El largo debe ser mayor que cero.',

            ]);

            // Crear la nueva máquina en la base de datos
            $maquina = Maquina::create([
                'codigo'       => $request->codigo,
                'nombre'       => $request->nombre,
                'tipo'         => $request->tipo,
                'obra_id'      => $request->obra_id,
                'diametro_min' => $request->diametro_min,
                'diametro_max' => $request->diametro_max,
                'peso_min'     => $request->peso_min,
                'peso_max'     => $request->peso_max,
                'ancho_m'      => $request->ancho_m,
                'largo_m'      => $request->largo_m,
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
        return redirect()
            ->route('maquinas.show', $request->nueva_maquina_id)
            ->with('success', 'Máquina cambiada correctamente.');
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
            'codigo'       => 'required|string|unique:maquinas,codigo,' . $id,
            'nombre'       => 'required|string|max:40',
            'tipo'         => 'nullable|string|max:50|in:cortadora_dobladora,ensambladora,soldadora,cortadora manual,dobladora_manual,grua',
            'obra_id'      => 'nullable|exists:obras,id',
            'diametro_min' => 'nullable|integer',
            'diametro_max' => 'nullable|integer',
            'peso_min'     => 'nullable|integer',
            'peso_max'     => 'nullable|integer',
            'ancho_m'      => 'nullable|numeric|min:0.01',
            'largo_m'      => 'nullable|numeric|min:0.01',
            'estado'       => 'nullable|string|in:activa,en mantenimiento,inactiva',
        ], [
            'codigo.required'   => 'El campo "código" es obligatorio.',
            'codigo.string'     => 'El campo "código" debe ser una cadena de texto.',
            'codigo.unique'     => 'El código ya existe, por favor ingrese otro diferente.',

            'nombre.required'   => 'El campo "nombre" es obligatorio.',
            'nombre.string'     => 'El campo "nombre" debe ser una cadena de texto.',
            'nombre.max'        => 'El campo "nombre" no puede tener más de 40 caracteres.',

            'tipo.string'       => 'El campo "tipo" debe ser texto.',
            'tipo.max'          => 'El campo "tipo" no puede tener más de 50 caracteres.',
            'tipo.in'           => 'El tipo no está entre los posibles.',

            'obra_id.exists'    => 'La obra seleccionada no es válida.',

            'diametro_min.integer' => 'El "diámetro mínimo" debe ser un número entero.',
            'diametro_max.integer' => 'El "diámetro máximo" debe ser un número entero.',
            'peso_min.integer'     => 'El "peso mínimo" debe ser un número entero.',
            'peso_max.integer'     => 'El "peso máximo" debe ser un número entero.',

            'ancho_m.numeric'   => 'El ancho debe ser un número.',
            'ancho_m.min'       => 'El ancho debe ser mayor que cero.',
            'largo_m.numeric'   => 'El largo debe ser un número.',
            'largo_m.min'       => 'El largo debe ser mayor que cero.',

            'estado.in'         => 'El estado debe ser: activa, en mantenimiento o inactiva.',
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
        $nombreLimpio   = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME));
        $extension      = $request->file('imagen')->getClientOriginalExtension();
        $nombreFinal    = $nombreLimpio . '.' . $extension;
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


    public function exportarBVBS(Maquina $maquina, ProgressBVBSService $bvbs)
    {
        // 1️⃣ Obtener las planillas activas (posición = 1) para esta máquina
        $planillaIdsActivas = OrdenPlanilla::where('maquina_id', $maquina->id)
            ->where('posicion', 1)
            ->pluck('planilla_id');
        $codigoProyecto = null;

        if ($planillaIdsActivas) {
            $planilla = Planilla::find($planillaIdsActivas[0]);
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

        // 3️⃣ Mapear cada elemento a los campos que necesita el servicio BVBS
        $datos = $elementos->map(fn($el) => [
            'proyecto' => $codigoProyecto,
            'plano'       => optional($el->etiquetaRelacion)->nombre,
            'indice'      => $el->indice,
            'marca'       => $el->etiqueta,
            'barras'      => (int)$el->barras,
            'diametro'    => (int)$el->diametro,
            'dimensiones' => (string)$el->dimensiones,
            'longitud'    => $el->longitud_total_cm,
            'peso'        => $el->peso,
            'mandril_mm'  => $el->mandril_mm, // opcional
            'calidad'     => 'B500SD',
            'capa'        => $el->capa,
            'box'         => $el->box,
        ])->all();

        // 4) Generar BVBS
        $contenido = $bvbs->exportarLote($datos);

        // 5) Nombre de archivo decente
        $timestamp   = now()->format('Ymd_His');
        $maquinaTag  = Str::upper(trim($maquina->codigo ?? $maquina->nombre ?? 'MAQUINA'));
        $maquinaTag  = Str::of($maquinaTag)->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_');
        $proyectoTag = $codigoProyecto ? 'PRJ' . $codigoProyecto : 'SINPRJ';
        $filename    = "BVBS_{$maquinaTag}_{$proyectoTag}_{$timestamp}.bvbs";
        $path        = "exports/bvbs/{$filename}";

        // 6) Guardar en disco y devolver descarga
        Storage::disk('local')->put($path, $contenido);

        Log::info("Export BVBS guardado en {$path} para máquina {$maquina->id} con " . count($datos) . " líneas.");

        return response()->download(
            Storage::disk('local')->path($path),
            $filename,
            [
                'Content-Type'  => 'text/plain; charset=UTF-8',
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

        try {
            $maquinaOrigen = Maquina::findOrFail($id);

            // Obtener elementos pendientes de esta máquina con su planilla
            $elementosQuery = Elemento::with(['planilla'])
                ->where('maquina_id', $id)
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'asc');

            // Si es "primeros", limitamos a un número razonable
            if ($tipo === 'primeros') {
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
            // Usando una transacción para simular sin persistir cambios
            $elementosConDestino = [];

            DB::transaction(function () use ($elementos, $id, &$elementosConDestino) {
                $asignarMaquinaService = new \App\Services\AsignarMaquinaService();

                // Guardar IDs originales
                $elementosIds = $elementos->pluck('id')->toArray();
                $maquinasOriginales = Elemento::whereIn('id', $elementosIds)->pluck('maquina_id', 'id')->toArray();

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

                // IMPORTANTE: hacer rollback para que no se guarden los cambios
                DB::rollBack();
            });

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
            'tipo' => 'required|in:primeros,todos',
        ]);

        $maquina = Maquina::findOrFail($id);
        $tipo = $request->input('tipo');

        try {
            // Obtener elementos pendientes de esta máquina
            $elementosQuery = Elemento::with(['planilla'])
                ->where('maquina_id', $id)
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'asc'); // Ordenar por fecha de creación

            // Si es "primeros", limitamos a un número razonable (por ejemplo, los primeros 50)
            if ($tipo === 'primeros') {
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
                $resumen[$nuevaMaquina]['peso_total'] += (float)$original['peso'];
            }

            // Convertir resumen a array de valores
            $resumen = array_values($resumen);

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

            $posicion1 = $request->input('posicion_1');
            $posicion2 = $request->input('posicion_2');

            if (!$posicion1 && !$posicion2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes seleccionar al menos una posición de planilla'
                ], 400);
            }

            $posiciones = array_filter([$posicion1, $posicion2]);
            $planillasCompletadas = [];
            $errores = [];

            DB::beginTransaction();

            foreach ($posiciones as $posicion) {
                // Buscar la planilla en esa posición para esta máquina
                $ordenPlanilla = OrdenPlanilla::where('maquina_id', $maquina->id)
                    ->where('posicion', $posicion)
                    ->lockForUpdate()
                    ->first();

                if (!$ordenPlanilla) {
                    $errores[] = "No se encontró planilla en la posición {$posicion}";
                    continue;
                }

                $planilla = $ordenPlanilla->planilla;

                // Verificar que todas las etiquetas de esa planilla tengan paquete asignado
                $etiquetasSinPaquete = $planilla->etiquetas()
                    ->whereDoesntHave('paquete')
                    ->count();

                if ($etiquetasSinPaquete > 0) {
                    $errores[] = "La planilla {$planilla->codigo} (Pos. {$posicion}) aún tiene {$etiquetasSinPaquete} etiqueta(s) sin paquete asignado";
                    continue;
                }

                // Guardar posición para reordenar
                $posicionEliminada = $ordenPlanilla->posicion;

                // Eliminar de la cola
                $ordenPlanilla->delete();

                // Reordenar posiciones posteriores
                OrdenPlanilla::where('maquina_id', $maquina->id)
                    ->where('posicion', '>', $posicionEliminada)
                    ->decrement('posicion');

                $planillasCompletadas[] = $planilla->codigo;

                Log::info("Planilla {$planilla->codigo} completada manualmente en máquina {$maquina->nombre} (Pos. {$posicion})");
            }

            DB::commit();

            if (empty($planillasCompletadas) && !empty($errores)) {
                return response()->json([
                    'success' => false,
                    'message' => implode('. ', $errores)
                ], 400);
            }

            $mensaje = count($planillasCompletadas) > 0
                ? 'Planilla(s) completada(s): ' . implode(', ', $planillasCompletadas)
                : 'No se completaron planillas';

            if (!empty($errores)) {
                $mensaje .= '. Advertencias: ' . implode('. ', $errores);
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'planillas_completadas' => $planillasCompletadas,
                'errores' => $errores
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
}
