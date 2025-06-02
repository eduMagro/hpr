<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\User;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    public function index()
    {
        $departamentos = Departamento::with('usuarios')->get();
        $usuariosOficina = User::where('rol', 'oficina')->orderBy('name')->get();

        return view('departamentos.index', compact('departamentos', 'usuariosOficina'));
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
        $request->validate([
            'nombre' => 'required|string|max:100|unique:departamentos,nombre,' . $departamento->id,
            'descripcion' => 'nullable|string',
        ]);

        $departamento->update($request->only('nombre', 'descripcion'));

        return redirect()->route('departamentos.index')->with('success', 'Departamento actualizado correctamente.');
    }

    public function destroy(Departamento $departamento)
    {
        $departamento->delete();

        return redirect()->route('departamentos.index')->with('success', 'Departamento eliminado correctamente.');
    }
}
