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
use App\Models\Maquina;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    //------------------------------------------------------------------------------------ FILTROS
    private function aplicarFiltros($query, Request $request)
    {
        // ID
        if ($request->filled('id')) {
            $raw = trim((string) $request->id);
            $query->whereRaw('CAST(productos.id AS CHAR) LIKE ?', ['%' . $raw . '%']);
        }

        // Entrada ID (numérico)
        if ($request->filled('entrada_id') && is_numeric($request->entrada_id)) {
            $query->where('entrada_id', (int) $request->entrada_id);
        }

        // Albarán (relación entrada) - contains
        if ($request->filled('albaran')) {
            $albaran = trim($request->albaran);
            $query->whereHas('entrada', function ($q) use ($albaran) {
                $q->whereRaw('LOWER(albaran) LIKE ?', ['%' . mb_strtolower($albaran, 'UTF-8') . '%']);
            });
        }

        // Código - contains
        if ($request->filled('codigo')) {
            $codigo = trim($request->codigo);
            $query->whereRaw('LOWER(codigo) LIKE ?', ['%' . mb_strtolower($codigo, 'UTF-8') . '%']);
        }

        // Nave (obra_id exacto desde select)
        if ($request->filled('nave_id') && is_numeric($request->nave_id)) {
            $query->where('obra_id', (int) $request->nave_id);
        }

        // Fabricante por NOMBRE (input de texto) - contains
        if ($request->filled('fabricante')) {
            $buscar = trim($request->fabricante);
            $query->whereHas('fabricante', function ($q) use ($buscar) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . mb_strtolower($buscar, 'UTF-8') . '%']);
            });
        }

        // Producto Base ID (select exacto)
        if ($request->filled('producto_base_id')) {
            $query->where('producto_base_id', (int) $request->producto_base_id);
        }

        // Tipo (ProductoBase) - contains
        if ($request->filled('tipo')) {
            $buscar = trim($request->tipo);
            $query->whereHas('productoBase', function ($q) use ($buscar) {
                $q->whereRaw('LOWER(tipo) LIKE ?', ['%' . mb_strtolower($buscar, 'UTF-8') . '%']);
            });
        }

        // Diámetro (ProductoBase) - contains (permite 10, 10., 10 mm parciales)
        if ($request->filled('diametro')) {
            $buscar = trim($request->diametro);
            $query->whereHas('productoBase', function ($q) use ($buscar) {
                $q->whereRaw('LOWER(diametro) LIKE ?', ['%' . mb_strtolower($buscar, 'UTF-8') . '%']);
            });
        }

        // Longitud (ProductoBase) - contains
        if ($request->filled('longitud')) {
            $buscar = trim($request->longitud);
            $query->whereHas('productoBase', function ($q) use ($buscar) {
                $q->whereRaw('LOWER(longitud) LIKE ?', ['%' . mb_strtolower($buscar, 'UTF-8') . '%']);
            });
        }

        // Nº Colada - contains
        if ($request->filled('n_colada')) {
            $buscar = trim($request->n_colada);
            $query->whereRaw('LOWER(n_colada) LIKE ?', ['%' . mb_strtolower($buscar, 'UTF-8') . '%']);
        }

        // Nº Paquete - contains
        if ($request->filled('n_paquete')) {
            $buscar = trim($request->n_paquete);
            $query->whereRaw('LOWER(n_paquete) LIKE ?', ['%' . mb_strtolower($buscar, 'UTF-8') . '%']);
        }

        // Estado - exacto (ya controlas luego la lógica de “mostrar_todos”)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Ubicación por nombre - contains
        if ($request->filled('ubicacion')) {
            $buscar = trim($request->ubicacion);
            $query->whereHas('ubicacion', function ($q) use ($buscar) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . mb_strtolower($buscar, 'UTF-8') . '%']);
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
            'tipo',
            'diametro',
            'longitud',
            'n_colada',
            'n_paquete',
            'peso_inicial',
            'peso_stock',
            'estado',
            'ubicacion',
            'nave',
            'entrada_id',
            'created_at',
        ];

        $sort  = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';
        }

        if ($sort === 'fabricante') {
            $query->leftJoin('fabricantes', 'productos.fabricante_id', '=', 'fabricantes.id')
                ->orderBy('fabricantes.nombre', $order)
                ->select('productos.*');
        } elseif ($sort === 'nave') {
            $query->leftJoin('obras', 'productos.obra_id', '=', 'obras.id')
                ->orderBy('obras.obra', $order)
                ->select('productos.*');
        } elseif (in_array($sort, ['tipo', 'diametro', 'longitud'], true)) {
            // Ordenar por campos del producto base
            $query->leftJoin('productos_base as pb', 'productos.producto_base_id', '=', 'pb.id')
                ->orderBy("pb.$sort", $order)
                ->select('productos.*');
        } elseif ($sort === 'ubicacion') {
            $query->leftJoin('ubicaciones', 'productos.ubicacion_id', '=', 'ubicaciones.id')
                ->orderBy('ubicaciones.nombre', $order)
                ->select('productos.*');
        } elseif ($sort === 'entrada_id') {
            // En la tabla lo llamas “Albarán”: ordena por entradas.albaran
            $query->leftJoin('entradas', 'productos.entrada_id', '=', 'entradas.id')
                ->orderBy('entradas.albaran', $order)
                ->select('productos.*');
        } else {
            $query->orderBy($sort, $order);
        }

        return $query;
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id'))          $filtros[] = 'ID: <strong>' . e($request->id) . '</strong>';
        if ($request->filled('entrada_id'))  $filtros[] = 'Entrada ID: <strong>' . e($request->entrada_id) . '</strong>';
        if ($request->filled('codigo'))      $filtros[] = 'Código: <strong>' . e($request->codigo) . '</strong>';

        if ($request->filled('nave_id')) {
            $obra = \App\Models\Obra::find($request->nave_id);
            $filtros[] = 'Nave: <strong>' . e($obra?->obra ?? 'ID ' . $request->nave_id) . '</strong>';
        }

        if ($request->filled('fabricante'))  $filtros[] = 'Fabricante: <strong>' . e($request->fabricante) . '</strong>';
        if ($request->filled('tipo'))        $filtros[] = 'Tipo: <strong>' . e($request->tipo) . '</strong>';
        if ($request->filled('diametro'))    $filtros[] = 'Diámetro: <strong>' . e($request->diametro) . '</strong>';
        if ($request->filled('longitud'))    $filtros[] = 'Longitud: <strong>' . e($request->longitud) . '</strong>';
        if ($request->filled('n_colada'))    $filtros[] = 'N.º Colada: <strong>' . e($request->n_colada) . '</strong>';
        if ($request->filled('n_paquete'))   $filtros[] = 'N.º Paquete: <strong>' . e($request->n_paquete) . '</strong>';
        if ($request->filled('estado'))      $filtros[] = 'Estado: <strong>' . e($request->estado) . '</strong>';
        if ($request->filled('ubicacion'))   $filtros[] = 'Ubicación: <strong>' . e($request->ubicacion) . '</strong>';

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
                'nave' => 'Nave',
                'created_at' => 'Fecha de Creación',
            ];
            $columna = $request->sort;
            $nombre  = $nombresBonitos[$columna] ?? ucfirst($columna);
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
            'obra:id,obra',
            'consumidoPor'
        ]);

        // Aplicar filtros
        $query = $this->aplicarFiltros($query, $request);

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

        // ✅ CALCULAR TOTAL ANTES DE ORDENAMIENTO Y DISTINCT
        $totalPesoInicial = (clone $query)->sum('peso_inicial');

        // Aplicar ordenamiento y distinct para la paginación
        $query = $this->aplicarOrdenamiento($query, $request);
        $query->distinct('productos.id');

        $filtrosActivos = $this->filtrosActivos($request);
        $ordenables = [
            'id'             => $this->getOrdenamiento('id', 'ID Materia Prima'),
            'entrada_id'     => $this->getOrdenamiento('entrada_id', 'Albarán'),
            'codigo'         => $this->getOrdenamiento('codigo', 'Código'),
            'nave'           => $this->getOrdenamiento('nave', 'Nave'),
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
            'created_at'     => $this->getOrdenamiento('created_at', 'Fecha de Creación'),
        ];
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

        $maquinasDisponibles = Maquina::select('id', 'nombre', 'codigo', 'diametro_min', 'diametro_max', 'obra_id')
            ->orderBy('nombre')
            ->get();

        // Ubicaciones por sector para el modal de movimiento libre
        // Prioridad: 1) Obra de la máquina del usuario, 2) Nave A por defecto
        $codigoAlmacen = null;
        $usuario = auth()->user();

        // Si el usuario tiene máquina asignada, usar la obra de esa máquina
        if ($usuario && $usuario->maquina && $usuario->maquina->obra) {
            $codigoAlmacen = Ubicacion::codigoDesdeNombreNave($usuario->maquina->obra->obra);
        }

        // Si no se obtuvo código, usar Nave A por defecto (tiene más ubicaciones)
        if (!$codigoAlmacen) {
            $codigoAlmacen = '0A'; // Nave A por defecto
        }

        $ubicacionesPorSector = collect();
        $sectores = [];
        $sectorPorDefecto = null;

        $ubicacionesPorSector = Ubicacion::where('almacen', $codigoAlmacen)
            ->orderBy('sector', 'asc')
            ->orderBy('ubicacion', 'asc')
            ->get()
            ->map(function ($ubicacion) {
                $ubicacion->nombre_sin_prefijo = Str::after($ubicacion->nombre, 'Almacén ');
                return $ubicacion;
            })
            ->groupBy('sector');

        $sectores = $ubicacionesPorSector->keys()->toArray();
        $sectorPorDefecto = !empty($sectores) ? $sectores[0] : null;

        return view('productos.index', compact('registrosProductos', 'productosBase', 'filtrosActivos', 'ordenables', 'totalPesoInicial', 'navesSelect', 'maquinasDisponibles', 'ubicacionesPorSector', 'sectores', 'sectorPorDefecto'));
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

    public function GenerarYObtenerDatos(Request $request)
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
                $nuevosCodigos[] = ['codigo' => $codigo];
            }

            // Actualizar el contador
            $contador->ultimo_numero = $hasta;
            $contador->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'codigos' => $nuevosCodigos,
                'cantidad' => count($nuevosCodigos)
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Error al generar los códigos: ' . $e->getMessage()
            ], 500);
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
                'obra_id'            => 'nullable|integer|exists:obras,id',
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
                'obra_id.exists'           => 'La nave seleccionada no es válida.',
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

    public function restablecerDesdeInventario(Request $request, $codigo)
    {
        try {
            $validated = $request->validate([
                'ubicacion_id' => 'required|exists:ubicaciones,id',
                'peso_stock'   => 'required|numeric|min:0',
            ], [
                'ubicacion_id.required' => 'Debes indicar una ubicación.',
                'ubicacion_id.exists'   => 'La ubicación seleccionada no existe.',
                'peso_stock.required'   => 'Debes indicar el peso en stock.',
                'peso_stock.numeric'    => 'El peso debe ser numérico.',
                'peso_stock.min'        => 'El peso no puede ser negativo.',
            ]);

            $producto = Producto::where('codigo', $codigo)->firstOrFail();
            $pesoInicial = $producto->peso_inicial ?? $validated['peso_stock'];
            $pesoStock   = min($validated['peso_stock'], $pesoInicial);

            $producto->estado          = 'almacenado';
            $producto->peso_stock      = $pesoStock;
            $producto->ubicacion_id    = $validated['ubicacion_id'];
            $producto->fecha_consumido = null;
            $producto->consumido_by    = null;
            $producto->save();

            return response()->json([
                'success'  => true,
                'message'  => "Producto {$producto->codigo} restablecido a almacenado en la ubicación {$producto->ubicacion_id}.",
                'producto' => [
                    'id'           => $producto->id,
                    'codigo'       => $producto->codigo,
                    'ubicacion_id' => $producto->ubicacion_id,
                    'peso_stock'   => $producto->peso_stock,
                    'estado'       => $producto->estado,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restablecer producto: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function liberarFabricandoInventario(Request $request, $codigo)
    {
        try {
            $validated = $request->validate([
                'ubicacion_id' => 'required|exists:ubicaciones,id',
            ], [
                'ubicacion_id.required' => 'Debes indicar una ubicación.',
                'ubicacion_id.exists'   => 'La ubicación seleccionada no existe.',
            ]);

            $producto = Producto::where('codigo', $codigo)->firstOrFail();
            $producto->maquina_id    = null;
            $producto->estado        = 'almacenado';
            $producto->ubicacion_id  = $validated['ubicacion_id'];
            $producto->save();

            return response()->json([
                'success'  => true,
                'message'  => "Producto {$producto->codigo} liberado de máquina y asignado a la ubicación {$producto->ubicacion_id}.",
                'producto' => [
                    'id'           => $producto->id,
                    'codigo'       => $producto->codigo,
                    'ubicacion_id' => $producto->ubicacion_id,
                    'estado'       => $producto->estado,
                    'maquina_id'   => $producto->maquina_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al liberar producto de máquina: ' . $e->getMessage(),
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
        $isAjax = $request->ajax() || $request->wantsJson();

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
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes consumir más de lo disponible en stock.'
                    ], 400);
                }
                return back()->with('error', '❌ No puedes consumir más de lo disponible en stock.');
            }

            // Restar kilos
            $producto->peso_stock -= $kgs;

            // Si llega a 0, marcar como consumido (también limpia ubicación/máquina)
            if ($producto->peso_stock <= 0) {
                $this->marcarComoConsumido($producto);
            }
        } else {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modo de consumo no válido.'
                ], 400);
            }
            return back()->with('error', '❌ Modo de consumo no válido.');
        }

        $producto->save();

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'Producto consumido correctamente.',
                'producto' => [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'estado' => $producto->estado,
                ]
            ]);
        }

        return back()->with('success', '✅ Producto actualizado correctamente.');
    }

    public function consumirLoteAjax(Request $request)
    {
        $codigos = $request->input('codigos', []);
        if (!is_array($codigos)) {
            return response()->json([
                'ok' => false,
                'message' => 'Listado de códigos inválido.',
            ], 422);
        }

        $codigosLimpios = collect($codigos)
            ->filter()
            ->map(fn ($c) => trim((string) $c))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($codigosLimpios)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se recibieron códigos para consumir.',
            ], 422);
        }

        $productos = Producto::whereIn('codigo', $codigosLimpios)->get();

        $consumidos = [];
        $errores = [];

        DB::beginTransaction();
        foreach ($productos as $producto) {
            try {
                $this->marcarComoConsumido($producto);
                $producto->save();
                $consumidos[] = $producto->codigo;
            } catch (\Throwable $e) {
                $errores[] = [
                    'codigo' => $producto->codigo,
                    'error' => $e->getMessage(),
                ];
            }
        }
        DB::commit();

        $faltantes = array_values(array_diff($codigosLimpios, $consumidos));
        $ok = empty($errores) && empty($faltantes);

        return response()->json([
            'ok' => $ok,
            'consumidos' => $consumidos,
            'faltantes' => $faltantes,
            'errores' => $errores,
            'message' => $ok ? 'Materiales consumidos correctamente.' : 'Consumo completado parcialmente.',
        ], $ok ? 200 : 207);
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
        Log::info('Borrando producto ' . ($producto->codigo ?? ('ID ' . $producto->id)) . ' por el usuario ' . (auth()->user()->nombre_completo ?? 'desconocido'));
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }

    //------------------------------------------------------------------------------------ SUGERENCIAS PARA GRÚA (API)
    /**
     * Obtiene productos sugeridos por diámetro y longitud para el modal de escaneo de grúa
     */
    public function sugerenciasPorDiametroLongitud(Request $request)
    {
        $diametro = (int) $request->input('diametro', 0);
        $longitud = (float) $request->input('longitud', 0); // En metros

        if ($diametro <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar un diámetro válido.'
            ], 400);
        }

        // Buscar productos tipo barra con ese diámetro y longitud, con stock disponible
        $query = Producto::with(['productoBase', 'ubicacion'])
            ->whereHas('productoBase', function ($q) use ($diametro, $longitud) {
                $q->where('tipo', 'barra')
                    ->where('diametro', $diametro);

                // Si se especifica longitud, filtrar por ella (tolerancia de 0.1m)
                if ($longitud > 0) {
                    $q->whereBetween('longitud', [$longitud - 0.1, $longitud + 0.1]);
                }
            })
            ->where('peso_stock', '>', 0)
            ->whereNotIn('estado', ['consumido', 'fabricando'])
            ->orderByDesc('peso_stock')
            ->limit(5);

        $productos = $query->get();

        return response()->json([
            'success' => true,
            'productos' => $productos->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'diametro' => $p->productoBase->diametro ?? $p->diametro,
                    'longitud' => $p->productoBase->longitud ?? $p->longitud,
                    'peso_stock' => round((float) $p->peso_stock, 2),
                    'n_colada' => $p->n_colada,
                    'ubicacion' => $p->ubicacion?->nombre ?? 'Sin ubicación',
                    'ubicacion_id' => $p->ubicacion_id,
                ];
            }),
            'count' => $productos->count(),
        ]);
    }

    //------------------------------------------------------------------------------------ BUSCAR POR CÓDIGO (API)
    /**
     * Busca un producto por su código (para escaneo de QR en grúa)
     */
    public function buscarPorCodigo(Request $request)
    {
        $codigo = trim($request->input('codigo', ''));

        if (empty($codigo)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar un código de producto.'
            ], 400);
        }

        $producto = Producto::with('productoBase')
            ->where('codigo', $codigo)
            ->first();

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => "No se encontró ningún producto con código: {$codigo}"
            ], 404);
        }

        if ($producto->estado === 'consumido') {
            return response()->json([
                'success' => false,
                'message' => "El producto {$codigo} ya está consumido."
            ], 400);
        }

        return response()->json([
            'id' => $producto->id,
            'codigo' => $producto->codigo,
            'tipo' => $producto->productoBase->tipo ?? null,
            'diametro' => $producto->productoBase->diametro ?? $producto->diametro ?? null,
            'longitud' => $producto->productoBase->longitud ?? $producto->longitud ?? null,
            'peso_stock' => (float) $producto->peso_stock,
            'peso_inicial' => (float) ($producto->peso_inicial ?? $producto->peso_stock),
            'n_colada' => $producto->n_colada,
            'estado' => $producto->estado,
            'ubicacion_id' => $producto->ubicacion_id,
        ]);
    }
}
