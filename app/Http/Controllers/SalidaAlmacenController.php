<?php

namespace App\Http\Controllers;

use App\Models\SalidaAlmacen;
use App\Models\SalidaAlmacenCliente;
use App\Models\SalidaProducto;
use App\Models\EmpresaTransporte;
use App\Models\Camion;
use App\Models\ProductoBase;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SalidaAlmacenController extends Controller
{
    private function aplicarFiltros($query, Request $request): void
    {
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('fecha_salida')) {
            $query->whereDate('fecha_salida', $request->fecha_salida);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
    }
    private function aplicarOrdenamiento($query, Request $request): void
    {
        $orden = $request->get('sort', 'created_at');
        $direccion = $request->get('order', 'desc');

        $query->orderBy($orden, $direccion);
    }
    private function getOrdenarColumna(string $columna, string $titulo): string
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

    public function index(Request $request)
    {
        $query = SalidaAlmacen::with([
            'salidasClientes.cliente',
            'salidasClientes.obra',
            'productos',
            'empresaTransporte',
            'camion',
        ]);

        $this->aplicarFiltros($query, $request);
        $this->aplicarOrdenamiento($query, $request);

        $salidas = $query->paginate(30)->withQueryString();

        $filtrosActivos = $request->only(['codigo', 'fecha_salida', 'estado']);

        $ordenables = [
            'codigo'         => $this->getOrdenarColumna('codigo', 'Código'),
            'fecha_salida'   => $this->getOrdenarColumna('fecha_salida', 'Fecha salida'),
            'estado'         => $this->getOrdenarColumna('estado', 'Estado'),
            'peso_total_kg'  => $this->getOrdenarColumna('peso_total_kg', 'Peso total'),
        ];

        $empresasTransporte = collect();
        $camiones = collect();
        $camionesJson = collect();

        if (auth()->user()->rol === 'oficina') {
            $empresasTransporte = EmpresaTransporte::orderBy('nombre')->get();

            $camiones = Camion::with('empresaTransporte:id,nombre')
                ->orderBy('modelo')
                ->get();

            $camionesJson = $camiones->map(fn($camion) => [
                'id'         => $camion->id,
                'modelo'     => $camion->modelo,
                'empresa_id' => $camion->empresa_transporte_id,
            ]);
        }

        return view('salidasAlmacen.index', compact(
            'salidas',
            'filtrosActivos',
            'ordenables',
            'empresasTransporte',
            'camiones',
            'camionesJson'
        ));
    }
    public function activar($id)
    {
        $salida = SalidaAlmacen::with(['obraDestino', 'empresaTransporte', 'camion'])->findOrFail($id);

        if (in_array($salida->estado, ['activa', 'completada'])) {
            return back()->with('error', 'No se puede activar una salida ya activa o completada.');
        }

        try {
            DB::transaction(function () use ($salida) {

                // 🧠 Buscar la nave que contiene "Almacén" del cliente "Hierros Paco Reyes"
                $naveAlmacen = Obra::whereHas('cliente', function ($q) {
                    $q->where('empresa', 'like', '%Hierros Paco Reyes%');
                })
                    ->where('obra', 'like', '%Almacén%')
                    ->first();

                // 🔄 Cambiar estado de la salida
                $salida->update([
                    'estado' => 'activa',
                    'updated_at' => now(),
                ]);

                // 📝 Descripción del movimiento
                $descripcion = sprintf(
                    'Salida de almacén %s hacia %s%s. Código: %s',
                    $naveAlmacen->obra ?? '—',
                    $salida->obraDestino->obra ?? 'obra no especificada',
                    $salida->empresaTransporte?->nombre ? (' con transporte de ' . $salida->empresaTransporte->nombre) : '',
                    $salida->codigo
                );

                // 🚛 Crear movimiento
                Movimiento::create([
                    'tipo'              => 'salida Almacén',
                    'estado'            => 'pendiente',
                    'salida_almacen_id' => $salida->id,
                    'descripcion'       => $descripcion,
                    'fecha_solicitud'   => now(),
                    'solicitado_por'    => auth()->id(),
                    'nave_id'           => $naveAlmacen?->id,
                    'prioridad'         => 2,
                    'ubicacion_origen'  => null,
                    'ubicacion_destino' => null,
                    'producto_id'       => null,
                    'producto_base_id'  => null,
                    'paquete_id'        => null,
                    'maquina_origen'    => null,
                    'maquina_destino'   => null,
                ]);
            });

            return back()->with('success', 'Salida activada y movimiento generado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al activar salida de almacén: ' . $e->getMessage());
            return back()->with('error', 'Error al activar la salida.');
        }
    }


    public function desactivar($id)
    {
        $salida = SalidaAlmacen::findOrFail($id);

        if ($salida->estado !== 'activa') {
            return back()->with('error', 'Solo se pueden desactivar salidas activas.');
        }

        try {
            DB::transaction(function () use ($salida) {
                // 🔄 Cambiar estado
                $salida->update([
                    'estado' => 'pendiente',
                    'updated_at' => now(),
                ]);

                // 🗑️ Eliminar movimiento asociado
                Movimiento::where('salida_almacen_id', $salida->id)->delete();
            });

            return back()->with('success', 'Salida desactivada y movimiento eliminado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al desactivar salida de almacén: ' . $e->getMessage());
            return back()->with('error', 'Error al desactivar la salida.');
        }
    }

    public function cancelar($id)
    {
        $salida = SalidaAlmacen::findOrFail($id);

        if (in_array($salida->estado, ['completada', 'cancelada'])) {
            return back()->with('error', 'No se puede cancelar una salida completada o ya cancelada.');
        }

        try {
            DB::transaction(function () use ($salida) {
                $salida->update([
                    'estado' => 'cancelada',
                    'updated_at' => now(),
                ]);
            });

            return back()->with('success', 'Salida cancelada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al cancelar salida de almacén: ' . $e->getMessage());
            return back()->with('error', 'Error al cancelar la salida.');
        }
    }


    public function actualizarEstado(Request $request, $salidaId)
    {
        try {
            $salida = SalidaAlmacen::findOrFail($salidaId);

            // Verificamos que el estado actual sea pendiente antes de cambiarlo a completado
            if ($salida->estado != 'pendiente') {
                return response()->json(['message' => 'La salida ya estaba completada.'], 400);
            }
            // Asignamos el usuario autenticado (gruista)
            $salida->user_id = auth()->id();

            // Actualizamos el estado
            $salida->estado = 'completada';
            $salida->fecha_salida = now();
            $salida->save();


            return response()->json([
                'message' => 'Salida completada con éxito.'
            ]);
        } catch (\Exception $e) {
            // Capturamos cualquier error y retornamos un mensaje
            return response()->json(['message' => 'Hubo un error al completar la salida. ' . $e->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        // Dropdown seeds
        $tipos = ['barra', 'encarretado']; // ajusta si tienes más
        $diametros = ProductoBase::select('diametro')
            ->distinct()->orderBy('diametro')->pluck('diametro');
        $longitudes = ProductoBase::select('longitud')
            ->distinct()->orderBy('longitud')->pluck('longitud');

        $empresas = EmpresaTransporte::with('camiones:id,empresa_id,modelo')
            ->orderBy('nombre')->get();

        $clientes = Cliente::orderBy('empresa')->get();
        $obras    = Obra::orderBy('obra')->get();


        // Lista cacheada de productos base
        $productosBase = Cache::rememberForever('productos_base_lista_barras', function () {
            return ProductoBase::select('id', 'tipo', 'diametro', 'longitud')
                ->where('tipo', 'barra') // 👈 solo tipo barra
                ->orderBy('tipo')
                ->orderBy('diametro')
                ->orderBy('longitud')
                ->get();
        });
        return view('salidasAlmacen.create', compact(
            'tipos',
            'diametros',
            'longitudes',
            'empresas',
            'clientes',
            'obras',
            'productosBase'
        ));
    }

    /**
     * AJAX: devuelve disponibilidad por tipo/diametro/longitud
     * Response: { total_peso_kg, total_productos, bases: [...], productos_preview: [...] }
     */
    public function disponibilidad(Request $request)
    {
        // 1) Validación sin redirects HTML
        $v = Validator::make($request->all(), [
            'tipo'     => ['required', 'string', Rule::in(['barra', 'encarretado'])],
            'diametro' => ['required', 'numeric'],
            'longitud' => ['nullable', 'numeric'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Validación fallida',
                'errors'  => $v->errors(),
            ], 422);
        }

        try {
            $tipo     = $request->input('tipo');
            $diametro = (float) $request->input('diametro');
            $longitud = $request->input('longitud'); // puede venir null o "": no usar empty()

            // 2) Bases compatibles
            $basesQuery = ProductoBase::query()
                ->select('id', 'tipo', 'diametro', 'longitud')
                ->where('tipo', $tipo)
                ->where('diametro', $diametro);

            if ($longitud !== null && $longitud !== '') {
                $basesQuery->where('longitud', $longitud);
            }

            $bases = $basesQuery->get();

            if ($bases->isEmpty()) {
                return response()->json([
                    'total_peso_kg'      => 0.0,
                    'total_productos'    => 0,
                    'bases'              => [],
                    'productos_preview'  => [],
                ]);
            }

            $baseIds = $bases->pluck('id');

            // 3) Agregados por base (eficiente, sin traer todos los productos)
            $agregados = Producto::query()
                ->selectRaw('producto_base_id, COUNT(*) as productos, COALESCE(SUM(peso_stock),0) as peso_total')
                ->whereIn('producto_base_id', $baseIds)
                ->where('peso_stock', '>', 0)
                ->groupBy('producto_base_id')
                ->get()
                ->keyBy('producto_base_id');

            // Totales globales a partir de los agregados
            $totalProductos = (int) $agregados->sum('productos');
            $totalPeso      = (float) $agregados->sum('peso_total');

            // 4) Preview FIFO (primeros 8 productos más antiguos)
            $preview = Producto::query()
                ->select('id', 'codigo', 'producto_base_id', 'peso_stock', 'created_at')
                ->whereIn('producto_base_id', $baseIds)
                ->where('peso_stock', '>', 0)
                ->orderBy('created_at', 'asc')
                ->take(8)
                ->get()
                ->map(function ($p) {
                    return [
                        'id'         => $p->id,
                        'codigo'     => $p->codigo,
                        'peso_kg'    => (float) $p->peso_stock,
                        'base_id'    => $p->producto_base_id,
                        'created_at' => optional($p->created_at)->format('Y-m-d H:i'),
                    ];
                })
                ->values();

            // 5) Montar respuesta de bases con su resumen
            $basesSalida = $bases->map(function ($b) use ($agregados) {
                $res = $agregados->get($b->id);
                return [
                    'id'       => $b->id,
                    'tipo'     => $b->tipo,
                    'diametro' => $b->diametro,
                    'longitud' => $b->longitud,
                    'resumen'  => [
                        'productos'  => $res ? (int) $res->productos : 0,
                        'peso_total' => $res ? (float) $res->peso_total : 0.0,
                    ],
                ];
            })->values();

            return response()->json([
                'total_peso_kg'     => round($totalPeso, 2),
                'total_productos'   => $totalProductos,
                'bases'             => $basesSalida,
                'productos_preview' => $preview,
            ]);
        } catch (\Throwable $e) {
            // Log::error('Disponibilidad error: '.$e->getMessage(), ['ex' => $e]);
            return response()->json([
                'message' => 'Error interno al consultar disponibilidad',
            ], 500);
        }
    }

    /**
     * Crea la salida de almacén consumiendo productos por FIFO,
     * ya sea por "peso_objetivo_kg" o "unidades_objetivo".
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_base_id'  => ['required', 'exists:productos_base,id'],
            'productos.*.peso_objetivo_kg'  => ['nullable', 'numeric', 'min:0.01'],
            'productos.*.unidades_objetivo' => ['nullable', 'integer', 'min:1'],
        ]);

        // Validar que cada línea tenga al menos uno de los dos campos
        foreach ($data['productos'] as $index => $linea) {
            if (empty($linea['peso_objetivo_kg']) && empty($linea['unidades_objetivo'])) {
                return back()
                    ->withErrors(["productos.$index" => 'Debes indicar un peso o unidades en cada línea.'])
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Crear salida de almacén
            $codigo = SalidaAlmacen::generarCodigo();
            $salida = SalidaAlmacen::create([
                'codigo'       => $codigo,
                'fecha_salida' => now(),
                'estado'       => 'pendiente',
                'user_id'      => auth()->id(),
            ]);

            // Insertar líneas de productos
            foreach ($data['productos'] as $linea) {
                DB::table('salidas_almacen_productos_base')->insert([
                    'salida_almacen_id' => $salida->id,
                    'producto_base_id'  => $linea['producto_base_id'],
                    'peso_objetivo_kg'  => $linea['peso_objetivo_kg'] ?? null,
                    'unidades_objetivo' => $linea['unidades_objetivo'] ?? null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('salidas-almacen.index')
                ->with('success', "Salida creada: {$salida->codigo}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
    public function productosAsignados($salidaId)
    {
        $asignados = DB::table('salidas_almacen_productos as sap')
            ->join('productos as p', 'p.id', '=', 'sap.producto_id')
            ->where('sap.salida_almacen_id', $salidaId)
            ->select(
                'p.codigo',
                'p.producto_base_id',
                'sap.peso_kg',
                'sap.cantidad'
            )
            ->orderBy('p.codigo')
            ->get()
            ->groupBy('producto_base_id');

        $resultado = [];

        foreach ($asignados as $pbId => $items) {
            $resultado[$pbId] = $items->map(function ($item) {
                return [
                    'codigo'     => $item->codigo,
                    'peso_kg'    => (float) $item->peso_kg,
                    'cantidad'   => (int) $item->cantidad,
                ];
            })->values();
        }

        return response()->json([
            'success'   => true,
            'asignados' => $resultado,
        ]);
    }

    public function productosPorMovimiento($movimientoId)
    {
        $movimiento = Movimiento::with('salidaAlmacen')->find($movimientoId);

        if (!$movimiento || !$movimiento->salida_almacen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no asociado a ninguna salida de almacén.'
            ]);
        }

        $salidaId = $movimiento->salida_almacen_id;

        // Subquery de progreso por PB
        $asignadosPorPB = DB::table('salidas_almacen_productos as sap')
            ->join('productos as p', 'p.id', '=', 'sap.producto_id')
            ->where('sap.salida_almacen_id', $salidaId)
            ->groupBy('p.producto_base_id')
            ->select(
                'p.producto_base_id',
                DB::raw('SUM(sap.peso_kg) as asignado_kg'),
                DB::raw('SUM(sap.cantidad) as asignado_ud')
            );

        // 👉 Lista detallada de asignados (para renderizar la tabla por PB)
        $asignadosDetallados = DB::table('salidas_almacen_productos as sap')
            ->join('productos as p', 'p.id', '=', 'sap.producto_id')
            ->where('sap.salida_almacen_id', $salidaId)
            ->select(
                'p.producto_base_id',
                'p.id as producto_id',
                'p.codigo',
                'sap.peso_kg',
                'sap.cantidad'
            )
            ->orderBy('p.codigo')
            ->get()
            ->groupBy('producto_base_id');

        $productosBase = DB::table('salidas_almacen_productos_base as sapb')
            ->join('productos_base as pb', 'sapb.producto_base_id', '=', 'pb.id')
            ->leftJoinSub($asignadosPorPB, 'asig', function ($join) {
                $join->on('sapb.producto_base_id', '=', 'asig.producto_base_id');
            })
            ->where('sapb.salida_almacen_id', $salidaId)
            ->select(
                'sapb.id',
                'sapb.producto_base_id',
                'sapb.peso_objetivo_kg',
                'sapb.unidades_objetivo',
                'pb.tipo',
                'pb.diametro',
                'pb.longitud',
                DB::raw('COALESCE(asig.asignado_kg,0) as asignado_kg'),
                DB::raw('COALESCE(asig.asignado_ud,0) as asignado_ud')
            )
            ->get()
            ->map(function ($row) use ($asignadosDetallados) {
                $lista = ($asignadosDetallados[$row->producto_base_id] ?? collect())->map(function ($i) {
                    return [
                        'producto_id' => $i->producto_id,
                        'codigo'      => $i->codigo,
                        'peso_kg'     => (float) $i->peso_kg,
                        'cantidad'    => (int) $i->cantidad,
                    ];
                })->values();
                $row->asignados = $lista; // ← array con los productos ya añadidos
                return $row;
            });

        return response()->json([
            'success' => true,
            'salida_id' => $salidaId,
            'productos_base' => $productosBase,
        ]);
    }

    public function validarProductoEscaneado(Request $request)
    {
        Log::info('🟦 Iniciando validación de producto escaneado', $request->all());

        try {
            // 1) Validación básica
            $request->validate([
                'codigo'                    => ['required', 'string'],
                'salida_almacen_id'         => ['required', 'exists:salidas_almacen,id'],
                'producto_base_id_esperado' => ['nullable', 'integer', 'min:1'],
            ]);

            Log::info('✅ Validación del request completada');

            // 2) Prefijo obligatorio MP (si mantienes esta norma)
            $codigo = strtoupper(trim($request->codigo));
            if (!str_starts_with($codigo, 'MP')) {
                $msg = 'El código debe comenzar por "MP".';
                Log::warning("❌ {$msg} | Código: {$request->codigo}");
                return response()->json([
                    'success' => false,
                    'swal' => ['icon' => 'error', 'title' => 'Código no válido', 'text' => $msg]
                ], 400);
            }

            // 3) Localizar producto por código
            /** @var \App\Models\Producto|null $producto */
            $producto = Producto::where('codigo', $request->codigo)->first();
            if (!$producto) {
                $msg = 'Producto no encontrado.';
                Log::warning("❌ {$msg} | Código: {$request->codigo}");
                return response()->json([
                    'success' => false,
                    'swal' => ['icon' => 'error', 'title' => 'Producto no válido', 'text' => $msg]
                ], 404);
            }

            // 4) (Muy importante) Si el frontend indicó el PB esperado, debe coincidir
            if ($request->filled('producto_base_id_esperado')) {
                $esperado = (int) $request->input('producto_base_id_esperado');
                if ((int) $producto->producto_base_id !== $esperado) {
                    $msg = 'El producto no es correcto.';
                    Log::warning("❌ {$msg} | Código: {$producto->codigo} | PB encontrado: {$producto->producto_base_id} | PB esperado: {$esperado}");
                    return response()->json([
                        'success' => false,
                        'message' => $msg, // error inline
                        'producto' => [
                            'producto_base_id' => (int) $producto->producto_base_id,
                        ],
                    ], 422);
                }
            }

            // 5) Si marcas reserva por producto completo, esto bloquearía parciales.
            //    Mantienes parciales → no marques salida_almacen_id en producto.
            if (!is_null($producto->salida_almacen_id) && (int)$producto->salida_almacen_id !== (int)$request->salida_almacen_id) {
                $msg = 'Este producto ya está reservado para otra salida de almacén.';
                Log::warning("❌ {$msg} | Código: {$producto->codigo}, salida_almacen_id: {$producto->salida_almacen_id}");
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            // 6) Comprobar que el PB del producto está en la DEMANDA (sapb)
            $demanda = DB::table('salidas_almacen_productos_base')
                ->where('salida_almacen_id', $request->salida_almacen_id)
                ->where('producto_base_id', $producto->producto_base_id)
                ->first();

            if (!$demanda) {
                $msg = 'El producto escaneado es incorrecto para esta salida.';
                Log::warning("❌ {$msg} | Código: {$producto->codigo}, producto_base_id: {$producto->producto_base_id}");
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            // 7) Calcular asignado actual para este PB en esta salida
            $asignado = DB::table('salidas_almacen_productos as sap')
                ->join('productos as p', 'p.id', '=', 'sap.producto_id')
                ->where('sap.salida_almacen_id', $request->salida_almacen_id)
                ->where('p.producto_base_id', $producto->producto_base_id)
                ->selectRaw('COALESCE(SUM(sap.peso_kg),0) as kg, COALESCE(SUM(sap.cantidad),0) as ud')
                ->first();

            $porPeso      = !is_null($demanda->peso_objetivo_kg);
            $porUnidades  = !is_null($demanda->unidades_objetivo);

            $pesoProducto = (float) ($producto->peso_stock ?? $producto->peso_inicial ?? 0);
            if ($porPeso && $pesoProducto <= 0) {
                $msg = 'El producto no tiene peso disponible.';
                Log::warning("❌ {$msg} | Código: {$producto->codigo}");
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            // 8) Restos por cubrir
            $kgRestantes = $porPeso     ? max(0.0, (float)$demanda->peso_objetivo_kg - (float)$asignado->kg) : 0.0;
            $udRestantes = $porUnidades ? max(0,   (int)$demanda->unidades_objetivo - (int)$asignado->ud)      : 0;

            Log::info("ℹ️ Demanda PB {$producto->producto_base_id}: objetivo {$demanda->peso_objetivo_kg} kg / {$demanda->unidades_objetivo} ud | asignado {$asignado->kg} kg / {$asignado->ud} ud | restantes {$kgRestantes} kg / {$udRestantes} ud");

            if (($porPeso && $kgRestantes <= 0.0) || ($porUnidades && $udRestantes <= 0)) {
                $msg = 'Este tipo ya está cubierto. No es necesario más material.';
                Log::info("ℹ️ {$msg} | PB: {$producto->producto_base_id}");
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            // 9) Aportación asignable (sin pasarse)
            $aportacionKg = $porPeso     ? (float) min($kgRestantes, $pesoProducto) : 0.0;
            $aportacionUd = $porUnidades ? min(1, $udRestantes)                     : 0;

            if (($porPeso && $aportacionKg <= 0.0) || ($porUnidades && $aportacionUd <= 0)) {
                $msg = 'No hay margen para asignar más en este tipo.';
                Log::info("ℹ️ {$msg} | PB: {$producto->producto_base_id}");
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            // 10) Persistencia (suma si ya hay fila de este producto; si no, inserta)
            DB::transaction(function () use ($request, $producto, $aportacionKg, $aportacionUd, $porPeso) {
                $detalle = DB::table('salidas_almacen_productos')
                    ->where('salida_almacen_id', $request->salida_almacen_id)
                    ->where('producto_id', $producto->id)
                    ->lockForUpdate()
                    ->first();

                if ($detalle) {
                    DB::table('salidas_almacen_productos')
                        ->where('id', $detalle->id)
                        ->update([
                            'peso_kg'    => $porPeso ? ((float)$detalle->peso_kg + $aportacionKg) : (float)$detalle->peso_kg,
                            'cantidad'   => !$porPeso ? ((int)$detalle->cantidad + $aportacionUd) : (int)$detalle->cantidad,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('salidas_almacen_productos')->insert([
                        'salida_almacen_id' => (int) $request->salida_almacen_id,
                        'producto_id'       => (int) $producto->id,
                        'cantidad'          => $porPeso ? 0 : $aportacionUd,
                        'peso_kg'           => $porPeso ? $aportacionKg : 0.0,
                        'observaciones'     => null,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                // 🔁 Si quisieras descontar stock aquí, hazlo con cuidado (comentado en tu versión).
            });

            Log::info("✅ Producto asignado: ID {$producto->id}, Código {$producto->codigo}, " .
                ($porPeso ? ("+{$aportacionKg} kg") : ("+{$aportacionUd} ud")));

            // 11) Respuesta OK
            return response()->json([
                'success'  => true,
                'producto' => [
                    'id'               => (int) $producto->id,
                    'codigo'           => $producto->codigo,
                    'peso_stock'       => $producto->peso_stock, // si no descuentas aquí, seguirá igual
                    'producto_base_id' => (int) $producto->producto_base_id,
                    'aportado_kg'      => $aportacionKg,
                    'aportado_ud'      => $aportacionUd,
                ],
                'message'  => $porPeso
                    ? "Añadidos " . number_format($aportacionKg, 0, ',', '.') . " kg."
                    : "Añadida 1 unidad.",
            ]);
        } catch (\Throwable $e) {
            Log::error('🔥 Error inesperado en validarProductoEscaneado: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'swal' => [
                    'icon'  => 'error',
                    'title' => 'Error inesperado',
                    'text'  => 'No se pudo validar el producto. Revisa los logs o contacta con soporte.',
                ],
                'debug' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function eliminarProductoEscaneado(SalidaAlmacen $salida, $codigo)
    {
        try {
            // 1) Localiza producto por código
            $producto = Producto::where('codigo', $codigo)->first();

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado.',
                ], 404);
            }

            // 2) Comprueba si hay registro en detalle para esta salida
            $registro = DB::table('salidas_almacen_productos')
                ->where('salida_almacen_id', $salida->id)
                ->where('producto_id', $producto->id)
                ->first();

            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'El producto no está asignado a esta salida.',
                ], 404);
            }

            // 3) Elimina el detalle (si has diseñado 1 fila por producto, basta con borrar esa fila)
            DB::table('salidas_almacen_productos')
                ->where('salida_almacen_id', $salida->id)
                ->where('producto_id', $producto->id)
                ->delete();

            // 4) (Opcional) NO tocamos producto->salida_almacen_id porque en asignación parcial no lo usamos.
            //    Si en tu flujo lo hubieras marcado, podrías limpiarlo SOLO si ya no queda ningún detalle
            //    de ese producto asignado a ninguna salida:
            /*
        $sigueAsignado = DB::table('salidas_almacen_productos')
            ->where('producto_id', $producto->id)
            ->exists();

        if (!$sigueAsignado) {
            $producto->update([
                'salida_almacen_id' => null,
                'estado' => 'disponible',
            ]);
        }
        */

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado de la salida correctamente.',
                'producto_id' => $producto->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar producto escaneado: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al eliminar el producto.',
            ], 500);
        }
    }
    public function completarDesdeMovimiento($movimientoId)
    {
        try {
            // Buscar el movimiento
            $movimiento = Movimiento::findOrFail($movimientoId);

            if (strtolower($movimiento->tipo) !== 'salida almacén') {
                return response()->json([
                    'success' => false,
                    'message' => 'El movimiento no corresponde a una salida de almacén.'
                ], 422);
            }

            // Buscar la salida asociada
            $salida = SalidaAlmacen::findOrFail($movimiento->salida_almacen_id);

            // Marcar salida como completada
            $salida->estado = 'completado';
            $salida->save();

            // Actualizar movimiento como ejecutado
            $movimiento->estado = 'ejecutado';
            $movimiento->ejecutado_por = auth()->id();
            $movimiento->fecha_ejecucion = now(); // 👈 aquí guardamos la fecha y hora
            $movimiento->save();

            return response()->json([
                'success' => true,
                'message' => 'Salida de almacén completada correctamente.'
            ]);
        } catch (\Throwable $e) {
            \Log::error("Error completando salida desde movimiento {$movimientoId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al completar la salida.'
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            /** @var \App\Models\Salida $salida */
            $salida = SalidaAlmacen::findOrFail($id);

            $field     = $request->input('field');
            $value     = $request->input('value');
            $clienteId = $request->input('cliente_id');
            $obraId    = $request->input('obra_id');

            // Campos actualizables en 'salidas'
            $salidaFields = [
                'fecha_salida',
                'estado',
                'codigo_sage',
                'empresa_id',
                'camion_id',
            ];

            // Campos actualizables en 'salida_cliente' (pivot)
            $salidaClienteFields = [
                'importe',
                'horas_paralizacion',
                'importe_paralizacion',
                'horas_grua',
                'importe_grua',
                'horas_almacen',
            ];

            $allFields = array_merge($salidaFields, $salidaClienteFields);

            if (!in_array($field, $allFields, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo especificado no es editable en línea.'
                ], 422);
            }

            // Reglas de validación campo a campo
            $rules = [
                'fecha_salida'         => ['nullable', 'date'],
                'estado'               => ['nullable', 'string', 'max:50'],
                'codigo_sage'          => ['nullable', 'string', 'max:100'],
                'empresa_id' => ['nullable', 'integer', 'exists:empresa_transportes,id'],
                'camion_id'            => ['nullable', 'integer', 'exists:camiones,id'],

                'importe'              => ['nullable', 'numeric'],
                'horas_paralizacion'   => ['nullable', 'numeric'],
                'importe_paralizacion' => ['nullable', 'numeric'],
                'horas_grua'           => ['nullable', 'numeric'],
                'importe_grua'         => ['nullable', 'numeric'],
                'horas_almacen'        => ['nullable', 'numeric'],
            ];

            // Valida sólo el campo que viene
            if (array_key_exists($field, $rules)) {
                $request->validate([$field => $rules[$field]]);
            }

            // Normalizaciones previas
            // 1) Fecha con hora aceptando varios formatos
            if ($field === 'fecha_salida' && filled($value)) {
                try {
                    // Acepta 'd/m/Y H:i' o cualquier parseable por Carbon
                    $value = self::parseFechaHora($value)?->format('Y-m-d H:i:s');
                    if (!$value) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Formato de fecha no válido. Usa DD/MM/YYYY HH:MM o YYYY-MM-DD HH:MM:SS.'
                        ], 422);
                    }
                } catch (\Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Formato de fecha no válido.'
                    ], 422);
                }
            }

            // 2) Numéricos: null -> null, cadena vacía -> null
            if (in_array($field, $salidaClienteFields, true)) {
                if ($value === '' || $value === null) {
                    $value = null;
                } else {
                    $value = (float) $value;
                }
            }

            // ------- Persistencia -------
            if (in_array($field, $salidaFields, true)) {

                if ($field === 'empresa_id') {
                    // Cambiar empresa => limpiar camión si no pertenece
                    $nuevaEmpresaId = $value ?: null;

                    $salida->empresa_id = $nuevaEmpresaId;

                    // Si hay un camión asignado y no coincide con la nueva empresa, nuléalo
                    if ($salida->camion_id) {
                        $camionPertenece = Camion::where('id', $salida->camion_id)
                            ->where('empresa_id', $nuevaEmpresaId)
                            ->exists();

                        if (!$camionPertenece) {
                            $salida->camion_id = null;
                        }
                    }

                    $salida->save();
                } elseif ($field === 'camion_id') {
                    // Validar pertenencia del camión a la empresa actual (si hay)
                    if ($value) {
                        $empresaId = $salida->empresa_id;
                        if ($empresaId) {
                            $camionOk = Camion::where('id', $value)
                                ->where('empresa_id', $empresaId)
                                ->exists();

                            if (!$camionOk) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'El camión no pertenece a la empresa de transporte seleccionada.'
                                ], 422);
                            }
                        }
                    }

                    $salida->camion_id = $value ?: null;
                    $salida->save();
                } else {
                    // fecha_salida, estado, codigo_sage
                    $salida->$field = $value;
                    $salida->save();
                }
            } else {
                // Pivot: salida_cliente — requiere cliente y obra
                if (!$clienteId || !$obraId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Falta el ID del cliente u obra para actualizar el campo.'
                    ], 422);
                }

                DB::table('salida_cliente')
                    ->where('salida_id', $salida->id)
                    ->where('cliente_id', $clienteId)
                    ->where('obra_id', $obraId)
                    ->update([$field => $value]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Salida actualizada correctamente.',
                'data'    => [
                    'id'                     => $salida->id,
                    'fecha_salida'           => $salida->fecha_salida,
                    'estado'                 => $salida->estado,
                    'codigo_sage'            => $salida->codigo_sage,
                    'empresa_id'             => $salida->empresa_id,
                    'camion_id'              => $salida->camion_id,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la salida.' . $e->getMessage(),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function actualizarCodigoSage(Request $request, SalidaAlmacen $salida)
    {
        $request->validate([
            'codigo' => 'required|string|max:255',
        ], [
            'codigo.required' => 'El código es obligatorio.',
        ]);

        try {
            $salida->codigo_sage = $request->codigo;
            $salida->save();

            return response()->json([
                'success' => true,
                'message' => 'Código SAGE actualizado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el código SAGE.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
