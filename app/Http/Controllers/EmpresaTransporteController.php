<?php

namespace App\Http\Controllers;

use App\Models\EmpresaTransporte;
use App\Models\Camion;
use Illuminate\Http\Request;

class EmpresaTransporteController extends Controller
{
    public function index()
    {
        // Cargar las empresas de transporte con sus camiones relacionados
        $empresasTransporte = EmpresaTransporte::with('camiones')->get();

        // Pasar los datos a la vista
        return view('empresas-transporte.index', compact('empresasTransporte'));
    }
    public function updateField(Request $request)
    {
        // Validar que se envíen los datos necesarios
        $data = $request->validate([
            'id'    => 'required|integer',
            'field' => 'required|string',
            'value' => 'required|string'
        ]);

        // Determinar a qué modelo pertenece el campo a actualizar.
        // Aquí se asume que:
        // - Los campos 'nombre', 'telefono' y 'email' corresponden a EmpresaTransporte.
        // - Los campos 'modelo', 'capacidad' y 'estado' corresponden a Camion.
        if (in_array($data['field'], ['nombre', 'telefono', 'email'])) {
            $registro = EmpresaTransporte::findOrFail($data['id']);
        } else {
            $registro = Camion::findOrFail($data['id']);
        }

        // Actualizar el campo dinámicamente
        $registro->{$data['field']} = $data['value'];
        $registro->save();

        return response()->json(['success' => true, 'message' => 'Campo actualizado correctamente.']);
    }

    public function create()
    {
        return view('empresas-transporte.create');
    }

    public function store(Request $request)
    {
        EmpresaTransporte::create($request->all());
        return redirect()->route('empresas-transporte.index')->with('success', 'Empresa añadida con éxito.');
    }

    public function show(EmpresaTransporte $empresaTransporte)
    {
        return view('empresas-transporte.show', compact('empresaTransporte'));
    }

    public function edit(EmpresaTransporte $empresaTransporte)
    {
        return view('empresas-transporte.edit', compact('empresaTransporte'));
    }

    public function update(Request $request, EmpresaTransporte $empresaTransporte)
    {
        $empresaTransporte->update($request->all());
        return redirect()->route('empresas-transporte.index');
    }

    public function destroy(EmpresaTransporte $empresaTransporte)
    {
        $empresaTransporte->delete();
        return redirect()->route('empresas-transporte.index');
    }
}
