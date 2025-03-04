<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Salida;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\EmpresaTransporte;
use App\Models\Camion;

class SalidaController extends Controller
{
    public function index()
    {
        // Obtener todas las salidas con sus paquetes asociados
        $salidas = Salida::all();

        // Pasar las salidas a la vista
        return view('salidas.index', compact('salidas'));
    }

    public function show($id)
    {
        // Obtener la salida con su ID
        $salida = Salida::findOrFail($id);

        // Obtener los paquetes asociados con los elementos y subpaquetes
        $paquetes = $salida->paquetes()->with(['elementos', 'subpaquetes'])->get();

        // Pasar la salida y los paquetes a la vista
        return view('salidas.show', compact('salida', 'paquetes'));
    }



    public function create()
    {
        // Obtener planillas COMPLETADAS con los paquetes, sus elementos y subpaquetes
        $planillasCompletadas = Planilla::where('estado', 'completada')
            ->with(['paquetes' => function ($query) {
                // Filtrar solo los paquetes que NO tienen salida asociada
                $query->whereDoesntHave('salidas');
            }, 'paquetes.elementos', 'paquetes.subpaquetes'])  // Incluir subpaquetes y elementos
            ->whereHas('paquetes', function ($query) {
                // Asegurarnos de que la planilla tenga al menos un paquete sin salida asociada
                $query->whereDoesntHave('salidas');
            })
            ->orderBy('fecha_estimada_entrega', 'asc') // Ordenar por fecha estimada de entrega
            ->get();

        // Obtener los paquetes de las planillas (filtrados previamente)
        $paquetes = $planillasCompletadas->pluck('paquetes')->flatten();

        // Obtener las empresas con sus camiones
        $empresas = EmpresaTransporte::with('camiones')->get();

        // Pasar planillas, paquetes disponibles, elementos y subpaquetes a la vista
        return view('salidas.create', [
            'planillasCompletadas' => $planillasCompletadas,
            'paquetes' => $paquetes,
            'empresas' => $empresas,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'camion_id' => 'required|exists:camiones,id',
            'paquete_ids' => 'required|array',
            'paquete_ids.*' => 'exists:paquetes,id',
        ], [
            'camion_id.required' => 'Por favor, seleccione un camión.',
            'camion_id.exists' => 'El camión seleccionado no existe en el sistema.',
            'paquete_ids.required' => 'Debe seleccionar al menos un paquete.',
            'paquete_ids.array' => 'Los paquetes seleccionados no son válidos.',
            'paquete_ids.*.exists' => 'Uno o más paquetes seleccionados no existen en el sistema.',
        ]);

        $camion = Camion::find($request->camion_id);
        $empresa = $camion->empresaTransporte;  // Accede a la empresa del camión

        // Crear la salida
        $salida = Salida::create([
            'empresa_id' => $empresa->id,
            'camion_id' => $request->camion_id,
            'fecha_salida' => now(),
            'estado' => 'pendiente', // Estado por defecto, puedes cambiarlo si es necesario
        ]);
        // Generar el código de salida
        $codigo_salida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);

        // Asignar el código de salida
        $salida->codigo_salida = $codigo_salida;
        $salida->save();

        // Asociar los paquetes a la salida
        foreach ($request->paquete_ids as $paquete_id) {
            // Asociar el paquete existente a la salida
            $salida->paquetes()->attach($paquete_id);
        }


        // Retornar una respuesta, por ejemplo, una redirección o un mensaje
        return redirect()->route('salidas.index')->with('success', 'Salida creada con éxito');
    }


    public function marcarSubido(Request $request)
    {
        $codigo = $request->codigo;

        // Buscar en paquetes, etiquetas o elementos
        $paquete = Paquete::where('id', $codigo)->first();
        $etiqueta = Etiqueta::where('id', $codigo)->first();
        $elemento = Elemento::where('id', $codigo)->first();

        if ($paquete) {
            $paquete->subido = true;
            $paquete->save();
            return response()->json(['success' => true, 'mensaje' => 'Paquete marcado como subido.']);
        }

        if ($etiqueta) {
            $etiqueta->subido = true;
            $etiqueta->save();
            return response()->json(['success' => true, 'mensaje' => 'Etiqueta marcada como subida.']);
        }

        if ($elemento) {
            $elemento->subido = true;
            $elemento->save();
            return response()->json(['success' => true, 'mensaje' => 'Elemento marcado como subido.']);
        }

        return response()->json(['success' => false, 'mensaje' => 'Código no encontrado.'], 404);
    }
}
