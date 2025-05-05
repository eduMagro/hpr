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
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
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
        try {
            // Inicializa la consulta de productos con sus relaciones necesarias
            $query = Entrada::with(['ubicacion', 'user', 'productos']);

            // Aplica los filtros mediante un método separado
            $query = $this->aplicarFiltros($query, $request);

            // Obtener las entradas paginadas, ordenadas por fecha de creación
            $entradas = $query->orderBy('created_at', 'desc')->paginate(10);

            $elementosPendientes = Elemento::with('maquina')
                ->where('estado', 'pendiente')
                ->get()
                ->filter(fn($e) => $e->maquina && $e->maquina->tipo && $e->diametro)
                ->groupBy(fn($e) => $e->maquina->tipo_material . '-' . intval($e->diametro))
                ->map(fn($group) => $group->sum('peso'));

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

            $stockData = Producto::with('productoBase')
                ->where('estado', 'almacenado')
                ->get()
                ->groupBy(fn($p) => $p->productoBase->diametro)
                ->map(function ($group) {
                    $encarretado = $group->filter(fn($p) => $p->productoBase->tipo === 'encarretado')->sum('peso_stock');
                    $barras = $group->filter(fn($p) => $p->productoBase->tipo === 'barra');
                    $barrasPorLongitud = $barras->groupBy(fn($p) => $p->productoBase->longitud)
                        ->map(fn($items) => $items->sum('peso_stock'));

                    $barrasTotal = $barrasPorLongitud->sum();
                    $total = $barrasTotal + $encarretado;

                    return [
                        'encarretado' => $encarretado,
                        'barras' => $barrasPorLongitud,
                        'barras_total' => $barrasTotal,
                        'total' => $total,
                    ];
                })->sortKeys();

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

            // Devolver la vista con las entradas
            return view('entradas.index', compact('entradas', 'stockData', 'comparativa'));
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

    // Mostrar el formulario de creación
    public function create()
    {
        $ubicaciones = Ubicacion::all();
        $usuarios = User::all();
        $productosBase = ProductoBase::orderBy('tipo')->orderBy('diametro')->orderBy('longitud')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('entradas.create', compact('ubicaciones', 'usuarios', 'productosBase', 'proveedores'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'proveedor_id' => 'required|exists:proveedores,id',
                'albaran' => 'required|string|min:1|max:30',
                'producto_base_id' => 'required|exists:productos_base,id',
                'n_colada' => 'required|string|max:50',
                'n_paquete' => 'required|string|max:50',
                'peso' => 'required|numeric|min:1',
                'ubicacion' => 'nullable|integer|exists:ubicaciones,id',
                'otros' => 'nullable|string|max:255',
            ], [
                'proveedor_id.required' => 'El proveedor es obligatorio.',
                'albaran.required' => 'El número de albarán es obligatorio.',
                'producto_base_id.required' => 'Debes seleccionar un producto base.',
                'producto_base_id.exists' => 'El producto base no es válido.',
                'n_colada.required' => 'El número de colada es obligatorio.',
                'n_paquete.required' => 'El número de paquete es obligatorio.',
                'peso.required' => 'El peso es obligatorio.',
            ]);

            $productoBase = ProductoBase::findOrFail($request->producto_base_id);

            if (!$productoBase) {
                throw new \Exception('No se encontró un producto base con las características proporcionadas.');
            }

            // Crear entrada
            $entrada = Entrada::create([
                'albaran' => $request->albaran,
                'usuario_id' => auth()->id(),
                'otros' => $request->otros ?? null,
            ]);

            // Crear el producto
            $producto = Producto::create([
                'producto_base_id' => $request->producto_base_id,
                'proveedor_id'     => $request->proveedor_id,
                'n_colada'         => $request->n_colada,
                'n_paquete'        => $request->n_paquete,
                'peso_inicial'     => $request->peso,
                'peso_stock'       => $request->peso,
                'estado'           => 'almacenado',
                'ubicacion_id'     => $request->ubicacion,
                'maquina_id'       => null,
                'otros'            => 'Alta manual. Fabricante: ' . $request->fabricante,
            ]);
            // Relación entrada-producto
            EntradaProducto::create([
                'entrada_id' => $entrada->id,
                'producto_id' => $producto->id,
                'ubicacion_id' => $request->ubicacion,
                'users_id' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('entradas.index')->with('success', 'Entrada registrada correctamente.');
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
