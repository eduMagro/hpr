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
            $filtros[] = 'Código pedido: <strong>' . $request->codigo . '</strong>';
        }
        if ($request->filled('pedido_producto_id')) {
            $filtros[] = 'ID línea: <strong>' . $request->pedido_producto_id . '</strong>';
        }

        if ($request->filled('pedido_global_id')) {
            $pg = PedidoGlobal::find($request->pedido_global_id);
            if ($pg) {
                $filtros[] = 'Código pedido global: <strong>' . $pg->codigo . '</strong>';
            }
        }

        if ($request->filled('fabricante_id')) {
            $fabricante = Fabricante::find($request->fabricante_id);
            if ($fabricante) {
                $filtros[] = 'Fabricante: <strong>' . $fabricante->nombre . '</strong>';
            }
        }
        if ($request->filled('distribuidor_id')) {
            $distribuidor = Fabricante::find($request->distribuidor_id);
            if ($distribuidor) {
                $filtros[] = 'Distribuidor: <strong>' . $distribuidor->nombre . '</strong>';
            }
        }

        if ($request->filled('fecha_pedido')) {
            $filtros[] = 'Fecha pedido: <strong>' . $request->fecha_pedido . '</strong>';
        }

        if ($request->filled('fecha_entrega')) {
            $filtros[] = 'Entrega estimada: <strong>' . $request->fecha_entrega . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
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
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }


        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
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
        if ($request->filled('pedido_producto_id')) {
            $lineaId = (int) $request->pedido_producto_id;

            $query->whereHas('pedidoProductos', function ($q) use ($lineaId) {
                $q->where('pedido_productos.id', $lineaId);
            });
        }

        // Filtra por id
        if ($request->filled('pedido_id')) {
            $query->where('id', $request->pedido_id);
        }

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }
        // Filtro por pedido_global_id
        if ($request->filled('pedido_global_id')) {
            $query->where('pedido_global_id', $request->pedido_global_id);
        }
        if ($request->filled('producto_tipo') || $request->filled('producto_diametro') || $request->filled('producto_longitud')) {
            $query->whereHas('pedidoProductos.productoBase', function ($q) use ($request) {
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

        if ($request->filled('fabricante_id')) {
            $query->where('fabricante_id', $request->fabricante_id);
        }
        if ($request->filled('distribuidor_id')) {
            $query->where('distribuidor_id', $request->distribuidor_id);
        }

        if ($request->filled('fecha_pedido')) {
            $query->whereDate('fecha_pedido', $request->fecha_pedido);
        }

        if ($request->filled('fecha_entrega')) {
            $query->whereHas('pedidoProductos', function ($q) use ($request) {
                $q->whereDate('fecha_estimada_entrega', $request->fecha_entrega);
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // ✅ Ordenación segura por columnas locales o por nombre de relación
        $sortBy = $request->input('sort', 'created_at'); // o 'fecha_pedido'
        $order  = $request->input('order', 'desc');

        // Limpia órdenes previas (importantísimo)
        $query->reorder();

        switch ($sortBy) {
            case 'fabricante':
                $query->orderBy(
                    \App\Models\Fabricante::select('nombre')
                        ->whereColumn('fabricantes.id', 'pedidos.fabricante_id'),
                    $order
                );
                break;

            case 'distribuidor':
                $query->orderBy(
                    \App\Models\Distribuidor::select('nombre')
                        ->whereColumn('distribuidores.id', 'pedidos.distribuidor_id'),
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
        $query = Pedido::with(['fabricante', 'distribuidor', 'productos', 'pedidoGlobal', 'pedidoProductos.productoBase']);


        if (auth()->user()->rol === 'operario') {
            $query->whereIn('estado', ['pendiente', 'parcial']);
        }

        $this->aplicarFiltrosPedidos($query, $request);

        $perPage = $request->input('per_page', 10);
        $pedidos = $query->paginate($perPage)->appends($request->all());
        $pedidosGlobales = PedidoGlobal::orderBy('codigo')->get();

        $pedidos->getCollection()->transform(function ($pedido) {
            $pedido->lineas = $pedido->pedidoProductos->map(function ($linea) {
                return [
                    'id'                     => $linea->id,
                    'tipo'                   => $linea->productoBase?->tipo ?? '—',
                    'diametro'               => $linea->productoBase?->diametro ?? '—',
                    'longitud'               => $linea->productoBase?->longitud ?? '—',
                    'cantidad'               => $linea->cantidad,
                    'cantidad_recepcionada'  => $linea->cantidad_recepcionada,
                    'estado'                 => $linea->estado ?? 'pendiente',
                    'fecha_estimada_entrega' => $linea->fecha_estimada_entrega ?? '—',
                    'created_at'             => $linea->created_at,
                    'codigo_sage'            => $linea->codigo_sage ?? '',
                ];
            });
            return $pedido;
        });

        $filtrosActivos = $this->filtrosActivosPedidos($request);
        $fabricantes    = Fabricante::select('id', 'nombre')->get();
        $distribuidores = Distribuidor::select('id', 'nombre')->get();

        $ordenables = [
            'codigo'         => $this->getOrdenamientoPedidos('codigo', 'Código'),
            'fabricante'     => $this->getOrdenamientoPedidos('fabricante', 'Fabricante'),
            'distribuidor'   => $this->getOrdenamientoPedidos('distribuidor', 'Distribuidor'),
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

        // 🔹 Obras de HPR
        $obrasHpr = $idClienteHpr
            ? Obra::where('cliente_id', $idClienteHpr)->orderBy('obra')->get()
            : collect();

        // 🔹 Naves (igual que obrasHpr pero filtrando por nombre de cliente)
        $navesHpr = Obra::whereHas('cliente', function ($q) {
            $q->where('empresa', 'like', '%HIERROS PACO REYES%');
        })
            ->orderBy('obra')
            ->get();

        // 🔹 Obras activas que NO sean del cliente HPR
        $obrasExternas = Obra::where('estado', 'activa')
            ->where('cliente_id', '!=', $idClienteHpr)
            ->orderBy('obra')
            ->get();

        // ===== Filtro para el cálculo del StockService =====
        $obraIdSeleccionada = $request->input('obra_id_hpr');        // id concreto (string o null)
        $soloHpr            = $request->boolean('solo_hpr');         // toggle opcional

        $obraIds     = $obraIdSeleccionada ? [(int)$obraIdSeleccionada] : null;
        $clienteLike = (!$obraIds && $soloHpr) ? '%Hierros Paco Reyes%' : null;


        // ✅ Llamada correcta al service (primero obraIds[], luego clienteLike)
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
            'obrasExternas'       => $obrasExternas,
            'idClienteHpr'   => $idClienteHpr,
            'solo_hpr'       => $soloHpr,
            'obra_id_hpr'    => $obraIdSeleccionada,
        ], $datosStock));
    }

    public function recepcion($id, $producto_base_id)
    {
        // 🔹 Cargar pedido con relaciones
        $pedido = Pedido::with(['productos', 'entradas.productos'])->findOrFail($id);

        // 🔹 Comprobar si se debe mostrar el campo de fabricante manual
        $requiereFabricanteManual = $pedido->distribuidor_id !== null && $pedido->fabricante_id === null;
        $ultimoFabricante = null;

        $ultimoProducto = Producto::with(['entrada', 'productoBase'])
            ->whereHas('entrada', fn($q) => $q->where('usuario_id', auth()->id()))
            ->latest()
            ->first();

        $ultimoFabricante = $ultimoProducto?->fabricante_id
            ?? $ultimoProducto?->productoBase?->fabricante_id;


        $fabricantes = $requiereFabricanteManual ? Fabricante::orderBy('nombre')->get() : collect();
        // 🔹 Filtrar productos del pedido
        $productosIds = $pedido->productos->pluck('id')->filter()->all();

        // 🔹 Cálculo de recepcionado por producto_base
        $recepcionadoPorProducto = [];

        foreach ($pedido->entradas as $entrada) {
            foreach ($entrada->productos as $producto) {
                $idBase = $producto->producto_base_id;
                if (!$idBase) continue;

                $recepcionadoPorProducto[$idBase] = ($recepcionadoPorProducto[$idBase] ?? 0) + $producto->peso_inicial;
            }
        }

        // 🔹 Calcular cantidad pendiente por producto
        $pedido->productos->each(function ($producto) use ($recepcionadoPorProducto) {
            $idBase = $producto->id;
            $yaRecepcionado = $recepcionadoPorProducto[$idBase] ?? 0;
            $producto->pendiente = max(0, $producto->pivot->cantidad - $yaRecepcionado);
        });

        // 🔹 Buscar solo el producto_base que nos interesa
        $productoBase = $pedido->productos->firstWhere('id', $producto_base_id);

        // 🔹 Ubicaciones
        $ubicaciones = Ubicacion::all()->map(function ($ubicacion) {
            $ubicacion->nombre_sin_prefijo = Str::after($ubicacion->nombre, 'Almacén ');
            return $ubicacion;
        });

        // 🔹 Últimas coladas usadas por este usuario
        $ultimos = Producto::select('producto_base_id', 'n_colada', 'productos.ubicacion_id')
            ->join('entradas', 'entradas.id', '=', 'productos.entrada_id')
            ->where('entradas.usuario_id', auth()->id())
            ->where('producto_base_id', $producto_base_id)
            ->latest('productos.created_at')
            ->get()
            ->unique('producto_base_id')
            ->keyBy('producto_base_id');
        $linea = PedidoProducto::where('pedido_id', $pedido->id)
            ->where('producto_base_id', $productoBase->id)
            ->where('estado', '!=', 'completado')
            ->orderBy('fecha_estimada_entrega')
            ->first();

        // ✅ Devolver vista con producto base específico
        return view('pedidos.recepcion', compact('pedido', 'productoBase', 'ubicaciones', 'ultimos', 'requiereFabricanteManual', 'fabricantes', 'ultimoFabricante', 'linea'));
    }

    public function procesarRecepcion(Request $request, $id)
    {
        try {
            Log::debug('📥 Datos recibidos en procesarRecepcion()', [
                'pedido_id_param'         => $id,
                'pedido_producto_id'      => $request->pedido_producto_id,
                'producto_base_id'        => $request->producto_base_id,
                'codigo'                  => $request->codigo,
                'peso'                    => $request->peso,
                'ubicacion_id'            => $request->ubicacion_id,
            ]);
            $pedido = Pedido::with('productos')->findOrFail($id);

            if (in_array($pedido->estado, ['completado', 'cancelado'])) {
                return redirect()->back()->with('error', "El pedido ya está {$pedido->estado} y no puede recepcionarse.");
            }

            $request->validate(
                [
                    'codigo'            => ['required', 'string', 'max:20', 'unique:productos,codigo', 'regex:/^mp/i'],
                    'codigo_2'          => ['nullable', 'string', 'max:20', 'different:codigo', 'unique:productos,codigo', 'regex:/^mp/i'],
                    'producto_base_id'   => 'required|exists:productos_base,id',
                    'peso'               => 'required|numeric|min:1',
                    'n_colada'           => 'required|string|max:50',
                    'n_paquete'          => 'required|string|max:50',
                    'n_colada_2'         => 'nullable|string|max:50',
                    'n_paquete_2'        => 'nullable|string|max:50',
                    'ubicacion_id'       => 'required|exists:ubicaciones,id',
                    'fabricante_id' => 'nullable|exists:fabricantes,id',
                ],
                [
                    'codigo.required'   => 'El código es obligatorio.',
                    'codigo.string'     => 'El código debe ser un texto.',
                    'codigo.unique'     => 'Ese código ya existe.',
                    'codigo.max'        => 'El código no puede tener más de 20 caracteres.',
                    'codigo.regex'   => 'El código debe empezar por MP.',
                    'codigo_2.regex' => 'El código del segundo paquete debe empezar por MP.',
                    'codigo_2.string'   => 'El segundo código debe ser un texto.',
                    'codigo_2.unique'   => 'Ese segundo código ya existe.',
                    'codigo_2.max'      => 'El segundo código no puede tener más de 20 caracteres.',

                    'producto_base_id.required' => 'El producto base es obligatorio.',
                    'producto_base_id.exists'   => 'El producto base seleccionado no es válido.',

                    'peso.required'     => 'El peso es obligatorio.',
                    'peso.numeric'      => 'El peso debe ser un número.',
                    'peso.min'          => 'El peso debe ser mayor que cero.',

                    'n_colada.required' => 'El número de colada es obligatorio.',
                    'n_colada.string'   => 'El número de colada debe ser texto.',
                    'n_colada.max'      => 'El número de colada no puede tener más de 50 caracteres.',

                    'n_paquete.required' => 'El número de paquete es obligatorio.',
                    'n_paquete.string'   => 'El número de paquete debe ser texto.',
                    'n_paquete.max'      => 'El número de paquete no puede tener más de 50 caracteres.',

                    'n_colada_2.string' => 'El número de colada del segundo paquete debe ser texto.',
                    'n_colada_2.max'    => 'El número de colada del segundo paquete no puede tener más de 50 caracteres.',

                    'n_paquete_2.string' => 'El número de paquete del segundo paquete debe ser texto.',
                    'n_paquete_2.max'    => 'El número de paquete del segundo paquete no puede tener más de 50 caracteres.',

                    'ubicacion_id.required' => 'La ubicación es obligatoria.',
                    'ubicacion_id.exists'   => 'La ubicación seleccionada no es válida.',

                    'fabricante_id.exists' => 'El fabricante seleccionado no es válido.',
                    'fabricante_id.required' => 'El fabricante es obligatorio.',

                ]
            );
            // Normaliza a MAYÚSCULAS antes de crear
            $obraIdActual = auth()->user()->lugarActualTrabajador();
            $codigo   = strtoupper(trim($request->input('codigo')));
            $codigo2  = $request->filled('codigo_2') ? strtoupper(trim($request->input('codigo_2'))) : null;
            $esDoble  = $request->filled('codigo_2') && $request->filled('n_colada_2') && $request->filled('n_paquete_2');
            $peso     = (float) $request->input('peso');
            $pesoPorPaquete = $esDoble ? round($peso / 2, 3) : $peso;

            $ubicacion = Ubicacion::findOrFail($request->ubicacion_id);

            Log::debug('🔍 Buscando línea de pedido...', [
                'pedido_producto_id' => $request->pedido_producto_id,
            ]);

            /** @var PedidoProducto $pedidoProducto */
            $pedidoProducto = PedidoProducto::lockForUpdate()->findOrFail($request->pedido_producto_id);

            if ($pedidoProducto->pedido_id !== $pedido->id) {
                return redirect()->back()->with('error', 'La línea de pedido no pertenece al pedido actual.');
            }

            Log::debug('✅ Línea de pedido encontrada', [
                'pedido_producto_id' => $pedidoProducto->id,
                'pedido_id_en_linea' => $pedidoProducto->pedido_id,
                'esperado_pedido_id' => $pedido->id,
            ]);

            // ✅ Buscar/crear ENTRADA ABIERTA POR LÍNEA
            $entrada = Entrada::where('pedido_id', $pedido->id)
                ->where('pedido_producto_id', $pedidoProducto->id)
                ->where('estado', 'abierto')
                ->lockForUpdate()
                ->first();

            $entradaRecienCreada = false;

            if (!$entrada) {
                $entrada = new Entrada();
                $entrada->pedido_id          = $pedido->id;
                $entrada->pedido_producto_id = $pedidoProducto->id; // 🔒 clave: por línea
                $entrada->albaran            = $this->generarCodigoAlbaran();
                $entrada->usuario_id         = auth()->id();
                $entrada->peso_total         = 0;
                $entrada->estado             = 'abierto';
                $entrada->otros              = 'Entrada generada desde recepción de pedido';
                $entrada->save();

                $entradaRecienCreada = true;
            }

            // 🔔 alertas si se creó ahora (como ya tenías)
            if ($entradaRecienCreada) {
                $alertaService = app(AlertaService::class);
                $emisorId = auth()->id();

                $fabricante = $entrada->pedido->fabricante->nombre ?? 'Desconocido';
                $pedidoCodigo = $entrada->pedido->codigo ?? $entrada->pedido->id ?? '—';

                $usuariosAdmin = User::whereHas('departamentos', function ($q) {
                    $q->where('nombre', 'Administración');
                })->get();

                foreach ($usuariosAdmin as $usuario) {
                    $alertaService->crearAlerta(
                        emisorId: $emisorId,
                        destinatarioId: $usuario->id,
                        mensaje: "Camión de ($fabricante) recibido. Pedido $pedidoCodigo. Línea de pedido ({$pedidoProducto->id})",
                        tipo: 'Entrada material',
                    );
                }
            }

            // Fabricante final como ya tenías
            $fabricanteFinal = $pedido->fabricante_id ?? $request->fabricante_id;

            // ➕ Crear producto(s) siempre colgando de ESTA entrada (de la línea correcta)
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
                    'entrada_id'        => $entrada->id, // 👈 misma entrada (misma línea)
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

            // Actualizar peso total de ESA entrada/linea
            $entrada->peso_total += $peso;
            $entrada->save();

            $pedido->save();

            return redirect()->back()->with('success', 'Producto(s) recepcionado(s) correctamente.');
        } catch (\Exception $e) {
            Log::error('❌ Error en procesarRecepcion()', [
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'file'  => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error: ' . $e->getMessage());
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
        $linea = DB::table('pedido_productos')->where('id', $lineaId)->first();

        if (!$linea) {
            return redirect()->back()->with('error', 'Línea de pedido no encontrada.');
        }

        // Cargamos el pedido con fabricante y distribuidor
        /** @var \App\Models\Pedido $pedido */
        $pedido = Pedido::with(['fabricante', 'distribuidor'])->findOrFail($linea->pedido_id);

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
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Línea activada y movimiento creado.');
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

                // 2) Restar del peso del pedido
                $cantidad = (float) ($linea->cantidad ?? 0);
                $pedido->peso_total = max(0, (float)$pedido->peso_total - $cantidad);
                $pedido->save();

                // 3) Si todas las líneas del pedido están canceladas, cancelar el pedido
                $lineasActivas = PedidoProducto::where('pedido_id', $pedido->id)
                    ->whereRaw('LOWER(estado) != ?', ['cancelado'])
                    ->count();

                if ($lineasActivas === 0) {
                    $pedido->estado = 'cancelado';
                    $pedido->save();
                }

                // 4) Reabrir y recalcular el Pedido Global si aplica
                if ($pedido->pedido_global_id) {

                    /** @var \App\Models\PedidoGlobal|null $pg */
                    $pedidoGlobal = PedidoGlobal::where('id', $pedido->pedido_global_id)->lockForUpdate()->first();

                    if ($pedidoGlobal) {
                        // Si estaba completado, al menos pásalo a pendiente
                        $estadoAnterior = trim(strtolower($pedidoGlobal->estado));
                        if ($estadoAnterior === 'completado') {
                            $pedidoGlobal->estado = 'pendiente';
                            $pedidoGlobal->save();
                        }

                        // Recalcular según su propia lógica agregada (pedidos/lineas asociadas)
                        // Esta rutina debería decidir si queda en 'pendiente', 'en curso' o volver a 'completado'
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


    public function store(Request $request)
    {
        Log::debug('Contenido completo del request al crear pedido:', $request->all());

        try {
            // Validación básica
            $request->validate([
                'seleccionados'     => 'required|array',
                'obra_id_hpr'       => 'nullable|exists:obras,id',
                'obra_id_externa'   => 'nullable|exists:obras,id',
                'obra_manual'       => 'nullable|string|max:255',
                'fabricante_id'     => 'nullable|exists:fabricantes,id',
                'distribuidor_id'   => 'nullable|exists:distribuidores,id',
            ], [
                'seleccionados.required'     => 'Selecciona al menos un producto para generar el pedido.',
                'obra_id_hpr.exists'         => 'La nave seleccionada no existe.',
                'obra_id_externa.exists'     => 'La obra externa seleccionada no existe.',
                'fabricante_id.exists'       => 'El fabricante seleccionado no es válido.',
                'distribuidor_id.exists'     => 'El distribuidor seleccionado no es válido.',
            ]);

            // Validar exclusividad de lugar de entrega
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

            // Buscar pedido global relacionado
            $pedidoGlobal = null;

            if ($request->fabricante_id) {
                $pedidoGlobal = PedidoGlobal::where('fabricante_id', $request->fabricante_id)
                    ->whereIn('estado', ['pendiente', 'en curso'])
                    ->orderByRaw("FIELD(estado, 'en curso', 'pendiente')")
                    ->first();
            } elseif ($request->distribuidor_id) {
                $pedidoGlobal = PedidoGlobal::where('distribuidor_id', $request->distribuidor_id)
                    ->whereIn('estado', ['pendiente', 'en curso'])
                    ->orderByRaw("FIELD(estado, 'en curso', 'pendiente')")
                    ->first();
            }

            if ($pedidoGlobal && $pedidoGlobal->estado === 'pendiente') {
                $pedidoGlobal->estado = 'en curso';
                $pedidoGlobal->save();
            }

            // 📝 Crear pedido principal
            $obraId = $request->obra_id_hpr ?: $request->obra_id_externa;

            $pedido = Pedido::create([
                'codigo'           => Pedido::generarCodigo(),
                'pedido_global_id' => $pedidoGlobal->id ?? null,
                'estado'           => 'pendiente',
                'fabricante_id'    => $request->fabricante_id,
                'distribuidor_id'  => $request->distribuidor_id,
                'obra_id'          => $obraId,
                'obra_manual'      => $request->obra_manual,
                'fecha_pedido'     => now(),
                'created_by'     => auth()->id(),
            ]);

            // 🔄 Procesar productos
            $pesoTotal = 0;

            foreach ($request->seleccionados as $clave) {
                $tipo     = $request->input("detalles.$clave.tipo");
                $diametro = $request->input("detalles.$clave.diametro");
                $longitud = $request->input("detalles.$clave.longitud");

                $productoBase = ProductoBase::where('tipo', $tipo)
                    ->where('diametro', $diametro)
                    ->when($longitud, fn($q) => $q->where('longitud', $longitud))
                    ->first();

                if (!$productoBase) continue;

                $subproductos = data_get($request->input('productos'), $clave, []);

                foreach ($subproductos as $index => $camion) {
                    $peso  = floatval($camion['peso'] ?? 0);
                    $fecha = $camion['fecha'] ?? null;

                    if ($peso <= 0 || !$fecha) continue;

                    $pedido->productos()->attach($productoBase->id, [
                        'cantidad' => $peso,
                        'fecha_estimada_entrega' => $fecha,
                        'observaciones' => "Camión #$index desde comparativa automática",
                    ]);

                    $pesoTotal += $peso;
                }
            }

            $pedido->peso_total = $pesoTotal;
            $pedido->save();

            if ($pedidoGlobal) {
                $pedidoGlobal->actualizarEstadoSegunProgreso();
            }

            return redirect()->route('pedidos.show', $pedido->id)
                ->with('success', 'Pedido creado correctamente. Revisa el correo antes de enviarlo.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al crear pedido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

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

    private function crearPedidoDesdeRequest(Request $request): Pedido
    {
        $pedidoGlobal = PedidoGlobal::where('fabricante_id', $request->fabricante_id)
            ->whereIn('estado', [PedidoGlobal::ESTADO_EN_CURSO, PedidoGlobal::ESTADO_PENDIENTE])
            ->orderByRaw("FIELD(estado, ?, ?)", [PedidoGlobal::ESTADO_EN_CURSO, PedidoGlobal::ESTADO_PENDIENTE])
            ->first();

        if ($pedidoGlobal && $pedidoGlobal->estado === PedidoGlobal::ESTADO_PENDIENTE) {
            $pedidoGlobal->estado = PedidoGlobal::ESTADO_EN_CURSO;
            $pedidoGlobal->save();
        }

        $pedido = new Pedido([
            'codigo' => Pedido::generarCodigo(),
            'pedido_global_id' => $pedidoGlobal?->id,
            'estado' => 'pendiente',
            'fabricante_id' => $request->fabricante_id,
            'fecha_pedido' => now(),
            'fecha_entrega' => $request->fecha_entrega,
        ]);

        $pesoTotal = 0;

        foreach ($request->seleccionados as $clave) {
            $tipo = $request->input("detalles.$clave.tipo");
            $diametro = $request->input("detalles.$clave.diametro");
            $peso = floatval($request->input("detalles.$clave.cantidad"));

            $productoBase = ProductoBase::where('tipo', $tipo)
                ->where('diametro', $diametro)
                ->first();

            if ($peso > 0 && $productoBase) {
                $pedido->productos->add([
                    'id' => $productoBase->id,
                    'cantidad' => $peso,
                    'observaciones' => "Pedido generado desde comparativa automática",
                ]);

                $pesoTotal += $peso;
            }
        }

        $pedido->peso_total = $pesoTotal;

        return $pedido;
    }

    public function edit(string $id)
    {
        //
    }




    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                /** @var \App\Models\Pedido $pedido */
                $pedido = Pedido::findOrFail($id);

                // Guardamos el PG antes de borrar
                $pedidoGlobalId = $pedido->pedido_global_id;

                // Si usas cascadas a líneas, el FK debe encargarse; si no, elimina manualmente:
                // $pedido->productosPivot()->delete(); // si tu relación pivot se llama así

                // Borrar pedido (soft o hard, según tu modelo)
                $pedido->delete();

                // Recalcular estado del Pedido Global, si aplica
                if ($pedidoGlobalId) {
                    /** @var \App\Models\PedidoGlobal|null $pg */
                    $pg = PedidoGlobal::where('id', $pedidoGlobalId)->lockForUpdate()->first();

                    if ($pg) {
                        // Recalcula con los pedidos restantes (excluye soft-deleted por defecto)
                        $pg->actualizarEstadoSegunProgreso();
                    }
                }
            });

            return redirect()
                ->route('pedidos.index')
                ->with('success', 'Pedido eliminado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar pedido', ['pedido_id' => $id, 'mensaje' => $e->getMessage()]);
            return back()->with('error', 'No se pudo eliminar el pedido. Consulta con administración.');
        }
    }
}
