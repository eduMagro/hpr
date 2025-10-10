<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\ProductoBase;
use App\Models\Localizacion;
use App\Models\Paquete;
use App\Models\Ubicacion;
use App\Models\Maquina;
use App\Models\Obra;
use App\Models\Alerta;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Termwind\Components\Dd;
use Illuminate\Validation\ValidationException;


class MovimientoController extends Controller
{
    //------------------------------------------------ FILTROS() --------------------------------------------------------
    private function aplicarFiltros($query, Request $request)
    {
        /* ─────────────── Filtros directos por columna ─────────────── */

        // ID de movimiento
        if ($request->filled('id') || $request->filled('movimiento_id')) {
            $query->where('id', $request->id ?? $request->movimiento_id);
        }

        // Tipo de movimiento
        if ($request->filled('tipo')) {
            $query->where('tipo', 'like', '%' . $request->tipo . '%');
        }
        // Línea de pedido (pedido_producto_id)
        if ($request->filled('pedido_producto_id')) {
            $query->where('pedido_producto_id', (int) $request->pedido_producto_id);
        }

        // Producto Base: filtrar por tipo, diámetro o longitud
        if ($request->filled('producto_tipo') || $request->filled('producto_diametro') || $request->filled('producto_longitud')) {
            $query->whereHas('productoBase', function ($q) use ($request) {
                if ($request->filled('producto_tipo')) {
                    $q->where('tipo', 'like', '%' . $request->producto_tipo . '%');
                }
                if ($request->filled('producto_diametro')) {
                    $q->where('diametro', 'like', '%' . $request->producto_diametro . '%');
                }
                if ($request->filled('producto_longitud')) {
                    $q->where('longitud', 'like', '%' . $request->producto_longitud . '%');
                }
            });
        }

        // Descripción
        if ($request->filled('descripcion')) {
            $query->where('descripcion', 'like', '%' . $request->descripcion . '%');
        }
        if ($request->filled('nave_id')) {
            $query->where('nave_id', $request->nave_id);
        }

        // Prioridad exacta (baja, media, alta)
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        // Estado exacto (pendiente, completado, cancelado…)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Origen / Destino (texto libre, ej. “Máquina A-1”)
        if ($request->filled('origen')) {
            $query->where('origen', 'like', '%' . $request->origen . '%');
        }
        if ($request->filled('destino')) {
            $query->whereHas('maquinaDestino', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->destino . '%');
            });
        }


        /* ─────────────── Filtros por relaciones ─────────────── */

        // Producto (códigos parciales separados por comas)
        if ($request->filled('producto_codigo')) {
            $codigos = array_filter(array_map('trim', explode(',', $request->producto_codigo)));

            $query->whereHas('producto', function ($q) use ($codigos) {
                $q->where(function ($sub) use ($codigos) {
                    foreach ($codigos as $codigo) {
                        $sub->orWhere('codigo', 'like', '%' . $codigo . '%');
                    }
                });
            });
        }

        // Filtro por ID de producto o paquete concretos (si llegan)
        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        if ($request->filled('paquete_id')) {
            $query->where('paquete_id', $request->paquete_id);
        }

        // Campo “producto_paquete” (permite buscar código en cualquiera de los dos)
        if ($request->filled('producto_paquete')) {
            $codigo = $request->producto_paquete;
            $query->where(function ($q) use ($codigo) {
                $q->whereHas('producto', fn($p) => $p->where('codigo', 'like', "%$codigo%"))
                    ->orWhereHas('paquete',  fn($p) => $p->where('codigo', 'like', "%$codigo%"));
            });
        }

        // Usuario que solicitó
        if ($request->filled('solicitado_por')) {
            $nombre = $request->solicitado_por;
            $query->whereHas('solicitadoPor', function ($q) use ($nombre) {
                $q->where(DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"), 'like', "%$nombre%");
            });
        }

        // Usuario que ejecutó
        if ($request->filled('ejecutado_por')) {
            $nombre = $request->ejecutado_por;
            $query->whereHas('ejecutadoPor', function ($q) use ($nombre) {
                $q->where(DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"), 'like', "%$nombre%");
            });
        }

        /* ─────────────── Filtros por fechas ─────────────── */

        // Rangos al estilo planillas
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_finalizacion')) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        // Fechas individuales específicas
        if ($request->filled('fecha_solicitud')) {
            $query->whereDate('created_at', $request->fecha_solicitud);
        }
        if ($request->filled('fecha_ejecucion')) {
            $query->whereDate('fecha_ejecucion', $request->fecha_ejecucion);
        }

        return $query;
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        /* ─── 1. Campos directos ─────────────────────────── */
        if ($request->filled('id') || $request->filled('movimiento_id')) {
            $filtros[] = 'ID movimiento: <strong>' . ($request->id ?? $request->movimiento_id) . '</strong>';
        }

        if ($request->filled('tipo')) {
            $filtros[] = 'Tipo: <strong>' . ucfirst($request->tipo) . '</strong>';
        }
        if ($request->filled('pedido_producto_id')) {
            $filtros[] = 'Línea pedido: <strong>#' . $request->pedido_producto_id . '</strong>';
        }

        if ($request->filled('producto_tipo')) {
            $filtros[] = 'Tipo: <strong>' . $request->producto_tipo . '</strong>';
        }
        if ($request->filled('producto_diametro')) {
            $filtros[] = 'Ø: <strong>' . $request->producto_diametro . '</strong>';
        }
        if ($request->filled('producto_longitud')) {
            $filtros[] = 'Longitud: <strong>' . $request->producto_longitud . '</strong>';
        }

        if ($request->filled('descripcion')) {
            $filtros[] = 'Descripción contiene: <strong>' . $request->descripcion . '</strong>';
        }

        if ($request->filled('nave_id')) {
            $obra = Obra::find($request->nave_id);
            if ($obra) {
                $filtros[] = 'Nave: <strong>' . e($obra->obra) . '</strong>';
            }
        }
        if ($request->filled('origen'))   $filtros[] = 'Origen: <strong>'   . $request->origen   . '</strong>';
        if ($request->filled('destino'))  $filtros[] = 'Destino: <strong>'  . $request->destino  . '</strong>';

        /* ─── 2. Prioridad ───────────────────────────────── */
        if ($request->filled('prioridad')) {
            $prioridades = [
                1         => 'Normal',
                2         => 'Alta',
                3         => 'Urgente',
                'normal'  => 'Normal',
                'alta'    => 'Alta',
                'urgente' => 'Urgente',
            ];
            $texto = $prioridades[$request->prioridad] ?? $request->prioridad;
            $filtros[] = 'Prioridad: <strong>' . $texto . '</strong>';
        }

        /* ─── 3. Relaciones: usuario que solicita / ejecuta ───── */
        if ($request->filled('solicitado_por')) {
            $usuario = User::where(
                DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"),
                'like',
                '%' . $request->solicitado_por . '%'
            )->first();
            $filtros[] = 'Solicitado por: <strong>' . ($usuario?->nombre_completo ?? $request->solicitado_por) . '</strong>';
        }

        if ($request->filled('ejecutado_por')) {
            $usuario = User::where(
                DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"),
                'like',
                '%' . $request->ejecutado_por . '%'
            )->first();
            $filtros[] = 'Ejecutado por: <strong>' . ($usuario?->nombre_completo ?? $request->ejecutado_por) . '</strong>';
        }

        /* ─── 4. Estado ─────────────────────────────────── */
        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }

        /* ─── 5. Producto/paquete ───────────────────────── */
        if ($request->filled('producto_codigo')) {
            $filtros[] = 'Producto(s): <strong>' . $request->producto_codigo . '</strong>';
        }
        if ($request->filled('producto_paquete')) {
            $filtros[] = 'Producto/Paquete: <strong>' . $request->producto_paquete . '</strong>';
        }

        /* ─── 6. Fechas ─────────────────────────────────── */
        if ($request->filled('fecha_solicitud'))    $filtros[] = 'Fecha solicitud: <strong>'    . $request->fecha_solicitud    . '</strong>';
        if ($request->filled('fecha_ejecucion'))    $filtros[] = 'Fecha ejecución: <strong>'    . $request->fecha_ejecucion    . '</strong>';
        if ($request->filled('fecha_inicio'))       $filtros[] = 'Desde: <strong>'              . $request->fecha_inicio       . '</strong>';
        if ($request->filled('fecha_finalizacion')) $filtros[] = 'Hasta: <strong>'              . $request->fecha_finalizacion . '</strong>';

        /* ─── 7. Orden y paginación ─────────────────────── */
        if ($request->filled('sort')) {
            $sorts = [
                'prioridad'        => 'Prioridad',
                'estado'           => 'Estado',
                'fecha_solicitud'  => 'Fecha solicitud',
                'fecha_ejecucion'  => 'Fecha ejecución',
                'id'               => 'ID',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort)
                . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }

        return $filtros;
    }

    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort  = request('sort');
        $currentOrder = request('order');
        $isSorted     = $currentSort === $columna;
        $nextOrder    = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? '▲' : '▼')
            : '⇅';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }

    private function aplicarOrdenamiento($query, Request $request)
    {
        // → Columnas que SÍ se pueden ordenar
        $columnasPermitidas = [
            'id',
            'tipo',
            'descripcion',
            'nave',
            'prioridad',
            'estado',
            'fecha_solicitud',
            'fecha_ejecucion',
            'producto_id',
            'pedido_producto_id', // ← nuevo
            'created_at',
        ];


        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        // Sanitiza: si la columna no es válida, cae al fallback
        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';
        }

        // Asegura que el orden sea solo asc|desc
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $order);
    }
    //------------------------------------------------ INDEX() --------------------------------------------------------
    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $usuario = auth()->user();

        // 👉 Redirigir a 'create' si el usuario es operario
        if ($usuario->rol === 'operario') {
            return redirect()->route('movimientos.create');
        }
        // Base query con relaciones necesarias
        $query = Movimiento::with([
            'producto',
            'productoBase',
            'ejecutadoPor',
            'solicitadoPor',
            'ubicacionOrigen',
            'ubicacionDestino',
            'maquinaOrigen',
            'maquinaDestino',
            'nave',
            'pedidoProducto' // ← por si pintas enlace
        ]);

        // Si es 'oficina', no aplicamos restricciones y puede ver todos los movimientos

        // Filtros
        $query = $this->aplicarFiltros($query, $request);

        // Ordenamiento (nuevo método modular)
        $query = $this->aplicarOrdenamiento($query, $request);

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosMovimientos = $query->paginate($perPage)->appends($request->except('page'));

        $ordenables = [
            'id'                => $this->getOrdenamiento('id', 'ID'),
            'producto_id'       => $this->getOrdenamiento('producto_id', 'Producto Solicitado'),
            'tipo'              => $this->getOrdenamiento('tipo', 'Tipo'),
            'descripcion'       => $this->getOrdenamiento('descripcion', 'Descripción'),
            'nave'              => $this->getOrdenamiento('nave', 'Nave'),
            'prioridad'         => $this->getOrdenamiento('prioridad', 'Prioridad'),
            'solicitado_por'    => $this->getOrdenamiento('solicitado_por', 'Solicitado por'),
            'ejecutado_por'     => $this->getOrdenamiento('ejecutado_por', 'Ejecutado por'),
            'estado'            => $this->getOrdenamiento('estado', 'Estado'),
            'fecha_solicitud'   => $this->getOrdenamiento('fecha_solicitud', 'Fecha Solicitud'),
            'fecha_ejecucion'   => $this->getOrdenamiento('fecha_ejecucion', 'Fecha Ejecución'),
            'pedido_producto_id' => $this->getOrdenamiento('pedido_producto_id', 'Línea Pedido'), // ← nuevo
        ];

        $navesSelect = Obra::whereHas('cliente', function ($q) {
            $q->whereRaw("UPPER(empresa) LIKE '%PACO REYES%'");
        })
            ->orderBy('obra')
            ->pluck('obra', 'id')   // ['id' => 'Obra']
            ->toArray();
        // 🔟 Obtener texto de filtros aplicados para mostrar en la vista
        $filtrosActivos = $this->filtrosActivos($request);
        // Retornar vista con los datos paginados
        return view('movimientos.index', compact('registrosMovimientos', 'ordenables', 'filtrosActivos', 'navesSelect'));
    }

    //------------------------------------------------ CREATE() --------------------------------------------------------

    public function create(Request $request)
    {
        $productos = Producto::with('ubicacion')->get();
        $paquetes = Paquete::with('ubicacion')->get();
        $ubicaciones = Ubicacion::all();
        $maquinas = Maquina::all();
        $localizaciones = Localizacion::all();

        $codigoMateriaPrima = $request->query('codigo');
        return view('movimientos.create', compact('productos', 'paquetes', 'ubicaciones', 'maquinas', 'localizaciones', 'codigoMateriaPrima'));
    }

    public function crearMovimiento(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required',
            'producto_id' => 'nullable|exists:productos,id',
            'producto_base_id' => 'required_if:tipo,recarga_materia_prima|exists:productos_base,id',
            'paquete_id' => 'required_if:tipo,paquete|nullable|exists:paquetes,id',
            'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
        ], [
            'tipo.required' => 'El tipo de movimiento es obligatorio.',
            'producto_id.required_if' => 'El producto es obligatorio cuando el tipo de movimiento es recarga de materia prima.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'paquete_id.required_if' => 'El paquete es obligatorio cuando el tipo de movimiento es paquete.',
            'paquete_id.exists' => 'El paquete seleccionado no existe.',
            'ubicacion_destino.exists' => 'Ubicación no válida.',
            'maquina_id.exists' => 'Máquina no válida.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->ubicacion_destino && $request->maquina_id) {
            return response()->json(['error' => 'No puedes elegir una ubicación y una máquina a la vez como destino']);
        }

        if (!$request->ubicacion_destino && !$request->maquina_id) {
            return response()->json(['error' => 'No has elegido destino']);
        }

        try {
            DB::transaction(function () use ($request) {

                switch ($request->tipo) {

                    // __________________________ RECARGA MATERIA PRIMA __________________________
                    case 'recarga_materia_prima':

                        // máquina destino obligatoria para este tipo
                        /** @var \App\Models\Maquina|null $maquina */
                        $maquina = Maquina::find($request->maquina_id);
                        if (!$maquina) {
                            throw new \Exception('No se encontró la máquina destino para asignar la nave.');
                        }
                        $naveId = $maquina->obra_id;

                        if (!$naveId) {
                            throw new \Exception('La máquina seleccionada no tiene una nave asignada (obra_id).');
                        }


                        // Resolver producto base (desde producto o producto_base_id)
                        if ($request->filled('producto_id')) {
                            $productoReferencia = Producto::with('productoBase')->find($request->producto_id);
                            $productoBase = $productoReferencia?->productoBase;
                        } else {
                            $productoBase = ProductoBase::find($request->producto_base_id);
                        }
                        if (!$productoBase) {
                            throw new \Exception('No se pudo resolver el producto base.');
                        }

                        $tipo = strtolower($productoBase->tipo ?? 'N/A');
                        $diametro = $productoBase->diametro ?? '?';
                        $longitud = $productoBase->longitud ?? '?';
                        $nombreMaquina = $maquina->nombre ?? 'desconocida';

                        $descripcion = "Se solicita materia prima del tipo {$tipo} (Ø{$diametro}, {$longitud} mm) en la máquina {$nombreMaquina}";

                        $yaExiste = Movimiento::where('tipo', 'Recarga materia prima')
                            ->where('producto_base_id', $productoBase->id)
                            ->where('maquina_destino', $maquina->id)
                            ->where('estado', 'pendiente')
                            ->exists();

                        if ($yaExiste) {
                            throw new \Exception('Ya existe una solicitud pendiente para esta máquina y producto base.');
                        }

                        Movimiento::create([
                            'tipo'              => 'Recarga materia prima',
                            'nave_id'           => $naveId,              // <<<<<<
                            'maquina_origen'    => null,
                            'maquina_destino'   => $maquina->id,
                            'producto_id'       => null,
                            'producto_base_id'  => $productoBase->id,
                            'estado'            => 'pendiente',
                            'descripcion'       => $descripcion,
                            'prioridad'         => 1,
                            'fecha_solicitud'   => now(),
                            'solicitado_por'    => auth()->id(),
                        ]);
                        break;

                    // __________________________ MOVIMIENTO PAQUETE __________________________
                    case 'paquete':
                        $paquete = Paquete::findOrFail($request->paquete_id);

                        // Determinar nave_id = obra_id de la máquina
                        // 1) Si hay máquina destino en la solicitud, usar esa
                        // 2) Si no, usar la máquina actual del paquete (origen), si existe
                        // 3) Si ninguna, queda null (último recurso)
                        $maquinaDestino = $request->maquina_id ? Maquina::find($request->maquina_id) : null;
                        $maquinaOrigen = $paquete->maquina_id ? Maquina::find($paquete->maquina_id) : null;

                        $maquinaParaNave = $maquinaDestino ?: $maquinaOrigen;

                        if (!$maquinaParaNave) {
                            throw new \Exception('No se puede determinar la máquina para asignar la nave.');
                        }

                        $naveId = $maquinaParaNave->obra_id;

                        if (!$naveId) {
                            throw new \Exception('La máquina relacionada no tiene una nave (obra_id) asignada.');
                        }

                        $nombreUbicacion = optional($paquete->ubicacion)->nombre ?? 'desconocida';
                        $descripcion = "Se solicita mover el paquete #{$paquete->codigo} desde {$nombreUbicacion}";

                        Movimiento::create([
                            'tipo'               => 'Movimiento de paquete',
                            'nave_id'            => $naveId,                 // <<<<<<
                            'paquete_id'         => $paquete->id,
                            'ubicacion_origen'   => $paquete->ubicacion_id,
                            'maquina_origen'     => $paquete->maquina_id,
                            'ubicacion_destino'  => $request->ubicacion_destino, // si aplicara
                            'maquina_destino'    => $request->maquina_id,        // si aplicara
                            'estado'             => 'pendiente',
                            'prioridad'          => 3,
                            'fecha_solicitud'    => now(),
                            'solicitado_por'     => auth()->id(),
                            'descripcion'        => $descripcion,
                        ]);
                        break;

                    default:
                        throw new \Exception('Tipo de movimiento no reconocido.');
                }
            });

            return redirect()->back()->with('success', 'Movimiento creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar movimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'No se ha podido crear el movimiento.');
        }
    }


    //------------------------------------------------ STORE() --------------------------------------------------------
    public function store(Request $request)
    {
        // 1) Validación (lista_qrs JSON) + destinos opcionales
        try {
            $validated = $request->validate([
                'lista_qrs'         => 'required|string', // JSON string con array de códigos
                'ubicacion_destino' => 'nullable|exists:ubicaciones,id',
                'maquina_destino'   => 'nullable|exists:maquinas,id',
                'tipo'              => 'nullable|string'
            ], [
                'lista_qrs.required'       => 'Debes escanear al menos un código.',
                'ubicacion_destino.exists' => 'Ubicación no válida.',
                'maquina_destino.exists'   => 'Máquina no válida.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        // 2) Parseo de lista_qrs (JSON -> array)
        $lista = json_decode((string) $validated['lista_qrs'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($lista)) {
            $msg = 'Formato de lista_qrs inválido. Debe ser un JSON de array.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withInput()->with('error', $msg);
        }
        // normaliza
        $lista = array_values(array_unique(array_filter(array_map(
            fn($c) => strtoupper(trim((string)$c)),
            $lista
        ))));
        if (!$lista) {
            $msg = 'No se ha recibido ningún código válido.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withInput()->with('error', $msg);
        }

        // 3) Variables base de destino
        $maquinaId   = $validated['maquina_destino'] ?? null;
        $ubicacionId = $validated['ubicacion_destino'] ?? null;
        $esRecarga   = $maquinaId !== null;

        $maquinaDetectada = $esRecarga ? Maquina::find($maquinaId) : null;
        $ubicacion        = $esRecarga ? null : ($ubicacionId ? Ubicacion::find($ubicacionId) : null);

        if (!$maquinaDetectada && $ubicacion) {
            $maquinaDetectada = Maquina::where('codigo', $ubicacion->descripcion)->first();
        }

        // Determinar nave
        $naveId = null;
        if ($ubicacion) {
            $mapaAlmacenes = ['0A' => 1, '0B' => 2, 'AL' => 3];
            $naveId = $mapaAlmacenes[$ubicacion->almacen] ?? null;
        } elseif ($maquinaDetectada) {
            $naveId = $maquinaDetectada->obra_id ?? null;
        }

        if (!$naveId) {
            $mensaje = 'No se puede determinar la nave de trabajo a partir de la ubicación o máquina de destino.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }
            return back()->with('error', $mensaje);
        }

        try {
            DB::transaction(function () use ($lista, $ubicacion, $maquinaDetectada, $naveId) {

                foreach ($lista as $codigo) {
                    // Cargamos uno a uno y si falla cualquiera → excepción (se revierte todo)
                    $producto = null;
                    $paquete  = null;
                    $tipoMovimiento = null;

                    if (str_starts_with($codigo, 'MP')) {
                        $producto = Producto::with('productoBase', 'ubicacion')->where('codigo', $codigo)->firstOrFail();
                        $tipoMovimiento = 'producto';
                    } elseif (str_starts_with($codigo, 'P')) {
                        $paquete = Paquete::with('ubicacion')->where('codigo', $codigo)->firstOrFail();
                        $tipoMovimiento = 'paquete';
                    } else {
                        throw new \Exception('El código escaneado no es válido. Debe comenzar por MP o P.');
                    }

                    // ===== PRODUCTO =====
                    if ($tipoMovimiento === 'producto') {
                        $tipoBase = strtolower($producto->productoBase->tipo);
                        $diametro = $producto->productoBase->diametro;
                        $longitud = $producto->productoBase->longitud;
                        $origen   = $producto->ubicacion->nombre ?? 'origen desconocido';

                        // Descripción PLANA (con Código). L solo si NO es encarretado.
                        $descripcion = "Movemos {$tipoBase} (Código: {$codigo}) Ø{$diametro} mm"
                            . ($tipoBase !== 'encarretado' ? " L:{$longitud} mm" : "")
                            . " de {$origen}"
                            . " a " . ($maquinaDetectada ? 'máquina ' . $maquinaDetectada->nombre
                                : 'ubicación ' . ($ubicacion->nombre ?? 'destino desconocido'));

                        // Si hay máquina
                        if ($maquinaDetectada) {

                            $maquinasEncarretado = ['MSR20', 'MS16', 'PS12', 'F12'];
                            if (in_array($maquinaDetectada->codigo, $maquinasEncarretado) && $tipoBase === 'barras') {
                                throw new \Exception('La máquina seleccionada solo acepta productos de tipo encarretado.');
                            }

                            if (
                                (!is_null($maquinaDetectada->diametro_min) && $diametro < $maquinaDetectada->diametro_min) ||
                                (!is_null($maquinaDetectada->diametro_max) && $diametro > $maquinaDetectada->diametro_max)
                            ) {
                                throw new \Exception('El diámetro del producto no está dentro del rango aceptado por la máquina.');
                            }

                            // Movimiento pendiente
                            $movPend = Movimiento::where('producto_base_id', $producto->producto_base_id)
                                ->where('maquina_destino', $maquinaDetectada->id)
                                ->where('estado', 'pendiente')
                                ->latest()
                                ->first();

                            if ($movPend) {
                                $movPend->update([
                                    'producto_id'      => $producto->id,
                                    'ubicacion_origen' => $producto->ubicacion_id,
                                    'estado'           => 'completado',
                                    'fecha_ejecucion'  => now(),
                                    'ejecutado_por'    => auth()->id(),
                                    // opcional: 'descripcion' => $descripcion,
                                ]);
                            } else {
                                Movimiento::create([
                                    'tipo'               => 'movimiento libre',
                                    'producto_id'        => $producto->id,
                                    'producto_base_id'   => $producto->producto_base_id,
                                    'ubicacion_origen'   => $producto->ubicacion_id,
                                    'maquina_origen'     => $producto->maquina_id,
                                    'maquina_destino'    => $maquinaDetectada->id,
                                    'estado'             => 'completado',
                                    'descripcion'        => $descripcion,
                                    'nave_id'            => $naveId,
                                    'fecha_ejecucion'    => now(),
                                    'ejecutado_por'      => auth()->id(),
                                ]);
                            }

                            // Actualiza producto a máquina
                            $producto->update([
                                'ubicacion_id' => null,
                                'obra_id'      => $naveId,
                                'maquina_id'   => $maquinaDetectada->id,
                                'estado'       => 'fabricando',
                            ]);

                            // Consumir anterior
                            $productoAnterior = Producto::where('producto_base_id', $producto->producto_base_id)
                                ->where('id', '!=', $producto->id)
                                ->where('maquina_id', $maquinaDetectada->id)
                                ->where('estado', 'fabricando')
                                ->latest('updated_at')
                                ->first();

                            if ($productoAnterior) {
                                $productoAnterior->update([
                                    'maquina_id' => null,
                                    'estado'     => 'consumido',
                                ]);
                            }
                        } else {
                            // A ubicación
                            Movimiento::create([
                                'tipo'               => 'movimiento libre',
                                'producto_id'        => $producto->id,
                                'producto_base_id'   => $producto->producto_base_id,
                                'ubicacion_origen'   => $producto->ubicacion_id,
                                'maquina_origen'     => $producto->maquina_id,
                                'ubicacion_destino'  => $ubicacion->id,
                                'maquina_destino'    => null,
                                'estado'             => 'completado',
                                'descripcion'        => $descripcion,
                                'nave_id'            => $naveId,
                                'fecha_ejecucion'    => now(),
                                'ejecutado_por'      => auth()->id(),
                            ]);

                            $producto->update([
                                'ubicacion_id' => $ubicacion->id,
                                'obra_id'      => $naveId,
                                'maquina_id'   => null,
                                'estado'       => 'almacenado',
                            ]);
                        }
                    }

                    // ===== PAQUETE =====
                    if ($tipoMovimiento === 'paquete') {
                        $origen = $paquete->ubicacion->nombre ?? 'origen desconocido';

                        $descripcion = "Movemos paquete (Código: {$codigo})"
                            . " de {$origen}"
                            . " a " . ($maquinaDetectada ? 'máquina ' . $maquinaDetectada->nombre
                                : 'ubicación ' . ($ubicacion->nombre ?? 'destino desconocido'));

                        $movPend = Movimiento::where('paquete_id', $paquete->id)
                            ->where(function ($q) use ($ubicacion, $maquinaDetectada) {
                                if ($ubicacion) {
                                    $q->where('ubicacion_destino', $ubicacion->id);
                                }
                                if ($maquinaDetectada) {
                                    $q->orWhere('maquina_destino', $maquinaDetectada->id);
                                }
                            })
                            ->where('estado', 'pendiente')
                            ->latest()
                            ->first();

                        if ($movPend) {
                            $movPend->update([
                                'estado'          => 'completado',
                                'fecha_ejecucion' => now(),
                                'ejecutado_por'   => auth()->id(),
                                // opcional: 'descripcion' => $descripcion,
                            ]);
                        } else {
                            Movimiento::create([
                                'tipo'               => 'movimiento libre',
                                'paquete_id'         => $paquete->id,
                                'ubicacion_origen'   => $paquete->ubicacion_id,
                                'maquina_origen'     => $paquete->maquina_id,
                                'ubicacion_destino'  => $ubicacion?->id,
                                'maquina_destino'    => $maquinaDetectada?->id,
                                'estado'             => 'completado',
                                'descripcion'        => $descripcion,
                                'nave_id'            => $naveId,
                                'fecha_ejecucion'    => now(),
                                'ejecutado_por'      => auth()->id(),
                            ]);
                        }

                        $paquete->update([
                            'ubicacion_id' => $ubicacion?->id,
                            'obra_id'      => $naveId,
                            'maquina_id'   => $maquinaDetectada?->id,
                        ]);
                    }
                } // foreach
            });

            // ÉXITO
            $msg = 'Movimiento(s) registrado(s) correctamente.';
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            \Log::error('Error al registrar movimiento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $mensajeError = $e->getMessage() ?: 'Hubo un problema al registrar el movimiento.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $mensajeError,
                    'error'   => app()->environment('local') ? $e->getMessage() : null,
                ], 500);
            }
            return back()->withInput()->with('error', $mensajeError);
        }
    }


    // --- API: devolver info rápida de un código (para chips asíncronos) ---
    public function infoCodigo(Request $request)
    {
        $code = strtoupper(trim((string)$request->query('code', '')));

        if ($code === '' || strlen($code) < 2) {
            return response()->json(['ok' => false, 'error' => 'Código vacío o inválido'], 422);
        }

        // MP******** → Producto
        if (str_starts_with($code, 'MP')) {
            // Carga lo justo: producto_base(diametro,longitud,tipo) + ubicacion(nombre)
            $prod = Producto::with([
                'productoBase:id,tipo,diametro,longitud',
                'ubicacion:id,nombre'
            ])->where('codigo', $code)->first();

            if (!$prod) {
                return response()->json(['ok' => false, 'error' => 'Producto no encontrado'], 404);
            }

            $tipoBase = strtolower($prod->productoBase->tipo ?? '');

            // Sigla según tu enum real en productos_base (barra / encarretado)
            $sigla = match ($tipoBase) {
                'barra'       => 'B',
                'encarretado' => 'E',
                default       => mb_strtoupper(mb_substr($tipoBase, 0, 1)),
            };

            return response()->json([
                'ok'        => true,
                'clase'     => 'producto',
                'codigo'    => $code,
                'sigla'     => $sigla,
                'tipo'      => $tipoBase,                                  // barra | encarretado
                'diametro'  => (int) $prod->productoBase->diametro,        // Ø (int)
                'longitud'  => $tipoBase === 'encarretado'
                    ? null
                    : ($prod->productoBase->longitud ?? null), // L solo si NO es encarretado
                'ubicacion' => $prod->ubicacion->nombre ?? null,
            ]);
        }

        // P******** → Paquete (si aplica)
        if (str_starts_with($code, 'P')) {
            $paq = Paquete::with(['ubicacion:id,nombre'])->where('codigo', $code)->first();
            if (!$paq) {
                return response()->json(['ok' => false, 'error' => 'Paquete no encontrado'], 404);
            }

            return response()->json([
                'ok'        => true,
                'clase'     => 'paquete',
                'codigo'    => $code,
                'sigla'     => 'PAQ',
                'tipo'      => 'paquete',
                'diametro'  => null,
                'longitud'  => null,
                'ubicacion' => $paq->ubicacion->nombre ?? null,
            ]);
        }

        return response()->json(['ok' => false, 'error' => 'Prefijo no soportado (usa MP o P)'], 422);
    }




    public function destroy($id)
    {
        // Iniciar una transacción para asegurar la integridad de los datos
        DB::beginTransaction();

        try {
            // Obtener el movimiento que será eliminado
            $movimiento = Movimiento::findOrFail($id);
            if ($movimiento->producto_id) {
                // Obtener el producto asociado al movimiento
                $producto = Producto::findOrFail($movimiento->producto_id);

                // Revertir la ubicación y máquina del producto al origen del movimiento
                $producto->ubicacion_id = $movimiento->ubicacion_origen ?: null;
                $producto->maquina_id = $movimiento->maquina_origen ?: null; // Asegúrate de tener este campo en tu modelo Movimiento

                // Actualizar el estado del producto basado en la ubicación de origen
                if ($movimiento->ubicacion_origen) {
                    $ubicacion = Ubicacion::find($movimiento->ubicacion_origen);
                    if ($ubicacion) {

                        $producto->estado = 'almacenado';
                    }
                } elseif ($movimiento->maquina_origen) {
                    // Si se movió a una máquina, revertir a la máquina de origen
                    $producto->estado = 'fabricando';
                } else {
                    // Si no hay información de origen, asignar un estado por defecto
                    $producto->estado = 'almacenado';
                }
                // Guardar los cambios en el producto
                $producto->save();
            }

            if ($movimiento->paquete_id) {
                // Si es un paquete, proceder con la lógica para paquetes
                $paquete = Paquete::findOrFail($movimiento->paquete_id);

                // Revertir la ubicación del paquete al origen del movimiento
                $paquete->ubicacion_id = $movimiento->ubicacion_origen ?: null;

                // Guardar los cambios en el paquete
                $paquete->save();
            }

            // Eliminar el movimiento
            $movimiento->delete();

            // Confirmar la transacción
            DB::commit();

            // Redirigir con un mensaje de éxito
            return redirect()->route('movimientos.index')->with('success', 'Movimiento eliminado correctamente.');
        } catch (\Throwable $e) {
            // Revertir la transacción si ocurre un error
            DB::rollBack();

            // Registrar el error en los logs
            Log::error('Error al eliminar el movimiento', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            // Redirigir con un mensaje de error genérico
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar el movimiento. Inténtalo nuevamente.');
        }
    }
}
