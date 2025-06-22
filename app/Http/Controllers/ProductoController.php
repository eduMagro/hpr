<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\Fabricante;
use Illuminate\Http\Request;
use App\Models\ProductoCodigo;
use App\Models\ProductoBase;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
use App\Exports\ProductosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProductoController extends Controller
{
    //------------------------------------------------------------------------------------ FILTROS
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('id') && is_numeric($request->id)) {
            $query->where('id', (int) $request->id);
        }

        if ($request->filled('entrada_id') && is_numeric($request->entrada_id)) {
            $query->where('entrada_id', (int) $request->entrada_id);
        }

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('fabricante') && is_numeric($request->fabricante)) {
            $query->where('fabricante_id', (int) $request->fabricante);
        }

        if ($request->filled('tipo')) {
            $query->whereHas('productoBase', function ($q) use ($request) {
                $q->where('tipo', $request->tipo);
            });
        }

        if ($request->filled('diametro')) {
            $query->whereHas('productoBase', function ($q) use ($request) {
                $q->where('diametro', $request->diametro);
            });
        }

        if ($request->filled('longitud')) {
            $query->whereHas('productoBase', function ($q) use ($request) {
                $q->where('longitud', $request->longitud);
            });
        }

        if ($request->filled('n_colada')) {
            $query->where('n_colada', $request->n_colada);
        }

        if ($request->filled('n_paquete')) {
            $query->where('n_paquete', $request->n_paquete);
        }

        if ($request->filled('estado')) {
            $query->where('estado', 'like', '%' . $request->estado . '%');
        }

        return $query;
    }
    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = '';
        if ($isSorted) {
            $icon = $currentOrder === 'asc'
                ? '▲' // flecha hacia arriba
                : '▼'; // flecha hacia abajo
        } else {
            $icon = '⇅'; // símbolo de orden genérico
        }

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }
    private function aplicarOrdenamiento($query, Request $request)
    {
        $columnasPermitidas = [
            'id',
            'codigo',
            'fabricante_id',
            'producto_base_id',
            'n_colada',
            'n_paquete',
            'peso_inicial',
            'peso_stock',
            'estado',
            'created_at',
        ];

        $sort = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';
        }

        return $query->orderBy($sort, $order);
    }
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id')) {
            $filtros[] = 'ID: <strong>' . e($request->id) . '</strong>';
        }

        if ($request->filled('entrada_id')) {
            $filtros[] = 'Entrada ID: <strong>' . e($request->entrada_id) . '</strong>';
        }

        if ($request->filled('codigo')) {
            $filtros[] = 'Código: <strong>' . e($request->codigo) . '</strong>';
        }

        if ($request->filled('fabricante')) {
            $filtros[] = 'Fabricante ID: <strong>' . e($request->fabricante) . '</strong>';
        }

        if ($request->filled('tipo')) {
            $filtros[] = 'Tipo: <strong>' . e($request->tipo) . '</strong>';
        }

        if ($request->filled('diametro')) {
            $filtros[] = 'Diámetro: <strong>' . e($request->diametro) . '</strong>';
        }

        if ($request->filled('longitud')) {
            $filtros[] = 'Longitud: <strong>' . e($request->longitud) . '</strong>';
        }

        if ($request->filled('n_colada')) {
            $filtros[] = 'N.º Colada: <strong>' . e($request->n_colada) . '</strong>';
        }

        if ($request->filled('n_paquete')) {
            $filtros[] = 'N.º Paquete: <strong>' . e($request->n_paquete) . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . e($request->estado) . '</strong>';
        }

        if ($request->filled('sort')) {
            $orden = $request->input('order') === 'asc' ? 'ascendente' : 'descendente';

            $nombresBonitos = [
                'id' => 'ID Materia Prima',
                'entrada_id' => 'Albarán',
                'codigo' => 'Código',
                'fabricante' => 'Fabricante',
                'tipo' => 'Tipo',
                'diametro' => 'Diámetro',
                'longitud' => 'Longitud',
                'n_colada' => 'Nº Colada',
                'n_paquete' => 'Nº Paquete',
                'peso_inicial' => 'Peso Inicial',
                'peso_stock' => 'Peso Stock',
                'estado' => 'Estado',
                'ubicacion' => 'Ubicación',
            ];

            $columna = $request->sort;
            $nombre = $nombresBonitos[$columna] ?? ucfirst($columna);

            $filtros[] = "Ordenado por <strong>$nombre</strong> en orden <strong>$orden</strong>";
        }


        return $filtros;
    }

    //------------------------------------------------------------------------------------ INDEX
    public function index(Request $request)
    {
        $query = Producto::with([
            'productoBase:id,tipo,diametro,longitud',
            'fabricante:id,nombre',
            'entrada:id,albaran',
            'ubicacion:id,nombre',
            'maquina:id,nombre'
        ])->select([
            'id',
            'codigo',
            'fabricante_id',
            'entrada_id',
            'producto_base_id',
            'n_colada',
            'n_paquete',
            'peso_inicial',
            'peso_stock',
            'estado',
            'ubicacion_id',
            'maquina_id',
            'created_at'
        ]);

        // Aplicar filtros y ordenamiento de forma segura
        $query = $this->aplicarFiltros($query, $request);
        $query = $this->aplicarOrdenamiento($query, $request);
        $filtrosActivos = $this->filtrosActivos($request);
        $ordenables = [
            'id'             => $this->getOrdenamiento('id', 'ID Materia Prima'),
            'entrada_id'     => $this->getOrdenamiento('entrada_id', 'Albarán'),
            'codigo'         => $this->getOrdenamiento('codigo', 'Código'),
            'fabricante'     => $this->getOrdenamiento('fabricante', 'Fabricante'),
            'tipo'           => $this->getOrdenamiento('tipo', 'Tipo'),
            'diametro'       => $this->getOrdenamiento('diametro', 'Diámetro'),
            'longitud'       => $this->getOrdenamiento('longitud', 'Longitud'),
            'n_colada'       => $this->getOrdenamiento('n_colada', 'Nº Colada'),
            'n_paquete'      => $this->getOrdenamiento('n_paquete', 'Nº Paquete'),
            'peso_inicial'   => $this->getOrdenamiento('peso_inicial', 'Peso Inicial'),
            'peso_stock'     => $this->getOrdenamiento('peso_stock', 'Peso Stock'),
            'estado'         => $this->getOrdenamiento('estado', 'Estado'),
            'ubicacion'      => $this->getOrdenamiento('ubicacion', 'Ubicación'),
        ];

        // Si no se está filtrando por estado ni código, excluir consumido/fabricando
        if (!$request->filled('estado') && !$request->filled('codigo')) {
            $query->whereNotIn('estado', ['consumido', 'fabricando']);
        }

        // Paginación segura
        $perPage = is_numeric($request->input('per_page')) ? max(5, min((int)$request->input('per_page'), 100)) : 10;
        $registrosProductos = $query->paginate($perPage)->appends($request->except('page'));

        // Lista cacheada de productos base
        $productosBase = Cache::rememberForever('productos_base_lista', function () {
            return ProductoBase::select('id', 'tipo', 'diametro', 'longitud')
                ->orderBy('tipo')
                ->orderBy('diametro')
                ->orderBy('longitud')
                ->get();
        });

        return view('productos.index', compact('registrosProductos', 'productosBase', 'filtrosActivos', 'ordenables'));
    }

    public function generarYExportar(Request $request)
    {
        $cantidad = intval($request->input('cantidad', 1));
        $anio = now()->format('y'); // Año en dos dígitos
        $mes = now()->format('m');  // Mes en dos dígitos
        $tipo = 'MP'; // fijo

        DB::beginTransaction();

        try {
            // Obtener o crear el contador del tipo, año y mes
            $contador = ProductoCodigo::lockForUpdate()->firstOrCreate(
                ['tipo' => $tipo, 'anio' => $anio, 'mes' => $mes],
                ['ultimo_numero' => 0]
            );

            $desde = $contador->ultimo_numero + 1;
            $hasta = $contador->ultimo_numero + $cantidad;

            // Generar los códigos en memoria
            $nuevosCodigos = [];
            for ($i = $desde; $i <= $hasta; $i++) {
                $numero = str_pad($i, 4, '0', STR_PAD_LEFT);
                $codigo = "$tipo$anio$mes$numero"; // Ahora incluye mes
                $nuevosCodigos[] = (object)['codigo' => $codigo];
            }

            // Actualizar el contador
            $contador->ultimo_numero = $hasta;
            $contador->save();

            DB::commit();
            $fecha = now()->format('Ymd_His');
            return Excel::download(new ProductosExport(collect($nuevosCodigos)), "codigos_MP_$fecha.xlsx");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al generar los códigos: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ SHOW
    // ProductoController.php
    public function show($id)
    {
        $detalles_producto = Producto::with([
            'fabricante',      // 👉 nombre del fabricante
            'productoBase'     // 👉 tipo, diámetro, longitud…
        ])->findOrFail($id);

        return view('productos.show', compact('detalles_producto'));
    }

    //------------------------------------------------------------------------------------ CREATE
    public function create()
    {
        return view('productos.create');
    }

    //------------------------------------------------------------------------------------ STORE
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        Producto::create($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

    //------------------------------------------------------------------------------------ EDIT
    public function edit(Producto $producto)
    {

        $ubicaciones = Ubicacion::all();
        $usuarios = User::all();
        $productosBase = ProductoBase::orderBy('tipo')->orderBy('diametro')->orderBy('longitud')->get();
        $fabricantes = Fabricante::orderBy('nombre')->get();
        return view('productos.edit', compact('producto', 'usuarios', 'productosBase', 'fabricantes'));
    }

    public function update(Request $request, Producto $producto)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'fabricante_id'      => 'required|exists:fabricantes,id',
                'producto_base_id'  => 'required|exists:productos_base,id',
                'nombre'            => 'nullable|string|max:255',
                'n_colada'          => 'required|string|max:255',
                'n_paquete'         => 'required|string|max:255',
                'peso_inicial'      => 'required|numeric|between:0,9999999.99',
                'ubicacion_id'      => 'nullable|integer|exists:ubicaciones,id',
                'maquina_id'        => 'nullable|integer|exists:maquinas,id',
                'estado'            => 'nullable|string|max:50',
                'otros'             => 'nullable|string|max:255',
            ], [
                'fabricante_id.required'      => 'El fabricante es obligatorio.',
                'fabricante_id.exists'        => 'El fabricante seleccionado no es válido.',
                'producto_base_id.required'  => 'El producto base es obligatorio.',
                'producto_base_id.exists'    => 'El producto base seleccionado no es válido.',
                'peso_inicial.*'             => 'El peso inicial debe ser un número válido mayor que 0.',
                'ubicacion_id.exists'        => 'La ubicación seleccionada no es válida.',
                'maquina_id.exists'          => 'La máquina seleccionada no es válida.',
            ]);
            $validated['updated_by'] = auth()->id();
            // Opcional: si necesitas reflejar los datos del producto base también en el producto
            $productoBase = ProductoBase::find($validated['producto_base_id']);

            if (!$productoBase) {
                throw ValidationException::withMessages([
                    'producto_base_id' => 'No se ha encontrado el producto base seleccionado.',
                ]);
            }

            // Si los campos existen en la tabla productos y quieres copiarlos:
            $validated['tipo']     = strtoupper($productoBase->tipo);
            $validated['diametro'] = $productoBase->diametro;
            $validated['longitud'] = $productoBase->longitud;

            if (isset($validated['estado'])) {
                $validated['estado'] = strtoupper($validated['estado']);
            }

            $producto->update($validated);

            DB::commit();
            return redirect()->route('productos.index')->with('success', 'Producto actualizado con éxito.');
        } catch (ValidationException $ve) {
            DB::rollBack();
            $errores = collect($ve->errors())
                ->flatten()
                ->map(fn($msg) => '• ' . $msg)
                ->implode("\n");

            return redirect()->back()->with('error', $errores)->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error inesperado: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function solicitarStock(Request $request)
    {
        // Aquí puedes obtener el diámetro o cualquier otro dato necesario de la solicitud
        $diametro = $request->input('diametro');

        // Lógica para manejar la solicitud (por ejemplo, registrar la solicitud, enviar un correo, etc.)
        // Ejemplo: registrar la solicitud
        // Solicitud::create(['diametro' => $diametro, 'estado' => 'pendiente']);

        // Redirigir con un mensaje de éxito
        return redirect()->back()->with('exito', 'La solicitud ha sido registrada exitosamente.');
    }

    public function consumir($id)
    {
        // 1. Buscar el producto
        $producto = Producto::findOrFail($id);

        // 2. Actualizar estado y limpiar ubicaciones
        $producto->estado        = 'consumido';
        $producto->ubicacion_id  = null;
        $producto->maquina_id    = null;
        $producto->peso_stock    = 0;

        // 3. Guardar cambios
        $producto->save();

        // 4. Regresar o redirigir con un mensaje (opcional)
        return redirect()->back()->with('success', 'Producto marcado como consumido');
    }

    //------------------------------------------------------------------------------------ DESTROY
    public function destroy(Producto $producto)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('productos.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
