<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Camion;
use App\Models\EmpresaTransporte;

class PapeleraController extends Controller
{
    public function index()
    {
        $paquetes = Paquete::onlyTrashed()->get();
        $camiones = Camion::onlyTrashed()->get();
        $empresas = EmpresaTransporte::onlyTrashed()->get();

        return view('papelera.index', compact('paquetes', 'camiones', 'empresas'));
    }

    public function restore($model, $id)
    {
        $models = [
            'paquetes' => \App\Models\Paquete::class,
            'camiones' => \App\Models\Camion::class,
            'empresas_transporte' => \App\Models\EmpresaTransporte::class,
        ];

        if (!array_key_exists($model, $models)) {
            return redirect()->route('papelera.index')->with('error', 'Modelo no encontrado.');
        }

        $record = $models[$model]::onlyTrashed()->findOrFail($id);
        $record->restore();

        return redirect()->route('papelera.index')->with('success', ucfirst($model) . ' restaurado correctamente.');
    }
}
