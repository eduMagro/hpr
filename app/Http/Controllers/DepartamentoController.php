<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepartamentoController extends Controller
{
    public function index()
    {
        $departamentos = Departamento::with('usuarios')->get();
        $usuariosOficina = User::where('rol', 'oficina')->orderBy('name')->get();
        $todasLasSecciones = Seccion::with('departamentos')->get();


        return view('departamentos.index', compact('departamentos', 'usuariosOficina', 'todasLasSecciones'));
    }
    public function asignarUsuarios(Request $request, Departamento $departamento)
    {
        $request->validate([
            'usuarios' => 'array',
            'usuarios.*' => 'exists:users,id',
        ]);

        // Si no hay usuarios marcados, se debe pasar un array vacío para eliminar todos
        $usuariosSeleccionados = collect($request->usuarios ?? []);

        // Prepara array con rol 'miembro' para todos los seleccionados
        $usuariosConRoles = $usuariosSeleccionados->mapWithKeys(function ($id) {
            return [$id => ['rol_departamental' => 'miembro']];
        });

        // Sincroniza: añade los nuevos y elimina los que no están
        $departamento->usuarios()->sync($usuariosConRoles);

        return redirect()->back()->with('success', 'Asignación actualizada correctamente.');
    }
    // DepartamentoController.php
    public function asignarSecciones(Request $request, Departamento $departamento)
    {
        $departamento->secciones()->sync($request->input('secciones', []));
        return back()->with('success', 'Secciones asignadas correctamente.');
    }



    public function create()
    {
        return view('departamentos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:departamentos,nombre',
            'descripcion' => 'nullable|string',
        ]);

        Departamento::create($request->only('nombre', 'descripcion'));

        return redirect()->route('departamentos.index')->with('success', 'Departamento creado correctamente.');
    }

    public function edit(Departamento $departamento)
    {
        return view('departamentos.edit', compact('departamento'));
    }

    public function update(Request $request, Departamento $departamento)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre del departamento es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
        ]);

        try {
            $departamento->update($validated);

            // 👇 Esta respuesta es necesaria para que el fetch() la entienda
            return response()->json([
                'success' => true,
                'message' => 'Departamento actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar el departamento: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al actualizar el departamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function destroy(Departamento $departamento)
    {
        $departamento->delete();

        return redirect()->route('departamentos.index')->with('success', 'Departamento eliminado correctamente.');
    }
}
