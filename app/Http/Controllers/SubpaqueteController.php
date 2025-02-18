<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subpaquete;

class SubpaqueteController extends Controller
{

    public function index(Request $request)
{
    $subpaquetes = Subpaquete::with(['elemento', 'planilla', 'paquete'])
        ->when($request->nombre, function ($query, $nombre) {
            return $query->where('nombre', 'like', "%{$nombre}%");
        })
        ->when($request->planilla_id, function ($query, $planilla_id) {
            return $query->where('planilla_id', $planilla_id);
        })
        ->when($request->paquete_id, function ($query, $paquete_id) {
            return $query->where('paquete_id', $paquete_id);
        })
        ->orderBy($request->get('sort_by', 'created_at'), $request->get('order', 'desc'))
        ->paginate($request->get('per_page', 10));

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
        'cantidad' => 'nullable|integer|min:1',
        'descripcion' => 'nullable|string',
    ]);

    // Obtener el elemento y su planilla_id
    $elemento = \App\Models\Elemento::findOrFail($request->elemento_id);

    $pesoDisponible = $elemento->peso - $elemento->subpaquetes->sum('peso');

    // Validar que el peso del subpaquete no exceda el peso disponible
    if ($request->peso > $pesoDisponible) {
        return back()->with('error', 'El peso del subpaquete no puede superar el peso del elemento (' . $elemento->peso_kg . ').');
    }
    // Crear el subpaquete con el mismo planilla_id que el elemento
    Subpaquete::create([
        'elemento_id' => $elemento->id,
        'planilla_id' => $elemento->planilla_id, // Se asigna automáticamente
      
        'peso' => $request->peso,
        'dimensiones' => $elemento->dimensiones,
        'cantidad' => $request->cantidad,
        'descripcion' => $request->descripcion,
    ]);

    return back()->with('success', 'Subpaquete creado correctamente.');
}

}
