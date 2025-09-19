<?php

namespace App\Http\Controllers;

use App\Models\PedidoAlmacenVenta;
use App\Models\PedidoAlmacenVentaLinea;
use App\Models\ClienteAlmacen;
use App\Models\ProductoBase;
use App\Models\Obra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PedidoAlmacenVentaController extends Controller
{
    private function filtrosActivosPedidosAlmacen(Request $request): array
    {
        $filtros = [];

        if ($request->filled('codigo')) {
            $filtros[] = 'Código pedido: <strong>' . $request->codigo . '</strong>';
        }
        if ($request->filled('cliente_id')) {
            $cliente = \App\Models\ClienteAlmacen::find($request->cliente_id);
            if ($cliente) {
                $filtros[] = 'Cliente: <strong>' . $cliente->nombre . '</strong>';
            }
        }
        if ($request->filled('fecha')) {
            $filtros[] = 'Fecha: <strong>' . $request->fecha . '</strong>';
        }
        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst(str_replace('_', ' ', $request->estado)) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'codigo'     => 'Código',
                'fecha'      => 'Fecha',
                'cliente'    => 'Cliente',
                'estado'     => 'Estado',
                'created_by' => 'Creado por',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }

        return $filtros;
    }

    private function getOrdenamientoPedidosAlmacen(string $columna, string $titulo): string
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

    public function aplicarFiltrosPedidosAlmacen($query, Request $request)
    {
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Ordenación
        $sortBy = $request->input('sort', 'created_at');
        $order  = $request->input('order', 'desc');

        $query->reorder();

        switch ($sortBy) {
            case 'cliente':
                $query->orderBy(
                    \App\Models\ClienteAlmacen::select('nombre')
                        ->whereColumn('clientes_almacen.id', 'pedidos_almacen_venta.cliente_id'),
                    $order
                );
                break;
            case 'codigo':
            case 'fecha':
            case 'estado':
            case 'created_by':
            case 'created_at':
                $query->orderBy("pedidos_almacen_venta.$sortBy", $order);
                break;
            default:
                $query->orderBy('pedidos_almacen_venta.created_at', 'desc');
                break;
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = PedidoAlmacenVenta::with(['cliente', 'lineas.productoBase', 'lineas.obra'])
            ->withCount('lineas');

        $this->aplicarFiltrosPedidosAlmacen($query, $request);

        $perPage = $request->input('per_page', 10);
        $pedidos = $query->paginate($perPage)->appends($request->all());

        $clientes = \App\Models\ClienteAlmacen::orderBy('nombre')->get();

        $ordenables = [
            'codigo'  => $this->getOrdenamientoPedidosAlmacen('codigo', 'Código'),
            'fecha'   => $this->getOrdenamientoPedidosAlmacen('fecha', 'Fecha'),
            'cliente' => $this->getOrdenamientoPedidosAlmacen('cliente', 'Cliente'),
            'estado'  => $this->getOrdenamientoPedidosAlmacen('estado', 'Estado'),
        ];

        $filtrosActivos = $this->filtrosActivosPedidosAlmacen($request);

        return view('pedidos-almacen-venta.index', compact(
            'pedidos',
            'clientes',
            'ordenables',
            'filtrosActivos'
        ));
    }

    public function create()
    {
        $clientes = ClienteAlmacen::orderBy('nombre')->get();

        $productosBase = ProductoBase::where('tipo', 'barra')
            ->orderBy('diametro')
            ->orderBy('longitud')
            ->get();

        $obras = Obra::with('cliente')->orderBy('obra')->get();

        $diametros = ProductoBase::where('tipo', 'barra')
            ->distinct()
            ->orderBy('diametro')
            ->pluck('diametro');

        $longitudes = ProductoBase::where('tipo', 'barra')
            ->whereNotNull('longitud')
            ->distinct()
            ->orderBy('longitud')
            ->pluck('longitud');

        return view('pedidos-almacen-venta.create', compact(
            'clientes',
            'productosBase',
            'obras',
            'diametros',
            'longitudes'
        ));
    }
    // PARA EL MODAL DE CREAR SALIDA Y ELEGIR PESOS
    public function detallesLineas(Request $request)
    {
        $ids = $request->input('lineas', []);

        $lineas = PedidoAlmacenVentaLinea::with(['productoBase', 'pedido.cliente'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function ($l) {
                $pendiente = max($l->cantidad_solicitada - $l->cantidad_servida_calculada, 0);

                return [
                    'id' => $l->id,
                    'pedido_codigo' => $l->pedido->codigo ?? '—',
                    'cliente_nombre' => $l->pedido->cliente->nombre ?? '—',
                    'producto' => $l->productoBase
                        ? "{$l->productoBase->tipo} Ø{$l->productoBase->diametro} {$l->productoBase->longitud}m"
                        : '—',
                    'cantidad_solicitada' => $l->cantidad_solicitada,
                    'cantidad_servida_calculada' => $l->cantidad_servida_calculada,
                    'cantidad_pendiente' => $pendiente,
                ];
            });

        return response()->json(['lineas' => $lineas]);
    }







    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes_almacen,id',
            'cliente_nombre' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'productos' => 'required|array|min:1',
            'productos.*.producto_base_id' => 'required|exists:productos_base,id',
            'productos.*.peso_objetivo_kg' => 'nullable|numeric|min:0.01',
            'productos.*.unidades_objetivo' => 'nullable|integer|min:1',
        ]);

        // 🧠 Determinar cliente (prioridad: select → input)
        if ($request->filled('cliente_id')) {
            $cliente = ClienteAlmacen::findOrFail($request->cliente_id);
            $clienteNuevo = false;
        } elseif ($request->filled('cliente_nombre')) {
            $nombreCliente = trim($request->cliente_nombre);
            $cliente = ClienteAlmacen::firstOrCreate(
                ['nombre' => $nombreCliente],
                ['cif' => null] // puedes añadir más campos si los pides
            );
            $clienteNuevo = $cliente->wasRecentlyCreated;
        } else {
            return back()->withErrors([
                'cliente' => 'Debes seleccionar un cliente existente o escribir uno nuevo.'
            ]);
        }

        DB::transaction(function () use ($request, $cliente) {
            // 🧾 Crear el pedido
            $pedido = PedidoAlmacenVenta::create([
                'cliente_id'    => $cliente->id,
                'codigo'        => $this->generarCodigo(),
                'estado'        => 'borrador',
                'fecha'         => now(),
                'observaciones' => $request->observaciones,
                'created_by'    => auth()->id(),
                'updated_by'    => auth()->id(),
            ]);

            // 📦 Crear líneas del pedido
            foreach ($request->productos as $linea) {
                $pedido->lineas()->create([
                    'producto_base_id'   => $linea['producto_base_id'],
                    'unidad_medida'      => 'kg', // por defecto
                    'cantidad_solicitada' => $linea['peso_objetivo_kg'] ?? 0,
                    'cantidad_servida'   => 0,
                    'cantidad_pendiente' => $linea['peso_objetivo_kg'] ?? 0,
                    'kg_por_bulto_override' => null,
                    'precio_unitario'    => null,
                    'notas'              => 'Solicitado por peso'
                        . ($linea['unidades_objetivo']
                            ? " o por unidades: {$linea['unidades_objetivo']}"
                            : ''),
                ]);
            }
        });

        return redirect()->route('pedidos-almacen-venta.index')
            ->with('success', 'Pedido creado correctamente.')
            ->with('cliente_creado', $clienteNuevo ? "Se ha creado el cliente: «{$cliente->nombre}»" : null);
    }
    public function cancelarLinea(PedidoAlmacenVenta $pedido, PedidoAlmacenVentaLinea $linea)
    {
        // aseguramos que la línea pertenece al pedido
        if ($linea->pedido_almacen_venta_id !== $pedido->id) {
            abort(403, 'No autorizado');
        }

        $linea->update([
            'estado' => 'cancelado',
            'cantidad_pendiente' => 0,
        ]);

        return back()->with('success', "Línea {$linea->id} cancelada correctamente.");
    }


    public function show($id)
    {
        $pedido = PedidoAlmacenVenta::with(['cliente', 'lineas.productoBase', 'lineas.obra'])->findOrFail($id);

        return view('pedidos-almacen-venta.show', compact('pedido'));
    }

    public function confirmar($id)
    {
        $pedido = PedidoAlmacenVenta::with('lineas')->findOrFail($id);

        if ($pedido->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden confirmar pedidos en estado "borrador".');
        }

        if ($pedido->lineas->isEmpty()) {
            return back()->with('error', 'El pedido no tiene líneas.');
        }

        $pedido->estado = 'pendiente';
        $pedido->updated_by = auth()->id();
        $pedido->save();

        return back()->with('success', 'Pedido confirmado correctamente.');
    }

    private function generarCodigo(): string
    {
        $año = now()->format('y');
        $último = PedidoAlmacenVenta::where('codigo', 'like', "PV$año/%")
            ->orderByDesc('codigo')
            ->first();

        $siguiente = 1;
        if ($último) {
            $partes = explode('/', $último->codigo);
            $siguiente = intval($partes[1]) + 1;
        }

        return sprintf("PV%s/%05d", $año, $siguiente);
    }
}
