<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fabricante;
use App\Models\Distribuidor;
use App\Models\PedidoGlobal;
use App\Models\Pedido;
use App\Models\Producto;
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
    public function index(Request $request)
    {
        $query = Pedido::with(['fabricante', 'productos', 'pedidoGlobal'])->latest();

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
            $pedido->productos_formateados = $pedido->productos->map(function ($p) {
                return [
                    'tipo' => $p->tipo,
                    'diametro' => $p->diametro,
                    'longitud' => $p->longitud,
                    'cantidad' => $p->pivot->cantidad,
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
            'fecha_entrega' => $this->getOrdenamientoPedidos('fecha_entrega', 'F. Estimada Entrega'),
            'estado' => $this->getOrdenamientoPedidos('estado', 'Estado'),
        ];

        // Tabla stock
        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

        $elementosPendientes = Elemento::with('maquina')
            ->where('estado', 'pendiente')
            ->get()
            ->filter(fn($e) => $e->maquina && $e->maquina->tipo && $e->diametro)
            ->groupBy(fn($e) => $e->maquina->tipo_material . '-' . intval($e->diametro))
            ->map(fn($group) => $group->sum('peso'));

        $necesarioPorDiametro = collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($elementosPendientes) {
            $encarretado = $elementosPendientes["encarretado-$diametro"] ?? 0;

            $barrasPorLongitud = collect([12, 14, 15, 16])->mapWithKeys(function ($longitud) use ($diametro) {
                return [$longitud => 0];
            });

            // Igual que en pedidos: si hay elementos pendientes de barra, los metemos en longitud 12 por defecto
            $barrasPorLongitud[12] = $elementosPendientes["barra-$diametro"] ?? 0;

            $barrasTotal = $barrasPorLongitud->sum();
            $total = $barrasTotal + $encarretado;

            return [
                $diametro => [
                    'encarretado' => $encarretado,
                    'barras' => $barrasPorLongitud,
                    'barras_total' => $barrasTotal,
                    'total' => $total,
                ]
            ];
        });

        $pedidosPendientes = Pedido::with('productos')
            ->where('estado', 'pendiente')
            ->get()
            ->flatMap(function ($pedido) {
                return $pedido->productos->map(function ($producto) use ($pedido) {
                    return [
                        'tipo' => $producto->tipo,
                        'diametro' => $producto->diametro,
                        'cantidad' => $producto->pivot->cantidad,
                    ];
                });
            })
            ->groupBy(fn($item) => "{$item['tipo']}-{$item['diametro']}")
            ->map(fn($items) => collect($items)->sum('cantidad'));

        $pedidosPorDiametro = collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($pedidosPendientes) {
            $encarretado = $pedidosPendientes["encarretado-$diametro"] ?? 0;

            $barrasPorLongitud = collect([12, 14, 15, 16])->mapWithKeys(function ($longitud) use ($diametro, $pedidosPendientes) {
                $clave = "barra-$diametro";
                // Aquí no tienes longitud en la clave, así que asumimos que todo pedido de tipo barra se reparte
                // Si en un futuro quieres registrar por longitud en pedidos, actualizamos esto
                return [$longitud => 0];
            });

            // Para ahora, metemos todo lo de tipo 'barra' en longitud 12 como simplificación
            $barrasPorLongitud[12] = $pedidosPendientes["barra-$diametro"] ?? 0;

            $barrasTotal = $barrasPorLongitud->sum();
            $total = $encarretado + $barrasTotal;

            return [
                $diametro => [
                    'encarretado' => $encarretado,
                    'barras' => $barrasPorLongitud,
                    'barras_total' => $barrasTotal,
                    'total' => $total,
                ]
            ];
        });

        $productos = Producto::with('productoBase')
            ->where('estado', 'almacenado')
            ->get();
        // Inicializa los datos para todos los diámetros fijos
        $stockData = collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($productos) {
            $grupo = $productos->filter(fn($p) => intval($p->productoBase->diametro) === $diametro);

            $encarretado = $grupo->filter(fn($p) => $p->productoBase->tipo === 'encarretado')->sum('peso_stock');
            $barras = $grupo->filter(fn($p) => $p->productoBase->tipo === 'barra');
            $barrasPorLongitud = $barras->groupBy(fn($p) => $p->productoBase->longitud)
                ->map(fn($items) => $items->sum('peso_stock'));

            $barrasTotal = $barrasPorLongitud->sum();
            $total = $barrasTotal + $encarretado;

            return [
                $diametro => [
                    'encarretado' => $encarretado,
                    'barras' => $barrasPorLongitud,
                    'barras_total' => $barrasTotal,
                    'total' => $total,
                ]
            ];
        });

        $comparativa = [];

        foreach ($stockData as $diametro => $data) {
            foreach (['barra', 'encarretado'] as $tipo) {
                $clave = "{$tipo}-{$diametro}";
                $pendiente = $elementosPendientes[$clave] ?? 0;
                $pedido = $pedidosPendientes[$clave] ?? 0;
                $disponible = $tipo === 'barra' ? $data['barras_total'] : $data['encarretado'];

                $diferencia = $disponible + $pedido - $pendiente;

                $comparativa[$clave] = [
                    'tipo' => $tipo,
                    'diametro' => $diametro,
                    'pendiente' => $pendiente,
                    'disponible' => $disponible,
                    'pedido' => $pedido,
                    'diferencia' => $diferencia,
                ];
            }
        }
        $totalEncarretado = $stockData->sum(fn($d) => $d['encarretado']);
        $totalBarras      = $stockData->sum(fn($d) => $d['barras_total']);
        $totalGeneral     = $totalEncarretado + $totalBarras;
        // Obtener obras activas
        $obrasActivas = Obra::where('estado', 'activa')->orderBy('obra')->get();

        return view('pedidos.index', compact('pedidos', 'obrasActivas', 'stockData', 'totalGeneral', 'comparativa', 'pedidosPorDiametro', 'necesarioPorDiametro', 'fabricantes', 'filtrosActivos', 'ordenables', 'distribuidores', 'pedidosGlobales',));
    }

    public function recepcion($id)
    {
        $pedido = Pedido::with(['productos'])->findOrFail($id);
        $productosIds = $pedido->productos->pluck('id');
        // Calcular cuánto se ha recepcionado por producto_base_id
        $recepcionadoPorProducto = [];

        foreach ($pedido->entradas as $entrada) {
            foreach ($entrada->productos as $producto) {
                $idBase = $producto->producto_base_id;
                $recepcionadoPorProducto[$idBase] = ($recepcionadoPorProducto[$idBase] ?? 0) + $producto->peso_inicial;
            }
        }

        // Añadir campo 'pendiente' a cada producto del pedido
        $pedido->productos->each(function ($producto) use ($recepcionadoPorProducto) {
            $idBase = $producto->id;
            $yaRecepcionado = $recepcionadoPorProducto[$idBase] ?? 0;
            $producto->pendiente = max(0, $producto->pivot->cantidad - $yaRecepcionado);
        });
        $ubicaciones = Ubicacion::all()->map(function ($ubicacion) {
            $ubicacion->nombre_sin_prefijo = Str::after($ubicacion->nombre, 'Almacén ');
            return $ubicacion;
        });
        /* -----------------------------------------------------------
     | 2) Últimos movimientos del usuario para CADA producto base |
     ----------------------------------------------------------- */
        // Buscamos en la tabla productos (los paquetes ya recepcionados)
        //  y unimos con la tabla entradas para filtrar por user_id.
        $ultimos = Producto::select(
            'producto_base_id',
            'n_colada',
            'productos.ubicacion_id'      // 👈 aquí cogemos la ubicación del propio producto
        )
            ->join('entradas', 'entradas.id', '=', 'productos.entrada_id')
            ->where('entradas.usuario_id', auth()->id())
            ->whereIn('producto_base_id', $productosIds)
            ->latest('productos.created_at')
            ->get()
            ->unique('producto_base_id')      // 1 registro por producto base
            ->keyBy('producto_base_id');      // [producto_base_id] => registro


        return view('pedidos.recepcion', compact('pedido', 'ubicaciones', 'ultimos'));
    }

    public function procesarRecepcion(Request $request, $id)
    {
        try {
            $pedido = Pedido::with('productos')->findOrFail($id);

            if (in_array($pedido->estado, ['completado', 'cancelado'])) {
                return redirect()->back()->with('error', "El pedido ya está {$pedido->estado} y no puede recepcionarse.");
            }

            $request->validate([
                'codigo'           => 'required|string|unique:productos,codigo|max:20',
                'codigo_2'         => 'nullable|string|unique:productos,codigo|max:20',
                'producto_base_id' => 'required|exists:productos_base,id',
                'peso'             => 'required|numeric|min:1',
                'n_colada'         => 'required|string|max:50',
                'n_paquete'        => 'required|string|max:50',
                'n_colada_2'       => 'nullable|string|max:50',
                'n_paquete_2'      => 'nullable|string|max:50',
                'ubicacion_id'     => 'required|exists:ubicaciones,id',
            ], [
                'codigo.required'      => 'El código del primer paquete es obligatorio.',
                'codigo.string'        => 'El código del primer paquete debe ser una cadena de texto.',
                'codigo.unique'        => 'El código del primer paquete ya existe en el sistema.',
                'codigo.max'           => 'El código del primer paquete no puede superar los 20 caracteres.',

                'codigo_2.string'      => 'El código del segundo paquete debe ser una cadena de texto.',
                'codigo_2.unique'      => 'El código del segundo paquete ya existe en el sistema.',
                'codigo_2.max'         => 'El código del segundo paquete no puede superar los 20 caracteres.',

                'producto_base_id.required' => 'Debes seleccionar un producto base.',
                'producto_base_id.exists'   => 'El producto base seleccionado no existe.',

                'peso.required'        => 'El peso total del paquete es obligatorio.',
                'peso.numeric'         => 'El peso debe ser un número válido.',
                'peso.min'             => 'El peso mínimo permitido es 1 kg.',

                'n_colada.required'    => 'El número de colada del primer paquete es obligatorio.',
                'n_colada.string'      => 'El número de colada debe ser texto.',
                'n_colada.max'         => 'El número de colada no puede superar los 50 caracteres.',

                'n_paquete.required'   => 'El número de paquete del primer paquete es obligatorio.',
                'n_paquete.string'     => 'El número de paquete debe ser texto.',
                'n_paquete.max'        => 'El número de paquete no puede superar los 50 caracteres.',

                'n_colada_2.string'    => 'El número de colada del segundo paquete debe ser texto.',
                'n_colada_2.max'       => 'El número de colada del segundo paquete no puede superar los 50 caracteres.',

                'n_paquete_2.string'   => 'El número de paquete del segundo paquete debe ser texto.',
                'n_paquete_2.max'      => 'El número de paquete del segundo paquete no puede superar los 50 caracteres.',

                'ubicacion_id.required' => 'Debes seleccionar una ubicación para almacenar el paquete.',
                'ubicacion_id.exists'   => 'La ubicación seleccionada no existe.',
            ]);

            $esDoble = $request->filled('codigo_2') && $request->filled('n_colada_2') && $request->filled('n_paquete_2');
            $peso = floatval($request->input('peso'));
            $pesoPorPaquete = $esDoble ? round($peso / 2, 3) : $peso;

            if ($peso <= 0) {
                return redirect()->back()->with('error', 'El peso del paquete debe ser mayor que cero.');
            }

            $ubicacion = Ubicacion::find($request->ubicacion_id);
            if (!$ubicacion) {
                return redirect()->back()->with('error', "Ubicación no encontrada: '{$request->ubicacion_id}'");
            }

            // Buscar o crear entrada abierta
            $entrada = Entrada::firstOrCreate(
                ['pedido_id' => $pedido->id, 'estado' => 'abierto'],
                [
                    'albaran'     => $this->generarCodigoAlbaran(),
                    'usuario_id'  => auth()->id(),
                    'peso_total'  => 0,
                    'otros'       => 'Entrada generada desde recepción de pedido',
                ]
            );
            $codigo1 = strtoupper($request->codigo);
            $codigo2 = strtoupper($request->codigo_2);

            // Crear primer producto con entrada_id asignado
            Producto::create([
                'codigo'           => $codigo1,
                'producto_base_id' => $request->producto_base_id,
                'fabricante_id'    => $pedido->fabricante_id,
                'entrada_id'       => $entrada->id, // ✅ Aquí se asigna
                'n_colada'         => $request->n_colada,
                'n_paquete'        => $request->n_paquete,
                'peso_inicial'     => $pesoPorPaquete,
                'peso_stock'       => $pesoPorPaquete,
                'estado'           => 'almacenado',
                'ubicacion_id'     => $ubicacion->id,
                'maquina_id'       => null,
                'otros'            => $request->otros ?? null,
            ]);

            // Segundo producto si aplica
            if ($esDoble) {
                Producto::create([
                    'codigo'           => $codigo2,
                    'producto_base_id' => $request->producto_base_id,
                    'fabricante_id'    => $pedido->fabricante_id,
                    'entrada_id'       => $entrada->id, // ✅ También aquí
                    'n_colada'         => $request->n_colada_2,
                    'n_paquete'        => $request->n_paquete_2,
                    'peso_inicial'     => $pesoPorPaquete,
                    'peso_stock'       => $pesoPorPaquete,
                    'estado'           => 'almacenado',
                    'ubicacion_id'     => $ubicacion->id,
                    'maquina_id'       => null,
                    'otros'            => $request->otros ?? null,
                ]);
            }

            // Sumar peso total
            $entrada->peso_total += $peso;
            $entrada->save();

            // Verificar si el pedido debe cambiar de estado
            $pesoSuministrado = $pedido->entradas()->sum('peso_total');
            $pesoPedido = $pedido->productos->sum(fn($p) => $p->pivot->cantidad);
            $margen = 0.15;

            $pedido->estado = ($pesoSuministrado >= $pesoPedido * (1 - $margen)) ? 'completado' : 'parcial';
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
    public function activar($id)
    {
        $pedido = Pedido::findOrFail($id);

        if (!in_array($pedido->estado, ['pendiente', 'parcial'])) {
            return redirect()->back()->with('error', 'Solo se pueden activar pedidos en estado pendiente o parcial.');
        }

        DB::beginTransaction();

        try {
            // Cambiar estado a activo
            $pedido->estado = 'activo';
            $pedido->save();

            // Crear movimiento de descarga de materia prima asociado al pedido
            Movimiento::create([
                'tipo'            => 'descarga materia prima',
                'estado'          => 'pendiente',
                'descripcion'     => 'Se solicita descarga de materiales para el pedido ' . $pedido->codigo,
                'fecha_solicitud' => now(),
                'solicitado_por'  => auth()->id(),
                'pedido_id'       => $pedido->id, // ← clave foránea hacia el pedido
                'prioridad'       => 2,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Pedido activado correctamente y movimiento generado.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al activar pedido o crear movimiento: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error al activar el pedido.');
        }
    }

    public function store(Request $request)
    {

        $request->validate([
            'seleccionados'    => 'required|array',
            'obra_id'          => 'required|exists:obras,id',
            'fecha_entrega'    => 'required|date|after_or_equal:today',
            'fabricante_id'    => 'nullable|exists:fabricantes,id',
            'distribuidor_id'  => 'nullable|exists:distribuidores,id',
        ], [
            'seleccionados.required' => 'Selecciona al menos un producto para generar el pedido.',
            'seleccionados.array'    => 'El formato de los productos seleccionados no es válido.',

            'obra_id.required'       => 'Debes indicar la obra a la que se destina el pedido.',
            'obra_id.exists'         => 'La obra seleccionada no existe en el sistema.',

            'fecha_entrega.required'     => 'Indica la fecha estimada de entrega del material.',
            'fecha_entrega.date'         => 'La fecha de entrega no es válida.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega debe ser hoy o una fecha futura.',

            'fabricante_id.exists'   => 'El fabricante seleccionado no es válido.',
            'distribuidor_id.exists' => 'El distribuidor seleccionado no es válido.',
        ]);

        if (!$request->fabricante_id && !$request->distribuidor_id) {
            return back()->withErrors(['fabricante_id' => 'Debes seleccionar un fabricante o un distribuidor.'])->withInput();
        }

        if ($request->fabricante_id && $request->distribuidor_id) {
            return back()->withErrors(['fabricante_id' => 'Solo puedes seleccionar un fabricante o un distribuidor, no ambos.'])->withInput();
        }


        $pedidoGlobal = null;

        if ($request->fabricante_id) {
            $pedidoGlobal = PedidoGlobal::where('fabricante_id', $request->fabricante_id)
                ->whereIn('estado', [PedidoGlobal::ESTADO_EN_CURSO, PedidoGlobal::ESTADO_PENDIENTE])
                ->orderByRaw("FIELD(estado, ?, ?)", [PedidoGlobal::ESTADO_EN_CURSO, PedidoGlobal::ESTADO_PENDIENTE])
                ->first();

            if ($pedidoGlobal && $pedidoGlobal->estado === PedidoGlobal::ESTADO_PENDIENTE) {
                $pedidoGlobal->estado = PedidoGlobal::ESTADO_EN_CURSO;
                $pedidoGlobal->save();
            }
        }

        $pedido = Pedido::create([
            'codigo'           => Pedido::generarCodigo(),
            'pedido_global_id' => $pedidoGlobal->id ?? null,
            'estado'           => 'pendiente',
            'fabricante_id'    => $request->fabricante_id,
            'distribuidor_id'  => $request->distribuidor_id,
            'obra_id'          => $request->obra_id,
            'fecha_pedido'     => now(),
            'fecha_entrega'    => $request->fecha_entrega,
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
                $pedido->productos()->attach($productoBase->id, [
                    'cantidad' => $peso,
                    'observaciones' => "Pedido generado desde comparativa automática",
                ]);

                $pesoTotal += $peso;
            }
        }

        $pedido->peso_total = $pesoTotal;

        if ($pedidoGlobal) {
            $pedidoGlobal->actualizarEstadoSegunProgreso();
        }

        $pedido->save();

        // Mail::to('eduardo.magro@pacoreyes.com')->send(new PedidoCreado($pedido));

        return redirect()->route('pedidos.show', $pedido->id)
            ->with('success', 'Pedido creado correctamente. Revisa el correo antes de enviarlo.');
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
            // Validación de datos con mensajes personalizados
            $validated = $request->validate([
                'codigo' => 'required|string|max:50|unique:pedidos,codigo,' . $pedido->id,
                'fabricante_id' => 'required|exists:fabricantes,id',
                'fecha_pedido' => 'nullable|date_format:Y-m-d',
                'fecha_entrega' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_pedido',
                'estado' => 'required|in:pendiente,parcial,completo,cancelado',
            ], [
                'codigo.required' => 'El campo código es obligatorio.',
                'codigo.string' => 'El código debe ser una cadena de texto.',
                'codigo.max' => 'El código no puede tener más de 50 caracteres.',
                'codigo.unique' => 'Ya existe un pedido con ese código.',
                'fabricante_id.required' => 'Debe seleccionar un fabricante.',
                'fabricante_id.exists' => 'El fabricante seleccionado no es válido.',
                'fecha_pedido.date_format' => 'La fecha del pedido debe tener el formato día-mes-año.',
                'fecha_entrega.date_format' => 'La fecha estimada debe tener el formato día-mes-año.',
                'fecha_entrega.after_or_equal' => 'La fecha estimada no puede ser anterior a la fecha del pedido.',
                'estado.required' => 'Debe seleccionar un estado.',
                'estado.in' => 'El estado seleccionado no es válido.',
            ]);

            if ($pedido->fecha_pedido === null && !empty($validated['fabricante_id'])) {
                $validated['fecha_pedido'] = now()->toDateString();
            } else {
                unset($validated['fecha_pedido']); // evitar que se sobreescriba
            }

            // Actualizar el pedido
            $pedido->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado correctamente.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Error de validación'
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ocurrió un error en el servidor',
                'error' => $e->getMessage()
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
