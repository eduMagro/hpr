<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\PedidoGlobal;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Elemento;
use App\Models\ProductoBase;
use App\Models\Entrada;
use App\Models\EntradaProducto;
use App\Models\Ubicacion;
use App\Mail\PedidoCreado;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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

        if ($request->filled('proveedor_id')) {
            $proveedor = Proveedor::find($request->proveedor_id);
            if ($proveedor) {
                $filtros[] = 'Proveedor: <strong>' . $proveedor->nombre . '</strong>';
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
                'proveedor' => 'Proveedor',
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
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }
        // Filtro por pedido_global_id
        if ($request->filled('pedido_global_id')) {
            $query->where('pedido_global_id', $request->pedido_global_id);
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
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
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pedido::with(['proveedor', 'productos', 'pedidoGlobal'])->latest();

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
        $proveedores = Proveedor::select('id', 'nombre')->get();

        $ordenables = [
            'codigo' => $this->getOrdenamientoPedidos('codigo', 'Código'),
            'proveedor' => $this->getOrdenamientoPedidos('proveedor', 'Proveedor'),
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

        return view('pedidos.index', compact('pedidos', 'stockData', 'comparativa', 'pedidosPorDiametro', 'necesarioPorDiametro', 'proveedores', 'filtrosActivos', 'ordenables', 'proveedores', 'pedidosGlobales'));
    }

    public function recepcion($id)
    {
        $pedido = Pedido::with(['productos', 'entradas.productos'])->findOrFail($id);

        if (in_array($pedido->estado, ['completado', 'cancelado'])) {
            return redirect()->back()->with('error', "El pedido ya está {$pedido->estado} y no puede recepcionarse.");
        }

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

        return view('pedidos.recepcion', compact('pedido'));
    }


    public function procesarRecepcion(Request $request, $id)
    {
        try {
            $pedido = Pedido::with('productos')->findOrFail($id);

            if (in_array($pedido->estado, ['completado', 'cancelado'])) {
                return redirect()->back()->with('error', "El pedido ya está {$pedido->estado} y no puede recepcionarse.");
            }

            $lineas = $request->input('lineas', []);
            $productosCreados = [];
            $pesoTotal = 0;

            foreach ($pedido->productos as $productoBase) {
                $productoBaseId = $productoBase->id;
                $entradas = $lineas[$productoBaseId] ?? [];

                $pesos = $entradas['peso'] ?? [];
                $coladas = $entradas['n_colada'] ?? [];
                $paquetes = $entradas['n_paquete'] ?? [];
                $ubicacionesTexto = $entradas['ubicacion_texto'] ?? [];
                $otros = $entradas['otros'] ?? [];

                for ($i = 0; $i < count($pesos); $i++) {
                    $peso = floatval($pesos[$i] ?? 0);
                    if ($peso <= 0) continue;

                    $ubicacionId = intval($ubicacionesTexto[$i] ?? 0);
                    if (!Ubicacion::find($ubicacionId)) {
                        throw new \Exception("Ubicación ID {$ubicacionId} no válida.");
                    }

                    $producto = Producto::create([
                        'producto_base_id' => $productoBaseId,
                        'proveedor_id' => $pedido->proveedor_id,
                        'n_colada' => $coladas[$i] ?? null,
                        'n_paquete' => $paquetes[$i] ?? null,
                        'peso_inicial' => $peso,
                        'peso_stock' => $peso,
                        'estado' => 'almacenado',
                        'ubicacion_id' => $ubicacionId,
                        'maquina_id' => null,
                        'otros' => $otros[$i] ?? null,
                    ]);

                    $productosCreados[] = [
                        'producto_id' => $producto->id,
                        'ubicacion_id' => $ubicacionId,
                    ];

                    $pesoTotal += $peso;
                }
            }

            $codigoEntrada = $this->generarCodigoAlbaran();
            $entrada = Entrada::create([
                'albaran' => $codigoEntrada,
                'pedido_id' => $request->pedido_id,
                'peso_total' => $pesoTotal,
                'usuario_id' => auth()->id(),
                'otros' => 'Entrada generada desde recepción de pedido',
            ]);

            foreach ($productosCreados as $item) {
                EntradaProducto::create([
                    'entrada_id' => $entrada->id,
                    'producto_id' => $item['producto_id'],
                    'ubicacion_id' => $item['ubicacion_id'],
                    'users_id' => auth()->id(),
                ]);
            }

            $pesoSuministrado = $pedido->entradas()->sum('peso_total');
            $pesoPedido = $pedido->productos->sum(fn($p) => $p->pivot->cantidad);
            $margen = 0.005;

            $pedido->estado = ($pesoSuministrado >= $pesoPedido * (1 - $margen)) ? 'completado' : 'parcial';
            $pedido->save();

            return redirect()->route('pedidos.index')->with('success', 'Recepción registrada y entrada creada correctamente.');
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
    public function store(Request $request)
    {
        $request->validate([
            'seleccionados' => 'required|array',
            'fabricante_id' => 'required|exists:proveedores,id',
            'fecha_estimada_entrega' => 'required|date|after_or_equal:today',
        ], [
            'seleccionados.required' => 'Debes seleccionar al menos un producto.',
            'seleccionados.array' => 'El formato de los productos seleccionados no es válido.',

            'fabricante_id.required' => 'El proveedor es obligatorio.',
            'fabricante_id.exists' => 'El proveedor seleccionado no es válido.',

            'fecha_estimada_entrega.required' => 'La fecha estimada de entrega es obligatoria.',
            'fecha_estimada_entrega.date' => 'La fecha estimada debe ser una fecha válida.',
            'fecha_estimada_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a hoy.',
        ]);


        $pedidoGlobal = PedidoGlobal::where('proveedor_id', $request->fabricante_id)
            ->whereIn('estado', [PedidoGlobal::ESTADO_EN_CURSO, PedidoGlobal::ESTADO_PENDIENTE])
            ->orderByRaw("FIELD(estado, ?, ?)", [PedidoGlobal::ESTADO_EN_CURSO, PedidoGlobal::ESTADO_PENDIENTE])
            ->first();

        if ($pedidoGlobal && $pedidoGlobal->estado === PedidoGlobal::ESTADO_PENDIENTE) {
            $pedidoGlobal->estado = PedidoGlobal::ESTADO_EN_CURSO;
            $pedidoGlobal->save();
        }
        $pedido = Pedido::create([
            'codigo' => Pedido::generarCodigo(),
            'pedido_global_id' => $pedidoGlobal->id,
            'estado' => 'pendiente',
            'proveedor_id' => $request->fabricante_id,
            'fecha_pedido' => now(),
            'fecha_entrega' => $request->fecha_estimada_entrega,
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

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pedido = Pedido::with(['productos', 'proveedor'])->findOrFail($id);

        $mailable = new PedidoCreado($pedido);

        return view('emails.pedidos.pedido_creado', [
            'pedido' => $pedido,
            'esVistaPrevia' => true, // 👈 Esto activará el botón solo en la vista previa
        ]);
    }
    public function enviarCorreo($id)
    {
        $pedido = Pedido::with('productos')->findOrFail($id);

        Mail::to('eduardo.magro@pacoreyes.com')->send(new PedidoCreado($pedido));

        return redirect()->route('pedidos.index')->with('success', 'Correo enviado correctamente.');
    }

    private function crearPedidoDesdeRequest(Request $request): Pedido
    {
        $pedidoGlobal = PedidoGlobal::where('proveedor_id', $request->fabricante_id)
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
            'proveedor_id' => $request->fabricante_id,
            'fecha_pedido' => now(),
            'fecha_entrega' => $request->fecha_estimada_entrega,
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, Pedido $pedido)
    {
        try {
            // Validación de datos con mensajes personalizados
            $validated = $request->validate([
                'codigo' => 'required|string|max:50|unique:pedidos,codigo,' . $pedido->id,
                'proveedor_id' => 'required|exists:proveedores,id',
                'fecha_pedido' => 'nullable|date_format:Y-m-d',
                'fecha_entrega' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_pedido',
                'estado' => 'required|in:pendiente,parcial,completo,cancelado',
            ], [
                'codigo.required' => 'El campo código es obligatorio.',
                'codigo.string' => 'El código debe ser una cadena de texto.',
                'codigo.max' => 'El código no puede tener más de 50 caracteres.',
                'codigo.unique' => 'Ya existe un pedido con ese código.',
                'proveedor_id.required' => 'Debe seleccionar un proveedor.',
                'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
                'fecha_pedido.date_format' => 'La fecha del pedido debe tener el formato día-mes-año.',
                'fecha_entrega.date_format' => 'La fecha estimada debe tener el formato día-mes-año.',
                'fecha_entrega.after_or_equal' => 'La fecha estimada no puede ser anterior a la fecha del pedido.',
                'estado.required' => 'Debe seleccionar un estado.',
                'estado.in' => 'El estado seleccionado no es válido.',
            ]);

            if ($pedido->fecha_pedido === null && !empty($validated['proveedor_id'])) {
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


    /**
     * Remove the specified resource from storage.
     */
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->delete();

        return redirect()->route('pedidos.index')->with('success', 'Pedido eliminado correctamente.');
    }
}
