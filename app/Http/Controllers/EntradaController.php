<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\EntradaProducto;
use App\Models\Producto;
use App\Models\PedidoProducto;
use App\Models\Pedido;
use App\Models\Elemento;
use App\Models\ProductoBase;
use App\Models\Fabricante;
use App\Models\Distribuidor;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class EntradaController extends Controller
{
    //------------------------------------------------------------------------------------ FILTROS
    public function aplicarFiltrosEntradas($query, Request $request)
    {
        if ($request->filled('albaran')) {
            $query->where('albaran', 'like', '%' . $request->albaran . '%');
        }

        if ($request->filled('pedido_codigo')) {
            $query->whereHas('pedido', function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->pedido_codigo . '%');
            });
        }

        if ($request->filled('usuario')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario . '%');
            });
        }

        // Ordenamiento
        $sortBy = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        if ($sortBy === 'pedido_codigo') {
            $query->join('pedidos', 'entradas.pedido_id', '=', 'pedidos.id')
                ->orderBy('pedidos.codigo', $order)
                ->select('entradas.*');
        } elseif ($sortBy === 'usuario') {
            $query->join('users', 'entradas.usuario_id', '=', 'users.id')
                ->orderBy('users.name', $order)
                ->select('entradas.*');
        } else {
            $query->orderBy($sortBy, $order);
        }

        return $query;
    }
    private function filtrosActivosEntradas(Request $request): array
    {
        $filtros = [];

        if ($request->filled('albaran')) {
            $filtros[] = 'Albarán: <strong>' . $request->albaran . '</strong>';
        }

        if ($request->filled('pedido_codigo')) {
            $filtros[] = 'Pedido: <strong>' . $request->pedido_codigo . '</strong>';
        }

        if ($request->filled('usuario')) {
            $filtros[] = 'Usuario: <strong>' . $request->usuario . '</strong>';
        }

        if ($request->filled('sort')) {
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ucfirst($request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        return $filtros;
    }

    private function getOrdenamientoEntradas(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down')
            : 'fas fa-sort';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="text-white">' . $titulo . ' <i class="' . $icon . '"></i></a>';
    }

    // Mostrar todas las entradas
    public function index(Request $request)
    {
        // // 🔐 Si el usuario es operario, redirigir a pedidos
        if (auth()->user()->rol === 'operario') {
            return redirect()->route('pedidos.index');
        }
        try {
            // Inicializa la consulta de productos con sus relaciones necesarias
            $query = Entrada::with(['ubicacion', 'user', 'productos.productoBase', 'productos.fabricante', 'pedido'])
                ->withCount('productos');

            // Aplica los filtros mediante un método separado
            $query = $this->aplicarFiltrosEntradas($query, $request);
            $ordenables = [
                'albaran' => $this->getOrdenamientoEntradas('albaran', 'Albarán'),
                'pedido_codigo' => $this->getOrdenamientoEntradas('pedido_codigo', 'Pedido Compra'),
                'usuario' => $this->getOrdenamientoEntradas('usuario', 'Usuario'),
                'created_at' => $this->getOrdenamientoEntradas('created_at', 'Fecha'),
            ];
            $filtrosActivos = $this->filtrosActivosEntradas($request);
            $fabricantes = Fabricante::select('id', 'nombre')->get();
            $distribuidores = Distribuidor::select('id', 'nombre')->get();

            $perPage = $request->input('per_page', 10);
            $entradas = $query->paginate($perPage)->appends($request->all());
            // Devolver la vista con las entradas
            return view('entradas.index', compact('entradas', 'fabricantes', 'filtrosActivos'));
        } catch (ValidationException $e) {
            // Manejo de excepciones de validación
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            // Manejo de excepciones generales
            return redirect()->back()
                ->with('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function create()
    {
        // ─────────────────────────────────────────────────────────────────────────────
        // 1) Listados para los select
        // ─────────────────────────────────────────────────────────────────────────────
        $ubicaciones = Ubicacion::all()->map(function ($ubicacion) {
            $ubicacion->nombre_sin_prefijo = Str::after($ubicacion->nombre, 'Almacén ');
            return $ubicacion;
        });

        $usuarios       = User::all();
        $productosBase  = ProductoBase::orderBy('tipo')
            ->orderBy('diametro')
            ->orderBy('longitud')
            ->get();
        $fabricantes    = Fabricante::orderBy('nombre')->get();

        // ─────────────────────────────────────────────────────────────────────────────
        // 2) Último producto registrado por el usuario autenticado
        //    (cargamos también entrada y productoBase para no hacer más queries)
        // ─────────────────────────────────────────────────────────────────────────────
        $ultimoProducto = Producto::with(['entrada', 'productoBase'])
            ->whereHas('entrada', fn($q) => $q->where('usuario_id', auth()->id()))
            ->latest()           // mismo efecto que orderByDesc('created_at')
            ->first();

        // ─────────────────────────────────────────────────────────────────────────────
        // 3) Datos precargados para el formulario
        // ─────────────────────────────────────────────────────────────────────────────
        $ultimaColada         = $ultimoProducto?->n_colada;
        $ultimoProductoBaseId = $ultimoProducto?->producto_base_id;

        // - Fabricante: primero miramos si el producto tiene fabricante_id propio;
        //   si no, lo tomamos del producto base.
        $ultimoFabricanteId   = $ultimoProducto?->fabricante_id
            ?? $ultimoProducto?->productoBase?->fabricante_id;

        // - Ubicación: la obtenemos desde la entrada asociada
        $ultimaUbicacionId    = $ultimoProducto?->ubicacion_id;

        // ─────────────────────────────────────────────────────────────────────────────
        // 4) Devolvemos la vista con todos los datos
        // ─────────────────────────────────────────────────────────────────────────────
        return view('entradas.create', compact(
            'ubicaciones',
            'usuarios',
            'productosBase',
            'fabricantes',
            'ultimaColada',
            'ultimoProductoBaseId',
            'ultimoFabricanteId',
            'ultimaUbicacionId'
        ));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'codigo'            => [
                    'required',
                    'string',
                    'unique:productos,codigo',
                    'max:20',
                    'regex:/^MP.*/i',
                ],
                'codigo_2'          => [
                    'nullable',
                    'string',
                    'unique:productos,codigo',
                    'max:20',
                    'regex:/^MP.*/i',
                ],
                'fabricante_id'     => 'required|exists:fabricantes,id',
                'albaran'           => 'required|string|min:1|max:30',
                'pedido_id'         => 'nullable|exists:pedidos,id',
                'producto_base_id'  => 'required|exists:productos_base,id',
                'n_colada'          => 'required|string|max:50',
                'n_paquete'         => 'required|string|max:50',
                'n_colada_2'        => 'nullable|string|max:50',
                'n_paquete_2'       => 'nullable|string|max:50',
                'peso'              => 'required|numeric|min:1',
                'ubicacion_id'      => 'nullable|integer|exists:ubicaciones,id',
                'otros'             => 'nullable|string|max:255',
            ], [
                'codigo.required'   => 'El código generado es obligatorio.',
                'codigo.string'     => 'El código debe ser una cadena de texto.',
                'codigo.unique'     => 'Ese código ya existe.',
                'codigo.max'        => 'El código no puede tener más de 20 caracteres.',
                'codigo.regex'      => 'El código debe empezar por MP.',

                'codigo_2.string'      => 'El segundo código debe ser una cadena de texto.',
                'codigo_2.unique'      => 'El segundo código ya existe.',
                'codigo_2.max'         => 'El segundo código no puede tener más de 20 caracteres.',

                'fabricante_id.required' => 'El fabricante es obligatorio.',
                'fabricante_id.exists'   => 'El fabricante seleccionado no es válido.',

                'albaran.required'     => 'El albarán es obligatorio.',
                'albaran.string'       => 'El albarán debe ser una cadena de texto.',
                'albaran.min'          => 'El albarán debe tener al menos 1 carácter.',
                'albaran.max'          => 'El albarán no puede tener más de 30 caracteres.',

                'pedido_id.exists'     => 'El pedido seleccionado no es válido.',

                'producto_base_id.required' => 'El producto base es obligatorio.',
                'producto_base_id.exists'   => 'El producto base seleccionado no es válido.',

                'n_colada.required'    => 'El número de colada es obligatorio.',
                'n_colada.string'      => 'El número de colada debe ser una cadena de texto.',
                'n_colada.max'         => 'El número de colada no puede tener más de 50 caracteres.',

                'n_paquete.required'   => 'El número de paquete es obligatorio.',
                'n_paquete.string'     => 'El número de paquete debe ser una cadena de texto.',
                'n_paquete.max'        => 'El número de paquete no puede tener más de 50 caracteres.',

                'n_colada_2.string'    => 'El segundo número de colada debe ser una cadena de texto.',
                'n_colada_2.max'       => 'El segundo número de colada no puede tener más de 50 caracteres.',

                'n_paquete_2.string'   => 'El segundo número de paquete debe ser una cadena de texto.',
                'n_paquete_2.max'      => 'El segundo número de paquete no puede tener más de 50 caracteres.',

                'peso.required'        => 'El peso es obligatorio.',
                'peso.numeric'         => 'El peso debe ser un número.',
                'peso.min'             => 'El peso debe ser mayor que cero.',

                'ubicacion.integer'    => 'La ubicación debe ser un número entero.',
                'ubicacion.exists'     => 'La ubicación seleccionada no es válida.',

                'otros.string'         => 'El campo "otros" debe ser una cadena de texto.',
                'otros.max'            => 'El campo "otros" no puede tener más de 255 caracteres.',
            ]);


            $productoBase = ProductoBase::findOrFail($request->producto_base_id);
            $esDoble = $request->filled('codigo_2') && $request->filled('n_colada_2') && $request->filled('n_paquete_2');
            $pesoPorPaquete = $esDoble ? round($request->peso / 2, 3) : $request->peso;
            $codigo1 = strtoupper($request->codigo);
            $codigo2 = strtoupper($request->codigo_2);
            $pedidoProductoId = null;

            if ($request->filled('pedido_id')) {

                $pedidoProducto = DB::table('pedido_productos')
                    ->where('pedido_id', $request->pedido_id)
                    ->where('producto_base_id', $request->producto_base_id)
                    ->where('estado', '!=', 'completado')
                    ->orderBy('fecha_estimada_entrega')
                    ->first();

                if ($pedidoProducto) {
                    $pedidoProductoId = $pedidoProducto->id;
                }
            }

            // Crear entrada principal
            $entrada = Entrada::create([
                'albaran'              => $request->albaran,
                'usuario_id'           => auth()->id(),
                'peso_total'           => $request->peso,
                'estado'               => 'cerrado',
                'otros'                => $request->otros ?? null,
                'pedido_id'            => $request->pedido_id,
                'pedido_producto_id'   => $pedidoProductoId,
            ]);

            // Primer producto
            $producto1 = Producto::create([
                'codigo'           => $codigo1,
                'producto_base_id' => $request->producto_base_id,
                'fabricante_id'     => $request->fabricante_id,
                'entrada_id'       => $entrada->id,
                'n_colada'         => $request->n_colada,
                'n_paquete'        => $request->n_paquete,
                'peso_inicial'     => $pesoPorPaquete,
                'peso_stock'       => $pesoPorPaquete,
                'estado'           => 'almacenado',
                'ubicacion_id' => $request->ubicacion_id,
                'maquina_id'       => null,
                'otros'            => 'Alta manual. Fabricante: ' . ($request->fabricante ?? '—'),
            ]);


            // Segundo producto si aplica
            if ($esDoble) {
                $producto2 = Producto::create([
                    'codigo'           => $codigo2,
                    'producto_base_id' => $request->producto_base_id,
                    'fabricante_id'     => $request->fabricante_id,
                    'entrada_id'   => $entrada->id,
                    'n_colada'         => $request->n_colada_2,
                    'n_paquete'        => $request->n_paquete_2,
                    'peso_inicial'     => $pesoPorPaquete,
                    'peso_stock'       => $pesoPorPaquete,
                    'estado'           => 'almacenado',
                    'ubicacion_id' => $request->ubicacion_id,
                    'maquina_id'       => null,
                    'otros'            => 'Alta manual. Fabricante: ' . ($request->fabricante ?? '—'),
                ]);
            }

            DB::commit();
            return redirect()->route('productos.index')->with('success', 'Entrada registrada correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $entrada = Entrada::findOrFail($id);  // Encuentra la entrada por su ID
        $ubicaciones = Ubicacion::all();  // Cargar todas las ubicaciones
        return view('entradas.edit', compact('entrada', 'ubicaciones'));
    }

    public function update(Request $request, Entrada $entrada)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([

                'codigo_sage'  => 'nullable|string|max:50',

            ]);

            $entrada->update($validated);

            DB::commit();

            // 🔁 Si viene de fetch (JSON)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entrada actualizada correctamente.',
                    'data'    => $entrada->fresh()
                ]);
            }

            // Navegación tradicional
            return redirect()
                ->route('entradas.index')
                ->with('success', 'Entrada actualizada correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors'  => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un error en el servidor',
                    'error'   => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }


    public function cerrar($id)
    {
        DB::transaction(function () use ($id) {
            // Cargar entrada con relaciones necesarias
            $entrada = Entrada::with(['pedido'])->lockForUpdate()->findOrFail($id);

            if ($entrada->estado === 'cerrado') {
                abort(400, 'Este albarán ya está cerrado.');
            }

            // Verificar existencia de la línea de pedido asociada
            $pivot = PedidoProducto::lockForUpdate()->find($entrada->pedido_producto_id);

            if (!$pivot) {
                abort(422, 'No se ha vinculado correctamente la entrada con la línea del pedido.');
            }

            // Calcular peso recepcionado específicamente para esta línea del pedido
            $pesoRecepcionado = Producto::where('producto_base_id', $pivot->producto_base_id)
                ->whereHas('entrada', fn($q) => $q->where('pedido_producto_id', $pivot->id))
                ->sum('peso_inicial');

            // Log inicial de control
            Log::info('📦 Cierre de albarán', [
                'entrada_id' => $entrada->id,
                'pedido_producto_id' => $pivot->id,
                'producto_base_id' => $pivot->producto_base_id,
                'cantidad_pedida' => $pivot->cantidad,
                'peso_recepcionado' => $pesoRecepcionado,
            ]);

            // Determinar estado de la línea
            $estado = match (true) {
                $pesoRecepcionado >= $pivot->cantidad * 0.8 => 'completado',
                $pesoRecepcionado > 0 => 'parcial',
                default => 'pendiente',
            };

            // Actualizar línea
            PedidoProducto::where('id', $pivot->id)->update([
                'cantidad_recepcionada' => $pesoRecepcionado,
                'estado' => $estado,
                'fecha_estimada_entrega' => now(),
            ]);

            // Cerrar entrada
            $entrada->estado = 'cerrado';
            $entrada->save();

            // Actualizar movimientos relacionados
            Movimiento::where('pedido_id', $entrada->pedido_id)
                ->where('pedido_producto_id', $pivot->id)
                ->where('estado', '!=', 'completado')
                ->lockForUpdate()
                ->update([
                    'estado' => 'completado',
                    'ejecutado_por' => auth()->id(),
                    'fecha_ejecucion' => now(),
                ]);

            // Recargar líneas desde la base de datos
            $lineas = PedidoProducto::where('pedido_id', $entrada->pedido_id)->get();

            // Comprobación de estado global
            $todosCompletados = $lineas->every(fn($linea) => $linea->estado === 'completado');

            if ($todosCompletados) {
                $entrada->pedido->estado = 'completado';
                $entrada->pedido->save();
                Log::info('✅ Pedido completado automáticamente', [
                    'pedido_id' => $entrada->pedido->id,
                ]);
            } else {
                Log::info('ℹ️ Pedido aún tiene líneas pendientes/parciales', [
                    'pedido_id' => $entrada->pedido->id,
                ]);
            }

            // Logs finales
            Log::info('✅ Línea de pedido actualizada', [
                'pedido_producto_id' => $pivot->id,
                'nuevo_estado' => $estado,
                'peso_recepcionado' => $pesoRecepcionado,
            ]);

            Log::info('🔍 Estados de TODAS las líneas del pedido', $lineas->mapWithKeys(
                fn($linea) => [$linea->id => $linea->estado]
            )->toArray());
        });

        return redirect()->route('maquinas.index')
            ->with('success', 'Albarán cerrado correctamente.');
    }

    // Eliminar una entrada y sus productos asociados
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Buscar la entrada a eliminar
            $entrada = Entrada::findOrFail($id);

            // Eliminar los productos asociados a la entrada
            $entrada->productos()->delete();

            // Eliminar la entrada
            $entrada->delete();
            DB::commit();  // Confirmamos la transacción
            return redirect()->route('entradas.index')->with('success', 'Entrada eliminada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la entrada: ' . $e->getMessage());
        }
    }
}
