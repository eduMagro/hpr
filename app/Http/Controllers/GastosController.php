<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Gasto;

class GastosController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $gastos = Gasto::with(['nave', 'obra', 'maquina', 'factura', 'proveedor', 'motivo'])
            ->orderBy('fecha_pedido', 'desc')
            ->paginate($perPage);

        // Placeholder for statistics
        $stats = [
            'global' => Gasto::sum('coste'),
            'mensual' => Gasto::whereMonth('fecha_pedido', now()->month)
                ->whereYear('fecha_pedido', now()->year)
                ->sum('coste'),
        ];

        $obras = \App\Models\Obra::whereNotIn('obra', ['Nave A', 'Nave B'])->orderBy('obra')->get();
        // Naves can be hardcoded or filtered from Obras if they exist there, 
        // but user specifically requested "Nave A" and "Nave B".
        // If these are IDs in the 'obras' table, we should find them. 
        // However, given the request "que solo salgan Nave A y Nave B", we can pass a specific list.
        // But since the DB expects an ID (nave_id constrained to obras), we must ensure 
        // these exist in the Obras table or handle them as specific logic.
        // Assuming current requirement is just to SHOW these options for selection.
        // If 'nave_id' is an FK to 'obras', we probably need to find the Obras named "Nave A" and "Nave B".
        // Let's filter the works to find those two, or if they are just strings, use strings.
        // But the model says: "nave_id constrained('obras')". So they MUST be in the Obras table.
        // Let's search for them.
        $naves = \App\Models\Obra::whereIn('obra', ['Nave A', 'Nave B'])->get();

        // If they don't exist by name, we might be in trouble, but assuming they do or user means these are the only choices.
        // If the user meant "Creating them if not exist", that's another step. 
        // For now, let's assume they are valid 'Obra' records.

        $maquinas = \App\Models\Maquina::orderBy('nombre')->get();

        $proveedoresLista = \App\Models\GastoProveedor::orderBy('nombre')->get();
        $motivosLista = \App\Models\GastoMotivo::orderBy('nombre')->get();

        return view('components.gastos.index', compact('gastos', 'perPage', 'stats', 'obras', 'naves', 'maquinas', 'proveedoresLista', 'motivosLista'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha_pedido' => 'nullable|date',
            'fecha_llegada' => 'nullable|date',
            'nave_id' => 'nullable|exists:obras,id',
            'obra_id' => 'nullable|exists:obras,id',
            'proveedor_id' => 'nullable|exists:gastos_proveedores,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
            'motivo_id' => 'nullable|exists:gastos_motivos,id',
            'coste' => 'nullable|numeric',
            'observaciones' => 'nullable|string',
        ]);

        $gasto = Gasto::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto creado correctamente.',
                'gasto' => $gasto
            ]);
        }

        return redirect()->route('gastos.index')->with('success', 'Gasto creado correctamente.');
    }

    public function update(Request $request, Gasto $gasto)
    {
        $validated = $request->validate([
            'fecha_pedido' => 'nullable|date',
            'fecha_llegada' => 'nullable|date',
            'nave_id' => 'nullable|exists:obras,id',
            'obra_id' => 'nullable|exists:obras,id',
            'proveedor_id' => 'nullable|exists:gastos_proveedores,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
            'motivo_id' => 'nullable|exists:gastos_motivos,id',
            'coste' => 'nullable|numeric',
            'observaciones' => 'nullable|string',
        ]);

        $gasto->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto actualizado correctamente.',
                'gasto' => $gasto
            ]);
        }

        return redirect()->route('gastos.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Gasto $gasto)
    {
        $gasto->delete();
        return redirect()->route('gastos.index')->with('success', 'Gasto eliminado correctamente.');
    }

    public function storeProveedor(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:gastos_proveedores,nombre|max:255',
        ]);

        $proveedor = \App\Models\GastoProveedor::create($validated);

        return response()->json([
            'success' => true,
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre
        ]);
    }

    public function storeMotivo(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:gastos_motivos,nombre|max:255',
        ]);

        $motivo = \App\Models\GastoMotivo::create($validated);

        return response()->json([
            'success' => true,
            'id' => $motivo->id,
            'nombre' => $motivo->nombre
        ]);
    }
}
