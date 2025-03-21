<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subpaquete;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Paquete;
use App\Models\Elemento;

class SubpaqueteController extends Controller
{

    public function index(Request $request)
    {
        $subpaquetes = Subpaquete::with(['elemento', 'planilla', 'paquete'])
            ->when($request->id, function ($query, $id) {
                return $query->where('id', $id);
            })
            ->when($request->planilla, function ($query, $planilla) {
                return $query->whereHas('planilla', function ($q) use ($planilla) {
                    $q->where('codigo', 'like', "%{$planilla}%"); // ✅ Buscar por la columna real en la BD
                });
            })
            ->when($request->paquete, function ($query, $paquete) {
                return $query->whereHas('paquete', function ($q) use ($paquete) {
                    $q->where('id', $paquete);
                });
            })
            ->when($request->elemento, function ($query, $elemento) {
                return $query->whereHas('elemento', function ($q) use ($elemento) {
                    $q->where('id', $elemento);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)->appends($request->query());

        return view('subpaquetes.index', compact('subpaquetes'));
    }

    public function store(Request $request)
    {
        // Validar que el elemento existe
        $request->validate([
            'elemento_id' => 'required|exists:elementos,id',
            'nombre' => 'nullable|string|max:255',
            'peso' => 'nullable|numeric|min:0.01',
            'dimensiones' => 'nullable|string|max:255',
            'cantidad' => 'required|integer|min:1', // Asegurar que la cantidad es requerida y válida
            'descripcion' => 'nullable|string',
        ]);

        // Obtener el elemento y su planilla_id
        $elemento = Elemento::findOrFail($request->elemento_id);
        $pesoDisponible = $elemento->peso - $elemento->subpaquetes->sum('peso');

        // Validar que el peso total de los subpaquetes no exceda el peso disponible
        $pesoTotal = $request->peso * $request->cantidad;
        if ($pesoTotal > $pesoDisponible) {
            return back()->with('error', 'El peso total de los subpaquetes no puede superar el peso disponible del elemento (' . $elemento->peso . ').');
        }

        // Crear los subpaquetes según la cantidad especificada
        for ($i = 0; $i < $request->cantidad; $i++) {
            Subpaquete::create([
                'elemento_id' => $elemento->id,
                'planilla_id' => $elemento->planilla_id,
                'peso' => $request->peso,
                'dimensiones' => $elemento->dimensiones,
                'cantidad' => 1, // Cada subpaquete representa una unidad
                'descripcion' => $request->descripcion,
            ]);
        }

        return back()->with('success', 'Se han creado ' . $request->cantidad . ' subpaquetes correctamente.');
    }

    public function destroy(Subpaquete $subpaquete)
    {
        try {
            $subpaquete->delete();
            return redirect()->route('subpaquetes.index')->with('success', 'Subpaquete eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('subpaquetes.index')->with('error', 'Error al eliminar el subpaquete.');
        }
    }
    public function edit(Subpaquete $subpaquete)
    {
        $planillas = Planilla::all();
        $paquetes = Paquete::all();
        $elementos = Elemento::all();

        return view('subpaquetes.edit', compact('subpaquete', 'planillas', 'paquetes', 'elementos'));
    }
    public function update(Request $request, Subpaquete $subpaquete)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'peso' => 'required|numeric|min:0',
            'dimensiones' => 'nullable|string|max:255',
            'cantidad' => 'nullable|integer|min:1',
            'descripcion' => 'nullable|string|max:500',
            'planilla_id' => 'nullable|exists:planillas,id',
            'paquete_id' => 'nullable|exists:paquetes,id',
            'elemento_id' => 'nullable|exists:elementos,id',
        ]);

        $subpaquete->update($validated);

        return redirect()->route('subpaquetes.index')->with('success', 'Subpaquete actualizado correctamente.');
    }
}
