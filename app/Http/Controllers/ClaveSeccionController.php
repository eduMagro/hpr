<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ClaveSeccionController extends Controller
{
    public function formulario($seccion)
    {
        return view('proteger_seccion', compact('seccion'));
    }

    public function verificar(Request $request, $seccion)
    {

        $request->validate([
            'clave' => 'required|string',
        ]);

        // 🔐 Claves definidas por sección
        $claves = [
            'nominas' => 'claveNominas123',
            'vacaciones' => 'claveVacaciones456',
            'stock' => 'claveStock789',
        ];

        if (!isset($claves[$seccion]) || $request->clave !== $claves[$seccion]) {
            return back()->withErrors(['clave' => 'Clave incorrecta'])->withInput();
        }

        Session::put("clave_validada_$seccion", true);

        return redirect()->route("$seccion.index"); // redirige a la sección
    }
}
