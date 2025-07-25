<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Localizacion;
use App\Models\LocalizacionPaquete;
use App\Models\Producto;
use App\Models\Paquete;
use App\Models\Maquina;
use Illuminate\Support\Facades\Log;


class LocalizacionController extends Controller
{
    //------------------------------------------------------------------------------------ INDEX()
    public function index()
    {
        $localizaciones = Localizacion::all();
        $paquete = Paquete::with('etiquetas.elementos')->find(469);
        $tamaño = $paquete->tamaño;

        $localizaciones = Localizacion::all();
        $localizacionesPaquetes = LocalizacionPaquete::with('paquete')->get();

        return view('localizaciones.index', [
            'localizaciones' => $localizaciones,
            'paquetesEnMapa' => $localizacionesPaquetes,
        ]);
    }
    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        $localizacion = Localizacion::findOrFail($id);
        return response()->json($localizacion);
    }

    //------------------------------------------------------------------------------------ EDITARMAPA()
    public function editarMapa()
    {
        $localizaciones = Localizacion::all();

        return view('localizaciones.editarMapa', compact('localizaciones'));
    }
    //------------------------------------------------------------------------------------ UPDATE LOCALIZACION()
    public function update(Request $request, $id)
    {
        Log::info("✅ Entró al método update() con ID: $id");
        try {
            $request->validate([
                'x1' => 'required|integer|min:1',
                'y1' => 'required|integer|min:1',
                'x2' => 'required|integer|min:1',
                'y2' => 'required|integer|min:1',
            ], [
                'x1.required' => 'La coordenada x1 es obligatoria.',
                'y1.required' => 'La coordenada y1 es obligatoria.',
                'x2.required' => 'La coordenada x2 es obligatoria.',
                'y2.required' => 'La coordenada y2 es obligatoria.',
            ]);

            $localizacion = Localizacion::findOrFail($id);

            // Reordenar coordenadas por si acaso
            $x1 = min($request->x1, $request->x2);
            $x2 = max($request->x1, $request->x2);
            $y1 = min($request->y1, $request->y2);
            $y2 = max($request->y1, $request->y2);

            $localizacion->update([
                'x1' => $x1,
                'y1' => $y1,
                'x2' => $x2,
                'y2' => $y2,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Localización actualizada correctamente.',
                'localizacion' => $localizacion
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La localización no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la localización: ' . $e->getMessage()
            ], 500);
        }
    }


    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        $localizaciones = Localizacion::all();
        return view('localizaciones.create', compact('localizaciones'));
    }
    //------------------------------------------------------------------------------------ VERIFICAR()

    public function verificar(Request $request)
    {
        // Mostrar en el log las coordenadas recibidas y lo que va a comparar
        Log::info('Verificando localización con:', [
            'x1' => $request->x1,
            'y1' => $request->y1,
            'x2' => $request->x2,
            'y2' => $request->y2,
        ]);

        // 1. Verificación exacta
        $exacta = \App\Models\Localizacion::where('x1', $request->x1)
            ->where('y1', $request->y1)
            ->where('x2', $request->x2)
            ->where('y2', $request->y2)
            ->first();

        if ($exacta) {
            Log::info('✅ Coincidencia exacta encontrada:', [
                'id' => $exacta->id,
                'localizacion' => $exacta->localizacion,
                'tipo' => $exacta->tipo,
            ]);

            return response()->json([
                'existe' => true,
                'tipo' => 'exacta',
                'localizacion' => $exacta
            ]);
        }

        // 2. Verificación de solapamiento parcial (alguna celda incluida)
        $superpuesta = \App\Models\Localizacion::where(function ($q) use ($request) {
            $q->where('x1', '<=', $request->x2)
                ->where('x2', '>=', $request->x1)
                ->where('y1', '<=', $request->y2)
                ->where('y2', '>=', $request->y1);
        })->first();

        if ($superpuesta) {
            Log::info('⚠️ Zona parcialmente ocupada por otra localización:', [
                'id' => $superpuesta->id,
                'localizacion' => $superpuesta->localizacion,
                'tipo' => $superpuesta->tipo,
            ]);

            return response()->json([
                'existe' => true,
                'tipo' => 'parcial',
                'localizacion' => $superpuesta
            ]);
        }

        Log::info('✅ Área libre. No existe ninguna coincidencia.');
        return response()->json(['existe' => false]);
    }

    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        // Validación de los datos recibidos
        $request->validate([
            'x1' => 'required|integer|min:1',
            'y1' => 'required|integer|min:1',
            'x2' => 'required|integer|min:1',
            'y2' => 'required|integer|min:1',
            'tipo' => 'required|in:material,maquina,transitable',
            'seccion' => 'required|string|max:50',
            'localizacion' => 'required|string|max:100',
        ], [
            'x1.required' => 'La coordenada x1 es obligatoria.',
            'y1.required' => 'La coordenada y1 es obligatoria.',
            'x2.required' => 'La coordenada x2 es obligatoria.',
            'y2.required' => 'La coordenada y2 es obligatoria.',
            'tipo.required' => 'Debe indicar el tipo de localización.',
            'tipo.in' => 'El tipo debe ser material, maquina o transitable.',
            'seccion.required' => 'La sección es obligatoria.',
            'localizacion.required' => 'El nombre de la localización es obligatorio.',
        ]);

        // Ordenar coordenadas para asegurar consistencia
        $x1 = min($request->x1, $request->x2);
        $x2 = max($request->x1, $request->x2);
        $y1 = min($request->y1, $request->y2);
        $y2 = max($request->y1, $request->y2);

        // Crear la localización
        $localizacion = Localizacion::create([
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2,
            'tipo' => $request->tipo,
            'seccion' => $request->seccion,
            'localizacion' => $request->localizacion,
        ]);

        // Devolver respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Localización guardada correctamente.',
            'localizacion' => $localizacion
        ]);
    }


    //------------------------------------------------------------------------------------ DESTROY()
    public function destroy($id)
    {
        $localizacion = \App\Models\Localizacion::findOrFail($id);

        $localizacion->delete();

        return response()->json([
            'message' => 'Localización eliminada correctamente.'
        ]);
    }
}
