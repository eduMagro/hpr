<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fabricante;
use App\Models\Distribuidor;
use App\Models\Cliente;
use App\Models\PedidoGlobal;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\PedidoProducto;
use App\Models\Elemento;
use App\Models\ProductoBase;
use App\Models\Entrada;
use App\Models\EntradaProducto;
use App\Models\Ubicacion;
use App\Models\Movimiento;
use App\Mail\PedidoCreado;
use App\Models\User;
use App\Models\Obra;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\StockService;
use App\Models\AsignacionTurno;
use App\Services\AlertaService;


class PedidoController extends Controller
{
    private function filtrosActivosPedidos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('codigo')) {
            $filtros[] = 'Código pedido: <strong>' . e($request->codigo) . '</strong>';
        }

        if ($request->filled('pedido_producto_id')) {
            $filtros[] = 'ID línea: <strong>' . e($request->pedido_producto_id) . '</strong>';
        }

        if ($request->filled('pedido_global_id')) {
            $pg = \App\Models\PedidoGlobal::find($request->pedido_global_id);
            if ($pg) {
                $filtros[] = 'Código pedido global: <strong>' . e($pg->codigo) . '</strong>';
            }
        }

        if ($request->filled('fabricante_id')) {
            $fabricante = \App\Models\Fabricante::find($request->fabricante_id);
            if ($fabricante) {
                $filtros[] = 'Fabricante: <strong>' . e($fabricante->nombre) . '</strong>';
            }
        }

        if ($request->filled('distribuidor_id')) {
            $distribuidor = \App\Models\Distribuidor::find($request->distribuidor_id);
            if ($distribuidor) {
                $filtros[] = 'Distribuidor: <strong>' . e($distribuidor->nombre) . '</strong>';
            }
        }

        if ($request->filled('obra_id')) {
            $obra = \App\Models\Obra::find($request->obra_id);
            $filtros[] = 'Obra: <strong>' . e($obra?->obra ?? ('ID ' . $request->obra_id)) . '</strong>';
        }

        if ($request->filled('fecha_pedido')) {
            $filtros[] = 'Fecha pedido: <strong>' . e($request->fecha_pedido) . '</strong>';
        }

        if ($request->filled('fecha_entrega')) {
            $filtros[] = 'Entrega estimada: <strong>' . e($request->fecha_entrega) . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . e(ucfirst($request->estado)) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'codigo'        => 'Código',
                'fecha_pedido'  => 'Fecha pedido',
                'fecha_entrega' => 'Entrega estimada',
                'estado'        => 'Estado',
                'peso_total'    => 'Peso total',
                'fabricante'    => 'Fabricante',
                'distribuidor'  => 'Distribuidor',
                'created_by'    => 'Creado por',
                'obra'          => 'Lugar de entrega',
            ];
            $orden = strtolower($request->order ?? 'desc') === 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . e($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . (int) $request->per_page . '</strong> registros por página';
        }

        return $filtros;
    }


    private function getOrdenamientoPedidos(string $columna, string $titulo): string
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

    public function aplicarFiltrosPedidos($query, Request $request)
    {
        // ===== Filtros =====

        // ID de línea (pedido_productos.id): contains + exacto con "=123"
        if ($request->filled('pedido_producto_id')) {
            $raw = trim((string) $request->pedido_producto_id);

            $query->whereHas('pedidoProductos', function ($q) use ($raw) {
                if (preg_match('/^=\s*(\d+)$/', $raw, $m)) {
                    $q->where('pedido_productos.id', (int) $m[1]);
                } else {
                    $q->whereRaw('CAST(pedido_productos.id AS CHAR) LIKE ?', ['%' . $raw . '%']);
                }
            });
        }

        // ID del pedido (pedidos.id): contains + exacto con "=123"
        if ($request->filled('pedido_id')) {
            $raw = trim((string) $request->pedido_id);
            if (preg_match('/^=\s*(\d+)$/', $raw, $m)) {
                $query->where('pedidos.id', (int) $m[1]);
            } else {
                $query->whereRaw('CAST(pedidos.id AS CHAR) LIKE ?', ['%' . $raw . '%']);
            }
        }

        // Código de pedido: contains, case-insensitive
        if ($request->filled('codigo')) {
            $codigo = trim($request->codigo);
            $query->whereRaw('LOWER(pedidos.codigo) LIKE ?', ['%' . mb_strtolower($codigo, 'UTF-8') . '%']);
        }

        // Pedido global (id exacto)
        if ($request->filled('pedido_global_id')) {
            $query->where('pedido_global_id', $request->pedido_global_id);
        }

        // Filtros por producto base de sus líneas
        if ($request->filled('producto_tipo') || $request->filled('producto_diametro') || $request->filled('producto_longitud')) {
            $tipo      = $request->filled('producto_tipo')      ? mb_strtolower(trim($request->producto_tipo), 'UTF-8') : null;
            $diametro  = $request->filled('producto_diametro')  ? mb_strtolower(trim($request->producto_diametro), 'UTF-8') : null;
            $longitud  = $request->filled('producto_longitud')  ? mb_strtolower(trim($request->producto_longitud), 'UTF-8') : null;

            $query->whereHas('pedidoProductos.productoBase', function ($q) use ($tipo, $diametro, $longitud) {
                if ($tipo !== null) {
                    $q->whereRaw('LOWER(tipo) LIKE ?', ['%' . $tipo . '%']);
                }
                if ($diametro !== null) {
                    $q->whereRaw('LOWER(diametro) LIKE ?', ['%' . $diametro . '%']);
                }
                if ($longitud !== null) {
                    $q->whereRaw('LOWER(longitud) LIKE ?', ['%' . $longitud . '%']);
                }
            });
        }

        // Fabricante / Distribuidor (por id exacto desde selects)
        if ($request->filled('fabricante_id')) {
            $query->where('fabricante_id', $request->fabricante_id);
        }
        if ($request->filled('distribuidor_id')) {
            $query->where('distribuidor_id', $request->distribuidor_id);
        }

        // Obra (id exacto)
        if ($request->filled('obra_id')) {
            $query->where('obra_id', $request->integer('obra_id'));
        }

        // Fechas
        if ($request->filled('fecha_pedido')) {
            $query->whereDate('fecha_pedido', $request->fecha_pedido);
        }
        if ($request->filled('fecha_entrega')) {
            $fecha = $request->fecha_entrega;
            $query->whereHas('pedidoProductos', function ($q) use ($fecha) {
                $q->whereDate('fecha_estimada_entrega', $fecha);
            });
        }

        // Estado (por LÍNEAS: pedido_productos.estado)
        if ($request->filled('estado')) {
            $estado = mb_strtolower(trim($request->estado), 'UTF-8');

            // Pedidos que tienen AL MENOS UNA línea en ese estado
            $query->whereHas('pedidoProductos', function ($q) use ($estado) {
                $q->whereRaw('LOWER(TRIM(pedido_productos.estado)) = ?', [$estado]);
            });
        }




        // ===== Orden =====
        $sortBy = $request->input('sort', 'created_at');
        $order  = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->reorder();

        switch ($sortBy) {
            case 'fabricante':
                $query->orderBy(
                    \App\Models\Fabricante::select('nombre')->whereColumn('fabricantes.id', 'pedidos.fabricante_id'),
                    $order
                );
                break;

            case 'distribuidor':
                $query->orderBy(
                    \App\Models\Distribuidor::select('nombre')->whereColumn('distribuidores.id', 'pedidos.distribuidor_id'),
                    $order
                );
                break;

            case 'obra': // ordenar por nombre de la obra (lugar de entrega)
                $query->orderBy(
                    \App\Models\Obra::select('obra')->whereColumn('obras.id', 'pedidos.obra_id'),
                    $order
                );
                break;

            // Campos locales
            case 'codigo':
            case 'fecha_pedido':
            case 'fecha_entrega':
            case 'estado':
            case 'peso_total':
            case 'created_by':
            case 'created_at':
                $query->orderBy("pedidos.$sortBy", $order);
                break;

            default:
                $query->orderBy('pedidos.fecha_entrega', 'desc');
                break;
        }

        return $query;
    }


    public function index(Request $request, StockService $stockService)
    {
        // Cargamos todos los modelos y relaciones necesarios
        $query = Pedido::with([
            'fabricante',
            'obra',
            'distribuidor',
            'productos',
            'pedidoGlobal',
            'pedidoProductos.productoBase',
            'pedidoProductos.pedidoGlobal', // ⚠️ importante cargar esta relación
        ]);

        if (auth()->user()->rol === 'operario') {
            $query->whereIn('estado', ['pendiente', 'parcial']);
        }

        $obras = Obra::whereIn('id', Pedido::select('obra_id')->distinct())
            ->orderBy('obra')
            ->pluck('obra', 'id');

        $this->aplicarFiltrosPedidos($query, $request);

        $perPage = $request->input('per_page', 10);
        $pedidos = $query->paginate($perPage)->appends($request->all());
        $pedidosGlobales = PedidoGlobal::orderBy('codigo')->get();

        // transform sin map a array: devolvemos modelos PedidoProducto filtrados
        $pedidos->getCollection()->transform(function ($pedido) use ($request) {
            $pedido->lineas = $pedido->pedidoProductos
                ->filter(function ($linea) use ($request) {
                    $pb = $linea->productoBase;

                    if (
                        !$request->filled('producto_tipo') &&
                        !$request->filled('producto_diametro') &&
                        !$request->filled('producto_longitud') &&
                        !$request->filled('fecha_entrega') &&
                        !$request->filled('estado')
                    ) {
                        return true;
                    }

                    if ($request->filled('producto_tipo') && (! $pb || ! str_contains($pb->tipo, $request->producto_tipo))) {
                        return false;
                    }

                    if ($request->filled('producto_diametro') && (! $pb || $pb->diametro != $request->producto_diametro)) {
                        return false;
                    }

                    if ($request->filled('producto_longitud') && (! $pb || $pb->longitud != $request->producto_longitud)) {
                        return false;
                    }

                    if ($request->filled('fecha_entrega')) {
                        $fechaLinea = $linea->fecha_estimada_entrega;
                        $fechaLinea = $fechaLinea ? \Carbon\Carbon::parse($fechaLinea)->format('Y-m-d') : null;

                        if ($fechaLinea !== $request->fecha_entrega) {
                            return false;
                        }
                    }

                    if ($request->filled('estado')) {
                        $estadoReq = mb_strtolower(trim($request->estado), 'UTF-8');
                        if (mb_strtolower(trim((string)$linea->estado), 'UTF-8') !== $estadoReq) {
                            return false;
                        }
                    }


                    return true;
                })
                ->values(); // devolvemos colección de modelos PedidoProducto

            return $pedido;
        });

        $filtrosActivos = $this->filtrosActivosPedidos($request);
        $fabricantes    = Fabricante::select('id', 'nombre')->get();
        $distribuidores = Distribuidor::select('id', 'nombre')->get();

        $ordenables = [
            'codigo'         => $this->getOrdenamientoPedidos('codigo', 'Código'),
            'fabricante'     => $this->getOrdenamientoPedidos('fabricante', 'Fabricante'),
            'distribuidor'   => $this->getOrdenamientoPedidos('distribuidor', 'Distribuidor'),
            'obra'           => $this->getOrdenamientoPedidos('obra', 'Lugar de entrega'),
            'peso_total'     => $this->getOrdenamientoPedidos('peso_total', 'Peso total'),
            'fecha_pedido'   => $this->getOrdenamientoPedidos('fecha_pedido', 'F. Pedido'),
            'fecha_entrega'  => $this->getOrdenamientoPedidos('fecha_entrega', 'F. Entrega'),
            'estado'         => $this->getOrdenamientoPedidos('estado', 'Estado'),
            'created_by'     => $this->getOrdenamientoPedidos('created_by', 'Creado por'),
        ];

        // ===== Cargar obras de HPR para el <select> =====
        $nombreCliente = 'Hierros Paco Reyes';
        $idClienteHpr = Cliente::query()
            ->where('empresa', 'like', "%{$nombreCliente}%")
            ->orderByRaw("CASE WHEN empresa = ? THEN 0 ELSE 1 END", [$nombreCliente])
            ->value('id');

        $obrasHpr = $idClienteHpr
            ? Obra::where('cliente_id', $idClienteHpr)->orderBy('obra')->get()
            : collect();

        $navesHpr = Obra::whereHas('cliente', function ($q) {
            $q->where('empresa', 'like', '%HIERROS PACO REYES%');
        })
            ->orderBy('obra')
            ->get();

        $obrasExternas = Obra::where('estado', 'activa')
            ->where('cliente_id', '!=', $idClienteHpr)
            ->orderBy('obra')
            ->get();

        // ===== Filtro para el cálculo del StockService =====
        $obraIdSeleccionada = $request->input('obra_id_hpr');
        $soloHpr            = $request->boolean('solo_hpr');

        $obraIds     = $obraIdSeleccionada ? [(int)$obraIdSeleccionada] : null;
        $clienteLike = (!$obraIds && $soloHpr) ? '%Hierros Paco Reyes%' : null;

        $datosStock = $stockService->obtenerDatosStock($obraIds, $clienteLike);

        return view('pedidos.index', array_merge([
            'pedidos'        => $pedidos,
            'navesHpr'       => $navesHpr,
            'fabricantes'    => $fabricantes,
            'filtrosActivos' => $filtrosActivos,
            'ordenables'     => $ordenables,
            'distribuidores' => $distribuidores,
            'pedidosGlobales' => $pedidosGlobales,
            'obrasHpr'       => $obrasHpr,
            'obrasExternas'  => $obrasExternas,
            'idClienteHpr'   => $idClienteHpr,
            'solo_hpr'       => $soloHpr,
            'obra_id_hpr'    => $obraIdSeleccionada,
            'obras'          => $obras,
        ], $datosStock));
    }

    public function recepcion($pedidoId, $productoBaseId, Request $request)
    {
        // Cargar pedido con relaciones
        $pedido = Pedido::with(['productos', 'entradas.productos', 'obra'])->findOrFail($pedidoId);

        // Movimiento obligatorio
        $movimientoId = $request->query('movimiento_id');
        if (!$movimientoId) {
            abort(422, 'Falta el movimiento.');
        }

        /** @var \App\Models\Movimiento $movimiento */
        $movimiento = Movimiento::with('pedidoProducto')->findOrFail($movimientoId);

        // Validar que pertenece al pedido
        if ((int) $movimiento->pedido_id !== (int) $pedido->id) {
            abort(422, 'El movimiento no corresponde al pedido.');
        }

        $linea = $movimiento->pedidoProducto; // 🔒 Línea asociada al movimiento
        $productoBase = $pedido->productos->firstWhere('id', $productoBaseId);

        // === resto de tu lógica de recepcion (naves, ubicaciones, fabricantes, últimos, etc.) ===
        $nave = $pedido->obra?->obra;
        $codigoAlmacen = Ubicacion::codigoDesdeNombreNave($nave);
        $ubicaciones = Ubicacion::where('almacen', $codigoAlmacen)
            ->orderBy('nombre')
            ->get()
            ->map(function ($ubicacion) {
                $ubicacion->nombre_sin_prefijo = Str::after($ubicacion->nombre, 'Almacén ');
                return $ubicacion;
            });

        $requiereFabricanteManual = $pedido->distribuidor_id !== null && $pedido->fabricante_id === null;
        $ultimoFabricante = Producto::with(['entrada', 'productoBase'])
            ->whereHas('entrada', fn($q) => $q->where('usuario_id', auth()->id()))
            ->latest()
            ->first()?->fabricante_id ?? null;

        $fabricantes = $requiereFabricanteManual ? Fabricante::orderBy('nombre')->get() : collect();

        // Últimas coladas por producto base para este usuario
        $ultimos = Producto::select('producto_base_id', 'n_colada', 'productos.ubicacion_id')
            ->join('entradas', 'entradas.id', '=', 'productos.entrada_id')
            ->where('entradas.usuario_id', auth()->id())
            ->where('producto_base_id', $productoBaseId)
            ->latest('productos.created_at')
            ->get()
            ->unique('producto_base_id')
            ->keyBy('producto_base_id');

        return view('pedidos.recepcion', compact(
            'pedido',
            'productoBase',
            'ubicaciones',
            'ultimos',
            'requiereFabricanteManual',
            'fabricantes',
            'ultimoFabricante',
            'linea',
            'movimiento'
        ));
    }


    public function procesarRecepcion(Request $request, $pedidoId)
    {
        try {
            $request->validate([
                'movimiento_id' => ['required', 'exists:movimientos,id'],
                'codigo'        => ['required', 'string', 'max:20', 'unique:productos,codigo', 'regex:/^mp/i'],
                'codigo_2'      => ['nullable', 'string', 'max:20', 'different:codigo', 'unique:productos,codigo', 'regex:/^mp/i'],
                'producto_base_id' => 'required|exists:productos_base,id',
                'peso'             => 'required|numeric|min:1',
                'n_colada'         => 'required|string|max:50',
                'n_paquete'        => 'required|string|max:50',
                'n_colada_2'       => 'nullable|string|max:50',
                'n_paquete_2'      => 'nullable|string|max:50',
                'ubicacion_id'     => 'required|exists:ubicaciones,id',
                'fabricante_id'    => 'nullable|exists:fabricantes,id',
            ]);

            $pedido = Pedido::with('productos')->findOrFail($pedidoId);

            if (in_array($pedido->estado, ['completado', 'cancelado'])) {
                return back()->with('error', "El pedido ya está {$pedido->estado} y no puede recepcionarse.");
            }

            /** @var \App\Models\Movimiento $movimiento */
            $movimiento = Movimiento::with('pedidoProducto')->lockForUpdate()->findOrFail($request->movimiento_id);

            if ((int)$movimiento->pedido_id !== (int)$pedido->id) {
                return back()->with('error', 'El movimiento no pertenece a este pedido.');
            }

            /** @var \App\Models\PedidoProducto $pedidoProducto */
            $pedidoProducto = $movimiento->pedidoProducto;

            // --- Preparar datos
            $codigo   = strtoupper(trim($request->codigo));
            $codigo2  = $request->filled('codigo_2') ? strtoupper(trim($request->codigo_2)) : null;
            $esDoble  = $request->filled('codigo_2') && $request->filled('n_colada_2') && $request->filled('n_paquete_2');
            $peso     = (float) $request->peso;
            $pesoPorPaquete = $esDoble ? round($peso / 2, 3) : $peso;

            $ubicacion = Ubicacion::findOrFail($request->ubicacion_id);

            $mapaAlmacenes = ['0A' => 1, '0B' => 2, 'AL' => 350];
            $obraIdActual = $mapaAlmacenes[$ubicacion->almacen] ?? null;
            if (!$obraIdActual) {
                return back()->with('error', 'No se puede determinar la obra a partir de la ubicación seleccionada.');
            }

            // --- Buscar/crear entrada abierta para la línea del movimiento
            $entrada = Entrada::where('pedido_id', $pedido->id)
                ->where('pedido_producto_id', $pedidoProducto->id)
                ->where('estado', 'abierto')
                ->lockForUpdate()
                ->first();

            if (!$entrada) {
                $entrada = new Entrada();
                $entrada->pedido_id          = $pedido->id;
                $entrada->pedido_producto_id = $pedidoProducto->id;
                $entrada->albaran            = $this->generarCodigoAlbaran();
                $entrada->usuario_id         = auth()->id();
                $entrada->peso_total         = 0;
                $entrada->estado             = 'abierto';
                $entrada->otros              = 'Entrada generada desde recepción de pedido';
                $entrada->save();
            }

            $fabricanteFinal = $pedido->fabricante_id ?? $request->fabricante_id;

            //COMPROBACION DE QUE NO NOS PASAMOS DE KG 
            // --- Calcular lo recepcionado hasta ahora en esa línea
            $recepcionadoHastaAhora = Producto::whereHas(
                'entrada',
                fn($q) =>
                $q->where('pedido_producto_id', $pedidoProducto->id)
            )->sum('peso_inicial');

            // --- Cantidad pedida
            $cantidadPedida = (float) $pedidoProducto->cantidad;

            // --- Cantidad que habrá después de este registro
            $totalConNuevo = $recepcionadoHastaAhora + $peso;

            if ($totalConNuevo > $cantidadPedida) {

                session()->flash('warning', "⚠️ Atención: se han recepcionado {$totalConNuevo} kg, superando lo pedido ({$cantidadPedida} kg).");
            }


            // --- Crear producto(s) en esa entrada
            Producto::create([
                'codigo'            => $codigo,
                'producto_base_id'  => $request->producto_base_id,
                'fabricante_id'     => $fabricanteFinal,
                'obra_id'           => $obraIdActual,
                'entrada_id'        => $entrada->id,
                'n_colada'          => $request->n_colada,
                'n_paquete'         => $request->n_paquete,
                'peso_inicial'      => $pesoPorPaquete,
                'peso_stock'        => $pesoPorPaquete,
                'estado'            => 'almacenado',
                'ubicacion_id'      => $ubicacion->id,
                'maquina_id'        => null,
                'otros'             => $request->otros ?? null,
            ]);

            if ($esDoble) {
                Producto::create([
                    'codigo'            => $codigo2,
                    'producto_base_id'  => $request->producto_base_id,
                    'fabricante_id'     => $fabricanteFinal,
                    'obra_id'           => $obraIdActual,
                    'entrada_id'        => $entrada->id,
                    'n_colada'          => $request->n_colada_2,
                    'n_paquete'         => $request->n_paquete_2,
                    'peso_inicial'      => $pesoPorPaquete,
                    'peso_stock'        => $pesoPorPaquete,
                    'estado'            => 'almacenado',
                    'ubicacion_id'      => $ubicacion->id,
                    'maquina_id'        => null,
                    'otros'             => $request->otros ?? null,
                ]);
            }

            // --- Actualizar peso de la entrada
            $entrada->peso_total += $peso;
            $entrada->save();

            return back()->with('success', 'Producto(s) recepcionado(s) correctamente.');
        } catch (\Throwable $e) {
            Log::error('❌ Error en procesarRecepcion()', [
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'file'  => $e->getFile(),
            ]);
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function generarCodigoAlbaran()
    {
        $año = now()->format('y');
        $prefix = "AC{$año}/";

        $ultimoCodigo = Entrada::where('albaran', 'like', "{$prefix}%")
            ->orderBy('albaran', 'desc')
            ->value('albaran');

        $siguiente = 1;

        if ($ultimoCodigo) {
            $partes = explode('/', $ultimoCodigo);
            $numeroActual = intval($partes[1]);
            $siguiente = $numeroActual + 1;
        }

        $numeroFormateado = str_pad($siguiente, 4, '0', STR_PAD_LEFT);

        return $prefix . $numeroFormateado;
    }

    // public function crearDesdeRecepcion(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'producto_base_id' => 'required|exists:productos_base,id',
    //             'peso' => 'required|numeric|min:0.01',
    //             'n_colada' => 'nullable|string|max:50',
    //             'n_paquete' => 'nullable|string|max:50|unique:productos,n_paquete',
    //             'ubicacion_id' => 'required|exists:ubicaciones,id',
    //             'otros' => 'nullable|string|max:255',
    //             'fabricante_id' => 'required|exists:fabricantes,id',
    //         ], [
    //             'producto_base_id.required' => 'El producto base es obligatorio.',
    //             'producto_base_id.exists' => 'El producto base no es válido.',

    //             'peso.required' => 'El peso es obligatorio.',
    //             'peso.numeric' => 'El peso debe ser un número.',
    //             'peso.min' => 'El peso debe ser mayor que 0.',

    //             'n_colada.string' => 'El número de colada debe ser texto.',
    //             'n_colada.max' => 'El número de colada no puede tener más de 50 caracteres.',

    //             'n_paquete.string' => 'El número de paquete debe ser texto.',
    //             'n_paquete.max' => 'El número de paquete no puede tener más de 50 caracteres.',
    //             'n_paquete.unique' => 'El número de paquete ya existe en otro producto.',

    //             'ubicacion_id.required' => 'La ubicación es obligatoria.',
    //             'ubicacion_id.exists' => 'La ubicación no es válida.',

    //             'otros.string' => 'El campo de observaciones debe ser texto.',
    //             'otros.max' => 'El campo de observaciones no puede tener más de 255 caracteres.',

    //             'fabricante_id.required' => 'El fabricante es obligatorio.',
    //             'fabricante_id.exists' => 'El fabricante no es válido.',
    //         ]);

    //         $producto = Producto::create([
    //             'producto_base_id' => $validated['producto_base_id'],
    //             'fabricante_id' => $validated['fabricante_id'],
    //             'n_colada' => $validated['n_colada'] ?? null,
    //             'n_paquete' => $validated['n_paquete'],
    //             'peso_inicial' => $validated['peso'],
    //             'peso_stock' => $validated['peso'],
    //             'estado' => 'almacenado',
    //             'ubicacion_id' => $validated['ubicacion_id'],
    //             'maquina_id' => null,
    //             'otros' => $validated['otros'] ?? null,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'producto_id' => $producto->id,
    //         ]);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error de validación',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error inesperado: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function activar($pedidoId, $lineaId)
    {
        // Cargamos la línea como stdClass (tal como ya haces)
        $linea = DB::table('pedido_productos')->where('id', $lineaId)->lockForUpdate()->first();

        if (!$linea) {
            return redirect()->back()->with('error', 'Línea de pedido no encontrada.');
        }
        if (!in_array($linea->estado, ['pendiente', 'parcial'])) {
            return redirect()->back()->with('error', 'Esta línea ya ha sido activada por otro usuario.');
        }

        // Cargamos el pedido con fabricante y distribuidor
        /** @var \App\Models\Pedido $pedido */
        $pedido = Pedido::with(['fabricante', 'distribuidor', 'obra'])->findOrFail($linea->pedido_id);


        if (!in_array($pedido->estado, ['pendiente', 'parcial', 'activo'])) {
            return redirect()->back()->with('error', 'Solo se pueden activar productos de pedidos pendientes, parciales o activos.');
        }

        // Producto base de la línea
        $productoBase = ProductoBase::findOrFail($linea->producto_base_id);

        // Proveedor: prioriza fabricante; si no hay, usa distribuidor; si tampoco, “No especificado”
        $proveedor = $pedido->fabricante->nombre
            ?? $pedido->distribuidor->nombre
            ?? 'No especificado';

        // Fecha estimada entrega (por si viene null, mostramos “—”)
        $fechaEntregaFmt = $linea->fecha_estimada_entrega
            ? Carbon::parse($linea->fecha_estimada_entrega)->format('d/m/Y')
            : '—';

        // Partes de descripción
        $partes = [];
        $partes[] = sprintf(
            'Se solicita descarga para producto %s Ø%s%s',
            $productoBase->tipo,
            (string) $productoBase->diametro,
            $productoBase->tipo === 'barra' ? (' de ' . (string) $productoBase->longitud . ' m') : ''
        );

        // Identificación del pedido, proveedor y línea
        $partes[] = sprintf('Pedido %s', $pedido->codigo ?? $pedido->id);
        $partes[] = sprintf('Proveedor: %s', $proveedor);
        $partes[] = sprintf('Línea: #%d', $lineaId);

        // Información adicional útil (opcional): cantidad prevista y fecha estimada
        if (!is_null($linea->cantidad)) {
            $partes[] = sprintf('Cantidad solicitada: %s kg', rtrim(rtrim(number_format((float)$linea->cantidad, 3, ',', '.'), '0'), ','));
        }
        $partes[] = sprintf('Fecha prevista: %s', $fechaEntregaFmt);

        // Descripción final
        $descripcion = implode(' | ', $partes);

        DB::beginTransaction();
        try {
            // Activamos la línea
            DB::table('pedido_productos')->where('id', $lineaId)->update([
                'estado'     => 'activo',
                'updated_at' => now(),
            ]);

            // Creamos el movimiento con la descripción ampliada
            Movimiento::create([
                'tipo'               => 'entrada',
                'estado'             => 'pendiente',
                'descripcion'        => $descripcion,
                'fecha_solicitud'    => now(),
                'solicitado_por'     => auth()->id(),
                'pedido_id'          => $pedidoId,
                'producto_base_id'   => $productoBase->id,
                'pedido_producto_id' => $lineaId,
                'prioridad'          => 2,
                'nave_id'          => $pedido->obra_id,
            ]);
            Log::info('Movimiento creado para activar línea de pedido', [
                'linea_id'          => $lineaId,
                'pedido_id'         => $pedidoId,
                'producto_base_id'  => $productoBase->id,
                'nave_id'           => $pedido->obra_id,
                'usuario'           => auth()->id(),
            ]);


            DB::commit();
            return redirect()->back()->with('success');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al activar línea del pedido: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al activar el producto.');
        }
    }

    public function desactivar($pedidoId, $lineaId)
    {
        DB::transaction(function () use ($pedidoId, $lineaId) {
            // Obtener la línea específica del pedido
            $linea = DB::table('pedido_productos')
                ->where('id', $lineaId)
                ->lockForUpdate()
                ->first();

            if (!$linea) {
                throw new \RuntimeException('Línea de pedido no encontrada.');
            }

            // Eliminar movimiento pendiente relacionado
            Movimiento::where('pedido_id', $pedidoId)
                ->where('producto_base_id', $linea->producto_base_id)
                ->where('estado', 'pendiente')
                ->delete();
            Log::info('Movimiento eliminado para desactivar línea de pedido', [
                'linea_id'         => $lineaId,
                'pedido_id'        => $pedidoId,
                'producto_base_id' => $linea->producto_base_id,
            ]);
            // Marcar la línea como pendiente
            DB::table('pedido_productos')
                ->where('id', $lineaId)
                ->update([
                    'estado' => 'pendiente',
                    'updated_at' => now(),
                ]);
        });

        return redirect()->back()->with('success');
    }

    public function cancelarLinea($pedidoId, $lineaId)
    {
        $pedido = Pedido::findOrFail($pedidoId);
        $linea  = PedidoProducto::findOrFail($lineaId);

        if ($linea->pedido_id !== $pedido->id) {
            abort(403, 'La línea no pertenece a este pedido.');
        }

        if (trim(strtolower($linea->estado)) === 'cancelado') {
            return redirect()->back()->with('info', 'La línea ya estaba cancelada.');
        }

        try {
            DB::transaction(function () use ($pedido, $linea) {

                // 1) Cancelar la línea
                $linea->estado = 'cancelado';
                $linea->save();

                // 2) Restar del peso del pedido cabecera
                $cantidad = (float) ($linea->cantidad ?? 0);
                $pedido->peso_total = max(0, (float)$pedido->peso_total - $cantidad);
                $pedido->save();

                // 3) Si todas las líneas del pedido están canceladas, cancelar el pedido
                $lineasActivas = PedidoProducto::where('pedido_id', $pedido->id)
                    ->where(function ($q) {
                        $q->whereNull('estado')
                            ->orWhere('estado', '!=', 'cancelado');
                    })
                    ->count();

                if ($lineasActivas === 0) {
                    $pedido->estado = 'cancelado';
                    $pedido->save();
                }

                // 4) Reabrir y recalcular el Pedido Global asociado a ESTA LÍNEA
                $pedidoGlobalId = $linea->pedido_global_id;

                if ($pedidoGlobalId) {
                    /** @var \App\Models\PedidoGlobal|null $pg */
                    $pedidoGlobal = PedidoGlobal::where('id', $pedidoGlobalId)->lockForUpdate()->first();

                    if ($pedidoGlobal) {
                        // Recalcular estado según las líneas vivas de ese PG
                        if (method_exists($pedidoGlobal, 'actualizarEstadoSegunProgreso')) {
                            $pedidoGlobal->actualizarEstadoSegunProgreso();
                        }
                    }
                }
            });

            return back()->with('success', 'Línea cancelada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al cancelar línea de pedido', [
                'pedido_id' => $pedido->id,
                'linea_id'  => $linea->id,
                'mensaje'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al cancelar la línea. Consulta con administración.');
        }
    }

    /**
     * Algoritmo único para asignar líneas a PedidoGlobal
     * SOLO encaja si la cantidad es EXACTA al restante de un PG.
     * Si ninguna línea cierra el más antiguo, se avisa.
     */
    /**
     * Asigna líneas a Pedidos Globales:
     * - Cierra el PG más antiguo con cualquier línea o combinación exacta.
     * - No divide líneas.
     */
    private function asignarPedidosGlobales($lineas, $pedidosGlobales)
    {
        $asignaciones = [];
        $lineasCol = collect($lineas)->values()->map(function ($l, $i) {
            return [
                'index'    => $l['index'] ?? $i,
                'cantidad' => (float) $l['cantidad'],
            ];
        });

        foreach ($pedidosGlobales as $pg) {
            $restante = (float) $pg->cantidad_restante;
            if ($restante <= 0) continue;
            if ($lineasCol->isEmpty()) break;

            // --- 1) EXACT MATCH con cualquier línea ---
            $idxExacto = $lineasCol->search(fn($l) => abs($l['cantidad'] - $restante) < 0.001);

            if ($idxExacto !== false) {
                $l = $lineasCol->get($idxExacto);
                $asignaciones[] = [
                    'linea_index'       => $l['index'],
                    'pedido_global_id'  => $pg->id,
                    'codigo'            => $pg->codigo,
                    'cantidad_asignada' => $l['cantidad'],
                    'cantidad_restante' => 0,
                    'mensaje'           => "Cierra {$pg->codigo}",
                ];
                $lineasCol->forget($idxExacto);
                $lineasCol = $lineasCol->values();
                continue; // pasamos al siguiente PG
            }

            // --- 2) INTELIGENTE: combinación de líneas que sume el restante ---
            $target  = (int) round($restante);
            $weights = $lineasCol->mapWithKeys(fn($l) => [$l['index'] => (int) round($l['cantidad'])]);

            $dp = [0 => []];
            foreach ($weights as $idx => $w) {
                // ITERAMOS SOBRE UN SNAPSHOT DE CLAVES
                foreach (array_keys($dp) as $s) {
                    $lista = $dp[$s];
                    $nueva = $s + $w;
                    if ($nueva > $target) continue;
                    if (!isset($dp[$nueva])) {
                        $dp[$nueva] = array_merge($lista, [$idx]);
                    }
                }
            }

            if (isset($dp[$target]) && !empty($dp[$target])) {
                foreach ($dp[$target] as $lineIndex) {
                    $lKey = $lineasCol->search(fn($x) => $x['index'] === $lineIndex);
                    if ($lKey === false) continue;
                    $l = $lineasCol->get($lKey);

                    $asignaciones[] = [
                        'linea_index'       => $l['index'],
                        'pedido_global_id'  => $pg->id,
                        'codigo'            => $pg->codigo,
                        'cantidad_asignada' => $l['cantidad'],
                        'cantidad_restante' => 0,
                        'mensaje'           => "Cierra {$pg->codigo}",
                    ];
                    $lineasCol->forget($lKey);
                }
                $lineasCol = $lineasCol->values();
                continue; // siguiente PG
            }

            // --- 3) Si todas caben, asignación parcial y salimos ---
            $sumaLineas = (float) $lineasCol->sum('cantidad');
            if ($sumaLineas <= $restante + 0.001) {
                $r = $restante;
                foreach ($lineasCol as $l) {
                    $asignaciones[] = [
                        'linea_index'       => $l['index'],
                        'pedido_global_id'  => $pg->id,
                        'codigo'            => $pg->codigo,
                        'cantidad_asignada' => $l['cantidad'],
                        'cantidad_restante' => max(0, $r - $l['cantidad']),
                        'mensaje'           => "Asignado parcial a {$pg->codigo}",
                    ];
                    $r -= $l['cantidad'];
                }
                $lineasCol = collect();
                break;
            }

            // --- 4) No se puede cerrar (ni parcial completa) → avisar y bloquear ---
            $asignaciones[] = [
                'linea_index'       => null,
                'pedido_global_id'  => null,
                'codigo'            => null,
                'cantidad_asignada' => 0,
                'cantidad_restante' => $restante,
                'mensaje'           => "El pedido global más antiguo ({$pg->codigo}) tiene {$restante} kg pendientes. Debes ajustar alguna línea a esa cantidad exacta antes de pasar al siguiente.",
            ];

            foreach ($lineasCol as $l) {
                $asignaciones[] = [
                    'linea_index'       => $l['index'],
                    'pedido_global_id'  => null,
                    'codigo'            => null,
                    'cantidad_asignada' => 0,
                    'cantidad_restante' => 0,
                    'mensaje'           => "Pendiente: primero cierra el PG más antiguo",
                ];
            }
            break; // no miramos siguientes PG hasta resolver éste
        }

        // Lo que quede sin PG
        foreach ($lineasCol as $l) {
            $asignaciones[] = [
                'linea_index'       => $l['index'],
                'pedido_global_id'  => null,
                'codigo'            => null,
                'cantidad_asignada' => 0,
                'cantidad_restante' => 0,
                'mensaje'           => "Sin PG disponible",
            ];
        }

        return ['ok' => true, 'asignaciones' => $asignaciones];
    }

    public function sugerirPedidoGlobal(Request $request)
    {
        $request->validate([
            'fabricante_id'     => 'nullable|exists:fabricantes,id',
            'distribuidor_id'   => 'nullable|exists:distribuidores,id',
            'lineas'            => 'required|array|min:1',
            'lineas.*.cantidad' => 'required|numeric|min:1'
        ]);

        $pedidosGlobales = PedidoGlobal::query()
            ->whereIn('estado', ['pendiente', 'en curso'])
            ->when($request->fabricante_id, fn($q) => $q->where('fabricante_id', $request->fabricante_id))
            ->when($request->distribuidor_id, fn($q) => $q->where('distribuidor_id', $request->distribuidor_id))
            ->orderBy('created_at')
            ->get();

        return response()->json(
            $this->asignarPedidosGlobales($request->lineas, $pedidosGlobales)
        );
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'seleccionados'     => 'required|array',
                'obra_id_hpr'       => 'nullable|exists:obras,id',
                'obra_id_externa'   => 'nullable|exists:obras,id',
                'obra_manual'       => 'nullable|string|max:255',
                'fabricante_id'     => 'nullable|exists:fabricantes,id',
                'distribuidor_id'   => 'nullable|exists:distribuidores,id',
            ]);

            // Exclusividad de lugar de entrega
            $hayObraHpr     = $request->filled('obra_id_hpr');
            $hayObraExterna = $request->filled('obra_id_externa');
            $hayObraManual  = $request->filled('obra_manual');
            $totalObrasMarcadas = (int) $hayObraHpr + (int) $hayObraExterna + (int) $hayObraManual;

            if ($totalObrasMarcadas > 1) {
                return back()->withErrors([
                    'obra_manual' => 'Solo puedes seleccionar una opción: nave, obra externa o introducirla manualmente.',
                ])->withInput();
            }
            if ($totalObrasMarcadas === 0) {
                return back()->withErrors([
                    'obra_manual' => 'Debes seleccionar una obra o escribir el lugar de entrega.',
                ])->withInput();
            }

            // Validar proveedor
            if (!$request->fabricante_id && !$request->distribuidor_id) {
                return back()->withErrors(['fabricante_id' => 'Debes seleccionar un fabricante o un distribuidor.'])->withInput();
            }
            if ($request->fabricante_id && $request->distribuidor_id) {
                return back()->withErrors(['fabricante_id' => 'Solo puedes seleccionar uno: fabricante o distribuidor.'])->withInput();
            }

            // Crear pedido principal
            $obraId = $request->obra_id_hpr ?: $request->obra_id_externa;
            $pedido = Pedido::create([
                'codigo'          => Pedido::generarCodigo(),
                'estado'          => 'pendiente',
                'fabricante_id'   => $request->fabricante_id,
                'distribuidor_id' => $request->distribuidor_id,
                'obra_id'         => $obraId,
                'obra_manual'     => $request->obra_manual,
                'fecha_pedido'    => now(),
                'created_by'      => auth()->id(),
            ]);

            // Guardar líneas según lo confirmado en el modal
            $pesoTotal      = 0;
            $pgIdsAfectados = []; // <-- iremos añadiendo los PG usados

            foreach ($request->seleccionados as $clave) {
                $tipo           = $request->input("detalles.$clave.tipo");
                $diametro       = $request->input("detalles.$clave.diametro");
                $longitud       = $request->input("detalles.$clave.longitud");
                $pedidoGlobalId = $request->input("detalles.$clave.pedido_global_id");

                $productoBase = ProductoBase::where('tipo', $tipo)
                    ->where('diametro', $diametro)
                    ->when($longitud, fn($q) => $q->where('longitud', $longitud))
                    ->first();

                if (!$productoBase) continue;

                $subproductos = data_get($request->input('productos'), $clave, []);
                foreach ($subproductos as $camion) {
                    $peso  = (float) ($camion['peso'] ?? 0);
                    $fecha = $camion['fecha'] ?? null;
                    if ($peso <= 0 || !$fecha) continue;

                    $pedido->productos()->attach($productoBase->id, [
                        'pedido_global_id'       => $pedidoGlobalId ?: null,
                        'cantidad'               => $peso,
                        'fecha_estimada_entrega' => $fecha,
                        'observaciones'          => null,
                    ]);

                    if ($pedidoGlobalId) $pgIdsAfectados[(int)$pedidoGlobalId] = true;
                    $pesoTotal += $peso;
                }
            }

            $pedido->peso_total = $pesoTotal;
            $pedido->save();

            // === Recalcular estado de cada PG afectado ===
            if (!empty($pgIdsAfectados)) {
                $ids = array_keys($pgIdsAfectados);
                $globales = PedidoGlobal::whereIn('id', $ids)->get();
                foreach ($globales as $pg) {
                    // usa tu método del modelo
                    $pg->actualizarEstadoSegunProgreso();
                }
            }

            DB::commit();

            return redirect()
                ->route('pedidos.show', $pedido->id)
                ->with('success', 'Pedido creado correctamente. Revisa el correo antes de enviarlo.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear pedido: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $msg = app()->environment('local') ? $e->getMessage() : 'Hubo un error inesperado al crear el pedido.';
            return back()->withInput()->with('error', $msg);
        }
    }

    public function show($id)
    {
        $pedido = Pedido::with(['productos', 'fabricante', 'obra'])->findOrFail($id);

        return view('emails.pedidos.pedido_creado', [
            'pedido' => $pedido,
            'esVistaPrevia' => true, // <- IMPORTANTE para que aparezcan los botones
        ]);
    }


    public function enviarCorreo($id, Request $request)
    {
        $pedido = Pedido::with(['productos', 'fabricante', 'distribuidor'])->findOrFail($id);

        // 📤 Obtener contacto y nombre visible del remitente
        $contacto = $this->obtenerContactoPedido($pedido);

        if (!$contacto['email']) {
            return back()->with('error', 'El contacto asignado no tiene correo electrónico.');
        }

        // 📌 Correos en copia
        $ccEmails = [
            'sebastian.duran@pacoreyes.com',
            'manuel.reyes@pacoreyes.com',
            'anagracia.aroca@pacoreyes.com',
            'indiana.tirado@pacoreyes.com',
            'josemanuel.amuedo@pacoreyes.com',
        ];

        $fromAddress = config('mail.from.address');                 // p.ej. info@pacoreyes.eu
        $fromName    = config('mail.from.name', 'Hierros Paco Reyes');

        // ✉️ Preparar el Mailable
        $mailable = new PedidoCreado(
            $pedido,
            $fromAddress,        // From visible
            $fromName,           // Nombre del remitente visible
            $ccEmails,           // CC
            $fromAddress,        // Reply-To email -> al correo de la app
            $fromName            // Reply-To name
        );

        // 🚀 Enviar
        Mail::to($contacto['email'])->send($mailable);

        return redirect()->route('pedidos.index')->with('success', 'Correo enviado correctamente.');
    }

    private function obtenerContactoPedido($pedido): array
    {
        if ($pedido->fabricante && $pedido->fabricante->email) {
            return [
                'email'  => $pedido->fabricante->email,
                'nombre' => 'Hierros Paco Reyes',
            ];
        }

        if ($pedido->distribuidor && $pedido->distribuidor->email) {
            return [
                'email'  => $pedido->distribuidor->email,
                'nombre' => 'Hierros Paco Reyes',
            ];
        }

        return [
            'email'  => null,
            'nombre' => null,
        ];
    }

    public function edit(string $id)
    {
        //
    }


    public function completarLineaManual(Request $request, Pedido $pedido, PedidoProducto $linea)
    {
        try {
            // Verifica pertenencia: evita que “linea=120” (pedido) cuele
            if ((int)$linea->pedido_id !== (int)$pedido->id) {
                abort(404, 'La línea no pertenece a este pedido.');
            }

            DB::transaction(function () use ($pedido, $linea) {
                // Completar SOLO esta línea (si no está facturada)
                if (strtolower((string)$linea->estado) !== 'facturado') {
                    $linea->estado = 'completado';
                    if ($linea->isFillable('fecha_completado')) $linea->fecha_completado = now();
                    if ($linea->isFillable('updated_by'))       $linea->updated_by       = auth()->id();
                    $linea->save();
                }

                // Pedido = completado si TODAS las líneas están en {completado, facturado}
                $estadosQueCierran = ['completado', 'facturado'];
                $ignorar           = ['cancelado']; // quítalo si no usas ‘cancelado’ en líneas

                $todas = PedidoProducto::where('pedido_id', $pedido->id)->get();
                $relevantes   = $todas->reject(fn($l) => in_array(strtolower((string)$l->estado), $ignorar, true));
                $todasCerradas = $relevantes->count() > 0
                    && $relevantes->every(fn($l) => in_array(strtolower((string)$l->estado), $estadosQueCierran, true));

                $pedido->estado = $todasCerradas ? 'completado' : 'pendiente';
                if ($todasCerradas && $pedido->isFillable('fecha_completado')) {
                    $pedido->fecha_completado = $pedido->fecha_completado ?? now();
                }
                if ($pedido->isFillable('updated_by')) $pedido->updated_by = auth()->id();
                $pedido->save();
            });

            return back()->with('success', 'Línea completada y pedido recalculado.');
        } catch (\Throwable $e) {
            Log::error('Error al completar línea manualmente', [
                'pedido_id' => $pedido->id,
                'linea_id'  => $linea->id ?? null,
                'msg'       => $e->getMessage(),
            ]);
            return back()->with('error', 'No se pudo completar la línea.');
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $pedido = Pedido::findOrFail($id);

                // guardamos el PG antes de borrar
                $pedidoGlobalId = $pedido->pedido_global_id;

                Log::info('Borrando pedido ' . $pedido->codigo . ' con pedido global id ' . $pedidoGlobalId . ' por ' . (auth()->user()->nombre_completo ?? null));

                // Elimina el pedido: si tienes cascada en FK se borran las líneas
                $pedido->delete();

                if ($pedidoGlobalId) {
                    $pg = PedidoGlobal::lockForUpdate()->find($pedidoGlobalId);
                    if ($pg) {
                        $pg->actualizarEstadoSegunProgreso(); // ahora recalcula con líneas
                    }
                }
            });

            return redirect()->route('pedidos.index')->with('success', 'Pedido eliminado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar pedido', ['pedido_id' => $id, 'mensaje' => $e->getMessage()]);
            return back()->with('error', 'No se pudo eliminar el pedido. Consulta con administración.');
        }
    }
}
