<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fabricante;
use App\Models\Distribuidor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class FabricanteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fabricantes = Fabricante::orderBy('nombre')->get();
        $distribuidores = Distribuidor::orderBy('nombre')->get();

        return view('fabricantes.index', compact('fabricantes', 'distribuidores'));
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
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'nif'      => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email'    => 'required|email|max:255',
        ], [
            'nombre.required'   => 'El nombre del fabricante es obligatorio.',
            'nombre.max'        => 'El nombre no puede tener más de 255 caracteres.',

            'nif.required'      => 'El NIF es obligatorio.',
            'nif.max'           => 'El NIF no puede tener más de 255 caracteres.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.max'      => 'El teléfono no puede tener más de 20 caracteres.',

            'email.required'    => 'El email es obligatorio.',
            'email.email'       => 'Introduce un email válido.',
            'email.max'         => 'El email no puede tener más de 255 caracteres.',
        ]);

        Fabricante::create([
            'nombre'   => $validated['nombre'],
            'nif'      => $validated['nif'],
            'telefono' => $validated['telefono'],
            'email'    => $validated['email'],
        ]);

        return redirect()->route('fabricantes.index')->with('success', 'Fabricante creado correctamente.');
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
    public function update(Request $request, $id)
    {
        try {
            $fabricante = Fabricante::findOrFail($id);

            // Normalizar campos vacíos
            $request->merge([
                'nif'      => $request->nif ?: null,
                'telefono' => $request->telefono ?: null,
                'email'    => $request->email ?: null,
            ]);

            // Validación
            $validatedData = $request->validate([
                'nombre'   => 'required|string|max:255',
                'nif'      => 'nullable|string|max:50',
                'telefono' => 'nullable|string|max:30',
                'email'    => 'nullable|email|max:100',
            ], [
                'nombre.required' => 'El nombre del fabricante es obligatorio.',
                'nombre.string'   => 'El nombre debe ser una cadena de texto.',
                'nombre.max'      => 'El nombre no debe superar los 255 caracteres.',

                'nif.string'      => 'El NIF debe ser una cadena de texto.',
                'nif.max'         => 'El NIF no debe superar los 50 caracteres.',

                'telefono.string' => 'El teléfono debe ser una cadena de texto.',
                'telefono.max'    => 'El teléfono no debe superar los 30 caracteres.',

                'email.email'     => 'El correo electrónico no tiene un formato válido.',
                'email.max'       => 'El correo no debe superar los 100 caracteres.',
            ]);

            $fabricante->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Fabricante actualizado correctamente',
                'data'    => $fabricante->fresh()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fabricante no encontrado'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el fabricante: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $fabricante = Fabricante::findOrFail($id);
        $fabricante->delete();

        return redirect()->route('fabricantes.index')->with('success', 'Fabricante eliminado correctamente.');
    }
}
