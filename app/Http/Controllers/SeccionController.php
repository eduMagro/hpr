<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'ruta' => 'required|string|max:150|unique:secciones,ruta',
            'icono' => 'nullable|string|max:255',
        ]);

        Seccion::create($request->only('nombre', 'ruta', 'icono'));

        return back()->with('success', 'Sección creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Seccion $seccion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Seccion $seccion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, Seccion $seccion)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'ruta'   => 'nullable|string|max:255',
                'icono'  => 'nullable|string|max:255',
            ]);

            // ⚠️ Aquí se actualiza con los campos validados + este booleano forzado
            $seccion->update(array_merge($validated, [
                'mostrar_en_dashboard' => $request->boolean('mostrar_en_dashboard'),
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sección actualizada correctamente.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar la sección: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al actualizar la sección.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Seccion $seccion)
    {
        //
    }
}
