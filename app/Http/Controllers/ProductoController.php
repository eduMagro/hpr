<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\Fabricante;
use Illuminate\Http\Request;
use App\Models\ProductoCodigo;
use App\Models\ProductoBase;
use App\Models\Obra;
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

        if ($request->filled('albaran')) {
            $albaran = $request->albaran;
            $query->whereHas('entrada', function ($q) use ($albaran) {
                $q->where('albaran', 'like', "%{$albaran}%");
            });
        }

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('nave_id') && is_numeric($request->nave_id)) {
            $query->where('obra_id', $request->nave_id);
        }


        if ($request->filled('fabricante')) {
            $fabricante = $request->fabricante;

            $query->where(function ($q) use ($fabricante) {
                if (is_numeric($fabricante)) {
                    $q->where('fabricante_id', (int) $fabricante);
                } else {
                    // buscar por nombre del fabricante (contiene)
                    $q->whereHas('fabricante', function ($fq) use ($fabricante) {
                        $fq->where('nombre', 'like', '%' . $fabricante . '%');
                    });
                }
            });
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
        if ($request->filled('ubicacion')) {
            $query->whereHas('ubicacion', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->ubicacion . '%');
            });
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
            'fabricante',
            'producto_base_id',
            'n_colada',
            'n_paquete',
            'peso_inicial',
            'peso_stock',
            'estado',
            'ubicacion',
            'created_at',
        ];

        $sort = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';
        }
        // Ordenar por nombre del fabricante
        if ($sort === 'fabricante') {
            $query->leftJoin('fabricantes', 'productos.fabricante_id', '=', 'fabricantes.id')
                ->orderBy('fabricantes.nombre', $order)
                ->select('productos.*'); // importante para evitar conflictos en las columnas
        } elseif ($sort === 'nave') {
            $query->leftJoin('obras', 'productos.obra_id', '=', 'obras.id')
                ->orderBy('obras.obra', $order)
                ->select('productos.*');
        } else {
            $query->orderBy($sort, $order);
        }
        return $query;
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
        if ($request->filled('nave_id')) {
            $obra = \App\Models\Obra::find($request->nave_id);
            $filtros[] = 'Nave: <strong>' . e($obra?->obra ?? 'ID ' . $request->nave_id) . '</strong>';
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
        if ($request->filled('ubicacion')) {
            $filtros[] = 'Ubicación: <strong>' . e($request->ubicacion) . '</strong>';
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
                'created_at' => 'Fecha de Creación',
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
            'maquina:id,nombre',
            'obra:id,obra'
        ])->select([
            'id',
            'codigo',
            'obra_id',
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
            'nave' => $this->getOrdenamiento('nave', 'Nave'),
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
            'created_at'      => $this->getOrdenamiento('created_at', 'Fecha de Creación'),
        ];

        // Si no se está filtrando por estado ni código, excluir consumido/fabricando
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        } elseif (
            !$request->filled('codigo') &&
            !$request->filled('id') &&
            !$request->boolean('mostrar_todos')
        ) {
            $query->whereNotIn('estado', ['consumido', 'fabricando']);
        }


        $totalPesoInicial = (clone $query)->sum('peso_inicial');
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
        $navesSelect = Obra::whereHas('cliente', function ($q) {
            $q->whereRaw("UPPER(empresa) LIKE '%PACO REYES%'");
        })
            ->orderBy('obra')
            ->pluck('obra', 'id')   // ['id' => 'Obra']
            ->toArray();
        return view('productos.index', compact('registrosProductos', 'productosBase', 'filtrosActivos', 'ordenables', 'totalPesoInicial', 'navesSelect'));
    }

    public function GenerarYExportar(Request $request)
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
                'codigo' => ['required', 'string', 'regex:/^MP.*/i', 'max:255'],
                'fabricante_id'      => 'nullable|exists:fabricantes,id',
                'producto_base_id'   => 'nullable|exists:productos_base,id',
                'nombre'             => 'nullable|string|max:255',
                'n_colada'           => 'required|string|max:255',
                'n_paquete'          => 'required|string|max:255',
                'peso_inicial'       => 'required|numeric|between:0,9999999.99',
                'ubicacion_id'       => 'nullable|integer|exists:ubicaciones,id',
                'maquina_id'         => 'nullable|integer|exists:maquinas,id',
                'estado'             => 'nullable|string|max:50',
                'otros'              => 'nullable|string|max:255',
            ], [
                'codigo.required' => 'El código es obligatorio.',
                'codigo.regex'    => 'El código debe empezar por "MP".',
                'fabricante_id.exists'     => 'El fabricante seleccionado no es válido.',
                'producto_base_id.exists'  => 'El producto base seleccionado no es válido.',
                'peso_inicial.*'           => 'El peso inicial debe ser un número válido mayor que 0.',
                'ubicacion_id.exists'      => 'La ubicación seleccionada no es válida.',
                'maquina_id.exists'        => 'La máquina seleccionada no es válida.',
            ]);

            $validated['updated_by'] = auth()->id();

            // Solo si se envió producto_base_id
            if (!empty($validated['producto_base_id'])) {
                $productoBase = ProductoBase::find($validated['producto_base_id']);
                if (!$productoBase) {
                    throw ValidationException::withMessages([
                        'producto_base_id' => 'No se ha encontrado el producto base seleccionado.',
                    ]);
                }

                $validated['tipo']     = strtoupper($productoBase->tipo);
                $validated['diametro'] = $productoBase->diametro;
                $validated['longitud'] = $productoBase->longitud;
            }

            if (isset($validated['estado']) && strtoupper($validated['estado']) === 'CONSUMIDO' && $producto->estado !== 'CONSUMIDO') {
                $this->marcarComoConsumido($producto);
            }

            $producto->update($validated);

            DB::commit();

            // 👇 Si es AJAX, respondemos JSON
            if ($request->wantsJson()) {
                return response()->json(['ok' => true, 'producto' => $producto]);
            }

            return redirect()->route('productos.index')->with('success', 'Producto actualizado con éxito.');
        } catch (ValidationException $ve) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['error' => $ve->errors()], 422);
            }
            $errores = collect($ve->errors())
                ->flatten()
                ->map(fn($msg) => '• ' . $msg)
                ->implode("\n");
            return redirect()->back()->with('error', $errores)->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->with('error', 'Error inesperado: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function editarUbicacionInventario(Request $request, $codigo)
    {
        try {
            // 1️⃣ Validación
            $validated = $request->validate([
                'ubicacion_id' => 'required|exists:ubicaciones,id',
            ], [
                'ubicacion_id.required' => 'Debes indicar una ubicación.',
                'ubicacion_id.exists'   => 'La ubicación seleccionada no existe.',
            ]);

            // 2️⃣ Buscar producto por código
            $producto = Producto::where('codigo', $codigo)->firstOrFail();

            // 3️⃣ Actualizar ubicación
            $producto->ubicacion_id = $validated['ubicacion_id'];
            $producto->save();

            // 4️⃣ Respuesta JSON
            return response()->json([
                'success'  => true,
                'message'  => "Producto {$producto->codigo} reasignado correctamente a la ubicación {$producto->ubicacion_id}.",
                'producto' => [
                    'id'           => $producto->id,
                    'codigo'       => $producto->codigo,
                    'ubicacion_id' => $producto->ubicacion_id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reasignar producto: ' . $e->getMessage(),
            ], 500);
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

    public function consumir(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $modo = $request->get('modo'); // 'total' o 'parcial'

        if ($modo === 'total') {
            // ✅ Consumir todo y limpiar ubicación/máquina
            $this->marcarComoConsumido($producto);
        } elseif ($modo === 'parcial') {
            // ✅ Validar entrada
            $request->validate([
                'kgs' => ['required', 'numeric', 'min:1'],
            ]);

            $kgs = (float) $request->get('kgs');

            if ($kgs > $producto->peso_stock) {
                return back()->with('error', '❌ No puedes consumir más de lo disponible en stock.');
            }

            // Restar kilos
            $producto->peso_stock -= $kgs;

            // Si llega a 0, marcar como consumido (también limpia ubicación/máquina)
            if ($producto->peso_stock <= 0) {
                $this->marcarComoConsumido($producto);
            }
        } else {
            return back()->with('error', '❌ Modo de consumo no válido.');
        }

        $producto->save();

        return back()->with('success', '✅ Producto actualizado correctamente.');
    }

    private function marcarComoConsumido(Producto $producto)
    {
        $producto->peso_stock     = 0;
        $producto->estado         = 'consumido';
        $producto->fecha_consumido = now();
        $producto->consumido_by    = auth()->id();
        $producto->ubicacion_id    = null; // 👈 limpiar ubicación
        $producto->maquina_id      = null; // (si aplica)
    }


    //------------------------------------------------------------------------------------ DESTROY
    public function destroy(Producto $producto)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('productos.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        \Log::info('Borrando producto ' . ($producto->codigo ?? ('ID ' . $producto->id)) . ' por el usuario ' . (auth()->user()->nombre_completo ?? 'desconocido'));
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
