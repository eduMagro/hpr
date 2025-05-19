<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoliticaController extends Controller
{
    public function mostrarPrivacidad()
    {
        return view('legales.privacidad');
    }

    public function mostrarCookies()
    {
        return view('legales.cookies');
    }

    public function aceptar(Request $request)
    {
        $request->validate([
            'acepta_privacidad' => 'required',
            'acepta_cookies' => 'required',
        ]);

        $usuario = Auth::user();
        $usuario->acepta_politica_privacidad = true;
        $usuario->acepta_politica_cookies = true;
        $usuario->fecha_aceptacion_politicas = now();
        $usuario->save();

        return redirect()->intended('/'); // Redirige a la página principal o donde prefieras
    }
}
