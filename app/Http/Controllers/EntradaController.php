<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\EntradaProducto;
use App\Models\Producto;
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
    private function aplicarFiltros($query, Request $request)
    {
        //$buscar = $request->input('albaran');
        //if (!empty($buscar)) {
        //   $query->where('albaran', $buscar);
        //}
        //return $query;
        // Filtro por 'id' si está presente
        if ($request->has('albaran') && $request->albaran) {
            $albaran = $request->input('albaran');
            $query->where('albaran', '=', $albaran);  // Filtro exacto por ID
        }
        if ($request->filled('entrada_id')) {
            $query->where('id', $request->entrada_id);
        }
        // Filtro por 'fecha' si está presente y busca en la columna 'created_at' usando LIKE
        if ($request->has('fecha') && $request->fecha) {
            $fecha = $request->input('fecha');  // Obtener el valor de la fecha proporcionada

            // Buscar en la columna 'created_at' utilizando LIKE para buscar por año, mes o día
            $query->whereRaw('DATE(created_at) LIKE ?', ['%' . $fecha . '%']);
        }

        return $query;
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
            $query = $this->aplicarFiltros($query, $request);

            $fabricantes = Fabricante::select('id', 'nombre')->get();
            $distribuidores = Distribuidor::select('id', 'nombre')->get();

            // Obtener las entradas paginadas, ordenadas por fecha de creación
            $entradas = $query->orderBy('created_at', 'desc')->paginate(10);

            // Devolver la vista con las entradas
            return view('entradas.index', compact('entradas', 'fabricantes'));
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
                'ubicacion'         => 'nullable|integer|exists:ubicaciones,id',
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

            // Crear entrada principal
            $entrada = Entrada::create([
                'albaran'     => $request->albaran,
                'usuario_id'  => auth()->id(),
                'peso_total'  => $request->peso,
                'estado'      => 'cerrado',
                'otros'       => $request->otros ?? null,
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
                'ubicacion_id'     => $request->ubicacion,
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
                    'ubicacion_id'     => $request->ubicacion,
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
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            $request->validate([
                'fabricante' => 'required|string|max:255',
                'albaran' => 'required|string|min:5|max:15|alpha_num',
                'peso_total' => 'required|numeric|min:1',
            ]);

            $entrada->update([
                'fabricante' => $request->fabricante,
                'albaran' => $request->albaran,
                'peso_total' => $request->peso_total,
            ]);
            DB::commit();
            return redirect()->route('entradas.index')->with('success', 'Entrada de material actualizada correctamente.');
        } catch (ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    public function cerrar($id)
    {
        DB::transaction(function () use ($id) {
            $entrada = Entrada::with('pedido')->lockForUpdate()->findOrFail($id);

            if ($entrada->estado === 'cerrado') {
                throw new \RuntimeException('Este albarán ya está cerrado.');
            }

            $entrada->estado = 'cerrado';
            $entrada->save();

            // Obtenemos el producto asociado a esta entrada
            $producto = $entrada->productos()->first(); // Asume que solo hay uno

            if (!$producto) {
                throw new \RuntimeException('No hay producto vinculado a esta entrada.');
            }

            $productoBaseId = $producto->producto_base_id;
            $pedido         = $entrada->pedido()->with('productos')->lockForUpdate()->first();

            // Buscar la cantidad pedida desde la tabla pivote
            $pivot = $pedido->productos->firstWhere('id', $productoBaseId)?->pivot;

            if (!$pivot) {
                throw new \RuntimeException('Producto no encontrado en el pedido.');
            }

            $cantidadPedida = $pivot->cantidad;

            // Calcular peso total recepcionado de este producto base
            $pesoRecepcionado = Producto::where('producto_base_id', $productoBaseId)
                ->whereHas('entrada', fn($q) => $q->where('pedido_id', $pedido->id))
                ->sum('peso_inicial');

            // Determinar estado
            if ($pesoRecepcionado >= $cantidadPedida * 0.9) {
                $estado = 'completado';
            } elseif ($pesoRecepcionado > 0) {
                $estado = 'parcial';
            } else {
                $estado = 'pendiente';
            }

            // Actualizar campos en la tabla pivote
            $pedido->productos()->updateExistingPivot($productoBaseId, [
                'cantidad_recepcionada' => $pesoRecepcionado,
                'estado' => $estado,
            ]);

            // Marcar los movimientos como completados si están relacionados
            $pedido->movimientos()
                ->where('estado', '!=', 'completado')
                ->lockForUpdate()
                ->update([
                    'estado' => 'completado',
                    'ejecutado_por' => auth()->id(),
                    'fecha_ejecucion' => now(),
                ]);
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
