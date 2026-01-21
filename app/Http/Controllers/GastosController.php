<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Gasto;

class GastosController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $gastos = Gasto::with(['nave', 'obra', 'maquina', 'factura'])
            ->orderBy('fecha_pedido', 'desc')
            ->paginate($perPage);

        // Placeholder for statistics
        $stats = [
            'global' => Gasto::sum('coste'),
            'mensual' => Gasto::whereMonth('fecha_pedido', now()->month)
                ->whereYear('fecha_pedido', now()->year)
                ->sum('coste'),
        ];

        $obras = \App\Models\Obra::orderBy('obra')->get();
        $maquinas = \App\Models\Maquina::orderBy('nombre')->get();

        $proveedoresLista = [
            'PROGRESS',
            'ARGEMAQ MACHINES S.L',
            'BIGMAT',
            'grupo portillo',
            'DAKO RETAIL, S.L',
            'RIGMSUR S.L',
            'PONCESUR',
            'FONTANERIA Y AZULEJOS RODRIGUEZ S.L',
            'SOLDADURAS DE ANDALUCÍA S.L',
            'HIERROS PACO REYES S.L.U',
            'ELEKTRA ANDALUCIA XXI S.L',
            'RODAMIENTOS BLANCO,S.L',
            'RODAMIENTOS PEREIRA',
            'COMERCIO JAILIN S.L',
            'PRONOVA SOLUCIONES INDUSTRIALES',
            'SATEL',
            'RADIADORES GARRINCHA',
            'MECANIZADOS R. LÓPEZ E HIJOS, S.L',
            'PASCUAL BLANCH',
        ];

        $motivosLista = [
            'AVERÍA',
            'PRODUCCIÓN',
            'MEJORA',
            'ACOND. OBRA',
            'EPI´S',
            'BOTAS SEGURIDAD',
            'LIMPIEZA NAVE/SERVICIOS',
            'OBRAS NAVE',
            'RECUENTO',
            'MONTAJE MÁQUINAS',
            'HILO SOLDAR',
            'FILTRO GASOIL',
            'MANTENIMIENTO',
            'MONTAJE RED ELÉCTRICA',
        ];

        return view('components.gastos.index', compact('gastos', 'perPage', 'stats', 'obras', 'maquinas', 'proveedoresLista', 'motivosLista'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha_pedido' => 'nullable|date',
            'fecha_llegada' => 'nullable|date',
            'nave_id' => 'nullable|exists:obras,id',
            'obra_id' => 'nullable|exists:obras,id',
            'proveedor' => 'nullable|string|max:255',
            'maquina_id' => 'nullable|exists:maquinas,id',
            'motivo' => 'nullable|string|max:255',
            'coste' => 'nullable|numeric',
            'observaciones' => 'nullable|string',
        ]);

        Gasto::create($validated);

        return redirect()->route('gastos.index')->with('success', 'Gasto creado correctamente.');
    }

    public function update(Request $request, Gasto $gasto)
    {
        $validated = $request->validate([
            'fecha_pedido' => 'nullable|date',
            'fecha_llegada' => 'nullable|date',
            'nave_id' => 'nullable|exists:obras,id',
            'obra_id' => 'nullable|exists:obras,id',
            'proveedor' => 'nullable|string|max:255',
            'maquina_id' => 'nullable|exists:maquinas,id',
            'motivo' => 'nullable|string|max:255',
            'coste' => 'nullable|numeric',
            'observaciones' => 'nullable|string',
        ]);

        $gasto->update($validated);

        return redirect()->route('gastos.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Gasto $gasto)
    {
        $gasto->delete();
        return redirect()->route('gastos.index')->with('success', 'Gasto eliminado correctamente.');
    }
}
