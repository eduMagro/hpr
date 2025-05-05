<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoBase;
use App\Models\Entrada;
use App\Models\EntradaProducto;
use App\Models\Ubicacion;

class PedidoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pedidos = Pedido::with(['proveedor', 'productos'])->latest()->paginate(10);

        return view('pedidos.index', compact('pedidos'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function confirmar(Request $request)
    {
        $seleccionados = $request->input('seleccionados', []);
        $detalles = $request->input('detalles', []);

        $filas = collect($seleccionados)->map(function ($clave) use ($detalles) {
            $cantidad = round(floatval($detalles[$clave]['cantidad'] ?? 0), 2);
            return [
                'clave' => $clave,
                'tipo' => $detalles[$clave]['tipo'],
                'diametro' => $detalles[$clave]['diametro'],
                'cantidad' => $cantidad,
            ];
        }); // ya no se filtra

        $proveedores = Proveedor::all();

        return view('pedidos.create', compact('filas', 'proveedores'));
    }

    public function recepcion($id)
    {
        $pedido = Pedido::with('productos')->findOrFail($id);

        return view('pedidos.recepcion', compact('pedido'));
    }

    public function procesarRecepcion(Request $request, $id)
    {
        $pedido = Pedido::with('productos')->findOrFail($id);
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
                    return back()->withErrors(['ubicacion' => "Ubicación ID {$ubicacionId} no válida."]);
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

        // Crear entrada (AC25/0001)
        $codigoEntrada = str_replace('PC', 'AC', $pedido->codigo);

        $entrada = Entrada::create([
            'albaran' => $codigoEntrada,
            'peso_total' => $pesoTotal,
            'users_id' => auth()->id(),
            'otros' => 'Entrada generada desde recepción de pedido',
        ]);

        // Relacionar productos con la entrada
        foreach ($productosCreados as $item) {
            EntradaProducto::create([
                'entrada_id' => $entrada->id,
                'producto_id' => $item['producto_id'],
                'ubicacion_id' => $item['ubicacion_id'],
                'users_id' => auth()->id(),
            ]);
        }
        $pesoPedido = $pedido->productos->sum(fn($p) => $p->pivot->cantidad);
        $margen = 0.005; // 0.5%

        if ($pesoTotal >= $pesoPedido * (1 - $margen)) {
            $pedido->estado = 'recibido';
        } else {
            $pedido->estado = 'parcial';
        }

        $pedido->save();

        return redirect()->route('entradas.index')->with('success', 'Recepción registrada y entrada creada correctamente.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_estimada' => 'required|date',
            'seleccionados' => 'required|array',
        ]);

        $pedido = Pedido::create([
            'codigo' => Pedido::generarCodigo(),
            'proveedor_id' => $request->proveedor_id,
            'fecha_pedido' => now(),
            'fecha_estimada' => $request->fecha_estimada,
            'estado' => 'pendiente',
        ]);

        foreach ($request->seleccionados as $clave) {
            $tipo = $request->input("detalles.$clave.tipo");
            $diametro = $request->input("detalles.$clave.diametro");
            $peso = floatval($request->input("detalles.$clave.cantidad"));
            // Buscar el producto base correspondiente
            $productoBase = ProductoBase::where('tipo', $tipo)
                ->where('diametro', $diametro)
                ->first();
            if ($peso > 0 && $productoBase) {
                $pedido->productos()->attach($productoBase->id, [
                    'cantidad' => $peso,
                    'observaciones' => "Pedido generado desde comparativa automática",
                ]);
            }
        }

        return redirect()->route('entradas.index')->with('success', 'Pedido creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
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
