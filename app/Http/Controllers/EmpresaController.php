<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\TasaSeguridadSocial;
use App\Models\Convenio;
use App\Models\Turno;
use App\Models\TasaIrpf;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Empresa::all();
        $porcentajes_ss = TasaSeguridadSocial::all();
        $tramos = TasaIrpf::all();
        $convenio = Convenio::all();
        $turnos = Turno::all();
        return view('empresas.index', compact('empresas', 'porcentajes_ss', 'tramos', 'convenio', 'turnos'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            // añade otros campos aquí
        ]);

        Empresa::create($request->all());

        return redirect()->route('empresas.index')->with('success', 'Empresa creada correctamente.');
    }

    public function update(Request $request, Empresa $empresa)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            // añade otros campos aquí
        ]);

        $empresa->update($request->all());

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();
        return redirect()->route('empresas.index')->with('success', 'Empresa eliminada correctamente.');
    }
}
