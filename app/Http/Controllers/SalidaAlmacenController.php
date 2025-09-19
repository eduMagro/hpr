<?php

namespace App\Http\Controllers;

use App\Models\SalidaAlmacen;
use App\Models\SalidaAlmacenCliente;
use App\Models\SalidaProducto;
use App\Models\EmpresaTransporte;
use App\Models\Camion;
use App\Models\ProductoBase;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\User;
use App\Models\PedidoAlmacenVentaLinea;
use App\Models\AlbaranVenta;
use App\Models\AlbaranVentaProducto;
use App\Models\AlbaranVentaLinea;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SalidaAlmacenController extends Controller
{
    public function aplicarFiltros($query, Request $request)
    {
        // Línea
        if ($request->filled('linea_id')) {
            $query->whereHas('albaranes.lineas', function ($q) use ($request) {
                $q->where('id', $request->linea_id);
            });
        }

        // Código salida
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        // Código albarán
        if ($request->filled('albaran')) {
            $query->whereHas('albaranes', function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->albaran . '%');
            });
        }

        // Cliente
        if ($request->filled('cliente_id')) {
            $query->whereHas('albaranes.cliente', function ($q) use ($request) {
                $q->where('id', $request->cliente_id);
            });
        }

        // Producto base
        // Producto base (tipo, diámetro, longitud)
        if ($request->filled('producto_tipo') || $request->filled('producto_diametro') || $request->filled('producto_longitud')) {
            $query->whereHas('albaranes.lineas.productoBase', function ($q) use ($request) {
                if ($request->filled('producto_tipo')) {
                    $q->where('tipo', 'like', '%' . $request->producto_tipo . '%');
                }
                if ($request->filled('producto_diametro')) {
                    $q->where('diametro',  $request->producto_diametro);
                }
                if ($request->filled('producto_longitud')) {
                    $q->where('longitud', $request->producto_longitud); // comparación exacta
                }
            });
        }



        // Cantidad mínima
        if ($request->filled('cantidad_min')) {
            $query->whereHas('albaranes.lineas', function ($q) use ($request) {
                $q->where('cantidad_kg', '=', $request->cantidad_min);
            });
        }

        // Precio mínimo
        if ($request->filled('precio_min')) {
            $query->whereHas('albaranes.lineas', function ($q) use ($request) {
                $q->where('precio_unitario', '>=', $request->precio_min);
            });
        }

        // Estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // ✅ Ordenación
        $sortBy = $request->input('sort', 'fecha'); // por defecto ordena por fecha
        $order  = $request->input('order', 'desc');

        $query->reorder();

        switch ($sortBy) {
            case 'cliente':
                $query->orderBy(
                    Cliente::select('nombre')
                        ->whereColumn('clientes.id', 'albaranes.cliente_id'),
                    $order
                );
                break;

            case 'codigo':
            case 'fecha':
            case 'estado':
                $query->orderBy("salidas_almacen.$sortBy", $order);
                break;

            default:
                $query->orderBy('salidas_almacen.fecha', 'desc');
                break;
        }

        return $query;
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('linea_id')) {
            $filtros[] = 'ID línea: <strong>' . $request->linea_id . '</strong>';
        }

        if ($request->filled('codigo')) {
            $filtros[] = 'Código salida: <strong>' . $request->codigo . '</strong>';
        }

        if ($request->filled('albaran')) {
            $filtros[] = 'Código albarán: <strong>' . $request->albaran . '</strong>';
        }

        if ($request->filled('cliente_id')) {
            $cliente = Cliente::find($request->cliente_id);
            if ($cliente) {
                $filtros[] = 'Cliente: <strong>' . $cliente->nombre . '</strong>';
            }
        }

        // Producto base (tipo, diámetro, longitud)
        if ($request->filled('producto_tipo') || $request->filled('producto_diametro') || $request->filled('producto_longitud')) {
            $detalles = [];

            if ($request->filled('producto_tipo')) {
                $detalles[] = "Tipo: <strong>{$request->producto_tipo}</strong>";
            }
            if ($request->filled('producto_diametro')) {
                $detalles[] = "Ø <strong>{$request->producto_diametro}</strong>";
            }
            if ($request->filled('producto_longitud')) {
                $detalles[] = "Longitud: <strong>{$request->producto_longitud} m</strong>";
            }

            $filtros[] = 'Producto base (' . implode(' — ', $detalles) . ')';
        }


        if ($request->filled('cantidad_min')) {
            $filtros[] = 'Cantidad <strong>' . $request->cantidad_min . ' kg</strong>';
        }

        if ($request->filled('precio_min')) {
            $filtros[] = 'Precio ≥ <strong>' . $request->precio_min . ' €</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'codigo'   => 'Código salida',
                'cliente'  => 'Cliente',
                'estado'   => 'Estado',
                'fecha'    => 'Fecha',
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

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down')
            : 'fas fa-sort';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="text-white text-decoration-none">' .
            $titulo . ' <i class="' . $icon . '"></i></a>';
    }

    public function index(Request $request)
    {
        $query = SalidaAlmacen::with([
            'camionero',
            'albaranes.cliente',
            'albaranes.lineas.productoBase',
        ]);

        // aplicar filtros dinámicos
        $this->aplicarFiltros($query, $request);

        $ordenables = [
            'codigo'  => $this->getOrdenamiento('codigo', 'Código salida'),
            'cliente' => $this->getOrdenamiento('cliente', 'Cliente'),
            'estado'  => $this->getOrdenamiento('estado', 'Estado'),
            'fecha'   => $this->getOrdenamiento('fecha', 'Fecha'),
        ];

        $perPage = $request->input('per_page', 10);
        $salidas = $query->paginate($perPage)->appends($request->all());
        // filtros activos para mostrarlos encima de la tabla
        $filtrosActivos = $this->filtrosActivos($request);

        // camioneros para el filtro select
        $camioneros = User::orderBy('name')->pluck('name', 'id');
        $clientes = Cliente::orderBy('empresa')->pluck('empresa', 'id');
        $obraDestinos = Obra::orderBy('obra')->pluck('obra', 'id');
        return view('salidasAlmacen.index', compact('salidas', 'filtrosActivos', 'ordenables', 'camioneros', 'clientes', 'obraDestinos'));
    }

    public function crearDesdeLineas(Request $request)
    {
        // Recibe un array asociativo: [ linea_id => cantidad_asignada ]
        $cantidades = $request->input('lineas', []);

        if (empty($cantidades) || !is_array($cantidades)) {
            return back()->with('error', 'No seleccionaste ninguna línea.');
        }

        $lineas = PedidoAlmacenVentaLinea::with('pedido.cliente')
            ->whereIn('id', array_keys($cantidades))
            ->get();

        if ($lineas->isEmpty()) {
            return back()->with('error', 'No se encontraron líneas válidas.');
        }

        DB::transaction(function () use ($lineas, $cantidades, &$salida) {
            // 🔹 Crear la salida (el viaje del camión)
            $salida = SalidaAlmacen::create([
                'codigo'     => $this->generarCodigoSalida(),
                'fecha'      => now(),
                'estado'     => 'pendiente',
                'created_by' => auth()->id(),
            ]);

            // 🔹 Agrupar líneas por cliente
            $porCliente = $lineas->groupBy(fn($l) => $l->pedido->cliente_id);

            foreach ($porCliente as $clienteId => $lineasCliente) {
                // Generar código AV25/xxxx
                $codigo = $this->generarCodigoAlbaran();

                // Crear el albarán de venta
                $av = AlbaranVenta::create([
                    'salida_id'  => $salida->id,
                    'cliente_id' => $clienteId,
                    'codigo'     => $codigo,
                    'fecha'      => now(),
                    'estado'     => 'pendiente',
                ]);

                // Asignar todas las líneas de ese cliente al AV
                foreach ($lineasCliente as $linea) {
                    $cantidadAsignada = $cantidades[$linea->id] ?? 0;

                    if ($cantidadAsignada > 0) {
                        AlbaranVentaLinea::create([
                            'albaran_id'        => $av->id,
                            'producto_base_id'  => $linea->producto_base_id,
                            'pedido_linea_id'   => $linea->id,
                            'cantidad_kg'       => $cantidadAsignada, // 🔹 aquí usamos el valor del modal
                            'precio_unitario'   => $linea->precio_unitario,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('salidas-almacen.index', $salida)
            ->with('success', 'Salida creada con albaranes generados.');
    }

    private function generarCodigoSalida(): string
    {
        $year = now()->format('y'); // 25
        $ultimo = SalidaAlmacen::whereYear('created_at', now()->year)->max('codigo');

        if ($ultimo) {
            // Extrae número y suma 1
            preg_match('/(\d+)$/', $ultimo, $m);
            $num = isset($m[1]) ? intval($m[1]) + 1 : 1;
        } else {
            $num = 1;
        }

        return sprintf("SA%s/%04d", $year, $num);
    }
    private function generarCodigoAlbaran(): string
    {
        $year = now()->format('y'); // "25" para 2025

        // Buscar el último código de este año
        $ultimo = \App\Models\AlbaranVenta::whereYear('created_at', now()->year)
            ->orderByDesc('id')
            ->value('codigo');

        if ($ultimo) {
            // Extraer la parte numérica final
            preg_match('/(\d+)$/', $ultimo, $m);
            $num = isset($m[1]) ? intval($m[1]) + 1 : 1;
        } else {
            $num = 1;
        }

        // Formato: AV25/0001
        return sprintf("AV%s/%04d", $year, $num);
    }


    public function activarLinea($salidaId, $lineaId)
    {
        $salida = SalidaAlmacen::findOrFail($salidaId);
        $linea = AlbaranVentaLinea::findOrFail($lineaId);

        $linea->estado = 'activo';
        $linea->save();

        return back()->with('success', "Línea {$linea->id} activada en salida {$salida->codigo}");
    }

    public function desactivarLinea($salidaId, $lineaId)
    {
        $salida = SalidaAlmacen::findOrFail($salidaId);
        $linea = AlbaranVentaLinea::findOrFail($lineaId);

        $linea->estado = 'pendiente';
        $linea->save();

        return back()->with('success', "Línea {$linea->id} desactivada en salida {$salida->codigo}");
    }

    public function cancelarLinea($salidaId, $lineaId)
    {
        $salida = SalidaAlmacen::findOrFail($salidaId);
        $linea = AlbaranVentaLinea::findOrFail($lineaId);

        $linea->estado = 'cancelado';
        $linea->save();

        return back()->with('success', "Línea {$linea->id} cancelada en salida {$salida->codigo}");
    }

    public function actualizarEstado(Request $request, $salidaId)
    {
        try {
            $salida = SalidaAlmacen::findOrFail($salidaId);

            // Verificamos que el estado actual sea pendiente antes de cambiarlo a completado
            if ($salida->estado != 'pendiente') {
                return response()->json(['message' => 'La salida ya estaba completada.'], 400);
            }
            // Asignamos el usuario autenticado (gruista)
            $salida->user_id = auth()->id();

            // Actualizamos el estado
            $salida->estado = 'completada';
            $salida->fecha_salida = now();
            $salida->save();


            return response()->json([
                'message' => 'Salida completada con éxito.'
            ]);
        } catch (\Exception $e) {
            // Capturamos cualquier error y retornamos un mensaje
            return response()->json(['message' => 'Hubo un error al completar la salida. ' . $e->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        // Dropdown seeds
        $tipos = ['barra', 'encarretado']; // ajusta si tienes más
        $diametros = ProductoBase::select('diametro')
            ->distinct()->orderBy('diametro')->pluck('diametro');
        $longitudes = ProductoBase::select('longitud')
            ->whereBetween('longitud', [6, 12]) // 👈 solo longitudes 6–12
            ->distinct()
            ->orderBy('longitud')
            ->pluck('longitud');

        $empresas = EmpresaTransporte::with('camiones:id,empresa_id,modelo')
            ->orderBy('nombre')->get();

        $clientes = Cliente::orderBy('empresa')->get();
        $obras    = Obra::orderBy('obra')->get();


        // Lista cacheada de productos base
        $productosBase = Cache::rememberForever('productos_base_lista_barras', function () {
            return ProductoBase::select('id', 'tipo', 'diametro', 'longitud')
                ->where('tipo', 'barra') // 👈 solo tipo barra
                ->whereBetween('longitud', [6, 12]) // 👈 longitud entre 6 y 12 metros
                ->orderBy('tipo')
                ->orderBy('diametro')
                ->orderBy('longitud')
                ->get();
        });

        return view('salidasAlmacen.create', compact(
            'tipos',
            'diametros',
            'longitudes',
            'empresas',
            'clientes',
            'obras',
            'productosBase'
        ));
    }

    public function show($id)
    {
        //
    }
    /**
     * AJAX: devuelve disponibilidad por tipo/diametro/longitud
     * Response: { total_peso_kg, total_productos, bases: [...], productos_preview: [...] }
     */
    public function disponibilidad(Request $request)
    {
        // 1) Validación sin redirects HTML
        $v = Validator::make($request->all(), [
            'tipo'     => ['required', 'string', Rule::in(['barra', 'encarretado'])],
            'diametro' => ['required', 'numeric'],
            'longitud' => ['nullable', 'numeric'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Validación fallida',
                'errors'  => $v->errors(),
            ], 422);
        }

        try {
            // 0) Nave Almacén del cliente HPR (obligatoria)
            $naveAlmacen = Obra::whereHas('cliente', function ($q) {
                $q->where('empresa', 'like', '%Hierros Paco Reyes%');
            })
                ->where('obra', 'like', '%Almacén%')
                ->first();

            if (!$naveAlmacen) {
                return response()->json([
                    'message' => 'No se encontró la nave "Almacén" del cliente "Hierros Paco Reyes".',
                    'total_peso_kg'     => 0.0,
                    'total_productos'   => 0,
                    'bases'             => [],
                    'productos_preview' => [],
                ], 404);
            }

            $tipo     = $request->input('tipo');
            $diametro = (float) $request->input('diametro');
            $longitud = $request->input('longitud'); // puede venir null o ""

            // 2) Bases compatibles
            $basesQuery = ProductoBase::query()
                ->select('id', 'tipo', 'diametro', 'longitud')
                ->where('tipo', $tipo)
                ->where('diametro', $diametro);

            if ($longitud !== null && $longitud !== '') {
                $basesQuery->where('longitud', $longitud);
            }

            $bases = $basesQuery->get();

            if ($bases->isEmpty()) {
                return response()->json([
                    'total_peso_kg'      => 0.0,
                    'total_productos'    => 0,
                    'bases'              => [],
                    'productos_preview'  => [],
                ]);
            }

            $baseIds = $bases->pluck('id');

            // 3) Agregados por base (SOLO en nave almacén)
            $agregados = Producto::query()
                ->selectRaw('producto_base_id, COUNT(*) as productos, COALESCE(SUM(peso_stock),0) as peso_total')
                ->where('obra_id', $naveAlmacen->id)
                ->whereIn('producto_base_id', $baseIds)
                ->where('peso_stock', '>', 0)
                ->groupBy('producto_base_id')
                ->get()
                ->keyBy('producto_base_id');

            // Totales globales (ya filtrados por nave)
            $totalProductos = (int) $agregados->sum('productos');
            $totalPeso      = (float) $agregados->sum('peso_total');

            // 4) Preview FIFO (primeros 8 más antiguos, en nave almacén)
            $preview = Producto::query()
                ->select('id', 'codigo', 'producto_base_id', 'peso_stock', 'created_at')
                ->where('obra_id', $naveAlmacen->id)
                ->whereIn('producto_base_id', $baseIds)
                ->where('peso_stock', '>', 0)
                ->orderBy('created_at', 'asc')
                ->take(8)
                ->get()
                ->map(function ($p) {
                    return [
                        'id'         => $p->id,
                        'codigo'     => $p->codigo,
                        'peso_kg'    => (float) $p->peso_stock,
                        'base_id'    => $p->producto_base_id,
                        'created_at' => optional($p->created_at)->format('Y-m-d H:i'),
                    ];
                })
                ->values();

            // 5) Resumen por base
            $basesSalida = $bases->map(function ($b) use ($agregados) {
                $res = $agregados->get($b->id);
                return [
                    'id'       => $b->id,
                    'tipo'     => $b->tipo,
                    'diametro' => $b->diametro,
                    'longitud' => $b->longitud,
                    'resumen'  => [
                        'productos'  => $res ? (int) $res->productos : 0,
                        'peso_total' => $res ? (float) $res->peso_total : 0.0,
                    ],
                ];
            })->values();

            return response()->json([
                'total_peso_kg'     => round($totalPeso, 2),
                'total_productos'   => $totalProductos,
                'bases'             => $basesSalida,
                'productos_preview' => $preview,
            ]);
        } catch (\Throwable $e) {
            // Log::error('Disponibilidad error: '.$e->getMessage(), ['ex' => $e]);
            return response()->json([
                'message' => 'Error interno al consultar disponibilidad',
            ], 500);
        }
    }

    public function eventos()
    {
        $salidas = SalidaAlmacen::with(['albaranes.cliente'])
            ->get()
            ->map(function ($s) {
                // Si tiene varios clientes en sus albaranes, los concatenamos
                $clientes = $s->albaranes
                    ->pluck('cliente.nombre')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                return [
                    'id'    => $s->id,
                    'title' => "Salida {$s->codigo}" . ($clientes ? " - {$clientes}" : ''),
                    'start' => $s->fecha->toDateString(),
                    'end'   => $s->fecha->toDateString(),
                    'color' => $s->estado === 'completado' ? '#16a34a'
                        : ($s->estado === 'cancelado' ? '#dc2626' : '#2563eb'),
                ];
            });

        return response()->json($salidas);
    }
    public function actualizarFecha(Request $request, SalidaAlmacen $salida)
    {
        $request->validate([
            'fecha' => 'required|date',
        ]);

        $salida->update([
            'fecha' => $request->input('fecha'),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'salida'  => $salida,
        ]);
    }

    /**
     * Crea la salida de almacén consumiendo productos por FIFO,
     * ya sea por "peso_objetivo_kg" o "unidades_objetivo".
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_base_id'  => ['required', 'exists:productos_base,id'],
            'productos.*.peso_objetivo_kg'  => ['nullable', 'numeric', 'min:0.01'],
            'productos.*.unidades_objetivo' => ['nullable', 'integer', 'min:1'],
        ]);

        // Validar que cada línea tenga al menos uno de los dos campos
        foreach ($data['productos'] as $index => $linea) {
            if (empty($linea['peso_objetivo_kg']) && empty($linea['unidades_objetivo'])) {
                return back()
                    ->withErrors(["productos.$index" => 'Debes indicar un peso o unidades en cada línea.'])
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Crear salida de almacén
            $codigo = SalidaAlmacen::generarCodigo();
            $salida = SalidaAlmacen::create([
                'codigo'       => $codigo,
                'fecha_salida' => now(),
                'estado'       => 'pendiente',
                'user_id'      => auth()->id(),
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Insertar líneas de productos
            foreach ($data['productos'] as $linea) {
                DB::table('salidas_almacen_productos_base')->insert([
                    'salida_almacen_id' => $salida->id,
                    'producto_base_id'  => $linea['producto_base_id'],
                    'peso_objetivo_kg'  => $linea['peso_objetivo_kg'] ?? null,
                    'unidades_objetivo' => $linea['unidades_objetivo'] ?? null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('salidas-almacen.index')
                ->with('success', "Salida creada: {$salida->codigo}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
    public function productosAsignados($salidaId)
    {
        $asignados = DB::table('albaranes_venta_productos as avp')
            ->join('productos as p', 'p.id', '=', 'avp.producto_id')
            ->where('avp.salida_almacen_id', $salidaId)
            ->select(
                'avp.albaran_linea_id',
                'p.codigo',
                'p.producto_base_id',
                'avp.peso_kg',
                'avp.cantidad',
                'p.peso_stock'
            )
            ->orderBy('p.codigo')
            ->get()
            ->groupBy('albaran_linea_id');

        $resultado = [];

        foreach ($asignados as $lineaId => $items) {
            $resultado[$lineaId] = $items->map(function ($item) {
                return [
                    'codigo'           => $item->codigo,
                    'producto_base_id' => $item->producto_base_id,
                    'peso_kg'          => (float) $item->peso_kg,
                    'cantidad'         => (int) $item->cantidad,
                    'peso_stock'       => (float) $item->peso_stock,
                ];
            })->values();
        }

        return response()->json([
            'success'   => true,
            'asignados' => $resultado,
        ]);
    }


    public function productosPorMovimiento($movimientoId)
    {
        $movimiento = Movimiento::with('salidaAlmacen')->find($movimientoId);

        if (!$movimiento || !$movimiento->salida_almacen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no asociado a ninguna salida de almacén.'
            ]);
        }

        $salidaId = $movimiento->salida_almacen_id;

        $lineas = AlbaranVentaLinea::with('productoBase')
            ->whereHas('albaran', fn($q) => $q->where('salida_id', $salidaId))
            ->get()
            ->map(function ($linea) {
                return [
                    'id'               => $linea->id,
                    'producto_base_id' => $linea->producto_base_id,
                    'tipo'             => $linea->productoBase->tipo,
                    'diametro'         => $linea->productoBase->diametro,
                    'longitud'         => $linea->productoBase->longitud,
                    'peso_objetivo_kg' => (float) $linea->cantidad_kg,
                    'asignado_kg'      => (float) ($linea->asignado_kg ?? 0), // campo nuevo o calculado
                    'asignado_ud'      => (int)   ($linea->asignado_ud ?? 0),
                    'asignados'        => [], // opcional si guardas detalle por producto
                ];
            });

        return response()->json([
            'success'       => true,
            'salida_id'     => $salidaId,
            'observaciones' => $movimiento->salidaAlmacen->observaciones,
            'lineas'        => $lineas,
        ]);
    }

    public function validarProductoEscaneado(Request $request)
    {
        Log::info('🔎 Validando producto escaneado', $request->all());

        $request->validate([
            'codigo'            => ['required', 'string'],
            'salida_almacen_id' => ['required', 'exists:salidas_almacen,id'],
            'albaran_linea_id'  => ['required', 'exists:albaranes_venta_lineas,id'],
        ]);

        $codigo = strtoupper(trim($request->codigo));

        // 🔍 Buscar producto por código
        $producto = Producto::where('codigo', $codigo)->first();
        if (!$producto) {
            Log::warning("❌ Producto no encontrado", ['codigo' => $codigo]);
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // 🔍 Buscar la línea de albarán
        $linea = AlbaranVentaLinea::with('productoBase')->findOrFail($request->albaran_linea_id);

        // 🔒 Validar que corresponde al mismo producto_base_id
        if ($producto->producto_base_id != $linea->producto_base_id) {
            Log::warning("❌ Producto no coincide con línea", [
                'producto_base_id' => $producto->producto_base_id,
                'linea_base_id'    => $linea->producto_base_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'El producto escaneado no corresponde a esta línea.'
            ], 422);
        }

        // ⚡ Comprobar si ya fue escaneado en esta salida
        $existe = DB::table('albaranes_venta_productos')
            ->where('salida_almacen_id', $request->salida_almacen_id)
            ->where('producto_id', $producto->id)
            ->exists();

        if ($existe) {
            Log::warning("⚠️ Producto ya escaneado previamente", [
                'codigo' => $producto->codigo,
                'salida_almacen_id' => $request->salida_almacen_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Este producto ya fue escaneado en esta salida.'
            ], 409);
        }

        // ✅ Guardar en pivot
        AlbaranVentaProducto::create([
            'salida_almacen_id' => $request->salida_almacen_id,
            'albaran_linea_id'  => $linea->id,
            'producto_id'       => $producto->id,
            'peso_kg'           => $producto->peso_stock,
            'cantidad'          => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        Log::info("✅ Producto asignado correctamente", [
            'codigo'           => $producto->codigo,
            'producto_base_id' => $producto->producto_base_id,
            'linea_id'         => $linea->id,
            'salida_id'        => $request->salida_almacen_id,
        ]);

        return response()->json([
            'success'  => true,
            'producto' => [
                'id'               => $producto->id,
                'codigo'           => $producto->codigo,
                'producto_base_id' => $producto->producto_base_id,
                'peso_kg'          => (float) $producto->peso_stock,
            ],
            'linea' => [
                'id'               => $linea->id,
                'producto_base_id' => $linea->producto_base_id,
                'objetivo_kg'      => (float) $linea->cantidad_kg,
            ]
        ]);
    }



    public function eliminarProductoEscaneado(SalidaAlmacen $salida, $codigo)
    {
        try {
            return DB::transaction(function () use ($salida, $codigo) {
                // 1) Producto con lock
                $producto = Producto::where('codigo', $codigo)
                    ->lockForUpdate()
                    ->first();

                if (!$producto) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Producto no encontrado.',
                    ], 404);
                }

                // 2) Buscar en la pivot con lock
                $registro = DB::table('albaranes_venta_productos')
                    ->where('salida_almacen_id', $salida->id)
                    ->where('producto_id', $producto->id)
                    ->lockForUpdate()
                    ->first();

                if (!$registro) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El producto no está asignado a esta salida.',
                    ], 404);
                }

                // 3) Revertir stock
                $devKg = (float) ($registro->peso_kg ?? 0);
                $devUd = (int)   ($registro->cantidad ?? 0);

                if ($devKg > 0) {
                    $producto->peso_stock = (float)$producto->peso_stock + $devKg;
                    if ($producto->estado === 'agotado' && $producto->peso_stock > 0) {
                        $producto->estado = 'disponible';
                    }
                } elseif ($devUd > 0 && property_exists($producto, 'unidades_stock')) {
                    $producto->unidades_stock = (int)$producto->unidades_stock + $devUd;
                    if ($producto->estado === 'agotado' && $producto->unidades_stock > 0) {
                        $producto->estado = 'disponible';
                    }
                }

                $producto->save();

                // 4) Eliminar de la pivot
                DB::table('albaranes_venta_productos')
                    ->where('id', $registro->id)
                    ->delete();

                return response()->json([
                    'success'     => true,
                    'message'     => 'Producto eliminado de la salida correctamente.',
                    'producto_id' => $producto->id,
                    'devuelto_kg' => $devKg ?: null,
                    'devuelto_ud' => $devUd ?: null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Error al eliminar producto escaneado: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al eliminar el producto.',
            ], 500);
        }
    }

    public function completarDesdeMovimiento($movimientoId)
    {
        // ✅ Tolerancias (ajusta a tu gusto)
        $TOL_KG = 0.1; // 100 gramos
        $TOL_UD = 0;   // exacto en unidades

        try {
            return DB::transaction(function () use ($movimientoId, $TOL_KG, $TOL_UD) {

                /** @var \App\Models\Movimiento $movimiento */
                $movimiento = Movimiento::lockForUpdate()->findOrFail($movimientoId);

                if (strtolower($movimiento->tipo) !== 'salida almacén') {
                    return response()->json([
                        'success' => false,
                        'message' => 'El movimiento no corresponde a una salida de almacén.'
                    ], 422);
                }

                /** @var \App\Models\SalidaAlmacen $salida */
                $salida = SalidaAlmacen::lockForUpdate()->findOrFail($movimiento->salida_almacen_id);

                // 🔎 1) Objetivos por producto base
                // 1) Objetivos desde las líneas de albarán
                $objetivos = DB::table('albaranes_venta_lineas as avl')
                    ->join('productos_base as pb', 'pb.id', '=', 'avl.producto_base_id')
                    ->join('albaranes_venta as av', 'av.id', '=', 'avl.albaran_id')
                    ->where('av.salida_id', $salida->id)
                    ->get([
                        'avl.id as linea_id',
                        'avl.producto_base_id',
                        'avl.cantidad_kg as peso_objetivo_kg',
                        DB::raw('NULL as unidades_objetivo'),
                        'pb.diametro',
                        'pb.longitud',
                        'pb.tipo',
                    ]);

                // 2) Asignados reales (productos ya escaneados)
                $asignados = DB::table('salidas_almacen_productos as sap')
                    ->join('productos as p', 'p.id', '=', 'sap.producto_id')
                    ->where('sap.salida_almacen_id', $salida->id)
                    ->groupBy('sap.linea_id')
                    ->select([
                        'sap.linea_id',
                        DB::raw('COALESCE(SUM(sap.peso_kg),0)  as asignado_kg'),
                        DB::raw('COALESCE(SUM(sap.cantidad),0) as asignado_ud'),
                    ])
                    ->get()
                    ->keyBy('linea_id');


                // 🔎 3) Comprobación de objetivos
                $pendientes = [];

                Log::info('Comprobando objetivos para completar salida', [
                    'salida_id' => $salida->id,
                    'objetivos' => $objetivos->toArray(),
                    'asignados' => $asignados->toArray(),
                ]);

                foreach ($objetivos as $obj) {
                    $pbId = (int) $obj->producto_base_id;
                    $asig = $asignados->get($pbId);

                    $objetivoKg = $obj->peso_objetivo_kg !== null ? (float)$obj->peso_objetivo_kg : null;
                    $objetivoUd = $obj->unidades_objetivo !== null ? (int)$obj->unidades_objetivo : null;

                    $asigKg = $asig ? (float)$asig->asignado_kg : 0.0;
                    $asigUd = $asig ? (int)$asig->asignado_ud : 0;

                    $faltaKg = $objetivoKg !== null ? max(0.0, $objetivoKg - $asigKg) : null;
                    $faltaUd = $objetivoUd !== null ? max(0,   $objetivoUd - $asigUd) : null;

                    // ✅ Solo exigimos el cumplimiento de los objetivos definidos
                    $incompleto = false;

                    if ($objetivoKg !== null && $faltaKg > $TOL_KG) {
                        $incompleto = true;
                    }

                    if ($objetivoUd !== null && $faltaUd > $TOL_UD) {
                        $incompleto = true;
                    }

                    if ($incompleto) {
                        $pendientes[] = [
                            'producto_base_id' => $pbId,
                            'diametro'         => $obj->diametro,
                            'longitud'         => $obj->longitud,
                            'tipo'             => $obj->tipo,
                            'objetivo_kg'      => $objetivoKg,
                            'asignado_kg'      => $asigKg,
                            'falta_kg'         => $faltaKg,
                            'objetivo_ud'      => $objetivoUd,
                            'asignado_ud'      => $asigUd,
                            'falta_ud'         => $faltaUd,
                        ];
                    }
                }

                if (!empty($pendientes)) {
                    return response()->json([
                        'success'    => false,
                        'message'    => 'No se puede completar: faltan objetivos por cubrir.',
                        'pendientes' => $pendientes,
                    ], 422);
                }

                // ✅ 4) Marcar como completado
                $salida->estado = 'completada';
                $salida->fecha_salida = $salida->fecha_salida ?? now();
                $salida->save();

                $movimiento->estado = 'ejecutado';
                $movimiento->ejecutado_por = auth()->id();
                $movimiento->fecha_ejecucion = now();
                $movimiento->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Salida de almacén completada correctamente.'
                ]);
            });
        } catch (\Throwable $e) {
            Log::error("Error completando salida desde movimiento {$movimientoId}: " . $e->getMessage(), ['ex' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al completar la salida.'
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            /** @var \App\Models\Salida $salida */
            $salida = SalidaAlmacen::findOrFail($id);

            $field     = $request->input('field');
            $value     = $request->input('value');
            $clienteId = $request->input('cliente_id');
            $obraId    = $request->input('obra_id');

            // Campos actualizables en 'salidas'
            $salidaFields = [
                'fecha_salida',
                'estado',
                'codigo_sage',
                'empresa_id',
                'camion_id',
            ];

            // Campos actualizables en 'salida_cliente' (pivot)
            $salidaClienteFields = [
                'importe',
                'horas_paralizacion',
                'importe_paralizacion',
                'horas_grua',
                'importe_grua',
                'horas_almacen',
            ];

            $allFields = array_merge($salidaFields, $salidaClienteFields);

            if (!in_array($field, $allFields, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo especificado no es editable en línea.'
                ], 422);
            }

            // Reglas de validación campo a campo
            $rules = [
                'fecha_salida'         => ['nullable', 'date'],
                'estado'               => ['nullable', 'string', 'max:50'],
                'codigo_sage'          => ['nullable', 'string', 'max:100'],
                'empresa_id' => ['nullable', 'integer', 'exists:empresa_transportes,id'],
                'camion_id'            => ['nullable', 'integer', 'exists:camiones,id'],

                'importe'              => ['nullable', 'numeric'],
                'horas_paralizacion'   => ['nullable', 'numeric'],
                'importe_paralizacion' => ['nullable', 'numeric'],
                'horas_grua'           => ['nullable', 'numeric'],
                'importe_grua'         => ['nullable', 'numeric'],
                'horas_almacen'        => ['nullable', 'numeric'],
            ];

            // Valida sólo el campo que viene
            if (array_key_exists($field, $rules)) {
                $request->validate([$field => $rules[$field]]);
            }

            // Normalizaciones previas
            // 1) Fecha con hora aceptando varios formatos
            if ($field === 'fecha_salida' && filled($value)) {
                try {
                    // Acepta 'd/m/Y H:i' o cualquier parseable por Carbon
                    $value = self::parseFechaHora($value)?->format('Y-m-d H:i:s');
                    if (!$value) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Formato de fecha no válido. Usa DD/MM/YYYY HH:MM o YYYY-MM-DD HH:MM:SS.'
                        ], 422);
                    }
                } catch (\Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Formato de fecha no válido.'
                    ], 422);
                }
            }

            // 2) Numéricos: null -> null, cadena vacía -> null
            if (in_array($field, $salidaClienteFields, true)) {
                if ($value === '' || $value === null) {
                    $value = null;
                } else {
                    $value = (float) $value;
                }
            }

            // ------- Persistencia -------
            if (in_array($field, $salidaFields, true)) {

                if ($field === 'empresa_id') {
                    // Cambiar empresa => limpiar camión si no pertenece
                    $nuevaEmpresaId = $value ?: null;

                    $salida->empresa_id = $nuevaEmpresaId;

                    // Si hay un camión asignado y no coincide con la nueva empresa, nuléalo
                    if ($salida->camion_id) {
                        $camionPertenece = Camion::where('id', $salida->camion_id)
                            ->where('empresa_id', $nuevaEmpresaId)
                            ->exists();

                        if (!$camionPertenece) {
                            $salida->camion_id = null;
                        }
                    }

                    $salida->save();
                } elseif ($field === 'camion_id') {
                    // Validar pertenencia del camión a la empresa actual (si hay)
                    if ($value) {
                        $empresaId = $salida->empresa_id;
                        if ($empresaId) {
                            $camionOk = Camion::where('id', $value)
                                ->where('empresa_id', $empresaId)
                                ->exists();

                            if (!$camionOk) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'El camión no pertenece a la empresa de transporte seleccionada.'
                                ], 422);
                            }
                        }
                    }

                    $salida->camion_id = $value ?: null;
                    $salida->save();
                } else {
                    // fecha_salida, estado, codigo_sage
                    $salida->$field = $value;
                    $salida->save();
                }
            } else {
                // Pivot: salida_cliente — requiere cliente y obra
                if (!$clienteId || !$obraId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Falta el ID del cliente u obra para actualizar el campo.'
                    ], 422);
                }

                DB::table('salida_cliente')
                    ->where('salida_id', $salida->id)
                    ->where('cliente_id', $clienteId)
                    ->where('obra_id', $obraId)
                    ->update([$field => $value]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Salida actualizada correctamente.',
                'data'    => [
                    'id'                     => $salida->id,
                    'fecha_salida'           => $salida->fecha_salida,
                    'estado'                 => $salida->estado,
                    'codigo_sage'            => $salida->codigo_sage,
                    'empresa_id'             => $salida->empresa_id,
                    'camion_id'              => $salida->camion_id,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la salida.' . $e->getMessage(),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function actualizarCodigoSage(Request $request, SalidaAlmacen $salida)
    {
        $request->validate([
            'codigo' => 'required|string|max:255',
        ], [
            'codigo.required' => 'El código es obligatorio.',
        ]);

        try {
            $salida->codigo_sage = $request->codigo;
            $salida->save();

            return response()->json([
                'success' => true,
                'message' => 'Código SAGE actualizado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el código SAGE.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
