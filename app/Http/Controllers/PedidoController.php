<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fabricante;
use App\Models\Distribuidor;
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

class PedidoController extends Controller
{
    private function filtrosActivosPedidos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('codigo')) {
            $filtros[] = 'Código pedido: <strong>' . $request->codigo . '</strong>';
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
                'fecha_pedido' => 'Fecha pedido',
                'fecha_entrega' => 'Entrega estimada',
                'estado' => 'Estado',
                'fabricante_id' => 'Fabricante',
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
            $query->whereDate('fecha_entrega', $request->fecha_entrega);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }


        // Ordenación
        $sortBy = $request->input('sort', 'fecha_entrega');
        $order = $request->input('order', 'desc');
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        return $query;
    }

    public function index(Request $request, StockService $stockService)
    {
        $query = Pedido::with(['fabricante', 'distribuidor', 'productos', 'pedidoGlobal', 'pedidoProductos.productoBase'])->latest();

        // Si el usuario autenticado es operario, solo puede ver pedidos pendientes o parciales
        if (auth()->user()->rol === 'operario') {
            $query->whereIn('estado', ['pendiente', 'parcial']);
        }

        // Aplicar filtros personalizados
        $this->aplicarFiltrosPedidos($query, $request);

        // Paginación configurable
        $perPage = $request->input('per_page', 10);
        $pedidos = $query->paginate($perPage)->appends($request->all());
        $pedidosGlobales = PedidoGlobal::orderBy('codigo')->get();

        // Añadir productos formateados a cada pedido paginado
        $pedidos->getCollection()->transform(function ($pedido) {
            $pedido->lineas = $pedido->pedidoProductos->map(function ($linea) {
                return [
                    'id' => $linea->id,
                    'tipo' => $linea->productoBase?->tipo ?? '—',
                    'diametro' => $linea->productoBase?->diametro ?? '—',
                    'longitud' => $linea->productoBase?->tipo === 'encarretado'
                        ? ($linea->productoBase?->longitud ?? '—')
                        : '—',
                    'tipo' => $linea->productoBase?->tipo ?? '—',
                    'diametro' => $linea->productoBase?->diametro ?? '—',
                    'longitud' => $linea->productoBase?->longitud ?? '—',
                    'cantidad' => $linea->cantidad,
                    'cantidad_recepcionada' => $linea->cantidad_recepcionada,
                    'estado' => $linea->estado ?? 'pendiente',
                    'fecha_estimada_entrega' => $linea->fecha_estimada_entrega ?? '—',
                    'created_at' => $linea->created_at,
                    'codigo_sage' => $linea->codigo_sage ?? '',
                ];
            });


            return $pedido;
        });

        $filtrosActivos = $this->filtrosActivosPedidos($request);
        $fabricantes = Fabricante::select('id', 'nombre')->get();
        $distribuidores = Distribuidor::select('id', 'nombre')->get();

        $ordenables = [
            'codigo' => $this->getOrdenamientoPedidos('codigo', 'Código'),
            'fabricante' => $this->getOrdenamientoPedidos('fabricante', 'Fabricante'),
            'distribuidor' => $this->getOrdenamientoPedidos('distribuidor', 'Distribuidor'),
            'peso_total' => $this->getOrdenamientoPedidos('peso_total', 'Peso total'),
            'fecha_pedido' => $this->getOrdenamientoPedidos('fecha_pedido', 'F. Pedido'),
            'fecha_entrega' => $this->getOrdenamientoPedidos('fecha_entrega', 'F. Entrega'),
            'estado' => $this->getOrdenamientoPedidos('estado', 'Estado'),
        ];

        // Obtener datos de stock, pedidos y necesidad
        $datosStock = $stockService->obtenerDatosStock();

        // Obtener obras activas
        $obrasActivas = Obra::where('estado', 'activa')->orderBy('obra')->get();

        return view('pedidos.index', array_merge([
            'pedidos' => $pedidos,
            'obrasActivas' => $obrasActivas,
            'fabricantes' => $fabricantes,
            'filtrosActivos' => $filtrosActivos,
            'ordenables' => $ordenables,
            'distribuidores' => $distribuidores,
            'pedidosGlobales' => $pedidosGlobales,
        ], $datosStock));
    }



    public function recepcion($id, $producto_base_id)
    {
        // 🔹 Cargar pedido con relaciones
        $pedido = Pedido::with(['productos', 'entradas.productos'])->findOrFail($id);

        // 🔹 Comprobar si se debe mostrar el campo de fabricante manual
        $requiereFabricanteManual = $pedido->distribuidor_id !== null && $pedido->fabricante_id === null;
        $ultimoFabricante = null;

        if ($requiereFabricanteManual) {
            $ultimoFabricante = Producto::select('fabricante_id')
                ->join('entradas', 'productos.entrada_id', '=', 'entradas.id')
                ->where('entradas.usuario_id', auth()->id())
                ->where('producto_base_id', $producto_base_id)
                ->whereNotNull('fabricante_id')
                ->latest('productos.created_at')
                ->value('fabricante_id');
        }
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

        // ✅ Devolver vista con producto base específico
        return view('pedidos.recepcion', compact('pedido', 'productoBase', 'ubicaciones', 'ultimos', 'requiereFabricanteManual', 'fabricantes', 'ultimoFabricante'));
    }

    public function procesarRecepcion(Request $request, $id)
    {
        try {
            $pedido = Pedido::with('productos')->findOrFail($id);

            if (in_array($pedido->estado, ['completado', 'cancelado'])) {
                return redirect()->back()->with('error', "El pedido ya está {$pedido->estado} y no puede recepcionarse.");
            }

            $request->validate(
                [
                    'codigo'             => 'required|string|unique:productos,codigo|max:20',
                    'codigo_2'           => 'nullable|string|unique:productos,codigo|max:20',
                    'producto_base_id'   => 'required|exists:productos_base,id',
                    'peso'               => 'required|numeric|min:1',
                    'n_colada'           => 'required|string|max:50',
                    'n_paquete'          => 'required|string|max:50',
                    'n_colada_2'         => 'nullable|string|max:50',
                    'n_paquete_2'        => 'nullable|string|max:50',
                    'ubicacion_id'       => 'required|exists:ubicaciones,id',
                    'fabricante_manual'  => 'nullable|string|max:100',
                ],
                [
                    'codigo.required'   => 'El código es obligatorio.',
                    'codigo.string'     => 'El código debe ser un texto.',
                    'codigo.unique'     => 'Ese código ya existe.',
                    'codigo.max'        => 'El código no puede tener más de 20 caracteres.',

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

                    'fabricante_manual.string' => 'El fabricante debe ser texto.',
                    'fabricante_manual.max'    => 'El fabricante no puede tener más de 100 caracteres.',
                ]
            );

            $esDoble         = $request->filled('codigo_2') && $request->filled('n_colada_2') && $request->filled('n_paquete_2');
            $peso            = floatval($request->input('peso'));
            $pesoPorPaquete  = $esDoble ? round($peso / 2, 3) : $peso;

            if ($peso <= 0) {
                return redirect()->back()->with('error', 'El peso del paquete debe ser mayor que cero.');
            }

            $ubicacion = Ubicacion::findOrFail($request->ubicacion_id);

            // Buscar línea de pedido pendiente
            $pedidoProducto = PedidoProducto::where('pedido_id', $pedido->id)
                ->where('producto_base_id', $request->producto_base_id)
                ->where('estado', '!=', 'completado')
                ->orderBy('fecha_estimada_entrega')
                ->first();

            // Buscar o crear entrada abierta
            $entrada = Entrada::where('pedido_id', $pedido->id)
                ->where('estado', 'abierto')
                ->first();

            if (!$entrada) {
                $entrada = new Entrada();
                $entrada->pedido_id          = $pedido->id;
                $entrada->pedido_producto_id = $pedidoProducto?->id; // ✅ Asignación directa
                $entrada->albaran            = $this->generarCodigoAlbaran();
                $entrada->usuario_id         = auth()->id();
                $entrada->peso_total         = 0;
                $entrada->estado             = 'abierto';
                $entrada->otros              = 'Entrada generada desde recepción de pedido';
                $entrada->save();
            }

            // Fabricante
            $fabricanteFinal = $pedido->fabricante_id;
            if (!$fabricanteFinal && $pedido->distribuidor_id && $request->filled('fabricante_manual')) {
                $fabricanteTexto = $request->fabricante_manual; // Si tienes campo "otros", puedes guardarlo allí
            }

            // Primer producto
            Producto::create([
                'codigo'            => strtoupper($request->codigo),
                'producto_base_id'  => $request->producto_base_id,
                'fabricante_id'     => $fabricanteFinal,
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

            // Segundo producto si aplica
            if ($esDoble) {
                Producto::create([
                    'codigo'            => strtoupper($request->codigo_2),
                    'producto_base_id'  => $request->producto_base_id,
                    'fabricante_id'     => $fabricanteFinal,
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

            // Actualizar peso total
            $entrada->peso_total += $peso;
            $entrada->save();

            // Guardar estado del pedido (si quieres dejarlo como parcial por ahora)
            $pedido->save();

            return redirect()->back()->with('success', 'Producto(s) recepcionado(s) correctamente.');
        } catch (\Exception $e) {
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

    public function crearDesdeRecepcion(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_base_id' => 'required|exists:productos_base,id',
                'peso' => 'required|numeric|min:0.01',
                'n_colada' => 'nullable|string|max:50',
                'n_paquete' => 'nullable|string|max:50|unique:productos,n_paquete',
                'ubicacion_id' => 'required|exists:ubicaciones,id',
                'otros' => 'nullable|string|max:255',
                'fabricante_id' => 'required|exists:fabricantes,id',
            ], [
                'producto_base_id.required' => 'El producto base es obligatorio.',
                'producto_base_id.exists' => 'El producto base no es válido.',

                'peso.required' => 'El peso es obligatorio.',
                'peso.numeric' => 'El peso debe ser un número.',
                'peso.min' => 'El peso debe ser mayor que 0.',

                'n_colada.string' => 'El número de colada debe ser texto.',
                'n_colada.max' => 'El número de colada no puede tener más de 50 caracteres.',

                'n_paquete.string' => 'El número de paquete debe ser texto.',
                'n_paquete.max' => 'El número de paquete no puede tener más de 50 caracteres.',
                'n_paquete.unique' => 'El número de paquete ya existe en otro producto.',

                'ubicacion_id.required' => 'La ubicación es obligatoria.',
                'ubicacion_id.exists' => 'La ubicación no es válida.',

                'otros.string' => 'El campo de observaciones debe ser texto.',
                'otros.max' => 'El campo de observaciones no puede tener más de 255 caracteres.',

                'fabricante_id.required' => 'El fabricante es obligatorio.',
                'fabricante_id.exists' => 'El fabricante no es válido.',
            ]);

            $producto = Producto::create([
                'producto_base_id' => $validated['producto_base_id'],
                'fabricante_id' => $validated['fabricante_id'],
                'n_colada' => $validated['n_colada'] ?? null,
                'n_paquete' => $validated['n_paquete'],
                'peso_inicial' => $validated['peso'],
                'peso_stock' => $validated['peso'],
                'estado' => 'almacenado',
                'ubicacion_id' => $validated['ubicacion_id'],
                'maquina_id' => null,
                'otros' => $validated['otros'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'producto_id' => $producto->id,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function activar($pedidoId, $lineaId)
    {
        $linea = DB::table('pedido_productos')->where('id', $lineaId)->first();

        if (!$linea) {
            return redirect()->back()->with('error', 'Línea de pedido no encontrada.');
        }

        $pedido = Pedido::findOrFail($linea->pedido_id);

        if (!in_array($pedido->estado, ['pendiente', 'parcial', 'activo'])) {
            return redirect()->back()->with('error', 'Solo se pueden activar productos de pedidos pendientes, parciales o activos.');
        }

        $productoBase = ProductoBase::findOrFail($linea->producto_base_id);

        $descripcion = "Se solicita descarga para producto {$productoBase->tipo} Ø{$productoBase->diametro}";
        if ($productoBase->tipo === 'barra') {
            $descripcion .= " de {$productoBase->longitud} m";
        }
        $descripcion .= " del pedido {$pedido->codigo} (fecha: {$linea->fecha_estimada_entrega})";

        DB::beginTransaction();

        try {
            DB::table('pedido_productos')->where('id', $lineaId)->update([
                'estado' => 'activo',
                'updated_at' => now()
            ]);

            Movimiento::create([
                'tipo'                => 'descarga materia prima',
                'estado'              => 'pendiente',
                'descripcion'         => $descripcion,
                'fecha_solicitud'     => now(),
                'solicitado_por'      => auth()->id(),
                'pedido_id'           => $pedidoId,
                'producto_base_id'    => $productoBase->id,
                'pedido_producto_id'  => $lineaId,
                'prioridad'           => 2,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Producto activado correctamente y movimiento generado.');
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

        return redirect()->back()->with('success', 'Producto desactivado correctamente.');
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'seleccionados'    => 'required|array',
                'obra_id'          => 'required|exists:obras,id',
                'fabricante_id'    => 'nullable|exists:fabricantes,id',
                'distribuidor_id'  => 'nullable|exists:distribuidores,id',
            ], [
                'seleccionados.required' => 'Selecciona al menos un producto para generar el pedido.',
                'obra_id.required'       => 'Debes indicar la obra a la que se destina el pedido.',
                'obra_id.exists'         => 'La obra seleccionada no existe.',
                'fabricante_id.exists'   => 'El fabricante seleccionado no es válido.',
                'distribuidor_id.exists' => 'El distribuidor seleccionado no es válido.',
            ]);

            if (!$request->fabricante_id && !$request->distribuidor_id) {
                return back()->withErrors(['fabricante_id' => 'Debes seleccionar un fabricante o un distribuidor.'])->withInput();
            }

            if ($request->fabricante_id && $request->distribuidor_id) {
                return back()->withErrors(['fabricante_id' => 'Solo puedes seleccionar uno: fabricante o distribuidor.'])->withInput();
            }

            // 🧾 Buscar pedido global (si hay fabricante)
            $pedidoGlobal = null;
            if ($request->fabricante_id) {
                $pedidoGlobal = PedidoGlobal::where('fabricante_id', $request->fabricante_id)
                    ->whereIn('estado', ['pendiente', 'en curso'])
                    ->orderByRaw("FIELD(estado, 'en curso', 'pendiente')")
                    ->first();

                if ($pedidoGlobal && $pedidoGlobal->estado === 'pendiente') {
                    $pedidoGlobal->estado = 'en curso';
                    $pedidoGlobal->save();
                }
            }

            // 📝 Crear pedido principal
            $pedido = Pedido::create([
                'codigo'           => Pedido::generarCodigo(),
                'pedido_global_id' => $pedidoGlobal->id ?? null,
                'estado'           => 'pendiente',
                'fabricante_id'    => $request->fabricante_id,
                'distribuidor_id'  => $request->distribuidor_id,
                'obra_id'          => $request->obra_id,
                'fecha_pedido'     => now(),
            ]);

            $pesoTotal = 0;

            // 🔄 Procesar cada producto seleccionado
            foreach ($request->seleccionados as $clave) {
                $tipo     = $request->input("detalles.$clave.tipo");
                $diametro = $request->input("detalles.$clave.diametro");
                $longitud = $request->input("detalles.$clave.longitud"); // si se usa

                $productoBase = ProductoBase::where('tipo', $tipo)
                    ->where('diametro', $diametro)
                    ->when($longitud, fn($q) => $q->where('longitud', $longitud))
                    ->first();

                if (!$productoBase) continue;

                // 👇 CAMBIO AQUÍ: buscamos con la clave "redondo-16", no con ID
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
            Log::error('❌ Error al crear pedido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $msg = app()->environment('local') ? $e->getMessage() : 'Hubo un error inesperado al crear el pedido.';

            return back()->withInput()->with('error', $msg);
        }
    }

    public function show($id)
    {
        $pedido = Pedido::with(['productos', 'fabricante', 'obra'])->findOrFail($id);

        // puedes comentar esto si no se usa en la vista directamente
        $mailable = new PedidoCreado(
            $pedido,
            'compras@pacoreyes.com',
            'Pedidos - Hierros Paco Reyes',
            [
                'sebastian.duran@pacoreyes.com',
                'indiana.tirado@pacoreyes.com',
                'alberto.mayo@pacoreyes.com',
                'josemanuel.amuedo@pacoreyes.com',
            ]
        );

        return view('emails.pedidos.pedido_creado', [
            'pedido' => $pedido,
            'esVistaPrevia' => true,
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
        $ccEmails = ['eduardo.magro@pacoreyes.com'];

        // 📧 Dirección de respuesta dinámica
        $replyToEmail = auth()->user()->email ?? 'noreply@pacoreyes.com';
        $replyToName  = auth()->user()->name  ?? 'Usuario no identificado';

        // ✉️ Preparar el Mailable
        $mailable = new PedidoCreado(
            $pedido,
            'compras@pacoreyes.com',      // From visible
            $contacto['nombre'],          // Nombre del remitente visible
            $ccEmails,                    // CC
            $replyToEmail,                // Respuestas a
            $replyToName
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

    public function update(Request $request, Pedido $pedido)
    {
        try {

            $validated = $request->validate([
                'codigo_sage' => 'nullable|string|max:50',
            ], [

                'codigo_sage.string' => 'El código SAGE debe ser una cadena de texto.',
                'codigo_sage.max' => 'El código SAGE no puede tener más de 50 caracteres.',
            ]);

            if ($pedido->fecha_pedido === null && !empty($validated['fabricante_id'])) {
                $validated['fecha_pedido'] = now()->toDateString();
            } else {
                unset($validated['fecha_pedido']);
            }

            $pedido->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado correctamente.',
            ]);
        } catch (ValidationException $e) {
            // 🔁 Devolver todos los errores juntos como string, además del array detallado
            $mensajes = collect($e->errors())->flatten()->implode("\n");

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errores' => $e->errors(),
                'resumen' => $mensajes,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error en el servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->delete();

        return redirect()->route('pedidos.index')->with('success', 'Pedido eliminado correctamente.');
    }
}
